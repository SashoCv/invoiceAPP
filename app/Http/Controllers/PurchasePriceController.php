<?php

namespace App\Http\Controllers;

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
        $data = $this->getPurchasePriceData($request);

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
        $data = $this->getPurchasePriceData($request);

        $filename = 'nabavni-ceni';
        if ($request->filled('date_from')) {
            $filename .= '-od-' . $request->input('date_from');
        }
        if ($request->filled('date_to')) {
            $filename .= '-do-' . $request->input('date_to');
        }
        $filename .= '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Артикл',
                'SKU',
                'Ед. мерка',
                'Примено кол.',
                'Просечна набавна цена',
                'Последна набавна цена',
                'Продажна цена',
                'Маржа %',
            ]);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['sku'] ?? '',
                    $row['unit'],
                    $row['total_quantity'],
                    number_format($row['avg_cost_price'], 2, '.', ''),
                    number_format($row['last_cost_price'], 2, '.', ''),
                    number_format($row['selling_price'], 2, '.', ''),
                    number_format($row['margin_percent'], 1, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getPurchasePriceData(Request $request): array
    {
        $userId = $request->user()->id;

        $query = StockMovement::query()
            ->where('stock_movements.user_id', $userId)
            ->where('stock_movements.type', 'receipt')
            ->whereNotNull('stock_movements.cost_price')
            ->where('stock_movements.cost_price', '>', 0);

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->join('goods_receipts', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'goods_receipts.id')
                    ->where('stock_movements.reference_type', '=', 'goods_receipt');
            });

            if ($request->filled('date_from')) {
                $query->where('goods_receipts.date', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->where('goods_receipts.date', '<=', $request->input('date_to'));
            }
        }

        $aggregated = $query
            ->select(
                'stock_movements.article_id',
                DB::raw('SUM(stock_movements.quantity) as total_quantity'),
                DB::raw('SUM(stock_movements.cost_price * stock_movements.quantity) / SUM(stock_movements.quantity) as avg_cost_price'),
            )
            ->groupBy('stock_movements.article_id')
            ->get()
            ->keyBy('article_id');

        if ($aggregated->isEmpty()) {
            return [];
        }

        // Get last cost price per article
        $articleIds = $aggregated->keys()->toArray();

        $lastPricesQuery = StockMovement::query()
            ->where('user_id', $userId)
            ->where('type', 'receipt')
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0)
            ->whereIn('article_id', $articleIds);

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $lastPricesQuery->join('goods_receipts as gr', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'gr.id')
                    ->where('stock_movements.reference_type', '=', 'goods_receipt');
            });
            if ($request->filled('date_from')) {
                $lastPricesQuery->where('gr.date', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $lastPricesQuery->where('gr.date', '<=', $request->input('date_to'));
            }
        }

        $lastPrices = $lastPricesQuery
            ->select('stock_movements.article_id', 'stock_movements.cost_price')
            ->orderByDesc('stock_movements.created_at')
            ->get()
            ->unique('article_id')
            ->keyBy('article_id');

        // Get article details
        $articles = \App\Models\Article::whereIn('id', $articleIds)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($aggregated as $articleId => $agg) {
            $article = $articles->get($articleId);
            if (!$article) continue;

            $avgCost = round((float) $agg->avg_cost_price, 2);
            $lastCost = round((float) ($lastPrices->get($articleId)?->cost_price ?? $avgCost), 2);
            $sellingPrice = (float) $article->price;
            $margin = $sellingPrice > 0
                ? round(($sellingPrice - $avgCost) / $sellingPrice * 100, 1)
                : 0;

            $result[] = [
                'id' => $article->id,
                'name' => $article->name,
                'sku' => $article->sku,
                'unit' => $article->unit,
                'total_quantity' => round((float) $agg->total_quantity, 2),
                'avg_cost_price' => $avgCost,
                'last_cost_price' => $lastCost,
                'selling_price' => $sellingPrice,
                'margin_percent' => $margin,
            ];
        }

        // Sort by name
        usort($result, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $result;
    }
}
