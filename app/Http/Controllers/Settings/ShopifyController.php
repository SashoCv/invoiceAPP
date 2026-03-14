<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessShopifyOrder;
use App\Models\ShopifyConnection;
use App\Models\ShopifyProductMapping;
use App\Services\ShopifyApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopifyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $connection = $user->shopifyConnection;

        // Only treat as connected if OAuth completed
        $activeConnection = $connection?->is_active ? $connection : null;

        $mappings = $activeConnection
            ? ShopifyProductMapping::where('user_id', $user->id)
                ->with('article:id,name,sku,unit')
                ->get()
            : [];

        $articles = $user->articles()->where('is_active', true)->get(['id', 'name', 'sku', 'unit']);

        return Inertia::render('Settings/Shopify', [
            'connection' => $activeConnection ? [
                'id' => $activeConnection->id,
                'shop_domain' => $activeConnection->shop_domain,
                'is_active' => $activeConnection->is_active,
                'last_synced_at' => $activeConnection->last_synced_at?->toISOString(),
            ] : null,
            'mappings' => $mappings,
            'articles' => $articles,
            'callbackUrl' => route('settings.shopify.callback'),
        ]);
    }

    public function connect(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'shop_domain' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        $user = $request->user();
        $shopDomain = rtrim($request->shop_domain, '/');

        // Remove protocol if provided
        $shopDomain = preg_replace('#^https?://#', '', $shopDomain);

        // Ensure .myshopify.com suffix
        if (!Str::endsWith($shopDomain, '.myshopify.com')) {
            $shopDomain .= '.myshopify.com';
        }

        // Save credentials (not yet active — waiting for OAuth callback)
        ShopifyConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'shop_domain' => $shopDomain,
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'is_active' => false,
            ]
        );

        // Redirect to Shopify OAuth
        $redirectUri = route('settings.shopify.callback');
        $scopes = 'read_orders,read_products';

        $url = "https://{$shopDomain}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $request->client_id,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
        ]);

        Log::info('Shopify OAuth redirect', [
            'shop' => $shopDomain,
            'redirect_uri' => $redirectUri,
            'oauth_url' => $url,
        ]);

        return Inertia::location($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        Log::info('Shopify OAuth callback received', [
            'query' => $request->query(),
        ]);

        $code = $request->query('code');
        $shop = $request->query('shop');

        if (!$code || !$shop) {
            Log::warning('Shopify callback missing code or shop', ['code' => $code, 'shop' => $shop]);
            return redirect()->route('settings.shopify')->with('error', __('shopify.connection_failed'));
        }

        $user = $request->user();
        $connection = ShopifyConnection::where('user_id', $user->id)->first();

        if (!$connection || $connection->shop_domain !== $shop) {
            Log::warning('Shopify callback shop mismatch', [
                'expected' => $connection?->shop_domain,
                'received' => $shop,
            ]);
            return redirect()->route('settings.shopify')->with('error', __('shopify.connection_failed'));
        }

        // Exchange code for access token
        try {
            Log::info('Shopify exchanging code for token', ['shop' => $shop]);

            $response = Http::post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => $connection->client_id,
                'client_secret' => $connection->client_secret,
                'code' => $code,
            ]);

            $response->throw();

            $accessToken = $response->json('access_token');

            Log::info('Shopify token received', ['has_token' => !empty($accessToken)]);

            $connection->update([
                'access_token' => $accessToken,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Shopify token exchange failed', [
                'error' => $e->getMessage(),
                'response' => isset($response) ? $response->body() : null,
            ]);
            $connection->delete();
            return redirect()->route('settings.shopify')->with('error', __('shopify.connection_failed'));
        }

        // Register webhooks
        try {
            $client = new ShopifyApiClient($connection->fresh());
            $baseUrl = config('app.url') . "/api/webhooks/shopify/{$user->id}";
            $client->registerWebhook('orders/paid', $baseUrl);
            $client->registerWebhook('refunds/create', $baseUrl);
        } catch (\Exception $e) {
            // Non-critical: webhooks may already exist
        }

        return redirect()->route('settings.shopify')->with('success', __('shopify.connected'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();
        $connection = $user->shopifyConnection;

        if ($connection) {
            // Try to remove webhooks from Shopify (only if we have a token)
            if ($connection->access_token) {
                try {
                    $client = new ShopifyApiClient($connection);
                    $webhooks = $client->getWebhooks();
                    foreach ($webhooks as $webhook) {
                        $client->deleteWebhook($webhook['id']);
                    }
                } catch (\Exception $e) {
                    // Non-critical
                }
            }

            // Delete local data
            ShopifyProductMapping::where('user_id', $user->id)->delete();
            $connection->delete();
        }

        return back()->with('success', __('shopify.disconnected'));
    }

    public function fetchProducts(Request $request): JsonResponse
    {
        $user = $request->user();
        $connection = $user->shopifyConnection;

        if (!$connection) {
            return response()->json(['error' => 'Not connected'], 422);
        }

        try {
            $client = new ShopifyApiClient($connection);
            $products = $client->getProducts(['limit' => 250]);

            return response()->json(['products' => $products]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveMapping(Request $request): RedirectResponse
    {
        $request->validate([
            'shopify_product_id' => ['required', 'integer'],
            'shopify_variant_id' => ['required', 'integer'],
            'shopify_product_title' => ['required', 'string'],
            'shopify_variant_title' => ['nullable', 'string'],
            'shopify_sku' => ['nullable', 'string'],
            'article_id' => ['required', 'exists:articles,id'],
        ]);

        $user = $request->user();

        ShopifyProductMapping::updateOrCreate(
            [
                'user_id' => $user->id,
                'shopify_variant_id' => $request->shopify_variant_id,
            ],
            [
                'shopify_product_id' => $request->shopify_product_id,
                'shopify_product_title' => $request->shopify_product_title,
                'shopify_variant_title' => $request->shopify_variant_title,
                'shopify_sku' => $request->shopify_sku,
                'article_id' => $request->article_id,
            ]
        );

        // Copy SKU to article if it doesn't have one
        if ($request->shopify_sku) {
            $article = $user->articles()->find($request->article_id);
            if ($article && !$article->sku) {
                $article->update(['sku' => $request->shopify_sku]);
            }
        }

        return back()->with('success', __('shopify.mapping_saved'));
    }

    public function deleteMapping(ShopifyProductMapping $mapping): RedirectResponse
    {
        if ($mapping->user_id !== auth()->id()) {
            abort(403);
        }

        $mapping->delete();

        return back()->with('success', __('shopify.mapping_deleted'));
    }

    public function autoMatch(Request $request): RedirectResponse
    {
        $user = $request->user();
        $connection = $user->shopifyConnection;

        if (!$connection) {
            return back()->with('error', __('shopify.not_connected'));
        }

        try {
            $client = new ShopifyApiClient($connection);
            $products = $client->getProducts(['limit' => 250]);

            $matched = 0;

            foreach ($products as $product) {
                foreach ($product['variants'] as $variant) {
                    $sku = $variant['sku'] ?? null;
                    if (!$sku) continue;

                    // Check if already mapped
                    $existing = ShopifyProductMapping::forVariant($user->id, $variant['id'])->exists();
                    if ($existing) continue;

                    // Find matching article by SKU
                    $article = $user->articles()->where('sku', $sku)->first();
                    if (!$article) continue;

                    ShopifyProductMapping::create([
                        'user_id' => $user->id,
                        'shopify_product_id' => $product['id'],
                        'shopify_variant_id' => $variant['id'],
                        'shopify_product_title' => $product['title'],
                        'shopify_variant_title' => $variant['title'] !== 'Default Title' ? $variant['title'] : null,
                        'shopify_sku' => $sku,
                        'article_id' => $article->id,
                    ]);

                    $matched++;
                }
            }

            return back()->with('success', __('shopify.auto_matched', ['count' => $matched]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function syncOrders(Request $request): RedirectResponse
    {
        $user = $request->user();
        $connection = $user->shopifyConnection;

        if (!$connection) {
            return back()->with('error', __('shopify.not_connected'));
        }

        try {
            $client = new ShopifyApiClient($connection);
            $orders = $client->getOrders([
                'status' => 'any',
                'financial_status' => 'paid',
                'created_at_min' => now()->subDays(30)->toIso8601String(),
                'limit' => 250,
            ]);

            foreach ($orders as $order) {
                ProcessShopifyOrder::dispatch($user->id, $order);
            }

            $connection->update(['last_synced_at' => now()]);

            return back()->with('success', __('shopify.sync_started', ['count' => count($orders)]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
