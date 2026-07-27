<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\GoodsReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: ['create', 'store', 'edit', 'update']),
        ];
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->goodsReceipts();

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $totalFiltered = (clone $query)->sum('total_cost');

        $receipts = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventory/GoodsReceipts/Index', [
            'receipts' => $receipts,
            'totalCost' => round((float) $totalFiltered, 2),
            'filters' => [
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $articles = $request->user()->articles()
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'stock_quantity']);

        return Inertia::render('Inventory/GoodsReceipts/Create', [
            'articles' => $articles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        // Generate receipt number
        $lastReceipt = $user->goodsReceipts()->orderByDesc('id')->first();
        $nextSeq = $lastReceipt ? ((int) str_replace('PR-', '', $lastReceipt->receipt_number)) + 1 : 1;
        $receiptNumber = 'PR-' . $nextSeq;

        $totalCost = 0;

        $receipt = $user->goodsReceipts()->create([
            'receipt_number' => $receiptNumber,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'total_cost' => 0,
        ]);

        foreach ($validated['items'] as $item) {
            $article = Article::where('user_id', $user->id)->findOrFail($item['article_id']);

            $taxRate = $item['tax_rate'] ?? 0;
            $subtotal = $item['quantity'] * $item['cost_price'];
            $lineCost = $subtotal + $subtotal * ($taxRate / 100);
            $totalCost += $lineCost;

            $before = $article->stock_quantity;
            $article->stock_quantity += $item['quantity'];
            $article->save();

            $article->stockMovements()->create([
                'user_id' => $user->id,
                'type' => 'receipt',
                'quantity' => $item['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $article->stock_quantity,
                'cost_price' => $item['cost_price'],
                'tax_rate' => $taxRate,
                'reference_type' => 'goods_receipt',
                'reference_id' => $receipt->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        $receipt->update(['total_cost' => $totalCost]);

        return redirect()->route('goods-receipts.index')
            ->with('success', __('toast.goods_receipt_created'));
    }

    public function edit(Request $request, GoodsReceipt $goodsReceipt): Response
    {
        if ($goodsReceipt->user_id !== auth()->id()) {
            abort(403);
        }

        $articles = $request->user()->articles()
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'stock_quantity']);

        $movements = $goodsReceipt->movements()->with('article:id,name,unit')->get();

        return Inertia::render('Inventory/GoodsReceipts/Edit', [
            'receipt' => $goodsReceipt,
            'articles' => $articles,
            'movements' => $movements,
        ]);
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if ($goodsReceipt->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        // Reverse old movements
        $oldMovements = $goodsReceipt->movements;
        foreach ($oldMovements as $movement) {
            $article = Article::where('user_id', $user->id)->find($movement->article_id);
            if ($article) {
                $article->stock_quantity -= $movement->quantity;
                $article->save();
            }
        }
        $goodsReceipt->movements()->delete();

        // Create new movements
        $totalCost = 0;
        foreach ($validated['items'] as $item) {
            $article = Article::where('user_id', $user->id)->findOrFail($item['article_id']);

            $taxRate = $item['tax_rate'] ?? 0;
            $subtotal = $item['quantity'] * $item['cost_price'];
            $lineCost = $subtotal + $subtotal * ($taxRate / 100);
            $totalCost += $lineCost;

            $before = $article->stock_quantity;
            $article->stock_quantity += $item['quantity'];
            $article->save();

            $article->stockMovements()->create([
                'user_id' => $user->id,
                'type' => 'receipt',
                'quantity' => $item['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $article->stock_quantity,
                'cost_price' => $item['cost_price'],
                'tax_rate' => $taxRate,
                'reference_type' => 'goods_receipt',
                'reference_id' => $goodsReceipt->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        $goodsReceipt->update([
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'total_cost' => $totalCost,
        ]);

        return redirect()->route('goods-receipts.show', $goodsReceipt)
            ->with('success', __('toast.goods_receipt_updated'));
    }

    public function show(GoodsReceipt $goodsReceipt): Response
    {
        if ($goodsReceipt->user_id !== auth()->id()) {
            abort(403);
        }

        $movements = $goodsReceipt->movements()->with('article:id,name,unit')->get();

        return Inertia::render('Inventory/GoodsReceipts/Show', [
            'receipt' => $goodsReceipt,
            'movements' => $movements,
        ]);
    }
}
