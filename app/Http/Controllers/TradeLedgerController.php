<?php

namespace App\Http\Controllers;

use App\Models\DailyFiscalReport;
use App\Services\PdfService;
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
 * Each accounting document (goods receipt, goods issue, invoice) is one row with
 * its purchase value (набавна) and sales value (продажна). The daily till turnover
 * (дневен промет) is recorded as a "Дн. фис. извештај" row per day, computed from
 * invoices + Shopify sales, or overridden by a manually entered fiscal (Z) report.
 */
class TradeLedgerController extends Controller implements HasMiddleware
{
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
     */
    private function buildLedger($user, Carbon $from, Carbon $to): array
    {
        $yearStart = $from->copy()->startOfYear();

        $rows = collect();

        // 1. Goods receipts → Прием од магацин
        foreach ($user->goodsReceipts()->with('movements.article')->whereBetween('date', [$yearStart, $to])->get() as $receipt) {
            $purchase = 0;
            $sales = 0;
            foreach ($receipt->movements as $m) {
                $qty = (float) $m->quantity;
                $purchase += $qty * (float) ($m->cost_price ?? 0);
                $sales += $qty * (float) ($m->article->price ?? 0);
            }
            $rows->push($this->row($receipt->date, 'receipt', $receipt->receipt_number, $receipt->date, $purchase, $sales, 0));
        }

        // 2. Goods issues → Испратница
        foreach ($user->goodsIssues()->with('movements.article')->whereBetween('date', [$yearStart, $to])->get() as $issue) {
            $purchase = 0;
            $sales = 0;
            foreach ($issue->movements as $m) {
                $qty = (float) $m->quantity;
                $purchase += $qty * (float) ($m->cost_price ?? 0);
                $sales += $qty * (float) ($m->article->price ?? 0);
            }
            $rows->push($this->row($issue->date, 'issue', $issue->issue_number, $issue->date, $purchase, $sales, 0));
        }

        // 3. Invoices → Фактура (sales value = invoice total)
        foreach ($user->invoices()->whereBetween('issue_date', [$yearStart, $to])->get() as $invoice) {
            $rows->push($this->row($invoice->issue_date, 'invoice', $invoice->invoice_number, $invoice->issue_date, 0, (float) $invoice->total, 0));
        }

        // 4. Daily turnover → Дн. фис. извештај (manual overrides auto invoices + Shopify)
        $invoiceByDay = $user->invoices()
            ->whereBetween('issue_date', [$yearStart, $to])
            ->selectRaw('DATE(issue_date) as d, SUM(total) as t')
            ->groupBy('d')->pluck('t', 'd');

        $shopifyByDay = $user->shopifyOrders()
            ->where('financial_status', 'paid')
            ->whereBetween('ordered_at', [$yearStart, $to])
            ->selectRaw('DATE(ordered_at) as d, SUM(total_price) as t')
            ->groupBy('d')->pluck('t', 'd');

        $manualByDay = $user->dailyFiscalReports()
            ->whereBetween('date', [$yearStart, $to])
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

        // Sort: by booking date, documents before the daily fiscal report of the same day
        $typeOrder = ['receipt' => 0, 'issue' => 1, 'invoice' => 2, 'fiscal' => 3];
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

    private function row(Carbon $bookingDate, string $type, ?string $number, Carbon $docDate, float $purchase, float $sales, float $turnover): array
    {
        return [
            'type' => $type,
            '_sortDate' => $bookingDate->toDateString(),
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
            'daily_turnover' => round($rows->sum('daily_turnover'), 2),
        ];
    }
}
