<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: [
                'create', 'store', 'edit', 'update', 'destroy',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->articles();

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by status (active/inactive)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('dir', 'asc');
        $allowedSorts = ['name', 'price', 'tax_rate', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $articles = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'filters' => $request->only(['search', 'status', 'per_page', 'sort', 'dir']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Articles/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $request->user()->articles()->create($validated);

        return redirect()->route('articles.index')->with('success', __('toast.article_created'));
    }

    public function edit(Article $article): Response
    {
        $this->authorize('update', $article);

        return Inertia::render('Articles/Edit', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $article->update($validated);

        return redirect()->route('articles.index')->with('success', __('toast.article_updated'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        return redirect()->route('articles.index')->with('success', __('toast.article_deleted'));
    }
}
