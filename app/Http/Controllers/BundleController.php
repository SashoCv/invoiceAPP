<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class BundleController extends Controller implements HasMiddleware
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

    public function create(Request $request): Response
    {
        $articles = $request->user()->articles()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Inventory/Bundles/Create', [
            'articles' => $articles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bundle = $request->user()->bundles()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'tax_rate' => $validated['tax_rate'],
        ]);

        foreach ($validated['items'] as $item) {
            $bundle->bundleItems()->create([
                'article_id' => $item['article_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return redirect()->route('inventory.index')
            ->with('success', __('toast.bundle_created'));
    }

    public function edit(Bundle $bundle, Request $request): Response
    {
        $this->authorize('update', $bundle);

        $bundle->load('bundleItems.article');

        $articles = $request->user()->articles()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Inventory/Bundles/Edit', [
            'bundle' => $bundle,
            'articles' => $articles,
        ]);
    }

    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        $this->authorize('update', $bundle);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['required', 'exists:articles,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bundle->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'tax_rate' => $validated['tax_rate'],
            'is_active' => $validated['is_active'],
        ]);

        $bundle->bundleItems()->delete();
        foreach ($validated['items'] as $item) {
            $bundle->bundleItems()->create([
                'article_id' => $item['article_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return redirect()->route('inventory.index')
            ->with('success', __('toast.bundle_updated'));
    }

    public function destroy(Bundle $bundle): RedirectResponse
    {
        $this->authorize('delete', $bundle);

        $bundle->bundleItems()->delete();
        $bundle->delete();

        return redirect()->route('inventory.index')
            ->with('success', __('toast.bundle_deleted'));
    }
}
