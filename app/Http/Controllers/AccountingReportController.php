<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Services\PdfService;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Сметководствени извештаи — по документи, по месеци, по набавни цени:
 *  - Влезни калкулации (приемници, почетна состојба, поврати, вишоци)
 *  - Излезни калкулации (фактури, е-трговија, испратници/промоции, кусоци)
 *  - Нивелации: дневни записници за нивелација (ЕТ), по месеци
 *  - Лагер: почетна + влез − излез = крајна; лагер листа по набавни и продажни цени
 * Valuation comes from StockValuationService (moving weighted average).
 */
class AccountingReportController extends Controller
{
    private const TABS = ['inputs', 'outputs', 'leveling', 'stock'];

    public const TYPE_LABELS = [
        'opening' => 'Почетна состојба',
        'receipt' => 'Приемница',
        'return' => 'Поврат од купувач',
        'surplus' => 'Вишок / рачен влез',
        'invoice' => 'Фактура',
        'shopify' => 'Е-трговија',
        'issue' => 'Испратница (промоција)',
        'shortage' => 'Кусок / рачен излез',
        'writeoff' => 'Кало, крш, растур',
    ];

    /** Show documents summed per day (one row per day and document type) */
    private bool $byDay = false;

    public function __construct(private StockValuationService $valuation)
    {
    }

    public function index(Request $request): Response
    {
        [$tab, $from, $to, $type] = $this->params($request);
        $userId = $request->user()->id;

        return Inertia::render('Reports/Accounting/Index', [
            'tab' => $tab,
            'report' => $this->build($userId, $tab, $from, $to, $type, true),
            'typeOptions' => $this->typeOptions($tab),
            'filters' => [
                'date_from' => $from,
                'date_to' => $to,
                'type' => $type,
                'by_day' => $this->byDay,
            ],
        ]);
    }

    public function exportPdf(Request $request, PdfService $pdfService): BinaryFileResponse
    {
        [$tab, $from, $to, $type] = $this->params($request);

        $data = [
            'agency' => $request->user()->agency,
            'tab' => $tab,
            'title' => $this->title($tab),
            'typeLabel' => trim(($type ? self::TYPE_LABELS[$type] : '') . ($this->byDay ? ' — збирно по ден' : ''), ' —') ?: null,
            'dateFrom' => Carbon::parse($from)->format('d.m.Y'),
            'dateTo' => Carbon::parse($to)->format('d.m.Y'),
            'printedAt' => now()->format('d.m.Y H:i'),
            'report' => $this->build($request->user()->id, $tab, $from, $to, $type, false),
        ];

        $pdfPath = $pdfService->generateAccountingReportPdf($data);

        return response()->download($pdfPath, $this->filename($tab, $from, $to, 'pdf'), [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$tab, $from, $to, $type] = $this->params($request);
        $report = $this->build($request->user()->id, $tab, $from, $to, $type, false);
        $rows = match ($tab) {
            'stock' => $this->stockCsvRows($report),
            'leveling' => $this->levelingCsvRows($report),
            default => $this->documentCsvRows($tab, $report),
        };

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel Cyrillic support
            foreach ($rows as $row) {
                fputcsv($handle, $row, escape: "");
            }
            fclose($handle);
        }, $this->filename($tab, $from, $to, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Образец МЕТГ — материјална евиденција во трговија на големо, for one article: every
     * document with the quantity received (кол. 6), sold/issued (кол. 7) and the balance
     * (кол. 8), starting from the balance carried into the period.
     */
    public function metgPdf(Request $request, int $article, PdfService $pdfService): BinaryFileResponse
    {
        [, $from, $to] = $this->params($request);
        $userId = $request->user()->id;
        $item = DB::table('articles')->where('user_id', $userId)->where('id', $article)->first(['id', 'code', 'name', 'unit']);
        abort_unless($item, 404);

        $opening = $this->valuation->balancesBefore($userId, $from)[$article]['qty'] ?? 0;
        $docsMeta = $this->valuation->documents($userId);

        $rows = [];
        $balance = $opening;
        foreach ($this->valuation->eventsBetween($userId, $from, $to) as $e) {
            if ($e['article_id'] !== $article) {
                continue;
            }
            $key = $e['doc_key'];
            $meta = $docsMeta[$key] ?? [];
            $in = $e['dir'] === 'in' ? $e['qty'] : 0;
            $out = $e['dir'] === 'out' ? $e['qty'] : 0;

            // One row per document (a document may carry the article on several lines)
            $last = count($rows) - 1;
            if ($last >= 0 && $rows[$last]['key'] === $key) {
                $rows[$last]['in'] += $in;
                $rows[$last]['out'] += $out;
                $balance += $in - $out;
                $rows[$last]['balance'] = $balance;
                continue;
            }

            $balance += $in - $out;
            $rows[] = [
                'key' => $key,
                'date' => Carbon::parse($e['date'])->format('d.m.Y'),
                'number' => $meta['number'] ?? '',
                'name' => self::TYPE_LABELS[$e['doc_type']] . (!empty($meta['partner']) ? ' — ' . $meta['partner'] : ''),
                'in' => $in,
                'out' => $out,
                'balance' => $balance,
            ];
        }

        $pdfPath = $pdfService->generateMetgPdf([
            'agency' => $request->user()->agency,
            'article' => $item,
            'dateFrom' => Carbon::parse($from)->format('d.m.Y'),
            'dateTo' => Carbon::parse($to)->format('d.m.Y'),
            'printedAt' => now()->format('d.m.Y H:i'),
            'opening' => $opening,
            'rows' => $rows,
            'totals' => [
                'in' => array_sum(array_column($rows, 'in')),
                'out' => array_sum(array_column($rows, 'out')),
                'balance' => $balance,
            ],
        ]);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="METG_' . ($item->code ?: $item->id) . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Set the purchase price of earlier manual inputs (почетна состојба, вишоци) that were
     * entered without one and are valued at 0 or an estimate.
     */
    public function updateManualCosts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            StockMovement::where('id', $item['id'])
                ->where('user_id', $request->user()->id)
                ->whereNull('reference_type')
                ->whereIn('type', ['receipt', 'adjustment'])
                ->where('quantity', '>', 0)
                ->update(['cost_price' => $item['cost_price']]);
        }

        return back()->with('success', __('toast.purchase_prices_saved'));
    }

    // ─── Parameters ────────────────────────────────────────────────────

    private function params(Request $request): array
    {
        $tab = in_array($request->get('tab'), self::TABS, true) ? $request->get('tab') : 'inputs';

        try {
            $from = $request->filled('date_from')
                ? Carbon::createFromFormat('Y-m-d', $request->date_from)
                : now()->startOfMonth();
        } catch (\Exception $e) {
            $from = now()->startOfMonth();
        }

        try {
            $to = $request->filled('date_to')
                ? Carbon::createFromFormat('Y-m-d', $request->date_to)
                : now();
        } catch (\Exception $e) {
            $to = now();
        }

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $allowed = $tab === 'outputs' ? StockValuationService::OUTPUT_TYPES : StockValuationService::INPUT_TYPES;
        $type = in_array($request->get('type'), $allowed, true) ? $request->get('type') : null;

        $this->byDay = in_array($tab, ['inputs', 'outputs'], true) && $request->boolean('by_day');

        return [$tab, $from->toDateString(), $to->toDateString(), in_array($tab, ['inputs', 'outputs'], true) ? $type : null];
    }

    private function typeOptions(string $tab): array
    {
        if (!in_array($tab, ['inputs', 'outputs'], true)) {
            return [];
        }
        $types = $tab === 'outputs' ? StockValuationService::OUTPUT_TYPES : StockValuationService::INPUT_TYPES;

        return array_map(fn ($t) => ['value' => $t, 'label' => self::TYPE_LABELS[$t]], $types);
    }

    private function title(string $tab): string
    {
        return match ($tab) {
            'outputs' => 'Преглед на излезни калкулации по набавни цени',
            'leveling' => 'Преглед на нивелации',
            'stock' => 'Лагер по набавни и продажни цени',
            default => 'Преглед на влезни калкулации по набавни цени',
        };
    }

    private function filename(string $tab, string $from, string $to, string $ext): string
    {
        $name = ['inputs' => 'vlezni_kalkulacii', 'outputs' => 'izlezni_kalkulacii', 'leveling' => 'nivelacii', 'stock' => 'lager'][$tab];

        return "{$name}" . ($this->byDay ? '_po_den' : '') . "_{$from}_{$to}.{$ext}";
    }

    // ─── Report building ───────────────────────────────────────────────

    private function build(int $userId, string $tab, string $from, string $to, ?string $type, bool $withLines): array
    {
        return match ($tab) {
            'stock' => $this->buildStock($userId, $from, $to),
            'leveling' => $this->buildLeveling($userId, $from, $to),
            default => $this->buildDocuments($userId, $tab, $from, $to, $type, $withLines),
        };
    }

    /**
     * One row per document, grouped by month, with month subtotals (also per type) and a grand total.
     */
    private function buildDocuments(int $userId, string $tab, string $from, string $to, ?string $type, bool $withLines): array
    {
        $dir = $tab === 'outputs' ? 'out' : 'in';
        $docsMeta = $this->valuation->documents($userId);
        $articles = $this->articles($userId);

        $docs = [];
        foreach ($this->valuation->eventsBetween($userId, $from, $to) as $e) {
            if ($e['dir'] !== $dir || ($type && $e['doc_type'] !== $type)) {
                continue;
            }

            $key = $e['doc_key'];
            if (!isset($docs[$key])) {
                $meta = $docsMeta[$key] ?? ['id' => null, 'number' => null, 'partner' => null];
                $docs[$key] = [
                    'key' => $key,
                    'type' => $e['doc_type'],
                    'type_label' => self::TYPE_LABELS[$e['doc_type']],
                    'id' => $meta['id'],
                    'number' => $meta['number'],
                    'partner' => $meta['partner'],
                    'date' => $e['date'],
                    'items' => 0,
                    'quantity' => 0.0,
                    'cost_value' => 0.0,
                    'cost_tax' => 0.0,
                    'sales_no_tax' => 0.0,
                    'sales_tax' => 0.0,
                    'charged' => $meta['charged'] ?? null,
                    'other' => 0.0,
                    'estimated' => false,
                    'lines' => [],
                ];
            }

            $d = &$docs[$key];
            $d['items']++;
            $d['quantity'] += $e['qty'];
            $d['cost_value'] += $e['cost_value'];
            // ДДВ на набавката: the receipt line's own rate; other inputs at the article's rate (as in ЕТ)
            $costRate = $e['doc_type'] === 'receipt' ? $e['tax_rate'] : (float) ($articles[$e['article_id']]->tax_rate ?? 0);
            $d['cost_tax'] += $dir === 'in' ? round($e['cost_value'] * $costRate / 100, 2) : 0;
            if ($dir === 'in') {
                // Продажна вредност на влезот: по продажната цена со ДДВ со која стоката
                // влегува во Образец ЕТ; ДДВ издвоен по стапката на артиклот
                $rate = (float) ($articles[$e['article_id']]->tax_rate ?? 0);
                $retailTax = round($e['retail_value'] * $rate / (100 + $rate), 2);
                $d['sales_no_tax'] += $e['retail_value'] - $retailTax;
                $d['sales_tax'] += $retailTax;
            } else {
                $d['sales_no_tax'] += $e['sales_no_tax'];
                $d['sales_tax'] += $e['sales_tax'];
            }
            $d['estimated'] = $d['estimated'] || $e['estimated'];

            if ($withLines) {
                $a = $articles[$e['article_id']] ?? null;
                $d['lines'][] = [
                    'code' => $a->code ?? '',
                    'name' => $a->name ?? ('#' . $e['article_id']),
                    'unit' => $a->unit ?? '',
                    'quantity' => $e['qty'],
                    'unit_cost' => $e['unit_cost'],
                    'cost_value' => $e['cost_value'],
                    'retail_unit' => $e['retail_unit'],
                    'retail_value' => $e['retail_value'],
                    'estimated' => $e['estimated'],
                ];
            }
            unset($d);
        }

        if ($dir === 'out') {
            $this->addDocumentsWithoutStock($userId, $from, $to, $type, $docs);
        }

        // Достава и друго: what the customer paid on top of the goods (Shopify shipping,
        // unmapped lines), so "Вкупно наплатено" equals the order total / bank payment
        foreach ($docs as &$d) {
            if ($d['charged'] !== null) {
                $d['other'] = round($d['charged'] - round($d['sales_no_tax'] + $d['sales_tax'], 2), 2);
            }
        }
        unset($d);

        if ($this->byDay) {
            $docs = $this->groupByDay($docs);
        }

        // Rows sorted by date then document number; finalise derived columns
        $docs = array_values($docs);
        usort($docs, fn ($a, $b) => [$a['date'], $a['type'], (string) $a['number']] <=> [$b['date'], $b['type'], (string) $b['number']]);

        $months = [];
        $grand = $this->emptyTotals();
        $grandByType = [];
        $rb = 0;

        foreach ($docs as $d) {
            $d['rb'] = ++$rb;
            $d = $this->finaliseRow($d, $tab);

            $mk = StockValuationService::monthKey($d['date']);
            $months[$mk] ??= ['month' => $mk, 'label' => StockValuationService::monthLabel($mk), 'rows' => [], 'totals' => $this->emptyTotals(), 'by_type' => []];
            $months[$mk]['rows'][] = $d;
            $this->addTotals($months[$mk]['totals'], $d);
            $months[$mk]['by_type'][$d['type']] ??= ['type' => $d['type'], 'label' => $d['type_label'], 'count' => 0] + $this->emptyTotals();
            $months[$mk]['by_type'][$d['type']]['count']++;
            $this->addTotals($months[$mk]['by_type'][$d['type']], $d);

            $this->addTotals($grand, $d);
            $grandByType[$d['type']] ??= ['type' => $d['type'], 'label' => $d['type_label'], 'count' => 0] + $this->emptyTotals();
            $grandByType[$d['type']]['count']++;
            $this->addTotals($grandByType[$d['type']], $d);
        }

        foreach ($months as &$m) {
            $m['totals'] = $this->roundTotals($m['totals']);
            $m['by_type'] = array_values(array_map(fn ($t) => $this->roundTotals($t), $m['by_type']));
        }
        unset($m);

        return [
            'months' => array_values($months),
            'totals' => $this->roundTotals($grand),
            'by_type' => array_values(array_map(fn ($t) => $this->roundTotals($t), $grandByType)),
            'count' => count($docs),
        ];
    }

    /**
     * Sales documents that moved no stock — a Shopify order whose items are not mapped to
     * articles, an invoice with only services — still belong in the outputs, so "Вкупно
     * наплатено" matches Shopify and ЕТ. They carry no набавна; their amount shows under
     * "Достава и друго".
     */
    private function addDocumentsWithoutStock(int $userId, string $from, string $to, ?string $type, array &$docs): void
    {
        $row = fn (string $key, string $docType, $id, $number, $partner, string $date, float $charged, array $lines) => [
            'key' => $key, 'type' => $docType, 'type_label' => self::TYPE_LABELS[$docType],
            'id' => $id, 'number' => $number, 'partner' => $partner, 'date' => $date,
            'items' => 0, 'quantity' => 0.0, 'cost_value' => 0.0, 'cost_tax' => 0.0,
            'sales_no_tax' => 0.0, 'sales_tax' => 0.0, 'charged' => round($charged, 2), 'other' => 0.0,
            'estimated' => false, 'lines' => $lines,
        ];

        if (!$type || $type === 'shopify') {
            $orders = DB::table('shopify_orders')->where('user_id', $userId)
                ->whereBetween('ordered_at', StockValuationService::dayRange($from, $to))
                ->get(['id', 'order_number', 'customer_name', 'ordered_at', 'total_price']);
            foreach ($orders as $o) {
                $key = 'shopify:' . $o->id;
                if (isset($docs[$key])) {
                    continue;
                }
                $lines = DB::table('shopify_order_items')->where('shopify_order_id', $o->id)->get(['title', 'quantity'])
                    ->map(fn ($i) => ['code' => '', 'name' => $i->title . ' (' . __('accounting.not_mapped') . ')', 'unit' => '',
                        'quantity' => (float) $i->quantity, 'unit_cost' => 0, 'cost_value' => 0, 'retail_unit' => 0, 'retail_value' => 0, 'estimated' => false])->all();
                $docs[$key] = $row($key, 'shopify', $o->id, $o->order_number, $o->customer_name,
                    StockValuationService::localDate($o->ordered_at), (float) $o->total_price, $lines);
            }
        }

        if (!$type || $type === 'invoice') {
            $invoices = DB::table('invoices as i')->leftJoin('clients as c', 'c.id', '=', 'i.client_id')
                ->leftJoin('client_branches as cb', 'cb.id', '=', 'i.branch_id')
                ->where('i.user_id', $userId)->whereNull('i.deleted_at')->where('i.status', '!=', 'cancelled')
                ->whereBetween('i.issue_date', [$from, $to])
                ->get(['i.id', 'i.invoice_number', 'i.issue_date', 'i.total', 'c.company', 'c.name', 'cb.name as branch_name']);
            foreach ($invoices as $i) {
                $key = 'invoice:' . $i->id;
                if (!isset($docs[$key])) {
                    $docs[$key] = $row($key, 'invoice', $i->id, $i->invoice_number,
                        StockValuationService::partnerName($i->company ?: $i->name, $i->branch_name),
                        substr((string) $i->issue_date, 0, 10), (float) $i->total, []);
                }
            }
        }
    }

    /**
     * Sum the documents of each day (per document type) into one row, e.g. all Shopify
     * orders of a day. The row's lines are the day's articles, summed.
     */
    private function groupByDay(array $docs): array
    {
        $days = [];
        foreach ($docs as $d) {
            $key = 'day:' . $d['type'] . ':' . $d['date'];
            if (!isset($days[$key])) {
                $days[$key] = ['key' => $key, 'id' => null, 'partner' => null, 'docs' => 0, 'lines' => []] + $d;
                foreach (['items', 'quantity', 'cost_value', 'cost_tax', 'sales_no_tax', 'sales_tax', 'other'] as $f) {
                    $days[$key][$f] = 0;
                }
                $days[$key]['lines'] = [];
            }

            $g = &$days[$key];
            $g['docs']++;
            foreach (['items', 'quantity', 'cost_value', 'cost_tax', 'sales_no_tax', 'sales_tax', 'other'] as $f) {
                $g[$f] += $d[$f];
            }
            $g['estimated'] = $g['estimated'] || $d['estimated'];

            foreach ($d['lines'] as $l) {
                $lk = $l['code'] . '|' . $l['name'];
                if (!isset($g['lines'][$lk])) {
                    $g['lines'][$lk] = $l;
                    continue;
                }
                $m = &$g['lines'][$lk];
                $m['quantity'] += $l['quantity'];
                $m['cost_value'] += $l['cost_value'];
                $m['retail_value'] += $l['retail_value'];
                $m['estimated'] = $m['estimated'] || $l['estimated'];
                $m['unit_cost'] = $m['quantity'] > 0 ? round($m['cost_value'] / $m['quantity'], 4) : 0;
                $m['retail_unit'] = $m['quantity'] > 0 ? round($m['retail_value'] / $m['quantity'], 2) : 0;
                unset($m);
            }
            unset($g);
        }

        foreach ($days as &$g) {
            $one = $g['docs'] === 1;
            $g['number'] = $g['docs'] . ' ' . ($g['type'] === 'shopify' ? ($one ? 'нарачка' : 'нарачки') : ($one ? 'документ' : 'документи'));
            $g['lines'] = array_values($g['lines']);
            usort($g['lines'], fn ($a, $b) => strcmp($a['name'], $b['name']));
        }
        unset($g);

        return $days;
    }

    private function finaliseRow(array $d, string $tab): array
    {
        foreach (['quantity', 'cost_value', 'cost_tax', 'sales_no_tax', 'sales_tax'] as $f) {
            $d[$f] = round($d[$f], 2);
        }
        $d['cost_with_tax'] = round($d['cost_value'] + $d['cost_tax'], 2);
        $d['sales_with_tax'] = round($d['sales_no_tax'] + $d['sales_tax'], 2);
        $d['other'] = round($d['other'] ?? 0, 2);
        // Nothing is charged for испратници / кусоци (their value is набавна + ДДВ / продажна)
        $d['charged_total'] = in_array($d['type'], ['invoice', 'shopify'], true) ? round($d['sales_with_tax'] + $d['other'], 2) : 0.0;
        // РУЦ: sales documents (realised) and inputs (вкалкулирана разлика во цена)
        $d['margin'] = in_array($d['type'], ['invoice', 'shopify', 'opening', 'receipt', 'return', 'surplus'], true)
            ? round($d['sales_no_tax'] - $d['cost_value'], 2)
            : 0.0;

        return $d;
    }

    private function emptyTotals(): array
    {
        return [
            'quantity' => 0.0, 'cost_value' => 0.0, 'cost_tax' => 0.0, 'cost_with_tax' => 0.0,
            'sales_no_tax' => 0.0, 'sales_tax' => 0.0, 'sales_with_tax' => 0.0, 'margin' => 0.0,
            'other' => 0.0, 'charged_total' => 0.0,
        ];
    }

    private function addTotals(array &$t, array $row): void
    {
        foreach (array_keys($this->emptyTotals()) as $f) {
            $t[$f] += $row[$f];
        }
    }

    private function roundTotals(array $t): array
    {
        foreach (array_keys($this->emptyTotals()) as $f) {
            $t[$f] = round($t[$f], 2);
        }

        return $t;
    }

    /**
     * Нивелации — one row per day (the "НИВ" record behind that day's ЕТ row), grouped by
     * month: full продажна вредност со ДДВ, sold со ДДВ, the нивелација (split into
     * фактури / е-трговија) and the ДДВ contained in it.
     */
    private function buildLeveling(int $userId, string $from, string $to): array
    {
        $taxRates = DB::table('articles')->where('user_id', $userId)->pluck('tax_rate', 'id');

        $days = [];
        foreach ($this->valuation->eventsBetween($userId, $from, $to) as $e) {
            if (!in_array($e['doc_type'], StockValuationService::LEVELED_TYPES, true) || abs($e['leveling']) < 0.005) {
                continue;
            }
            $rate = $e['doc_type'] === 'shopify' ? StockValuationService::RETAIL_VAT : (float) ($taxRates[$e['article_id']] ?? 0);

            $d = &$days[$e['date']];
            $d ??= ['date' => $e['date'], 'docs' => [], 'full_value' => 0.0, 'sold_value' => 0.0,
                'leveling_invoice' => 0.0, 'leveling_shopify' => 0.0, 'leveling_issue' => 0.0, 'leveling' => 0.0, 'leveling_tax' => 0.0];
            $d['docs'][$e['doc_key']] = true;
            $d['full_value'] += $e['retail_value'];
            $d['sold_value'] += $e['sales_no_tax'] + $e['sales_tax'];
            $d['leveling_' . (in_array($e['doc_type'], ['issue', 'shortage'], true) ? 'issue' : $e['doc_type'])] += $e['leveling'];
            $d['leveling'] += $e['leveling'];
            $d['leveling_tax'] += round($e['leveling'] * $rate / (100 + $rate), 2);
            unset($d);
        }
        ksort($days);

        $fields = ['full_value', 'sold_value', 'leveling_invoice', 'leveling_shopify', 'leveling_issue', 'leveling', 'leveling_tax'];
        $numbers = $this->valuation->levelingNumbers($userId);
        $empty = array_fill_keys($fields, 0.0) + ['count' => 0];
        $months = [];
        $grand = $empty;
        foreach ($days as $d) {
            $row = ['date' => $d['date'], 'number' => 'НИВ ' . ($numbers[$d['date']] ?? ''), 'count' => count($d['docs'])];
            foreach ($fields as $f) {
                $row[$f] = round($d[$f], 2);
            }

            $mk = StockValuationService::monthKey($d['date']);
            $months[$mk] ??= ['month' => $mk, 'label' => StockValuationService::monthLabel($mk), 'rows' => [], 'totals' => $empty];
            $months[$mk]['rows'][] = $row;
            foreach ([...$fields, 'count'] as $f) {
                $months[$mk]['totals'][$f] += $row[$f];
                $grand[$f] += $row[$f];
            }
        }

        $round = function (array $t) use ($fields) {
            foreach ($fields as $f) {
                $t[$f] = round($t[$f], 2);
            }
            return $t;
        };

        return [
            'months' => array_values(array_map(fn ($m) => ['totals' => $round($m['totals'])] + $m, $months)),
            'totals' => $round($grand),
            'count' => count($days),
        ];
    }

    /**
     * Monthly roll-forward (почетна + влез − излез = крајна) and the лагер листа at date_to.
     */
    private function buildStock(int $userId, string $from, string $to): array
    {
        $events = $this->valuation->eventsBetween($userId, $from, $to);
        $opening = $this->valuation->balancesBefore($userId, $from);

        // Month windows clipped to [from, to]
        $months = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        while ($cursor->toDateString() <= $to) {
            $mk = $cursor->format('Y-m');
            $months[$mk] = [
                'month' => $mk,
                'label' => StockValuationService::monthLabel($mk),
                'from' => max($from, $cursor->toDateString()),
                'to' => min($to, $cursor->copy()->endOfMonth()->toDateString()),
                'in_qty' => 0.0, 'in_value' => 0.0, 'out_qty' => 0.0, 'out_value' => 0.0,
                'in_by_type' => [], 'out_by_type' => [],
            ];
            $cursor->addMonth();
        }

        foreach ($events as $e) {
            $m = &$months[StockValuationService::monthKey($e['date'])];
            $p = $e['dir'];
            $m[$p . '_qty'] += $e['qty'];
            $m[$p . '_value'] += $e['cost_value'];
            $m[$p . '_by_type'][$e['doc_type']] = ($m[$p . '_by_type'][$e['doc_type']] ?? 0) + $e['cost_value'];
            unset($m);
        }

        $qty = array_sum(array_column($opening, 'qty'));
        $value = array_sum(array_column($opening, 'value'));
        $periodOpening = ['qty' => round($qty, 2), 'value' => round($value, 2)];

        $rows = [];
        foreach ($months as $m) {
            $row = [
                'month' => $m['month'],
                'label' => $m['label'],
                'from' => $m['from'],
                'to' => $m['to'],
                'opening_qty' => round($qty, 2),
                'opening_value' => round($value, 2),
                'in_qty' => round($m['in_qty'], 2),
                'in_value' => round($m['in_value'], 2),
                'out_qty' => round($m['out_qty'], 2),
                'out_value' => round($m['out_value'], 2),
                'in_by_type' => $this->labelTypes($m['in_by_type']),
                'out_by_type' => $this->labelTypes($m['out_by_type']),
            ];
            $qty += $m['in_qty'] - $m['out_qty'];
            $value += $m['in_value'] - $m['out_value'];
            $row['closing_qty'] = round($qty, 2);
            $row['closing_value'] = round($value, 2);
            $rows[] = $row;
        }

        // Лагер листа at date_to, cross-checked against the roll-forward
        $articles = $this->articles($userId);
        $list = [];
        foreach ($this->valuation->balancesAt($userId, $to) as $articleId => $bal) {
            if (abs($bal['qty']) < 0.00001 && abs($bal['value']) < 0.005) {
                continue;
            }
            $a = $articles[$articleId] ?? null;
            $list[] = [
                'article_id' => $articleId,
                'code' => $a->code ?? '',
                'name' => $a->name ?? ('#' . $articleId),
                'unit' => $a->unit ?? '',
                'quantity' => round($bal['qty'], 2),
                'avg_cost' => abs($bal['qty']) > 0.00001 ? round($bal['value'] / $bal['qty'], 4) : 0,
                'value' => round($bal['value'], 2),
                'retail_price' => abs($bal['qty']) > 0.00001 ? round($bal['retail'] / $bal['qty'], 2) : 0,
                'retail_value' => round($bal['retail'], 2),
            ];
        }
        usort($list, fn ($a, $b) => strcmp($a['name'], $b['name']));

        $totals = [
            'opening_qty' => $periodOpening['qty'],
            'opening_value' => $periodOpening['value'],
            'in_qty' => round(array_sum(array_column($rows, 'in_qty')), 2),
            'in_value' => round(array_sum(array_column($rows, 'in_value')), 2),
            'out_qty' => round(array_sum(array_column($rows, 'out_qty')), 2),
            'out_value' => round(array_sum(array_column($rows, 'out_value')), 2),
            'closing_qty' => round($qty, 2),
            'closing_value' => round($value, 2),
        ];

        $allEvents = $this->valuation->events($userId);
        $negative = [];
        $unpriced = [];
        foreach ($allEvents as $e) {
            if ($e['date'] > $to) {
                break;
            }
            if ($e['balance_qty'] < -0.00001) {
                $negative[$e['article_id']] = true;
            }
            // Manual inputs without an entered purchase price (valued at 0 or estimated)
            if (in_array($e['doc_type'], ['opening', 'surplus'], true) && !$e['cost_given'] && isset($e['movement_id'])
                && ($e['estimated'] || $e['cost_value'] == 0)) {
                $a = $articles[$e['article_id']] ?? null;
                $unpriced[] = [
                    'movement_id' => $e['movement_id'],
                    'date' => $e['date'],
                    'type_label' => self::TYPE_LABELS[$e['doc_type']],
                    'code' => $a->code ?? '',
                    'name' => $a->name ?? ('#' . $e['article_id']),
                    'quantity' => $e['qty'],
                    'unit_cost' => $e['unit_cost'],
                ];
            }
        }

        return [
            'months' => $rows,
            'totals' => $totals,
            'list' => $list,
            'list_totals' => [
                'quantity' => round(array_sum(array_column($list, 'quantity')), 2),
                'value' => round(array_sum(array_column($list, 'value')), 2),
                'retail_value' => round(array_sum(array_column($list, 'retail_value')), 2),
            ],
            'checks' => [
                'balanced' => abs($totals['opening_value'] + $totals['in_value'] - $totals['out_value'] - $totals['closing_value']) < 0.01,
                'discrepancies' => $this->valuation->reconcile($userId),
                'negative' => array_values(array_map(
                    fn ($id) => ['code' => $articles[$id]->code ?? '', 'name' => $articles[$id]->name ?? ('#' . $id)],
                    array_keys($negative)
                )),
                'estimated_count' => count(array_filter($events, fn ($e) => $e['estimated'])),
                'unpriced_inputs' => $unpriced,
            ],
        ];
    }

    private function labelTypes(array $byType): array
    {
        $out = [];
        foreach ($byType as $type => $value) {
            $out[] = ['type' => $type, 'label' => self::TYPE_LABELS[$type], 'value' => round($value, 2)];
        }

        return $out;
    }

    private function articles(int $userId): array
    {
        return DB::table('articles')->where('user_id', $userId)
            ->get(['id', 'code', 'name', 'unit', 'tax_rate'])->keyBy('id')->all();
    }

    // ─── CSV ───────────────────────────────────────────────────────────

    private function documentCsvRows(string $tab, array $report): array
    {
        $isOut = $tab === 'outputs';
        $n = fn ($v) => number_format((float) $v, 2, '.', '');

        $header = $isOut
            ? ['Р.бр.', 'Тип', 'Број', 'Датум', 'Партнер', 'Ставки', 'Количина', 'Набавна вредност', 'Продажна без ДДВ', 'ДДВ', 'Продажна со ДДВ', 'РУЦ', 'Достава и друго', 'Вкупно наплатено']
            : ['Р.бр.', 'Тип', 'Број', 'Датум', 'Ставки', 'Количина', 'Набавна без ДДВ', 'ДДВ', 'Набавна со ДДВ', 'Продажна без ДДВ', 'ДДВ (продажна)', 'Продажна со ДДВ', 'РУЦ', 'Проценета'];

        $line = function (array $r) use ($isOut, $n) {
            $date = Carbon::parse($r['date'])->format('d.m.Y');

            return $isOut
                ? [$r['rb'], $r['type_label'], $r['number'], $date, $r['partner'], $r['items'], $n($r['quantity']), $n($r['cost_value']), $n($r['sales_no_tax']), $n($r['sales_tax']), $n($r['sales_with_tax']), $n($r['margin']), $n($r['other']), $n($r['charged_total'])]
                : [$r['rb'], $r['type_label'], $r['number'], $date, $r['items'], $n($r['quantity']), $n($r['cost_value']), $n($r['cost_tax']), $n($r['cost_with_tax']), $n($r['sales_no_tax']), $n($r['sales_tax']), $n($r['sales_with_tax']), $n($r['margin']), $r['estimated'] ? 'да' : ''];
        };

        $total = function (string $label, array $t) use ($isOut, $n) {
            return $isOut
                ? ['', $label, '', '', '', '', $n($t['quantity']), $n($t['cost_value']), $n($t['sales_no_tax']), $n($t['sales_tax']), $n($t['sales_with_tax']), $n($t['margin']), $n($t['other']), $n($t['charged_total'])]
                : ['', $label, '', '', '', $n($t['quantity']), $n($t['cost_value']), $n($t['cost_tax']), $n($t['cost_with_tax']), $n($t['sales_no_tax']), $n($t['sales_tax']), $n($t['sales_with_tax']), $n($t['margin']), ''];
        };

        $rows = [$header];
        foreach ($report['months'] as $m) {
            foreach ($m['rows'] as $r) {
                $rows[] = $line($r);
            }
            foreach ($m['by_type'] as $t) {
                $rows[] = $total("Вкупно {$m['label']} — {$t['label']}", $t);
            }
            $rows[] = $total("Вкупно {$m['label']}", $m['totals']);
        }
        $rows[] = $total('ВКУПНО ЗА ПЕРИОД', $report['totals']);

        return $rows;
    }

    private function stockCsvRows(array $report): array
    {
        $n = fn ($v) => number_format((float) $v, 2, '.', '');

        $rows = [['Месец', 'Почетна кол.', 'Почетна вредност', 'Влез кол.', 'Влез вредност', 'Излез кол.', 'Излез вредност', 'Крајна кол.', 'Крајна вредност']];
        foreach ($report['months'] as $m) {
            $rows[] = [$m['label'], $n($m['opening_qty']), $n($m['opening_value']), $n($m['in_qty']), $n($m['in_value']), $n($m['out_qty']), $n($m['out_value']), $n($m['closing_qty']), $n($m['closing_value'])];
        }
        $t = $report['totals'];
        $rows[] = ['ВКУПНО', $n($t['opening_qty']), $n($t['opening_value']), $n($t['in_qty']), $n($t['in_value']), $n($t['out_qty']), $n($t['out_value']), $n($t['closing_qty']), $n($t['closing_value'])];

        $rows[] = [];
        $rows[] = ['Лагер листа'];
        $rows[] = ['Шифра', 'Назив', 'Ед.', 'Количина', 'Просечна набавна цена', 'Набавна вредност', 'Продажна цена со ДДВ', 'Продажна вредност со ДДВ'];
        foreach ($report['list'] as $r) {
            $rows[] = [$r['code'], $r['name'], $r['unit'], $n($r['quantity']), number_format((float) $r['avg_cost'], 4, '.', ''), $n($r['value']), $n($r['retail_price']), $n($r['retail_value'])];
        }
        $rows[] = ['', 'ВКУПНО', '', $n($report['list_totals']['quantity']), '', $n($report['list_totals']['value']), '', $n($report['list_totals']['retail_value'])];

        return $rows;
    }

    private function levelingCsvRows(array $report): array
    {
        $n = fn ($v) => number_format((float) $v, 2, '.', '');
        $line = fn (string $a, string $b, $count, array $t) => [$a, $b, $count, $n($t['full_value']), $n($t['sold_value']), $n($t['leveling_invoice']), $n($t['leveling_shopify']), $n($t['leveling_issue']), $n($t['leveling']), $n($t['leveling_tax'])];

        $rows = [['Број', 'Датум', 'Документи', 'Полна продажна со ДДВ', 'Продадено / издадено со ДДВ', 'Нивелација фактури', 'Нивелација е-трговија', 'Нивелација испратници и кусоци', 'Нивелација вкупно', 'ДДВ во нивелацијата']];
        foreach ($report['months'] as $m) {
            foreach ($m['rows'] as $r) {
                $rows[] = $line($r['number'], Carbon::parse($r['date'])->format('d.m.Y'), $r['count'], $r);
            }
            $rows[] = $line("Вкупно {$m['label']}", '', $m['totals']['count'], $m['totals']);
        }
        $rows[] = $line('ВКУПНО ЗА ПЕРИОД', '', $report['totals']['count'], $report['totals']);

        return $rows;
    }
}
