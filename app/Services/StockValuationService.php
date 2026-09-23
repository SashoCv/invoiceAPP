<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Вреднување на залиха по набавни цени — подвижна пондерирана просечна цена (МСС 2).
 *
 * Builds a per-article stream of stock events from the documents themselves
 * (приемници, фактури, Shopify нарачки, испратници, попис корекции), replays it in
 * date order and values every output at the average cost on its date. Because every
 * event carries the rounded value that was added to / taken from the balance,
 * почетна + влез − излез = крајна holds to the denar for any period.
 *
 * The same replay also carries the stock at продажни цени со ДДВ (for Образец ЕТ):
 * goods enter at the retail price of their receipt and leave at the running average
 * retail price. The difference between that and what a sale actually brought in is
 * the нивелација of that sale (negative when sold at a discount).
 */
class StockValuationService
{
    public const RETAIL_VAT = 18; // Shopify line prices are gross (со ДДВ)

    public const INPUT_TYPES = ['opening', 'receipt', 'return', 'surplus'];
    public const OUTPUT_TYPES = ['invoice', 'shopify', 'issue', 'shortage'];

    /** @var array<int, array> cached valued events per user */
    private array $cache = [];

    /**
     * All valued events for a user, in chronological order.
     * Each event: article_id, date, dir (in|out), doc_type, doc_key, qty, unit_cost,
     * cost_value, estimated, tax_rate, sales_no_tax, sales_tax, balance_qty, balance_value.
     */
    public function events(int $userId): array
    {
        return $this->cache[$userId] ??= $this->value($this->buildEvents($userId), $userId);
    }

    /**
     * Document header info keyed by doc_key.
     */
    public function documents(int $userId): array
    {
        $this->events($userId);

        return $this->documents[$userId] ?? [];
    }

    /** @var array<int, array> */
    private array $documents = [];

    /** @var array<int, array<int, float>> first known receipt cost per article */
    private array $firstCost = [];

    /** @var array<int, array<int, object>> id, code, name, unit, price, tax_rate, gross per article */
    private array $articleInfo = [];

    // ─── Event building ────────────────────────────────────────────────

    private function buildEvents(int $userId): array
    {
        $events = [];
        $docs = [];

        $this->articleInfo[$userId] = DB::table('articles')->where('user_id', $userId)
            ->get(['id', 'code', 'name', 'unit', 'price', 'tax_rate', 'track_inventory'])
            ->each(fn ($a) => $a->gross = round((float) $a->price * (1 + (float) $a->tax_rate / 100), 4))
            ->keyBy('id')->all();

        $this->buildReceiptEvents($userId, $events, $docs);
        $this->buildManualEvents($userId, $events, $docs);
        $this->buildInvoiceEvents($userId, $events, $docs);
        $this->buildShopifyEvents($userId, $events, $docs);
        $this->buildIssueEvents($userId, $events, $docs);

        $this->documents[$userId] = $docs;

        // Chronological: date, inputs before outputs on the same day, then insertion order
        foreach ($events as $i => &$e) {
            $e['seq'] = $i;
        }
        unset($e);

        usort($events, function ($a, $b) {
            return [$a['date'], $a['dir'] === 'in' ? 0 : 1, $a['seq']]
                <=> [$b['date'], $b['dir'] === 'in' ? 0 : 1, $b['seq']];
        });

        return $events;
    }

    /** Приемници (goods receipts), dated by the receipt date. */
    private function buildReceiptEvents(int $userId, array &$events, array &$docs): void
    {
        $rows = DB::table('stock_movements as sm')
            ->join('goods_receipts as gr', 'gr.id', '=', 'sm.reference_id')
            ->where('sm.user_id', $userId)
            ->where('sm.type', 'receipt')
            ->where('sm.reference_type', 'goods_receipt')
            ->orderBy('gr.date')->orderBy('sm.id')
            ->get(['sm.id', 'sm.article_id', 'sm.quantity', 'sm.cost_price', 'sm.tax_rate', 'sm.retail_price', 'gr.id as doc_id', 'gr.receipt_number', 'gr.date']);

        foreach ($rows as $r) {
            $key = 'receipt:' . $r->doc_id;
            $date = substr((string) $r->date, 0, 10);
            $docs[$key] ??= ['type' => 'receipt', 'id' => $r->doc_id, 'number' => $r->receipt_number, 'date' => $date, 'partner' => null];

            $this->firstCost[$userId][$r->article_id] ??= (float) $r->cost_price;

            $events[] = $this->event($r->article_id, $date, 'in', 'receipt', $key, abs((float) $r->quantity), [
                'unit_cost' => (float) $r->cost_price,
                'tax_rate' => (float) $r->tax_rate,
                'retail_unit' => $r->retail_price !== null ? (float) $r->retail_price : $this->grossPrice($userId, $r->article_id),
            ]);
        }
    }

    /**
     * Movements without a document: initial stock when tracking was enabled (почетна
     * состојба), manual receipts/issues and inventory-count adjustments (вишок/кусок),
     * Shopify refund restocks (поврат). Invoice restores are skipped — invoices are read
     * from the documents themselves.
     */
    private function buildManualEvents(int $userId, array &$events, array &$docs): void
    {
        $rows = DB::table('stock_movements')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIn('type', ['receipt', 'issue', 'adjustment'])->whereNull('reference_type');
                })->orWhere('reference_type', 'shopify_refund');
            })
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'article_id', 'type', 'quantity', 'notes', 'created_at', 'reference_type', 'reference_id']);

        // Initial stock = the "Почетна залиха" row written when tracking is enabled, or an
        // untitled receipt that is the article's very first movement
        $firstMovement = DB::table('stock_movements')->where('user_id', $userId)
            ->selectRaw('article_id, MIN(id) as id')->groupBy('article_id')->pluck('id', 'article_id');
        $openingNotes = [trans('inventory.initial_stock', [], 'mk'), trans('inventory.initial_stock', [], 'en')];

        foreach ($rows as $r) {
            $qty = (float) $r->quantity;
            if ($qty == 0.0) {
                continue;
            }

            // Legacy untagged restores (pre-migration safety net)
            if ($r->type === 'adjustment' && $r->notes
                && (str_starts_with($r->notes, 'Restored: invoice #') || str_starts_with($r->notes, 'Restored from bundle:'))) {
                continue;
            }

            $date = substr((string) $r->created_at, 0, 10);

            if ($r->reference_type === 'shopify_refund' || ($r->notes && str_starts_with($r->notes, 'Shopify refund for order '))) {
                $docType = 'return';
                $key = 'return:' . ($r->reference_id ?? $date);
                $number = $r->notes;
            } elseif ($r->type === 'receipt' && (in_array($r->notes, $openingNotes, true)
                    || (!$r->notes && ($firstMovement[$r->article_id] ?? null) == $r->id))) {
                $docType = 'opening';
                $key = 'opening:' . $date;
                $number = $r->notes ?: null;
            } else {
                $docType = $qty > 0 ? 'surplus' : 'shortage';
                $key = $docType . ':' . $date . ':' . md5((string) $r->notes);
                $number = $r->notes ?: null;
            }

            $docs[$key] ??= ['type' => $docType, 'id' => null, 'number' => $number, 'date' => $date, 'partner' => null];

            $events[] = $this->event($r->article_id, $date, $qty > 0 ? 'in' : 'out', $docType, $key, abs($qty), [
                'estimated' => $docType === 'opening',
            ]);
        }
    }

    /**
     * Фактури — current items of non-deleted, non-cancelled invoices. Tracked articles
     * directly; bundles broken into their tracked components, with the line's sales
     * value (без ДДВ / ДДВ) split across the components by their retail price.
     */
    private function buildInvoiceEvents(int $userId, array &$events, array &$docs): void
    {
        $tracked = collect($this->articleInfo[$userId])->filter(fn ($a) => $a->track_inventory)->map(fn () => true)->all();

        $components = DB::table('bundle_items')
            ->join('bundles', 'bundles.id', '=', 'bundle_items.bundle_id')
            ->where('bundles.user_id', $userId)
            ->get(['bundle_items.bundle_id', 'bundle_items.article_id', 'bundle_items.quantity'])
            ->groupBy('bundle_id');

        $items = DB::table('invoice_items as ii')
            ->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
            ->leftJoin('clients as c', 'c.id', '=', 'i.client_id')
            ->where('i.user_id', $userId)
            ->whereNull('i.deleted_at')
            ->where('i.status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNotNull('ii.article_id')->orWhereNotNull('ii.bundle_id'))
            ->orderBy('i.issue_date')->orderBy('i.id')->orderBy('ii.id')
            ->get([
                'ii.id as line_id', 'ii.article_id', 'ii.bundle_id', 'ii.quantity', 'ii.unit_price', 'ii.discount', 'ii.additional_discount', 'ii.tax_rate',
                'i.id as doc_id', 'i.invoice_number', 'i.issue_date', 'i.currency', 'c.company', 'c.name as client_name',
            ]);

        foreach ($items as $it) {
            $lines = [];
            if ($it->article_id && isset($tracked[$it->article_id])) {
                $lines[] = [$it->article_id, (float) $it->quantity];
            } elseif ($it->bundle_id) {
                foreach ($components[$it->bundle_id] ?? [] as $comp) {
                    if (isset($tracked[$comp->article_id])) {
                        $lines[] = [$comp->article_id, (float) $comp->quantity * (float) $it->quantity];
                    }
                }
            }
            if (!$lines) {
                continue; // services / untracked goods are not stock outputs
            }

            $date = substr((string) $it->issue_date, 0, 10);
            $key = 'invoice:' . $it->doc_id;
            $docs[$key] ??= [
                'type' => 'invoice', 'id' => $it->doc_id, 'number' => $it->invoice_number, 'date' => $date,
                'partner' => $it->company ?: $it->client_name,
            ];

            $rate = $this->toMkdRate($it->currency, $date);
            $base = self::invoiceLineBase((float) $it->quantity, (float) $it->unit_price, (float) $it->discount, (float) ($it->additional_discount ?? 0));
            $salesNoTax = round($base * $rate, 2);
            $salesTax = round($base * (float) $it->tax_rate / 100 * $rate, 2);

            foreach ($this->splitSales($userId, $lines, $salesNoTax, $salesTax) as [$articleId, $qty, $noTax, $tax]) {
                $events[] = $this->event($articleId, $date, 'out', 'invoice', $key, $qty, [
                    'sales_no_tax' => $noTax,
                    'sales_tax' => $tax,
                    'line_id' => $it->line_id,
                ]);
            }
        }
    }

    /**
     * Е-трговија — Shopify orders. Quantities come from the deduction movements written
     * when the order was ingested (bundle composition at that moment); sales from the
     * mapped order lines (gross, 18% split out), bundles split across components by
     * retail price. Sales of an article are attached to its first movement in the order.
     */
    private function buildShopifyEvents(int $userId, array &$events, array &$docs): void
    {
        $orders = DB::table('shopify_orders')->where('user_id', $userId)
            ->get(['id', 'order_number', 'customer_name', 'ordered_at', 'currency'])->keyBy('id');

        $components = DB::table('bundle_items')
            ->join('bundles', 'bundles.id', '=', 'bundle_items.bundle_id')
            ->where('bundles.user_id', $userId)
            ->get(['bundle_items.bundle_id', 'bundle_items.article_id', 'bundle_items.quantity'])
            ->groupBy('bundle_id');

        $items = DB::table('shopify_order_items as oi')
            ->join('shopify_orders as o', 'o.id', '=', 'oi.shopify_order_id')
            ->where('o.user_id', $userId)
            ->where(fn ($q) => $q->whereNotNull('oi.article_id')->orWhereNotNull('oi.bundle_id'))
            ->get(['oi.shopify_order_id', 'oi.article_id', 'oi.bundle_id', 'oi.quantity', 'oi.price', 'oi.total_discount']);

        // [order_id][article_id] => gross sales (со ДДВ) of that article in the order
        $share = [];
        foreach ($items as $it) {
            $gross = (float) $it->quantity * (float) $it->price - (float) $it->total_discount;
            if ($it->article_id) {
                $lines = [[$it->article_id, (float) $it->quantity]];
            } else {
                $lines = [];
                foreach ($components[$it->bundle_id] ?? [] as $comp) {
                    $lines[] = [$comp->article_id, (float) $comp->quantity * (float) $it->quantity];
                }
            }
            foreach ($this->splitSales($userId, $lines, $gross, 0) as [$articleId, , $part]) {
                $share[$it->shopify_order_id][$articleId] = ($share[$it->shopify_order_id][$articleId] ?? 0) + $part;
            }
        }

        $moves = DB::table('stock_movements')
            ->where('user_id', $userId)
            ->where('reference_type', 'shopify_order')
            ->orderBy('reference_id')->orderBy('id')
            ->get(['article_id', 'quantity', 'reference_id']);

        $salesAttached = [];
        foreach ($moves as $m) {
            $order = $orders->get($m->reference_id);
            if (!$order) {
                continue;
            }

            $date = substr((string) $order->ordered_at, 0, 10);
            $key = 'shopify:' . $order->id;
            $docs[$key] ??= ['type' => 'shopify', 'id' => $order->id, 'number' => $order->order_number, 'date' => $date, 'partner' => $order->customer_name];

            $extra = [];
            if (!isset($salesAttached[$order->id][$m->article_id])) {
                $salesAttached[$order->id][$m->article_id] = true;
                $gross = round((float) ($share[$order->id][$m->article_id] ?? 0) * $this->toMkdRate($order->currency, $date), 2);
                $noTax = round($gross / (1 + self::RETAIL_VAT / 100), 2);
                $extra = ['sales_no_tax' => $noTax, 'sales_tax' => round($gross - $noTax, 2)];
            }

            $events[] = $this->event($m->article_id, $date, 'out', 'shopify', $key, abs((float) $m->quantity), $extra);
        }
    }

    /** Испратници — гратис, промоции, реклама. No sales value. */
    private function buildIssueEvents(int $userId, array &$events, array &$docs): void
    {
        $rows = DB::table('stock_movements as sm')
            ->join('goods_issues as gi', 'gi.id', '=', 'sm.reference_id')
            ->leftJoin('clients as c', 'c.id', '=', 'gi.client_id')
            ->where('sm.user_id', $userId)
            ->where('sm.reference_type', 'goods_issue')
            ->orderBy('gi.date')->orderBy('sm.id')
            ->get(['sm.article_id', 'sm.quantity', 'gi.id as doc_id', 'gi.issue_number', 'gi.date', 'gi.notes', 'c.company', 'c.name as client_name']);

        foreach ($rows as $r) {
            $date = substr((string) $r->date, 0, 10);
            $key = 'issue:' . $r->doc_id;
            $docs[$key] ??= [
                'type' => 'issue', 'id' => $r->doc_id, 'number' => $r->issue_number, 'date' => $date,
                'partner' => $r->company ?: ($r->client_name ?: $r->notes),
            ];

            $events[] = $this->event($r->article_id, $date, 'out', 'issue', $key, abs((float) $r->quantity));
        }
    }

    private function event(int $articleId, string $date, string $dir, string $docType, string $docKey, float $qty, array $extra = []): array
    {
        return array_merge([
            'article_id' => $articleId,
            'date' => $date,
            'dir' => $dir,
            'doc_type' => $docType,
            'doc_key' => $docKey,
            'qty' => $qty,
            'unit_cost' => null,
            'tax_rate' => 0.0,
            'estimated' => false,
            'sales_no_tax' => 0.0,
            'sales_tax' => 0.0,
            'retail_unit' => null,
        ], $extra);
    }

    /**
     * Split a line's sales over its stock lines ([article_id, qty]) in proportion to
     * their retail value; the last line takes the rounding remainder so the parts add
     * up exactly. Returns [article_id, qty, no_tax, tax].
     */
    private function splitSales(int $userId, array $lines, float $noTax, float $tax): array
    {
        if (count($lines) === 1) {
            return [[$lines[0][0], $lines[0][1], round($noTax, 2), round($tax, 2)]];
        }

        $weights = array_map(fn ($l) => $l[1] * (float) ($this->articleInfo[$userId][$l[0]]->price ?? 0), $lines);
        $sum = array_sum($weights);
        if ($sum <= 0) {
            $weights = array_map(fn ($l) => $l[1], $lines);
            $sum = array_sum($weights) ?: 1;
        }

        $out = [];
        $restNoTax = round($noTax, 2);
        $restTax = round($tax, 2);
        $last = count($lines) - 1;
        foreach ($lines as $i => [$articleId, $qty]) {
            $partNoTax = $i === $last ? $restNoTax : round($noTax * $weights[$i] / $sum, 2);
            $partTax = $i === $last ? $restTax : round($tax * $weights[$i] / $sum, 2);
            $restNoTax = round($restNoTax - $partNoTax, 2);
            $restTax = round($restTax - $partTax, 2);
            $out[] = [$articleId, $qty, $partNoTax, $partTax];
        }

        return $out;
    }

    private function grossPrice(int $userId, int $articleId): float
    {
        return (float) ($this->articleInfo[$userId][$articleId]->gross ?? 0);
    }

    // ─── Valuation ─────────────────────────────────────────────────────

    /**
     * Moving weighted average replay. Receipts enter at their own cost; everything
     * else moves at the running average. When there is no average (no stock yet or
     * negative stock) the article's first receipt cost is used and flagged.
     *
     * In parallel the retail (продажна со ДДВ) balance: receipts enter at their stored
     * retail price, other inputs at the running retail average (or the article's
     * current price), outputs leave at the running retail average. For sales,
     * leveling = actual sale со ДДВ − retail value that left the stock.
     */
    private function value(array $events, int $userId): array
    {
        $qty = [];
        $val = [];
        $rval = [];

        foreach ($events as &$e) {
            $a = $e['article_id'];
            $q = $qty[$a] ?? 0.0;
            $v = $val[$a] ?? 0.0;
            $rv = $rval[$a] ?? 0.0;

            if ($e['doc_type'] === 'receipt') {
                $unit = (float) $e['unit_cost'];
            } elseif ($q > 0 && $v > 0) {
                $unit = $v / $q;
            } else {
                $unit = $this->firstCost[$userId][$a] ?? 0.0;
                $e['estimated'] = true;
            }

            if ($e['retail_unit'] !== null) {
                $retailUnit = (float) $e['retail_unit'];
            } elseif ($q > 0 && $rv > 0) {
                $retailUnit = $rv / $q;
            } else {
                $retailUnit = $this->grossPrice($userId, $a);
            }

            // Taking out the whole remaining stock takes out the whole value — no residual cents
            $emptiesStock = $e['dir'] === 'out' && abs($q - $e['qty']) < 0.00001;

            if ($e['dir'] === 'in') {
                $cost = round($e['qty'] * $unit, 2);
                $retail = round($e['qty'] * $retailUnit, 2);
                $q += $e['qty'];
                $v = round($v + $cost, 2);
                $rv = round($rv + $retail, 2);
            } else {
                $cost = $emptiesStock ? $v : round($e['qty'] * $unit, 2);
                $retail = $emptiesStock ? $rv : round($e['qty'] * $retailUnit, 2);
                $q -= $e['qty'];
                $v = round($v - $cost, 2);
                $rv = round($rv - $retail, 2);
            }

            $e['unit_cost'] = round($unit, 4);
            $e['cost_value'] = $cost;
            $e['retail_unit'] = round($retailUnit, 4);
            $e['retail_value'] = $retail;
            $e['leveling'] = in_array($e['doc_type'], ['invoice', 'shopify'], true)
                ? round($e['sales_no_tax'] + $e['sales_tax'] - $retail, 2)
                : 0.0;
            $e['balance_qty'] = $q;
            $e['balance_value'] = $v;
            $e['balance_retail'] = $rv;

            $qty[$a] = $q;
            $val[$a] = $v;
            $rval[$a] = $rv;
        }
        unset($e);

        return $events;
    }

    // ─── Period queries ────────────────────────────────────────────────

    /**
     * Opening balance per article just before $from (qty/value keyed by article_id).
     */
    public function balancesBefore(int $userId, string $date): array
    {
        $bal = [];
        foreach ($this->events($userId) as $e) {
            if ($e['date'] >= $date) {
                break;
            }
            $bal[$e['article_id']] = ['qty' => $e['balance_qty'], 'value' => $e['balance_value'], 'retail' => $e['balance_retail']];
        }

        return $bal;
    }

    /**
     * Closing balance per article at end of $date (inclusive).
     */
    public function balancesAt(int $userId, string $date): array
    {
        $bal = [];
        foreach ($this->events($userId) as $e) {
            if ($e['date'] > $date) {
                break;
            }
            $bal[$e['article_id']] = ['qty' => $e['balance_qty'], 'value' => $e['balance_value'], 'retail' => $e['balance_retail']];
        }

        return $bal;
    }

    /**
     * Events within [from, to] (Y-m-d strings, inclusive).
     */
    public function eventsBetween(int $userId, string $from, string $to): array
    {
        return array_values(array_filter(
            $this->events($userId),
            fn ($e) => $e['date'] >= $from && $e['date'] <= $to
        ));
    }

    /**
     * Valued events of one document (e.g. "invoice:12"), in replay order.
     */
    public function documentEvents(int $userId, string $docKey): array
    {
        return array_values(array_filter($this->events($userId), fn ($e) => $e['doc_key'] === $docKey));
    }

    /**
     * Articles whose replayed quantity differs from the live stock_quantity.
     */
    public function reconcile(int $userId): array
    {
        $computed = [];
        foreach ($this->events($userId) as $e) {
            $computed[$e['article_id']] = $e['balance_qty'];
        }

        $articles = DB::table('articles')->where('user_id', $userId)
            ->where(fn ($q) => $q->where('track_inventory', true)->orWhereIn('id', array_keys($computed)))
            ->get(['id', 'code', 'name', 'stock_quantity', 'track_inventory']);

        $out = [];
        foreach ($articles as $a) {
            $calc = round($computed[$a->id] ?? 0, 2);
            $live = $a->track_inventory ? round((float) $a->stock_quantity, 2) : 0.0;
            if (abs($calc - $live) > 0.001) {
                $out[] = ['article_id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'computed' => $calc, 'actual' => $live];
            }
        }

        return $out;
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    /**
     * Net line amount (без ДДВ) — same basis as InvoiceItem::booted().
     */
    public static function invoiceLineBase(float $qty, float $unitPrice, float $discount, float $additionalDiscount): float
    {
        return round($qty * $unitPrice * (1 - $discount / 100) * (1 - $additionalDiscount / 100), 2);
    }

    /** @var array<string, float> */
    private array $rateCache = [];

    private function toMkdRate(?string $currency, string $date): float
    {
        if (!$currency || $currency === 'MKD') {
            return 1.0;
        }

        return $this->rateCache[$currency . $date] ??= (ExchangeRate::getRate($currency, $date) ?? 1.0);
    }

    public static function monthKey(string $date): string
    {
        return substr($date, 0, 7);
    }

    public static function monthLabel(string $monthKey): string
    {
        return Carbon::createFromFormat('Y-m-d', $monthKey . '-01')->format('m.Y');
    }
}
