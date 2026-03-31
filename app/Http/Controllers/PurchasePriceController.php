<?php

namespace App\Http\Controllers;

use App\Models\Article;
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
                'Просечна продажна (фактури)',
                'Приход фактури',
                'Продадено кол. (Shopify)',
                'Просечна продажна (Shopify)',
                'Приход Shopify',
                'Вкупно продадено кол.',
                'Вкупен приход',
                'Маржа %',
            ]);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['sku'] ?? '',
                    $row['unit'],
                    $row['purchased_qty'],
                    number_format($row['avg_purchase_price'], 2, '.', ''),
                    number_format($row['total_purchase_cost'], 2, '.', ''),
                    $row['invoice_sold_qty'],
                    number_format($row['invoice_avg_price'], 2, '.', ''),
                    number_format($row['invoice_revenue'], 2, '.', ''),
                    $row['shopify_sold_qty'],
                    number_format($row['shopify_avg_price'], 2, '.', ''),
                    number_format($row['shopify_revenue'], 2, '.', ''),
                    $row['total_sold_qty'],
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

        // 2. Sales from invoices
        $invoiceSalesQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoice_items.article_id');

        if ($dateFrom) $invoiceSalesQuery->where('invoices.issue_date', '>=', $dateFrom);
        if ($dateTo) $invoiceSalesQuery->where('invoices.issue_date', '<=', $dateTo);

        $invoiceSales = $invoiceSalesQuery
            ->select(
                'invoice_items.article_id',
                DB::raw('SUM(invoice_items.quantity) as qty'),
                DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) / SUM(invoice_items.quantity) as avg_price'),
                DB::raw('SUM(invoice_items.total) as revenue'),
            )
            ->groupBy('invoice_items.article_id')
            ->get()
            ->keyBy('article_id');

        // 3. Sales from Shopify
        $shopifySalesQuery = DB::table('shopify_order_items')
            ->join('shopify_orders', 'shopify_order_items.shopify_order_id', '=', 'shopify_orders.id')
            ->where('shopify_orders.user_id', $userId)
            ->whereNotNull('shopify_order_items.article_id');

        if ($dateFrom) $shopifySalesQuery->where('shopify_orders.ordered_at', '>=', $dateFrom);
        if ($dateTo) $shopifySalesQuery->where('shopify_orders.ordered_at', '<=', $dateTo . ' 23:59:59');

        $shopifySales = $shopifySalesQuery
            ->select(
                'shopify_order_items.article_id',
                DB::raw('SUM(shopify_order_items.quantity) as qty'),
                DB::raw('SUM(shopify_order_items.quantity * shopify_order_items.price) / SUM(shopify_order_items.quantity) as avg_price'),
                DB::raw('SUM(shopify_order_items.quantity * shopify_order_items.price - shopify_order_items.total_discount) as revenue'),
            )
            ->groupBy('shopify_order_items.article_id')
            ->get()
            ->keyBy('article_id');

        // Collect all article IDs that have any activity
        $allArticleIds = $purchases->keys()
            ->merge($invoiceSales->keys())
            ->merge($shopifySales->keys())
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
            $inv = $invoiceSales->get($articleId);
            $shop = $shopifySales->get($articleId);

            $purchasedQty = $p ? round((float) $p->qty, 2) : 0;
            $avgPurchasePrice = $p ? round((float) $p->avg_price, 2) : 0;
            $totalPurchaseCost = $p ? round((float) $p->total_cost, 2) : 0;

            $invoiceSoldQty = $inv ? round((float) $inv->qty, 2) : 0;
            $invoiceAvgPrice = $inv ? round((float) $inv->avg_price, 2) : 0;
            $invoiceRevenue = $inv ? round((float) $inv->revenue, 2) : 0;

            $shopifySoldQty = $shop ? round((float) $shop->qty, 2) : 0;
            $shopifyAvgPrice = $shop ? round((float) $shop->avg_price, 2) : 0;
            $shopifyRevenue = $shop ? round((float) $shop->revenue, 2) : 0;

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
                'invoice_avg_price' => $invoiceAvgPrice,
                'invoice_revenue' => $invoiceRevenue,
                'shopify_sold_qty' => $shopifySoldQty,
                'shopify_avg_price' => $shopifyAvgPrice,
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
