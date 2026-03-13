<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Models\StockMovement;
use App\Services\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfitabilityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $displayCurrency = $user->agency?->display_currency ?? 'MKD';
        $converter = new CurrencyConverter();

        // Date range filter
        $from = $request->get('from', Carbon::now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // Theoretical data: all articles with avg cost from receipts (all-time)
        $articles = $user->articles()
            ->withAvg(['stockMovements as avg_cost_price' => function ($q) {
                $q->where('type', 'receipt')->where('cost_price', '>', 0);
            }], 'cost_price')
            ->get(['id', 'name', 'unit', 'price']);

        // Actual revenue: invoice items from paid invoices in date range
        $revenueItems = InvoiceItem::query()
            ->whereNotNull('article_id')
            ->whereHas('invoice', function ($q) use ($user, $fromDate, $toDate) {
                $q->where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->whereBetween('issue_date', [$fromDate, $toDate]);
            })
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select('invoice_items.article_id', 'invoice_items.quantity', 'invoice_items.total', 'invoice_items.tax_amount', 'invoices.currency', 'invoices.issue_date')
            ->get();

        // Group revenue by article, converting to display currency
        $revenueByArticle = [];
        $qtySoldByArticle = [];
        foreach ($revenueItems as $item) {
            $articleId = $item->article_id;
            $netAmount = $item->total - $item->tax_amount;
            $converted = $converter->convert($netAmount, $item->currency, $displayCurrency, $item->issue_date);
            $revenueByArticle[$articleId] = ($revenueByArticle[$articleId] ?? 0) + $converted;
            $qtySoldByArticle[$articleId] = ($qtySoldByArticle[$articleId] ?? 0) + $item->quantity;
        }

        // Actual cost: stock movements (receipts) in date range
        $costItems = StockMovement::query()
            ->where('user_id', $user->id)
            ->where('type', 'receipt')
            ->where('cost_price', '>', 0)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('article_id, SUM(cost_price * quantity) as total_cost, SUM(quantity) as total_qty')
            ->groupBy('article_id')
            ->get();

        $costByArticle = [];
        $qtyPurchasedByArticle = [];
        foreach ($costItems as $item) {
            $articleId = $item->article_id;
            // Stock movements cost_price is in MKD
            $converted = $converter->convert($item->total_cost, 'MKD', $displayCurrency);
            $costByArticle[$articleId] = $converted;
            $qtyPurchasedByArticle[$articleId] = $item->total_qty;
        }

        // Assemble per-article data
        $articleData = [];
        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($articles as $article) {
            $avgCost = $article->avg_cost_price;
            $sellingPrice = (float) $article->price;
            $revenue = $revenueByArticle[$article->id] ?? 0;
            $cost = $costByArticle[$article->id] ?? 0;
            $qtySold = $qtySoldByArticle[$article->id] ?? 0;
            $qtyPurchased = $qtyPurchasedByArticle[$article->id] ?? 0;

            // Skip articles with no data at all
            if (!$avgCost && !$revenue && !$cost) {
                continue;
            }

            // Convert theoretical prices to display currency (articles are in MKD)
            $sellingPriceConverted = $converter->convert($sellingPrice, 'MKD', $displayCurrency);
            $avgCostConverted = $avgCost ? $converter->convert($avgCost, 'MKD', $displayCurrency) : null;

            $theoreticalMargin = ($avgCostConverted && $sellingPriceConverted > 0)
                ? round((($sellingPriceConverted - $avgCostConverted) / $sellingPriceConverted) * 100, 1)
                : null;

            $actualProfit = $revenue - $cost;
            $actualMargin = $revenue > 0
                ? round(($actualProfit / $revenue) * 100, 1)
                : null;

            $totalRevenue += $revenue;
            $totalCost += $cost;

            $articleData[] = [
                'id' => $article->id,
                'name' => $article->name,
                'unit' => $article->unit,
                'selling_price' => round($sellingPriceConverted, 2),
                'avg_cost' => $avgCostConverted ? round($avgCostConverted, 2) : null,
                'theoretical_margin' => $theoreticalMargin,
                'qty_sold' => round($qtySold, 2),
                'revenue' => round($revenue, 2),
                'qty_purchased' => round($qtyPurchased, 2),
                'cost' => round($cost, 2),
                'profit' => round($actualProfit, 2),
                'actual_margin' => $actualMargin,
            ];
        }

        $totalProfit = $totalRevenue - $totalCost;
        $overallMargin = $totalRevenue > 0
            ? round(($totalProfit / $totalRevenue) * 100, 1)
            : null;

        return Inertia::render('Profitability/Index', [
            'articles' => $articleData,
            'totalRevenue' => round($totalRevenue, 2),
            'totalCost' => round($totalCost, 2),
            'totalProfit' => round($totalProfit, 2),
            'overallMargin' => $overallMargin,
            'displayCurrency' => $displayCurrency,
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
        ]);
    }
}
