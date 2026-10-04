<?php

namespace App\Services;

use App\Models\ShopifyConnection;
use Illuminate\Support\Facades\Http;

class ShopifyApiClient
{
    private const GRAPHQL_VERSION = '2025-01';

    private string $baseUrl;
    private string $graphqlUrl;
    private ?string $accessToken;

    public function __construct(ShopifyConnection $connection)
    {
        $this->baseUrl = "https://{$connection->shop_domain}/admin/api/2024-01";
        $this->graphqlUrl = "https://{$connection->shop_domain}/admin/api/" . self::GRAPHQL_VERSION . '/graphql.json';
        $this->accessToken = $connection->access_token;
    }

    /**
     * GraphQL Admin API query. Throws ShopifyGraphqlException when Shopify returns errors
     * (e.g. ACCESS_DENIED for orders older than 60 days without read_all_orders).
     */
    public function graphql(string $query, array $variables = []): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->graphqlUrl, ['query' => $query, 'variables' => (object) $variables]);

        $response->throw();
        $json = $response->json();

        if (!empty($json['errors'])) {
            throw new ShopifyGraphqlException($json['errors']);
        }

        return $json['data'] ?? [];
    }

    public function getOrder(int $id): array
    {
        return $this->request('GET', "/orders/{$id}.json")['order'];
    }

    public function getShop(): array
    {
        return $this->request('GET', '/shop.json')['shop'];
    }

    public function getProducts(array $params = []): array
    {
        return $this->request('GET', '/products.json', $params)['products'];
    }

    public function getProduct(int $id): array
    {
        return $this->request('GET', "/products/{$id}.json")['product'];
    }

    public function getOrders(array $params = []): array
    {
        return $this->request('GET', '/orders.json', $params)['orders'];
    }

    public function registerWebhook(string $topic, string $address): array
    {
        return $this->request('POST', '/webhooks.json', [], [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ])['webhook'];
    }

    public function deleteWebhook(int $id): void
    {
        $this->request('DELETE', "/webhooks/{$id}.json");
    }

    public function getWebhooks(): array
    {
        return $this->request('GET', '/webhooks.json')['webhooks'] ?? [];
    }

    private function request(string $method, string $endpoint, array $query = [], array $body = []): array
    {
        $request = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->timeout(15);

        $url = $this->baseUrl . $endpoint;

        $response = match ($method) {
            'GET' => $request->get($url, $query),
            'POST' => $request->post($url, $body),
            'PUT' => $request->put($url, $body),
            'DELETE' => $request->delete($url),
        };

        $response->throw();

        if ($method === 'DELETE') {
            return [];
        }

        return $response->json();
    }
}
