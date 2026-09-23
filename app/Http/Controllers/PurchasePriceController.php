<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\StockValuationService;
use Illuminate\Http\Request;
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
                    number_format($row['avg_purchase_price'], 4, '.', ''),
                    number_format($row['total_purchase_cost'], 2, '.', ''),
                    $row['invoice_sold_qty'],
                    number_format($row['invoice_revenue'], 2, '.', ''),
                    $row['shopify_sold_qty'],
                    number_format($row['shopify_revenue'], 2, '.', ''),
                    $row['total_sold_qty'],
                    number_format($avgSelling, 4, '.', ''),
                    number_format($row['total_revenue'], 2, '.', ''),
                    number_format($row['margin_percent'], 1, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Per article: purchases (приемници) and sales (фактури, е-трговија) in the period,
     * from StockValuationService so quantities, набавна and продажна match the accounting
     * reports. Revenue is со ДДВ; margin % is РУЦ on sales без ДДВ vs the набавна
     * вредност of the goods sold (moving weighted average).
     */
    private function getData(Request $request): array
    {
        $userId = $request->user()->id;
        $dateFrom = $request->input('date_from') ?: '0000-01-01';
        $dateTo = $request->input('date_to') ?: '9999-12-31';

        $map = [];
        foreach (app(StockValuationService::class)->eventsBetween($userId, $dateFrom, $dateTo) as $e) {
            if (!in_array($e['doc_type'], ['receipt', 'invoice', 'shopify'], true)) {
                continue;
            }
            $m = &$map[$e['article_id']];
            $m ??= [
                'purchased_qty' => 0, 'purchase_cost' => 0, 'purchase_cost_tax' => 0,
                'invoice_qty' => 0, 'invoice_revenue' => 0, 'shopify_qty' => 0, 'shopify_revenue' => 0,
                'sold_cost' => 0, 'sales_no_tax' => 0,
            ];

            if ($e['doc_type'] === 'receipt') {
                $m['purchased_qty'] += $e['qty'];
                $m['purchase_cost'] += $e['cost_value'];
                $m['purchase_cost_tax'] += round($e['cost_value'] * $e['tax_rate'] / 100, 2);
            } else {
                $prefix = $e['doc_type'];
                $m[$prefix . '_qty'] += $e['qty'];
                $m[$prefix . '_revenue'] += $e['sales_no_tax'] + $e['sales_tax'];
                $m['sold_cost'] += $e['cost_value'];
                $m['sales_no_tax'] += $e['sales_no_tax'];
            }
            unset($m);
        }

        if (empty($map)) {
            return [];
        }

        $articles = Article::withTrashed()->whereIn('id', array_keys($map))
            ->where('user_id', $userId)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($map as $articleId => $m) {
            $article = $articles->get($articleId);
            if (!$article) continue;

            $totalSoldQty = round($m['invoice_qty'] + $m['shopify_qty'], 2);
            $totalRevenue = round($m['invoice_revenue'] + $m['shopify_revenue'], 2);
            $margin = $m['sales_no_tax'] > 0
                ? round(($m['sales_no_tax'] - $m['sold_cost']) / $m['sales_no_tax'] * 100, 1)
                : 0;

            $result[] = [
                'id' => $article->id,
                'name' => $article->name,
                'sku' => $article->sku,
                'unit' => $article->unit,
                'purchased_qty' => round($m['purchased_qty'], 2),
                'avg_purchase_price' => $m['purchased_qty'] > 0 ? round($m['purchase_cost'] / $m['purchased_qty'], 4) : 0,
                'total_purchase_cost' => round($m['purchase_cost'] + $m['purchase_cost_tax'], 2),
                'invoice_sold_qty' => round($m['invoice_qty'], 2),
                'invoice_revenue' => round($m['invoice_revenue'], 2),
                'shopify_sold_qty' => round($m['shopify_qty'], 2),
                'shopify_revenue' => round($m['shopify_revenue'], 2),
                'total_sold_qty' => $totalSoldQty,
                'total_revenue' => $totalRevenue,
                'margin_percent' => $margin,
            ];
        }

        usort($result, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $result;
    }
}
