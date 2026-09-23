<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Services\CurrencyConverter;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Профитабилност по артикл. Sales (без ДДВ) and the набавна вредност of the goods sold
 * come from StockValuationService, so revenue, cost and РУЦ match the accounting
 * reports (Излезни калкулации) and the Дневен финансиски извештај.
 */
class ProfitabilityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $displayCurrency = $user->agency?->display_currency ?? 'MKD';
        $converter = new CurrencyConverter();
        $toDisplay = fn (float $mkd) => $displayCurrency === 'MKD' ? $mkd : $converter->convert($mkd, 'MKD', $displayCurrency);

        // Date range filter
        $from = $request->get('from', Carbon::now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $valuation = app(StockValuationService::class);

        // Sales, cost of goods sold and purchases per article in the period
        $revenueByArticle = [];
        $costByArticle = [];
        $qtySoldByArticle = [];
        $qtyPurchasedByArticle = [];
        foreach ($valuation->eventsBetween($user->id, $fromDate->toDateString(), $toDate->toDateString()) as $e) {
            $a = $e['article_id'];
            if ($e['doc_type'] === 'receipt') {
                $qtyPurchasedByArticle[$a] = ($qtyPurchasedByArticle[$a] ?? 0) + $e['qty'];
            } elseif (in_array($e['doc_type'], ['invoice', 'shopify'], true)) {
                $revenueByArticle[$a] = ($revenueByArticle[$a] ?? 0) + $e['sales_no_tax'];
                $costByArticle[$a] = ($costByArticle[$a] ?? 0) + $e['cost_value'];
                $qtySoldByArticle[$a] = ($qtySoldByArticle[$a] ?? 0) + $e['qty'];
            }
        }

        // Current average purchase cost (moving weighted average at the end of the period)
        $balances = $valuation->balancesAt($user->id, $toDate->toDateString());

        $articles = $user->articles()->get(['id', 'name', 'unit', 'price']);

        // === Revenue not tied to stock (без ДДВ) ===
        $unlinkedRevenue = [];

        // Invoice lines without an article/bundle (services etc.)
        $invoiceUnlinkedItems = InvoiceItem::query()
            ->whereNull('article_id')
            ->whereNull('bundle_id')
            ->whereHas('invoice', function ($q) use ($user, $fromDate, $toDate) {
                $q->where('user_id', $user->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('issue_date', [$fromDate, $toDate]);
            })
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select('invoice_items.description', 'invoice_items.quantity', 'invoice_items.unit_price', 'invoice_items.discount', 'invoice_items.additional_discount', 'invoices.currency', 'invoices.issue_date', 'invoices.invoice_number')
            ->get();

        foreach ($invoiceUnlinkedItems as $item) {
            $base = StockValuationService::invoiceLineBase((float) $item->quantity, (float) $item->unit_price, (float) $item->discount, (float) ($item->additional_discount ?? 0));
            $unlinkedRevenue[] = [
                'source' => __('profitability.source_invoice') . ' ' . $item->invoice_number,
                'description' => $item->description,
                'qty' => (float) $item->quantity,
                'amount' => round($converter->convert($base, $item->currency, $displayCurrency, $item->issue_date), 2),
            ];
        }

        // Unmapped Shopify items (no article, no bundle)
        $netOfRetailVat = fn (float $gross) => $gross / (1 + StockValuationService::RETAIL_VAT / 100);

        $shopifyUnmappedItems = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $user->id)
            ->whereBetween('shopify_orders.ordered_at', [$fromDate, $toDate])
            ->whereNull('shopify_order_items.article_id')
            ->whereNull('shopify_order_items.bundle_id')
            ->select('shopify_order_items.title', 'shopify_order_items.quantity', 'shopify_order_items.price', 'shopify_order_items.total_discount', 'shopify_orders.currency', 'shopify_orders.ordered_at', 'shopify_orders.order_number')
            ->get();

        foreach ($shopifyUnmappedItems as $item) {
            $lineTotal = ($item->price * $item->quantity) - $item->total_discount;
            $unlinkedRevenue[] = [
                'source' => 'Shopify #' . $item->order_number,
                'description' => $item->title,
                'qty' => (float) $item->quantity,
                'amount' => round($converter->convert($netOfRetailVat($lineTotal), $item->currency, $displayCurrency, $item->ordered_at), 2),
            ];
        }

        // Shopify shipping & other (order total - sum of items)
        $shopifyOrders = \App\Models\ShopifyOrder::where('user_id', $user->id)
            ->whereBetween('ordered_at', [$fromDate, $toDate])
            ->with('items')
            ->get();

        $shopifyShippingOther = 0;
        foreach ($shopifyOrders as $order) {
            $itemsTotal = $order->items->sum(fn ($item) => ($item->price * $item->quantity) - $item->total_discount);
            $shopifyShippingOther += $converter->convert($netOfRetailVat((float) $order->total_price - $itemsTotal), $order->currency, $displayCurrency, $order->ordered_at);
        }

        if (abs($shopifyShippingOther) > 0.01) {
            $unlinkedRevenue[] = [
                'source' => 'Shopify',
                'description' => __('profitability.shopify_shipping_other'),
                'qty' => null,
                'amount' => round($shopifyShippingOther, 2),
            ];
        }

        // Assemble per-article data
        $articleData = [];
        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($articles as $article) {
            $bal = $balances[$article->id] ?? null;
            $avgCost = $bal && $bal['qty'] > 0 ? $bal['value'] / $bal['qty'] : null;
            $revenue = $toDisplay($revenueByArticle[$article->id] ?? 0);
            $cost = $toDisplay($costByArticle[$article->id] ?? 0);
            $qtySold = $qtySoldByArticle[$article->id] ?? 0;
            $qtyPurchased = $qtyPurchasedByArticle[$article->id] ?? 0;

            if (!$avgCost && !$revenue && !$cost) {
                continue;
            }

            $sellingPriceConverted = $toDisplay((float) $article->price);
            $avgCostConverted = $avgCost ? $toDisplay($avgCost) : null;

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
                'avg_cost' => $avgCostConverted ? round($avgCostConverted, 4) : null,
                'theoretical_margin' => $theoreticalMargin,
                'qty_sold' => round($qtySold, 2),
                'revenue' => round($revenue, 2),
                'qty_purchased' => round($qtyPurchased, 2),
                'cost' => round($cost, 2),
                'profit' => round($actualProfit, 2),
                'actual_margin' => $actualMargin,
            ];
        }

        $totalUnlinked = collect($unlinkedRevenue)->sum('amount');
        $totalRevenue += $totalUnlinked;

        $totalProfit = $totalRevenue - $totalCost;
        $overallMargin = $totalRevenue > 0
            ? round(($totalProfit / $totalRevenue) * 100, 1)
            : null;

        return Inertia::render('Profitability/Index', [
            'articles' => $articleData,
            'unlinkedRevenue' => $unlinkedRevenue,
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
