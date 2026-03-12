<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\GoodsIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class GoodsIssueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: ['create', 'store', 'edit', 'update']),
        ];
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->goodsIssues();

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $receipts = $query
            ->with('client:id,name,company')
            ->withCount('movements')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventory/GoodsIssues/Index', [
            'issues' => $receipts,
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

        $clients = $request->user()->clients()
            ->orderBy('name')
            ->get(['id', 'name', 'company']);

        return Inertia::render('Inventory/GoodsIssues/Create', [
            'articles' => $articles,
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();

        $issue = $user->goodsIssues()->create([
            'issue_number' => GoodsIssue::generateIssueNumber($user->id),
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $article = Article::where('user_id', $user->id)->findOrFail($item['article_id']);

            $before = $article->stock_quantity;
            $article->stock_quantity -= $item['quantity'];
            $article->save();

            $article->stockMovements()->create([
                'user_id' => $user->id,
                'type' => 'issue',
                'quantity' => $item['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $article->stock_quantity,
                'cost_price' => 0,
                'reference_type' => 'goods_issue',
                'reference_id' => $issue->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return redirect()->route('goods-issues.index')
            ->with('success', __('toast.goods_issue_created'));
    }

    public function show(GoodsIssue $goodsIssue): Response
    {
        if ($goodsIssue->user_id !== auth()->id()) {
            abort(403);
        }

        $goodsIssue->load('client:id,name,company');
        $movements = $goodsIssue->movements()->with('article:id,name,unit')->get();

        return Inertia::render('Inventory/GoodsIssues/Show', [
            'issue' => $goodsIssue,
            'movements' => $movements,
        ]);
    }

    public function edit(Request $request, GoodsIssue $goodsIssue): Response
    {
        if ($goodsIssue->user_id !== auth()->id()) {
            abort(403);
        }

        $articles = $request->user()->articles()
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'stock_quantity']);

        $clients = $request->user()->clients()
            ->orderBy('name')
            ->get(['id', 'name', 'company']);

        $movements = $goodsIssue->movements()->with('article:id,name,unit')->get();

        return Inertia::render('Inventory/GoodsIssues/Edit', [
            'issue' => $goodsIssue,
            'articles' => $articles,
            'clients' => $clients,
            'movements' => $movements,
        ]);
    }

    public function update(Request $request, GoodsIssue $goodsIssue): RedirectResponse
    {
        if ($goodsIssue->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();

        // Reverse old movements (add stock back)
        $oldMovements = $goodsIssue->movements;
        foreach ($oldMovements as $movement) {
            $article = Article::where('user_id', $user->id)->find($movement->article_id);
            if ($article) {
                $article->stock_quantity += $movement->quantity;
                $article->save();
            }
        }
        $goodsIssue->movements()->delete();

        // Create new movements (deduct stock)
        foreach ($validated['items'] as $item) {
            $article = Article::where('user_id', $user->id)->findOrFail($item['article_id']);

            $before = $article->stock_quantity;
            $article->stock_quantity -= $item['quantity'];
            $article->save();

            $article->stockMovements()->create([
                'user_id' => $user->id,
                'type' => 'issue',
                'quantity' => $item['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $article->stock_quantity,
                'cost_price' => 0,
                'reference_type' => 'goods_issue',
                'reference_id' => $goodsIssue->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        $goodsIssue->update([
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
        ]);

        return redirect()->route('goods-issues.show', $goodsIssue)
            ->with('success', __('toast.goods_issue_updated'));
    }
}
