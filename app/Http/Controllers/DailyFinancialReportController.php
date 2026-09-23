<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Дневен финансиски извештај — дневна продажба на артикли.
 *  - ЕТ (на мало): продажби од Shopify
 *  - МЕГТ (на големо): продажби од фактури
 * Grouped per article over a date range; values from StockValuationService.
 */
class DailyFinancialReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $type = $request->get('type', 'retail') === 'wholesale' ? 'wholesale' : 'retail';

        $report = $this->buildReport($request->user(), $from, $to, $type);

        return Inertia::render('Reports/DailyFinancial/Index', [
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'type' => $type,
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
        ]);
    }

    public function exportPdf(Request $request, PdfService $pdfService): BinaryFileResponse
    {
        [$from, $to] = $this->resolvePeriod($request);
        $type = $request->get('type', 'retail') === 'wholesale' ? 'wholesale' : 'retail';

        $report = $this->buildReport($request->user(), $from, $to, $type);

        $data = [
            'agency' => $request->user()->agency,
            'type' => $type,
            'typeLabel' => $type === 'wholesale' ? 'МЕГТ — продажба на големо' : 'ЕТ — продажба на мало',
            'dateFrom' => $from->format('d.m.Y'),
            'dateTo' => $to->format('d.m.Y'),
            'printedAt' => now()->format('d.m.Y H:i'),
            'rows' => $report['rows'],
            'totals' => $report['totals'],
        ];

        $pdfPath = $pdfService->generateDailyFinancialReportPdf($data);

        $filename = ($type === 'wholesale' ? 'MEGT_golemo_' : 'ET_malo_') . $from->toDateString() . '_' . $to->toDateString() . '.pdf';

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

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
     * Per-article sales for the period, taken from the stock replay so набавна вредност,
     * quantities and sales match the accounting reports (Излезни калкулации) exactly:
     * retail = Е-трговија (Shopify), wholesale = Фактури; bundles are broken into their
     * components with the line's sales split by retail price.
     */
    private function buildReport($user, Carbon $from, Carbon $to, string $type): array
    {
        $docType = $type === 'wholesale' ? 'invoice' : 'shopify';
        $valuation = app(StockValuationService::class);

        $perArticle = [];
        foreach ($valuation->eventsBetween($user->id, $from->toDateString(), $to->toDateString()) as $e) {
            if ($e['doc_type'] !== $docType) {
                continue;
            }
            $a = &$perArticle[$e['article_id']];
            $a ??= ['qty' => 0, 'purchase' => 0, 'sales_no_tax' => 0, 'tax' => 0];
            $a['qty'] += $e['qty'];
            $a['purchase'] += $e['cost_value'];
            $a['sales_no_tax'] += $e['sales_no_tax'];
            $a['tax'] += $e['sales_tax'];
            unset($a);
        }

        if (empty($perArticle)) {
            return ['rows' => [], 'totals' => $this->emptyTotals()];
        }

        $articles = $user->articles()->withTrashed()->whereIn('id', array_keys($perArticle))->get()->keyBy('id');

        $rows = [];
        foreach ($perArticle as $articleId => $agg) {
            $article = $articles->get($articleId);
            $purchase = round($agg['purchase'], 2);
            $salesNoTax = round($agg['sales_no_tax'], 2);
            $tax = round($agg['tax'], 2);

            $rows[] = [
                'code' => $article->code ?? '',
                'name' => $article->name ?? ('#' . $articleId),
                'unit' => $article->unit ?? '',
                'quantity' => round($agg['qty'], 2),
                'purchase_value' => $purchase,
                'sales_no_tax' => $salesNoTax,
                'tax' => $tax,
                'sales_with_tax' => round($salesNoTax + $tax, 2),
                'margin' => round($salesNoTax - $purchase, 2),
            ];
        }

        // Sort by sales (со ДДВ) descending
        usort($rows, fn ($a, $b) => $b['sales_with_tax'] <=> $a['sales_with_tax']);

        $totals = [
            'quantity' => round(array_sum(array_column($rows, 'quantity')), 2),
            'purchase_value' => round(array_sum(array_column($rows, 'purchase_value')), 2),
            'sales_no_tax' => round(array_sum(array_column($rows, 'sales_no_tax')), 2),
            'tax' => round(array_sum(array_column($rows, 'tax')), 2),
            'sales_with_tax' => round(array_sum(array_column($rows, 'sales_with_tax')), 2),
            'margin' => round(array_sum(array_column($rows, 'margin')), 2),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    private function emptyTotals(): array
    {
        return [
            'quantity' => 0, 'purchase_value' => 0, 'sales_no_tax' => 0,
            'tax' => 0, 'sales_with_tax' => 0, 'margin' => 0,
        ];
    }
}
