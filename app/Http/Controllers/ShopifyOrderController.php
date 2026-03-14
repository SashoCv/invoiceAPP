<?php

namespace App\Http\Controllers;

use App\Models\ShopifyOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopifyOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $user->shopifyOrders()->with('items');

        if ($request->filled('from')) {
            $query->where('ordered_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('ordered_at', '<=', $request->to . ' 23:59:59');
        }
        if ($request->filled('status')) {
            $query->where('financial_status', $request->status);
        }

        $orders = $query->orderByDesc('ordered_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Shopify/Orders', [
            'orders' => $orders,
            'filters' => $request->only(['from', 'to', 'status']),
        ]);
    }

    public function show(ShopifyOrder $shopifyOrder): Response
    {
        if ($shopifyOrder->user_id !== auth()->id()) {
            abort(403);
        }

        $shopifyOrder->load('items.article:id,name,sku,unit');

        return Inertia::render('Shopify/OrderShow', [
            'order' => $shopifyOrder,
        ]);
    }
}
