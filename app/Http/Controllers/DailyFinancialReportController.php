<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Дневен финансиски извештај — дневна продажба на артикли.
 *  - ЕТ (на мало): продажби од Shopify
 *  - МЕГТ (на големо): продажби од фактури
 * Grouped per article over a date range.
 */
class DailyFinancialReportController extends Controller
{
    private const RETAIL_VAT = 18; // retail (Shopify) prices are gross; default tariff for splitting VAT

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

    private function buildReport($user, Carbon $from, Carbon $to, string $type): array
    {
        $perArticle = $type === 'wholesale'
            ? $this->wholesaleByArticle($user, $from, $to)
            : $this->retailByArticle($user, $from, $to);

        $articleIds = array_keys($perArticle);
        if (empty($articleIds)) {
            return ['rows' => [], 'totals' => $this->emptyTotals()];
        }

        $avgCosts = StockMovement::whereIn('article_id', $articleIds)
            ->where('type', 'receipt')
            ->where('cost_price', '>', 0)
            ->selectRaw('article_id, SUM(cost_price * quantity) / SUM(quantity) as avg_cost')
            ->groupBy('article_id')
            ->pluck('avg_cost', 'article_id');

        $articles = $user->articles()->whereIn('id', $articleIds)->get()->keyBy('id');

        $rows = [];
        foreach ($perArticle as $articleId => $agg) {
            $article = $articles->get($articleId);
            $qty = $agg['qty'];
            $avgCost = (float) ($avgCosts[$articleId] ?? 0);
            $purchase = round($avgCost * $qty, 2);
            $salesNoTax = round($agg['sales_no_tax'], 2);
            $tax = round($agg['tax'], 2);
            $salesWithTax = round($agg['sales_with_tax'], 2);

            $rows[] = [
                'code' => $article->code ?? '',
                'name' => $article->name ?? ('#' . $articleId),
                'unit' => $article->unit ?? '',
                'quantity' => $qty,
                'purchase_value' => $purchase,
                'sales_no_tax' => $salesNoTax,
                'tax' => $tax,
                'sales_with_tax' => $salesWithTax,
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

    /**
     * Wholesale (МЕГТ) — sales from invoices, recomputed from line fields so
     * values are consistent regardless of stored totals. [article_id => agg].
     */
    private function wholesaleByArticle($user, Carbon $from, Carbon $to): array
    {
        $items = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $user->id)
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoice_items.article_id')
            ->whereBetween('invoices.issue_date', [$from, $to])
            ->select('invoice_items.article_id', 'invoice_items.quantity', 'invoice_items.unit_price', 'invoice_items.discount', 'invoice_items.tax_rate')
            ->get();

        $map = [];
        foreach ($items as $it) {
            $qty = (float) $it->quantity;
            $base = round($qty * (float) $it->unit_price * (1 - (float) $it->discount / 100), 2);
            $tax = round($base * (float) $it->tax_rate / 100, 2);
            $this->accumulate($map, $it->article_id, $qty, $base, $tax, $base + $tax);
        }

        return $map;
    }

    /**
     * Retail (ЕТ) — sales from paid Shopify orders. Line revenue is gross (со ДДВ);
     * VAT split out at the standard retail tariff. [article_id => agg].
     */
    private function retailByArticle($user, Carbon $from, Carbon $to): array
    {
        $items = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $user->id)
            ->where('shopify_orders.financial_status', 'paid')
            ->whereNotNull('shopify_order_items.article_id')
            ->whereBetween('shopify_orders.ordered_at', [$from, $to])
            ->select('shopify_order_items.article_id', 'shopify_order_items.quantity', 'shopify_order_items.price', 'shopify_order_items.total_discount')
            ->get();

        $rate = self::RETAIL_VAT;
        $map = [];
        foreach ($items as $it) {
            $qty = (float) $it->quantity;
            $gross = round($qty * (float) $it->price - (float) $it->total_discount, 2); // со ДДВ
            $noTax = round($gross / (1 + $rate / 100), 2);
            $tax = round($gross - $noTax, 2);
            $this->accumulate($map, $it->article_id, $qty, $noTax, $tax, $gross);
        }

        return $map;
    }

    private function accumulate(array &$map, $articleId, float $qty, float $noTax, float $tax, float $withTax): void
    {
        if (!isset($map[$articleId])) {
            $map[$articleId] = ['qty' => 0, 'sales_no_tax' => 0, 'tax' => 0, 'sales_with_tax' => 0];
        }
        $map[$articleId]['qty'] += $qty;
        $map[$articleId]['sales_no_tax'] += $noTax;
        $map[$articleId]['tax'] += $tax;
        $map[$articleId]['sales_with_tax'] += $withTax;
    }

    private function emptyTotals(): array
    {
        return [
            'quantity' => 0, 'purchase_value' => 0, 'sales_no_tax' => 0,
            'tax' => 0, 'sales_with_tax' => 0, 'margin' => 0,
        ];
    }
}
