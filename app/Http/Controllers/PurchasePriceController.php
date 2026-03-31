<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bundle;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchasePriceController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $this->getData($request);

        return Inertia::render('Inventory/PurchasePrices', [
            'articles' => $data,
            'filters' => [
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->getData($request);

        $filename = 'nabavki-prodazhbi';
        if ($request->filled('date_from')) {
            $filename .= '-od-' . $request->input('date_from');
        }
        if ($request->filled('date_to')) {
            $filename .= '-do-' . $request->input('date_to');
        }
        $filename .= '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Артикл',
                'SKU',
                'Ед. мерка',
                'Набавено кол.',
                'Просечна набавна цена',
                'Набавна вредност (со ДДВ)',
                'Продадено кол. (фактури)',
                'Приход фактури',
                'Продадено кол. (Shopify)',
                'Приход Shopify',
                'Вкупно продадено кол.',
                'Просечна продажна цена',
                'Вкупен приход',
                'Маржа %',
            ]);

            foreach ($data as $row) {
                $avgSelling = $row['total_sold_qty'] > 0 ? $row['total_revenue'] / $row['total_sold_qty'] : 0;
                fputcsv($handle, [
                    $row['name'],
                    $row['sku'] ?? '',
                    $row['unit'],
                    $row['purchased_qty'],
                    number_format($row['avg_purchase_price'], 2, '.', ''),
                    number_format($row['total_purchase_cost'], 2, '.', ''),
                    $row['invoice_sold_qty'],
                    number_format($row['invoice_revenue'], 2, '.', ''),
                    $row['shopify_sold_qty'],
                    number_format($row['shopify_revenue'], 2, '.', ''),
                    $row['total_sold_qty'],
                    number_format($avgSelling, 2, '.', ''),
                    number_format($row['total_revenue'], 2, '.', ''),
                    number_format($row['margin_percent'], 1, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getData(Request $request): array
    {
        $userId = $request->user()->id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 1. Purchases from goods receipts
        $purchaseQuery = StockMovement::query()
            ->where('stock_movements.user_id', $userId)
            ->where('stock_movements.type', 'receipt')
            ->whereNotNull('stock_movements.cost_price')
            ->where('stock_movements.cost_price', '>', 0);

        if ($dateFrom || $dateTo) {
            $purchaseQuery->join('goods_receipts', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'goods_receipts.id')
                    ->where('stock_movements.reference_type', '=', 'goods_receipt');
            });
            if ($dateFrom) $purchaseQuery->where('goods_receipts.date', '>=', $dateFrom);
            if ($dateTo) $purchaseQuery->where('goods_receipts.date', '<=', $dateTo);
        }

        $purchases = $purchaseQuery
            ->select(
                'stock_movements.article_id',
                DB::raw('SUM(stock_movements.quantity) as qty'),
                DB::raw('SUM(stock_movements.cost_price * stock_movements.quantity) / SUM(stock_movements.quantity) as avg_price'),
                DB::raw('SUM(stock_movements.cost_price * stock_movements.quantity * (1 + COALESCE(stock_movements.tax_rate, 0) / 100)) as total_cost'),
            )
            ->groupBy('stock_movements.article_id')
            ->get()
            ->keyBy('article_id');

        // Load bundles with their components for distributing bundle sales
        $bundles = Bundle::where('user_id', $userId)
            ->with('bundleItems.article')
            ->get()
            ->keyBy('id');

        // Helper: distribute bundle sale to component articles
        // Returns array of [article_id => ['qty' => x, 'revenue' => y]]
        $distributeBundleSale = function (int $bundleId, float $bundleQty, float $lineRevenue) use ($bundles): array {
            $bundle = $bundles->get($bundleId);
            if (!$bundle || $bundle->bundleItems->isEmpty()) return [];

            // Calculate total component value for proportional revenue distribution
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

        // 2. Sales from invoices - direct articles
        $invoiceDirectQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoice_items.article_id')
            ->whereNull('invoice_items.bundle_id');

        if ($dateFrom) $invoiceDirectQuery->where('invoices.issue_date', '>=', $dateFrom);
        if ($dateTo) $invoiceDirectQuery->where('invoices.issue_date', '<=', $dateTo);

        $invoiceDirectSales = $invoiceDirectQuery
            ->select(
                'invoice_items.article_id',
                DB::raw('SUM(invoice_items.quantity) as qty'),
                DB::raw('SUM(invoice_items.total) as revenue'),
            )
            ->groupBy('invoice_items.article_id')
            ->get();

        // Accumulate invoice sales per article
        $invoiceSalesMap = []; // article_id => ['qty' => x, 'revenue' => y]
        foreach ($invoiceDirectSales as $row) {
            $id = $row->article_id;
            $invoiceSalesMap[$id] = [
                'qty' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ];
        }

        // 2b. Invoice bundle sales
        $invoiceBundleQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoice_items.bundle_id');

        if ($dateFrom) $invoiceBundleQuery->where('invoices.issue_date', '>=', $dateFrom);
        if ($dateTo) $invoiceBundleQuery->where('invoices.issue_date', '<=', $dateTo);

        $invoiceBundleSales = $invoiceBundleQuery
            ->select('invoice_items.bundle_id', 'invoice_items.quantity', 'invoice_items.total')
            ->get();

        foreach ($invoiceBundleSales as $row) {
            $distributed = $distributeBundleSale($row->bundle_id, (float) $row->quantity, (float) $row->total);
            foreach ($distributed as $articleId => $data) {
                if (!isset($invoiceSalesMap[$articleId])) {
                    $invoiceSalesMap[$articleId] = ['qty' => 0, 'revenue' => 0];
                }
                $invoiceSalesMap[$articleId]['qty'] += $data['qty'];
                $invoiceSalesMap[$articleId]['revenue'] += $data['revenue'];
            }
        }

        // 3. Sales from Shopify - direct articles
        $shopifyDirectQuery = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $userId)
            ->whereNotNull('shopify_order_items.article_id')
            ->whereNull('shopify_order_items.bundle_id');

        if ($dateFrom) $shopifyDirectQuery->where('shopify_orders.ordered_at', '>=', $dateFrom);
        if ($dateTo) $shopifyDirectQuery->where('shopify_orders.ordered_at', '<=', $dateTo . ' 23:59:59');

        $shopifyDirectSales = $shopifyDirectQuery
            ->select(
                'shopify_order_items.article_id',
                DB::raw('SUM(shopify_order_items.quantity) as qty'),
                DB::raw('SUM(shopify_order_items.quantity * shopify_order_items.price - shopify_order_items.total_discount) as revenue'),
            )
            ->groupBy('shopify_order_items.article_id')
            ->get();

        $shopifySalesMap = []; // article_id => ['qty' => x, 'revenue' => y]
        foreach ($shopifyDirectSales as $row) {
            $id = $row->article_id;
            $shopifySalesMap[$id] = [
                'qty' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ];
        }

        // 3b. Shopify bundle sales
        $shopifyBundleQuery = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $userId)
            ->whereNotNull('shopify_order_items.bundle_id');

        if ($dateFrom) $shopifyBundleQuery->where('shopify_orders.ordered_at', '>=', $dateFrom);
        if ($dateTo) $shopifyBundleQuery->where('shopify_orders.ordered_at', '<=', $dateTo . ' 23:59:59');

        $shopifyBundleSales = $shopifyBundleQuery
            ->select('shopify_order_items.bundle_id', 'shopify_order_items.quantity', 'shopify_order_items.price', 'shopify_order_items.total_discount')
            ->get();

        foreach ($shopifyBundleSales as $row) {
            $lineRevenue = (float) $row->quantity * (float) $row->price - (float) $row->total_discount;
            $distributed = $distributeBundleSale($row->bundle_id, (float) $row->quantity, $lineRevenue);
            foreach ($distributed as $articleId => $data) {
                if (!isset($shopifySalesMap[$articleId])) {
                    $shopifySalesMap[$articleId] = ['qty' => 0, 'revenue' => 0];
                }
                $shopifySalesMap[$articleId]['qty'] += $data['qty'];
                $shopifySalesMap[$articleId]['revenue'] += $data['revenue'];
            }
        }

        // Collect all article IDs that have any activity
        $allArticleIds = $purchases->keys()
            ->merge(collect(array_keys($invoiceSalesMap)))
            ->merge(collect(array_keys($shopifySalesMap)))
            ->unique()
            ->values()
            ->toArray();

        if (empty($allArticleIds)) {
            return [];
        }

        $articles = Article::whereIn('id', $allArticleIds)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($allArticleIds as $articleId) {
            $article = $articles->get($articleId);
            if (!$article) continue;

            $p = $purchases->get($articleId);

            $purchasedQty = $p ? round((float) $p->qty, 2) : 0;
            $avgPurchasePrice = $p ? round((float) $p->avg_price, 2) : 0;
            $totalPurchaseCost = $p ? round((float) $p->total_cost, 2) : 0;

            $inv = $invoiceSalesMap[$articleId] ?? null;
            $invoiceSoldQty = $inv ? round($inv['qty'], 2) : 0;
            $invoiceRevenue = $inv ? round($inv['revenue'], 2) : 0;

            $shop = $shopifySalesMap[$articleId] ?? null;
            $shopifySoldQty = $shop ? round($shop['qty'], 2) : 0;
            $shopifyRevenue = $shop ? round($shop['revenue'], 2) : 0;

            $totalSoldQty = $invoiceSoldQty + $shopifySoldQty;
            $totalRevenue = $invoiceRevenue + $shopifyRevenue;

            // Margin: (avg selling price - avg purchase price) / avg selling price * 100
            $avgSellingPrice = $totalSoldQty > 0 ? $totalRevenue / $totalSoldQty : 0;
            $margin = $avgSellingPrice > 0 && $avgPurchasePrice > 0
                ? round(($avgSellingPrice - $avgPurchasePrice) / $avgSellingPrice * 100, 1)
                : 0;

            $result[] = [
                'id' => $article->id,
                'name' => $article->name,
                'sku' => $article->sku,
                'unit' => $article->unit,
                'purchased_qty' => $purchasedQty,
                'avg_purchase_price' => $avgPurchasePrice,
                'total_purchase_cost' => $totalPurchaseCost,
                'invoice_sold_qty' => $invoiceSoldQty,
                'invoice_revenue' => $invoiceRevenue,
                'shopify_sold_qty' => $shopifySoldQty,
                'shopify_revenue' => $shopifyRevenue,
                'total_sold_qty' => $totalSoldQty,
                'total_revenue' => $totalRevenue,
                'margin_percent' => $margin,
            ];
        }

        usort($result, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $result;
    }
}
