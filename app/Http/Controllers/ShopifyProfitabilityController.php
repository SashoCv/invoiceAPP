<?php

namespace App\Http\Controllers;

use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderItem;
use App\Services\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopifyProfitabilityController extends Controller
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

        // Orders in date range
        $orders = ShopifyOrder::where('user_id', $user->id)
            ->whereBetween('ordered_at', [$fromDate, $toDate])
            ->with('items')
            ->get();

        $totalRevenue = 0;
        $totalOrders = $orders->count();
        $totalItems = 0;

        // Per-article sales
        $articleSales = [];
        // Per-customer stats
        $customerStats = [];

        foreach ($orders as $order) {
            $orderTotal = $converter->convert((float) $order->total_price, $order->currency, $displayCurrency, $order->ordered_at);
            $totalRevenue += $orderTotal;

            // Customer stats
            $customerKey = $order->customer_email ?: ($order->customer_name ?: 'unknown');
            if (!isset($customerStats[$customerKey])) {
                $customerStats[$customerKey] = [
                    'name' => $order->customer_name ?: $order->customer_email ?: '-',
                    'email' => $order->customer_email,
                    'orders' => 0,
                    'total' => 0,
                    'items' => 0,
                ];
            }
            $customerStats[$customerKey]['orders']++;
            $customerStats[$customerKey]['total'] += $orderTotal;

            foreach ($order->items as $item) {
                $totalItems += $item->quantity;
                $customerStats[$customerKey]['items'] += $item->quantity;

                $lineTotal = ($item->price * $item->quantity) - $item->total_discount;
                $converted = $converter->convert($lineTotal, $order->currency, $displayCurrency, $order->ordered_at);

                $articleId = $item->article_id ?: 'unmapped_' . $item->shopify_variant_id;
                if (!isset($articleSales[$articleId])) {
                    $articleSales[$articleId] = [
                        'name' => $item->article ? $item->article->name : $item->title,
                        'mapped' => (bool) $item->article_id,
                        'qty_sold' => 0,
                        'revenue' => 0,
                    ];
                }
                $articleSales[$articleId]['qty_sold'] += $item->quantity;
                $articleSales[$articleId]['revenue'] += $converted;
            }
        }

        // Sort articles by revenue desc
        $articleData = collect($articleSales)->values()
            ->sortByDesc('revenue')
            ->values()
            ->map(fn ($a) => [
                ...$a,
                'qty_sold' => round($a['qty_sold'], 2),
                'revenue' => round($a['revenue'], 2),
            ])
            ->all();

        // Sort customers by total desc
        $customerData = collect($customerStats)->values()
            ->sortByDesc('total')
            ->values()
            ->map(fn ($c) => [
                ...$c,
                'total' => round($c['total'], 2),
            ])
            ->all();

        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        return Inertia::render('Shopify/Profitability', [
            'articles' => $articleData,
            'customers' => $customerData,
            'totalRevenue' => round($totalRevenue, 2),
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'avgOrderValue' => $avgOrderValue,
            'displayCurrency' => $displayCurrency,
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
        ]);
    }
}
