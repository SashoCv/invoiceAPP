<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\InvoiceItem;
use App\Models\StockMovement;
use App\Services\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Load bundles for distributing bundle sales
        $bundles = Bundle::where('user_id', $user->id)
            ->with('bundleItems.article')
            ->get()
            ->keyBy('id');

        $distributeBundleSale = function (int $bundleId, float $bundleQty, float $lineRevenue) use ($bundles): array {
            $bundle = $bundles->get($bundleId);
            if (!$bundle || $bundle->bundleItems->isEmpty()) return [];

            $totalComponentValue = 0;
            foreach ($bundle->bundleItems as $bi) {
                if ($bi->article) {
                    $totalComponentValue += (float) $bi->article->price * $bi->quantity;
                }
            }

            $result = [];
            foreach ($bundle->bundleItems as $bi) {
                if (!$bi->article) continue;
                $articleQty = $bundleQty * $bi->quantity;
                $articleRevenue = $totalComponentValue > 0
                    ? $lineRevenue * ((float) $bi->article->price * $bi->quantity / $totalComponentValue)
                    : 0;
                $result[$bi->article_id] = [
                    'qty' => $articleQty,
                    'revenue' => $articleRevenue,
                ];
            }
            return $result;
        };

        // === REVENUE ===

        // 1. Direct invoice article sales (net = total - tax)
        $invoiceDirectItems = InvoiceItem::query()
            ->whereNotNull('article_id')
            ->whereNull('bundle_id')
            ->whereHas('invoice', function ($q) use ($user, $fromDate, $toDate) {
                $q->where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->whereBetween('issue_date', [$fromDate, $toDate]);
            })
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select('invoice_items.article_id', 'invoice_items.quantity', 'invoice_items.total', 'invoice_items.tax_amount', 'invoices.currency', 'invoices.issue_date')
            ->get();

        $revenueByArticle = [];
        $qtySoldByArticle = [];

        foreach ($invoiceDirectItems as $item) {
            $articleId = $item->article_id;
            $netAmount = $item->total - $item->tax_amount;
            $converted = $converter->convert($netAmount, $item->currency, $displayCurrency, $item->issue_date);
            $revenueByArticle[$articleId] = ($revenueByArticle[$articleId] ?? 0) + $converted;
            $qtySoldByArticle[$articleId] = ($qtySoldByArticle[$articleId] ?? 0) + $item->quantity;
        }

        // 2. Invoice bundle sales (net = total - tax, distributed to components)
        $invoiceBundleItems = InvoiceItem::query()
            ->whereNotNull('bundle_id')
            ->whereHas('invoice', function ($q) use ($user, $fromDate, $toDate) {
                $q->where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->whereBetween('issue_date', [$fromDate, $toDate]);
            })
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select('invoice_items.bundle_id', 'invoice_items.quantity', 'invoice_items.total', 'invoice_items.tax_amount', 'invoices.currency', 'invoices.issue_date')
            ->get();

        foreach ($invoiceBundleItems as $item) {
            $netAmount = $item->total - $item->tax_amount;
            $converted = $converter->convert($netAmount, $item->currency, $displayCurrency, $item->issue_date);
            $distributed = $distributeBundleSale($item->bundle_id, (float) $item->quantity, $converted);
            foreach ($distributed as $articleId => $data) {
                $revenueByArticle[$articleId] = ($revenueByArticle[$articleId] ?? 0) + $data['revenue'];
                $qtySoldByArticle[$articleId] = ($qtySoldByArticle[$articleId] ?? 0) + $data['qty'];
            }
        }

        // 3. Shopify direct article sales
        $shopifyDirectItems = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $user->id)
            ->whereBetween('shopify_orders.ordered_at', [$fromDate, $toDate])
            ->whereNotNull('shopify_order_items.article_id')
            ->whereNull('shopify_order_items.bundle_id')
            ->select('shopify_order_items.article_id', 'shopify_order_items.quantity', 'shopify_order_items.price', 'shopify_order_items.total_discount', 'shopify_orders.currency', 'shopify_orders.ordered_at')
            ->get();

        foreach ($shopifyDirectItems as $item) {
            $articleId = $item->article_id;
            $lineTotal = ($item->price * $item->quantity) - $item->total_discount;
            $converted = $converter->convert($lineTotal, $item->currency, $displayCurrency, $item->ordered_at);
            $revenueByArticle[$articleId] = ($revenueByArticle[$articleId] ?? 0) + $converted;
            $qtySoldByArticle[$articleId] = ($qtySoldByArticle[$articleId] ?? 0) + $item->quantity;
        }

        // 4. Shopify bundle sales
        $shopifyBundleItems = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $user->id)
            ->whereBetween('shopify_orders.ordered_at', [$fromDate, $toDate])
            ->whereNotNull('shopify_order_items.bundle_id')
            ->select('shopify_order_items.bundle_id', 'shopify_order_items.quantity', 'shopify_order_items.price', 'shopify_order_items.total_discount', 'shopify_orders.currency', 'shopify_orders.ordered_at')
            ->get();

        foreach ($shopifyBundleItems as $item) {
            $lineTotal = ($item->price * $item->quantity) - $item->total_discount;
            $converted = $converter->convert($lineTotal, $item->currency, $displayCurrency, $item->ordered_at);
            $distributed = $distributeBundleSale($item->bundle_id, (float) $item->quantity, $converted);
            foreach ($distributed as $articleId => $data) {
                $revenueByArticle[$articleId] = ($revenueByArticle[$articleId] ?? 0) + $data['revenue'];
                $qtySoldByArticle[$articleId] = ($qtySoldByArticle[$articleId] ?? 0) + $data['qty'];
            }
        }

        // === COST ===
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

            if (!$avgCost && !$revenue && !$cost) {
                continue;
            }

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
