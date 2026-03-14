<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessShopifyOrder;
use App\Jobs\ProcessShopifyRefund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopifyWebhookController extends Controller
{
    public function handle(Request $request, int $userId): JsonResponse
    {
        $topic = $request->header('X-Shopify-Topic');
        $data = $request->all();

        match ($topic) {
            'orders/paid' => ProcessShopifyOrder::dispatch($userId, $data),
            'refunds/create' => ProcessShopifyRefund::dispatch($userId, $data),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }
}
