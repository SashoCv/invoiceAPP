<?php

namespace App\Http\Middleware;

use App\Models\ShopifyConnection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->route('userId');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        if (!$hmacHeader || !$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $connection = ShopifyConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rawBody = $request->getContent();
        $secret = $connection->client_secret ?: $connection->webhook_secret;
        $calculatedHmac = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        if (!hash_equals($calculatedHmac, $hmacHeader)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
