<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Overall stock stats
        $trackedArticles = $user->articles()->where('track_inventory', true);

        $totalItems = (clone $trackedArticles)->count();
        $totalStockValue = (float) (clone $trackedArticles)->selectRaw('SUM(stock_quantity * price) as value')->value('value') ?? 0;
        $lowStockCount = (int) (clone $trackedArticles)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
        $outOfStockCount = (int) (clone $trackedArticles)
            ->where('stock_quantity', '<=', 0)
            ->count();

        // Stock status distribution
        $inStockCount = $totalItems - $lowStockCount - $outOfStockCount;
        $stockDistribution = [
            'in_stock' => $inStockCount,
            'low_stock' => $lowStockCount,
            'out_of_stock' => $outOfStockCount,
        ];

        // Top items by stock value
        $topItemsByValue = $user->articles()
            ->where('track_inventory', true)
            ->where('stock_quantity', '>', 0)
            ->selectRaw('id, name, unit, stock_quantity, price, (stock_quantity * price) as stock_value')
            ->orderByDesc('stock_value')
            ->limit(10)
            ->get();

        // Low stock items (need attention)
        $lowStockItems = $user->articles()
            ->where('track_inventory', true)
            ->where(function ($q) {
                $q->where('stock_quantity', '<=', 0)
                    ->orWhereColumn('stock_quantity', '<=', 'low_stock_threshold');
            })
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        // Recent movements (paginated)
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $recentMovements = $user->stockMovements()
            ->with('article:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'movements_page')
            ->withQueryString();

        // Attach invoice numbers for invoice deductions
        $invoiceIds = $recentMovements->getCollection()
            ->where('reference_type', 'invoice')
            ->pluck('reference_id')
            ->filter()
            ->unique();

        if ($invoiceIds->isNotEmpty()) {
            $invoices = \App\Models\Invoice::whereIn('id', $invoiceIds)
                ->pluck('invoice_number', 'id');

            $recentMovements->getCollection()->each(function ($movement) use ($invoices) {
                if ($movement->reference_type === 'invoice' && isset($invoices[$movement->reference_id])) {
                    $movement->invoice_number = $invoices[$movement->reference_id];
                    $movement->invoice_id = $movement->reference_id;
                }
            });
        }

        // Monthly movement summary (last 6 months)
        $monthlyMovements = [];
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $receipts = (float) $user->stockMovements()
                ->where('type', 'receipt')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('quantity');

            $issues = (float) $user->stockMovements()
                ->whereIn('type', ['issue', 'invoice_deduction'])
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum(DB::raw('ABS(quantity)'));

            $monthlyMovements[] = [
                'month' => $month->translatedFormat('M Y'),
                'receipts' => round($receipts, 2),
                'issues' => round($issues, 2),
            ];
        }

        // Total goods receipts value
        $totalReceiptsValue = (float) ($user->goodsReceipts()->sum('total_cost') ?? 0);

        return Inertia::render('Inventory/Dashboard', [
            'totalItems' => $totalItems,
            'totalStockValue' => round($totalStockValue, 2),
            'totalReceiptsValue' => round($totalReceiptsValue, 2),
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'stockDistribution' => $stockDistribution,
            'topItemsByValue' => $topItemsByValue,
            'lowStockItems' => $lowStockItems,
            'recentMovements' => $recentMovements,
            'monthlyMovements' => $monthlyMovements,
        ]);
    }
}
