<?php

namespace App\Http\Controllers;

use App\Models\DailyFiscalReport;
use App\Services\PdfService;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Образец ЕТ — Евиденција во трговија (Trade ledger).
 *
 * Kept at продажни цени со ДДВ. Each accounting document is one row: inputs carry
 * набавна (кол. 5) and продажна (кол. 6) вредност; the daily нивелација lowers кол. 6
 * by the discounts given; испратници and кусоци go out through дневен промет (кол. 7).
 * Invoice and Shopify rows show their own amount in дневен промет for visibility, but
 * only the "Дн. фис. извештај" row per day counts in the totals — the day total from
 * invoices + Shopify, or a manually entered fiscal (Z) report — so nothing is
 * double-counted.
 */
class TradeLedgerController extends Controller implements HasMiddleware
{
    /** Row types whose дневен промет counts in the totals (the rest is per-document detail) */
    private const TURNOVER_TYPES = ['fiscal', 'issue', 'shortage'];

    /** @var array<int, array<int, float>> article VAT rates per user */
    private array $taxRates = [];

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: [
                'storeReport', 'updateReport', 'destroyReport',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $ledger = $this->buildLedger($request->user(), $from, $to);

        return Inertia::render('Reports/TradeLedger/Index', [
            'rows' => $ledger['displayRows'],
            'periodTotals' => $ledger['periodTotals'],
            'grandTotals' => $ledger['grandTotals'],
            'reports' => $request->user()->dailyFiscalReports()
                ->whereBetween('date', [$from, $to])
                ->orderByDesc('date')
                ->get(),
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
        ]);
    }

    public function exportPdf(Request $request, PdfService $pdfService): BinaryFileResponse
    {
        [$from, $to] = $this->resolvePeriod($request);

        $ledger = $this->buildLedger($request->user(), $from, $to);
        $agency = $request->user()->agency;

        $data = [
            'agency' => $agency,
            'authorizedPerson' => trim(($request->user()->first_name ?? '') . ' ' . ($request->user()->last_name ?? '')) ?: $request->user()->name,
            'year' => $to->year,
            'dateFrom' => $from->format('d.m.Y'),
            'dateTo' => $to->format('d.m.Y'),
            'printedAt' => now()->format('d.m.Y H:i'),
            'rows' => $ledger['displayRows'],
            'periodTotals' => $ledger['periodTotals'],
            'grandTotals' => $ledger['grandTotals'],
        ];

        $pdfPath = $pdfService->generateTradeLedgerPdf($data);

        $filename = 'Evidencija_vo_trgovija_' . $from->toDateString() . '_' . $to->toDateString() . '.pdf';

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Записник за нивелација for one day — the document behind that day's "НИВ" row in ЕТ.
     * Per sales document and article: the full продажна вредност со ДДВ the goods were
     * carried at, what they were sold for, the difference and the ДДВ contained in it.
     */
    public function levelingPdf(Request $request, string $date, PdfService $pdfService): BinaryFileResponse
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            abort(404);
        }

        $user = $request->user();
        $valuation = app(StockValuationService::class);
        $docsMeta = $valuation->documents($user->id);
        $articles = $user->articles()->withTrashed()->get(['id', 'code', 'name', 'unit', 'tax_rate'])->keyBy('id');
        $typeLabels = ['invoice' => 'Фактура', 'shopify' => 'Е-трговија'];

        $docs = [];
        foreach ($valuation->eventsBetween($user->id, $day->toDateString(), $day->toDateString()) as $e) {
            if (!in_array($e['doc_type'], ['invoice', 'shopify'], true) || abs($e['leveling']) < 0.005) {
                continue;
            }

            $article = $articles->get($e['article_id']);
            $rate = $e['doc_type'] === 'shopify' ? StockValuationService::RETAIL_VAT : (float) ($article->tax_rate ?? 0);
            $meta = $docsMeta[$e['doc_key']] ?? [];

            $docs[$e['doc_key']] ??= [
                'label' => $typeLabels[$e['doc_type']] . ' ' . ($meta['number'] ?? ''),
                'partner' => $meta['partner'] ?? null,
                'lines' => [],
            ];
            $docs[$e['doc_key']]['lines'][] = [
                'code' => $article->code ?? '',
                'name' => $article->name ?? ('#' . $e['article_id']),
                'unit' => $article->unit ?? '',
                'quantity' => $e['qty'],
                'full_unit' => $e['retail_unit'],
                'full_value' => $e['retail_value'],
                'sold_value' => round($e['sales_no_tax'] + $e['sales_tax'], 2),
                'difference' => $e['leveling'],
                // ДДВ содржан во разликата (пресметковна стапка = стапка / (100 + стапка))
                'difference_tax' => round($e['leveling'] * $rate / (100 + $rate), 2),
            ];
        }

        $sum = fn (array $lines, string $f) => round(array_sum(array_column($lines, $f)), 2);
        $docs = array_values(array_map(function ($d) use ($sum) {
            foreach (['full_value', 'sold_value', 'difference', 'difference_tax'] as $f) {
                $d['totals'][$f] = $sum($d['lines'], $f);
            }
            return $d;
        }, $docs));

        $allLines = array_merge(...array_map(fn ($d) => $d['lines'], $docs ?: [['lines' => []]]));
        $totals = [];
        foreach (['full_value', 'sold_value', 'difference', 'difference_tax'] as $f) {
            $totals[$f] = $sum($allLines, $f);
        }

        $pdfPath = $pdfService->generateLevelingPdf([
            'agency' => $user->agency,
            'authorizedPerson' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
            'number' => 'НИВ-' . $day->format('d.m.Y'),
            'date' => $day->format('d.m.Y'),
            'printedAt' => now()->format('d.m.Y H:i'),
            'docs' => $docs,
            'totals' => $totals,
        ]);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Nivelacija_' . $day->toDateString() . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    // --- Manual daily fiscal reports ---

    public function storeReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'report_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $request->user()->dailyFiscalReports()->create($validated);

        return back()->with('success', __('toast.fiscal_report_saved'));
    }

    public function updateReport(Request $request, DailyFiscalReport $dailyFiscalReport): RedirectResponse
    {
        abort_if($dailyFiscalReport->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'report_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $dailyFiscalReport->update($validated);

        return back()->with('success', __('toast.fiscal_report_saved'));
    }

    public function destroyReport(Request $request, DailyFiscalReport $dailyFiscalReport): RedirectResponse
    {
        abort_if($dailyFiscalReport->user_id !== $request->user()->id, 403);

        $dailyFiscalReport->delete();

        return back()->with('success', __('toast.fiscal_report_deleted'));
    }

    // --- Ledger building ---

    private function resolvePeriod(Request $request): array
    {
        try {
            $from = $request->filled('date_from')
                ? Carbon::createFromFormat('Y-m-d', $request->date_from)->startOfDay()
                : now()->startOfMonth();
        } catch (\Exception $e) {
            $from = now()->startOfMonth();
        }

        try {
            $to = $request->filled('date_to')
                ? Carbon::createFromFormat('Y-m-d', $request->date_to)->endOfDay()
                : now()->endOfDay();
        } catch (\Exception $e) {
            $to = now()->endOfDay();
        }

        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Build the full ledger from the start of the period's year up to $to so that
     * row numbers and the cumulative "Вкупно" total are correct, then expose only
     * the rows within [$from, $to] for display.
     *
     * Образец ЕТ по продажни цени со ДДВ (Правилник, Сл. весник 51/04, 89/04):
     *  - кол. 5 набавна вредност со ДДВ (ПЛТ кол. 6 + 7) — приеми, почетна, вишоци
     *  - кол. 6 продажна вредност со ДДВ (ПЛТ кол. 10) — приеми, почетна, вишоци, поврати,
     *    and the daily нивелација (red storno, negative) for goods sold below full price
     *  - кол. 7 дневен промет — invoices + Shopify per day (or the manual Z report), plus
     *    испратници (промоции, гратис) and кусоци at продажна вредност
     * Залиха по продажни цени = Σ кол. 6 − Σ кол. 7. All values come from
     * StockValuationService so they agree with the accounting reports.
     */
    private function buildLedger($user, Carbon $from, Carbon $to): array
    {
        $yearStart = $from->copy()->startOfYear()->toDateString();
        $toDate = $to->toDateString();
        $valuation = app(StockValuationService::class);

        $rows = collect();

        // 0. Пренос — stock carried over from before the year, at both prices
        $carry = $valuation->balancesBefore($user->id, $yearStart);
        $carryRetail = round(array_sum(array_column($carry, 'retail')), 2);
        if (abs($carryRetail) >= 0.01) {
            $taxRates = $user->articles()->withTrashed()->pluck('tax_rate', 'id');
            $carryPurchase = 0;
            foreach ($carry as $articleId => $bal) {
                $carryPurchase += $bal['value'] * (1 + (float) ($taxRates[$articleId] ?? 0) / 100);
            }
            $date = Carbon::parse($yearStart);
            $rows->push($this->row($date, 'carryover', '—', $date, $carryPurchase, $carryRetail, 0));
        }

        // 1. Documents from the stock replay, one row per document
        $docs = $valuation->documents($user->id);
        $perDoc = [];
        $levelingByDay = [];
        foreach ($valuation->eventsBetween($user->id, $yearStart, $toDate) as $e) {
            $key = $e['doc_key'];
            $perDoc[$key] ??= ['type' => $e['doc_type'], 'date' => $e['date'], 'purchase' => 0.0, 'retail' => 0.0];
            $perDoc[$key]['purchase'] += $e['cost_value'] + round($e['cost_value'] * $this->purchaseTaxRate($e, $user) / 100, 2);
            $perDoc[$key]['retail'] += $e['retail_value'];

            if ($e['leveling'] != 0) {
                $levelingByDay[$e['date']] = ($levelingByDay[$e['date']] ?? 0) + $e['leveling'];
            }
        }

        foreach ($perDoc as $key => $d) {
            $date = Carbon::parse($d['date']);
            $number = $docs[$key]['number'] ?? '—';

            match ($d['type']) {
                // Inputs: набавна (кол. 5) and продажна (кол. 6)
                'receipt', 'opening', 'surplus' => $rows->push($this->row($date, $d['type'], $number, $date, $d['purchase'], $d['retail'], 0)),
                // Returned goods go back into stock at продажна вредност
                'return' => $rows->push($this->row($date, 'return', $number, $date, 0, $d['retail'], 0)),
                // Промоции / гратис and кусоци leave the stock through дневен промет
                'issue', 'shortage' => $rows->push($this->row($date, $d['type'], $number, $date, 0, 0, $d['retail'])),
                default => null, // invoices and Shopify: shown below by amount
            };
        }

        // 2. Invoices → Фактура. Every invoice that took goods out of stock (all but
        // cancelled/deleted) — the same set as the stock and the accounting reports.
        $invoices = $user->invoices()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('issue_date', [$yearStart, $toDate])
            ->get(['id', 'invoice_number', 'issue_date', 'total']);
        foreach ($invoices as $invoice) {
            $rows->push($this->row($invoice->issue_date, 'invoice', $invoice->invoice_number, $invoice->issue_date, 0, 0, (float) $invoice->total));
        }
        $invoiceByDay = $invoices->groupBy(fn ($i) => $i->issue_date->toDateString())->map(fn ($g) => $g->sum('total'));

        // 3. Shopify → one row per day
        $shopifyByDay = $user->shopifyOrders()
            ->whereBetween('ordered_at', [$yearStart, $to])
            ->selectRaw('DATE(ordered_at) as d, SUM(total_price) as t')
            ->groupBy('d')->pluck('t', 'd');
        foreach ($shopifyByDay as $day => $total) {
            $date = Carbon::parse($day);
            if ((float) $total > 0) {
                $rows->push($this->row($date, 'shopify', '—', $date, 0, 0, (float) $total));
            }
        }

        // 4. Нивелација — daily difference between full продажна and actual sale price
        foreach ($levelingByDay as $day => $amount) {
            if (abs($amount) >= 0.01) {
                $date = Carbon::parse($day);
                $rows->push($this->row($date, 'leveling', 'НИВ-' . $date->format('d.m.Y'), $date, 0, $amount, 0));
            }
        }

        // 5. Дневен промет → Дн. фис. извештај (manual overrides auto invoices + Shopify)
        $manualByDay = $user->dailyFiscalReports()
            ->whereBetween('date', [$yearStart, $toDate])
            ->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $days = collect($invoiceByDay->keys())
            ->merge($shopifyByDay->keys())
            ->merge($manualByDay->keys())
            ->unique();

        foreach ($days as $day) {
            $date = Carbon::parse($day);
            if ($manualByDay->has($day)) {
                $report = $manualByDay->get($day);
                $rows->push($this->row($date, 'fiscal', $report->report_number ?: '—', $date, 0, 0, (float) $report->amount));
            } else {
                $turnover = (float) ($invoiceByDay[$day] ?? 0) + (float) ($shopifyByDay[$day] ?? 0);
                if ($turnover > 0) {
                    $rows->push($this->row($date, 'fiscal', '—', $date, 0, 0, $turnover));
                }
            }
        }

        // Sort: by booking date; inputs, then outputs, sales documents, нивелација, day total
        $typeOrder = [
            'carryover' => 0, 'opening' => 1, 'receipt' => 2, 'surplus' => 3, 'return' => 4,
            'issue' => 5, 'shortage' => 6, 'invoice' => 7, 'shopify' => 8, 'leveling' => 9, 'fiscal' => 10,
        ];
        $sorted = $rows->sort(function ($a, $b) use ($typeOrder) {
            $cmp = $a['_sortDate'] <=> $b['_sortDate'];
            if ($cmp !== 0) return $cmp;
            return $typeOrder[$a['type']] <=> $typeOrder[$b['type']];
        })->values();

        // Running number across the whole year-to-date set
        $sorted = $sorted->map(function ($row, $i) {
            $row['row_no'] = $i + 1;
            return $row;
        });

        $displayRows = $sorted->filter(fn ($r) => $r['_sortDate'] >= $from->toDateString())->values();

        return [
            'displayRows' => $displayRows->map(fn ($r) => collect($r)->except('_sortDate'))->values(),
            'periodTotals' => $this->sumRows($displayRows),
            'grandTotals' => $this->sumRows($sorted),
        ];
    }

    /**
     * ДДВ on the purchase value (ПЛТ кол. 7): the receipt line's own rate, otherwise the article's.
     */
    private function purchaseTaxRate(array $event, $user): float
    {
        if ($event['doc_type'] === 'receipt') {
            return (float) $event['tax_rate'];
        }

        $this->taxRates[$user->id] ??= $user->articles()->withTrashed()->pluck('tax_rate', 'id')->all();

        return (float) ($this->taxRates[$user->id][$event['article_id']] ?? 0);
    }

    private function row(Carbon $bookingDate, string $type, ?string $number, Carbon $docDate, float $purchase, float $sales, float $turnover): array
    {
        return [
            'type' => $type,
            '_sortDate' => $bookingDate->toDateString(),
            'date_iso' => $bookingDate->toDateString(),
            'booking_date' => $bookingDate->format('d.m.Y'),
            'doc_number' => $number ?? '',
            'doc_date' => $docDate->format('d.m.Y'),
            'purchase_value' => round($purchase, 2),
            'sales_value' => round($sales, 2),
            'daily_turnover' => round($turnover, 2),
        ];
    }

    private function sumRows($rows): array
    {
        return [
            'purchase_value' => round($rows->sum('purchase_value'), 2),
            'sales_value' => round($rows->sum('sales_value'), 2),
            // Invoice rows also carry their own amount in daily_turnover (for display,
            // so you can see each invoice's contribution) — only the "fiscal" row per
            // day is the real day total, so that's the only type summed here to avoid
            // double-counting the same money twice. Испратници and кусоци carry their
            // own продажна вредност in this column (goods out without payment).
            'daily_turnover' => round($rows->whereIn('type', self::TURNOVER_TYPES)->sum('daily_turnover'), 2),
        ];
    }
}
