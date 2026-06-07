<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\StockMovement;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryItemController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: [
                'store', 'update', 'destroy', 'adjustStock', 'enableTracking',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        // Articles with inventory tracking
        $itemsQuery = $this->filteredItemsQuery($request);

        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('dir', 'asc');
        $allowedSorts = ['name', 'price', 'stock_quantity', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $itemsQuery->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $items = $itemsQuery->paginate($perPage)->withQueryString();

        // Historical stock view: override displayed quantity as of a chosen date
        $asOfDate = $this->parseAsOfDate($request);
        if ($asOfDate) {
            $map = $this->stockAsOfMap($request, $asOfDate->copy()->endOfDay());
            $items->getCollection()->transform(function (Article $item) use ($map) {
                $item->stock_quantity = (float) ($map[$item->id] ?? 0);
                return $item;
            });
        }

        // Articles not yet tracked (for "add to warehouse" dropdown)
        $untrackedArticles = $request->user()->articles()
            ->where('track_inventory', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Bundles
        $bundlesQuery = $request->user()->bundles()->with('bundleItems.article');
        $bundles = $bundlesQuery->orderBy('name')->get();

        // Stock movements
        $movementsQuery = $request->user()->stockMovements()->with('article');

        if ($request->filled('movement_type')) {
            $movementsQuery->where('type', $request->movement_type);
        }
        if ($request->filled('movement_from')) {
            try {
                $from = \Carbon\Carbon::createFromFormat('Y-m-d', $request->movement_from);
                $movementsQuery->whereDate('created_at', '>=', $from);
            } catch (\Exception $e) {}
        }
        if ($request->filled('movement_to')) {
            try {
                $to = \Carbon\Carbon::createFromFormat('Y-m-d', $request->movement_to);
                $movementsQuery->whereDate('created_at', '<=', $to);
            } catch (\Exception $e) {}
        }

        $movements = $movementsQuery->orderByDesc('created_at')->limit(100)->get();

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'untrackedArticles' => $untrackedArticles,
            'bundles' => $bundles,
            'movements' => $movements,
            'filters' => $request->only([
                'search', 'stock_status', 'per_page', 'sort', 'dir',
                'movement_type', 'movement_from', 'movement_to', 'as_of_date',
            ]),
        ]);
    }

    /**
     * Build the tracked-articles query with search and stock-status filters applied.
     */
    private function filteredItemsQuery(Request $request)
    {
        $itemsQuery = $request->user()->articles()->where('track_inventory', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('stock_status')) {
            $status = $request->stock_status;
            if ($status === 'in_stock') {
                $itemsQuery->whereColumn('stock_quantity', '>', 'low_stock_threshold');
            } elseif ($status === 'low_stock') {
                $itemsQuery->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            } elseif ($status === 'out_of_stock') {
                $itemsQuery->where('stock_quantity', '<=', 0);
            }
        }

        return $itemsQuery;
    }

    /**
     * Parse the as_of_date filter. Returns null for today/future or invalid input
     * (a "today" view uses the live stock_quantity column directly).
     */
    private function parseAsOfDate(Request $request): ?Carbon
    {
        if (!$request->filled('as_of_date')) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $request->as_of_date)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }

        return $date->lt(now()->startOfDay()) ? $date : null;
    }

    /**
     * Stock quantity per article as of a cutoff datetime, reconstructed from the
     * last stock movement on or before the cutoff. Returns [article_id => quantity].
     */
    private function stockAsOfMap(Request $request, Carbon $cutoff)
    {
        $latestIds = $request->user()->stockMovements()
            ->where('created_at', '<=', $cutoff)
            ->selectRaw('MAX(id) as id')
            ->groupBy('article_id')
            ->pluck('id');

        return StockMovement::whereIn('id', $latestIds)
            ->pluck('quantity_after', 'article_id');
    }

    /**
     * Export the inventory (warehouse) list to PDF, optionally as of a past date.
     */
    public function exportPdf(Request $request, PdfService $pdfService): BinaryFileResponse
    {
        $items = $this->filteredItemsQuery($request)->orderBy('name')->get();

        $asOfDate = $this->parseAsOfDate($request);
        if ($asOfDate) {
            $map = $this->stockAsOfMap($request, $asOfDate->copy()->endOfDay());
            $items->transform(function (Article $item) use ($map) {
                $item->stock_quantity = (float) ($map[$item->id] ?? 0);
                return $item;
            });
        }

        $reportDate = $asOfDate ?? now();

        $rows = $items->map(function (Article $item) {
            $value = (float) $item->stock_quantity * (float) $item->price;
            return [
                'code' => $item->code,
                'name' => $item->name,
                'unit' => $item->unit,
                'quantity' => (float) $item->stock_quantity,
                'price' => (float) $item->price,
                'value' => $value,
            ];
        });

        $data = [
            'agency' => $request->user()->agency,
            'reportDate' => $reportDate->format('d.m.Y'),
            'isHistorical' => $asOfDate !== null,
            'rows' => $rows,
            'totalValue' => $rows->sum('value'),
            'totalItems' => $rows->count(),
        ];

        $pdfPath = $pdfService->generateInventoryPdf($data);

        $filename = 'Lager_lista_' . $reportDate->format('Y-m-d') . '.pdf';

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function show(Article $inventory): Response
    {
        $this->authorize('view', $inventory);

        $movements = $inventory->stockMovements()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('Inventory/Show', [
            'item' => $inventory,
            'movements' => $movements,
        ]);
    }

    /**
     * Enable inventory tracking on an article and set initial stock.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'article_id' => ['required', 'exists:articles,id'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        $article = Article::where('user_id', $request->user()->id)
            ->findOrFail($validated['article_id']);

        $article->update([
            'track_inventory' => true,
            'stock_quantity' => $validated['stock_quantity'],
            'low_stock_threshold' => $validated['low_stock_threshold'],
        ]);

        // Create initial stock movement if there's stock
        if ($validated['stock_quantity'] > 0) {
            $article->stockMovements()->create([
                'user_id' => $request->user()->id,
                'type' => 'receipt',
                'quantity' => $validated['stock_quantity'],
                'quantity_before' => 0,
                'quantity_after' => $validated['stock_quantity'],
                'notes' => __('inventory.initial_stock'),
            ]);
        }

        return redirect()->route('inventory.index')
            ->with('success', __('toast.inventory_tracking_enabled'));
    }

    /**
     * Update stock settings for a tracked article.
     */
    public function update(Request $request, Article $inventory): RedirectResponse
    {
        $this->authorize('update', $inventory);

        $validated = $request->validate([
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        $inventory->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', __('toast.inventory_item_updated'));
    }

    /**
     * Disable inventory tracking on an article.
     */
    public function destroy(Article $inventory): RedirectResponse
    {
        $this->authorize('update', $inventory);

        $inventory->update([
            'track_inventory' => false,
            'stock_quantity' => 0,
        ]);

        return redirect()->route('inventory.index')
            ->with('success', __('toast.inventory_tracking_disabled'));
    }

    public function adjustStock(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validate([
            'type' => ['required', 'in:receipt,issue,adjustment'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['type'] === 'receipt') {
            $article->addStock($validated['quantity'], $validated['notes'], 'receipt');
        } elseif ($validated['type'] === 'issue') {
            $article->deductStock($validated['quantity'], null, null, $validated['notes']);
        } else {
            // Adjustment - set absolute quantity
            $before = $article->stock_quantity;
            $diff = $validated['quantity'] - $before;
            $article->stock_quantity = $validated['quantity'];
            $article->save();

            $article->stockMovements()->create([
                'user_id' => $request->user()->id,
                'type' => 'adjustment',
                'quantity' => $diff,
                'quantity_before' => $before,
                'quantity_after' => $validated['quantity'],
                'notes' => $validated['notes'],
            ]);
        }

        return back()->with('success', __('toast.stock_adjusted'));
    }
}
