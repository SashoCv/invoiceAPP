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

        // Processed right away instead of on the queue: with no queue worker running the
        // jobs never ran and paid orders silently went missing. Processing is idempotent and
        // quick; on an exception Shopify gets an error response and retries the webhook.
        match ($topic) {
            'orders/paid' => ProcessShopifyOrder::dispatchSync($userId, $data),
            'refunds/create' => ProcessShopifyRefund::dispatchSync($userId, $data),
            default => null,
        };

        return response()->json(['status' => 'ok']);
    }
}
