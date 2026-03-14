<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ShopifyConnection;
use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderItem;
use App\Models\ShopifyProductMapping;
use App\Models\User;
use Illuminate\Console\Command;

class ShopifyTestSeed extends Command
{
    protected $signature = 'shopify:test-seed {--user= : User ID to seed for}';
    protected $description = 'Seed test Shopify data for testing the integration';

    public function handle(): void
    {
        $userId = $this->option('user') ?? User::where('role', '!=', 'admin')->first()?->id;

        if (!$userId) {
            $this->error('No user found.');
            return;
        }

        $user = User::find($userId);
        $this->info("Seeding Shopify test data for user: {$user->name} (ID: {$userId})");

        // 1. Create a fake connection
        $connection = ShopifyConnection::updateOrCreate(
            ['user_id' => $userId],
            [
                'shop_domain' => 'test-store.myshopify.com',
                'access_token' => 'test_token_for_development',
                'webhook_secret' => 'test_secret_for_development',
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );
        $this->info('Created test Shopify connection.');

        // 2. Get user articles and create mappings
        $articles = $user->articles()->where('is_active', true)->take(5)->get();

        if ($articles->isEmpty()) {
            $this->error('No articles found for this user. Create some articles first.');
            return;
        }

        $this->info("Found {$articles->count()} articles, creating mappings...");

        $variantId = 100001;
        $productId = 200001;

        foreach ($articles as $article) {
            ShopifyProductMapping::updateOrCreate(
                ['user_id' => $userId, 'shopify_variant_id' => $variantId],
                [
                    'shopify_product_id' => $productId,
                    'shopify_product_title' => $article->name,
                    'shopify_variant_title' => null,
                    'shopify_sku' => $article->sku,
                    'article_id' => $article->id,
                ]
            );
            $variantId++;
            $productId++;
        }

        $this->info('Created product mappings.');

        // 3. Create test orders with different customers
        $customers = [
            ['name' => 'Марко Петровски', 'email' => 'marko@example.com'],
            ['name' => 'Ана Стојановска', 'email' => 'ana@example.com'],
            ['name' => 'Петар Николов', 'email' => 'petar@example.com'],
            ['name' => 'Елена Димитрова', 'email' => 'elena@example.com'],
            ['name' => 'Иван Трајковски', 'email' => 'ivan@example.com'],
        ];

        $currencies = ['EUR', 'USD', 'EUR', 'EUR', 'USD'];
        $shopifyOrderId = 900001;

        for ($i = 0; $i < 12; $i++) {
            $customer = $customers[$i % count($customers)];
            $currency = $currencies[$i % count($currencies)];
            $orderedAt = now()->subDays(rand(1, 60));

            // Pick 1-3 random articles for this order
            $orderArticles = $articles->random(min(rand(1, 3), $articles->count()));

            $subtotal = 0;
            $items = [];

            foreach ($orderArticles as $article) {
                $qty = rand(1, 5);
                $price = round($article->price * (rand(80, 120) / 100), 2); // vary price slightly
                $discount = rand(0, 3) === 0 ? round($price * 0.1, 2) : 0;
                $lineTotal = ($price * $qty) - $discount;
                $subtotal += $lineTotal;

                $mapping = ShopifyProductMapping::where('user_id', $userId)
                    ->where('article_id', $article->id)
                    ->first();

                $items[] = [
                    'article_id' => $article->id,
                    'shopify_product_id' => $mapping->shopify_product_id,
                    'shopify_variant_id' => $mapping->shopify_variant_id,
                    'title' => $article->name,
                    'quantity' => $qty,
                    'price' => $price,
                    'total_discount' => $discount,
                ];
            }

            $tax = round($subtotal * 0.18, 2);
            $total = $subtotal + $tax;

            // Check if order already exists
            $existing = ShopifyOrder::where('user_id', $userId)
                ->where('shopify_order_id', $shopifyOrderId)
                ->exists();

            if (!$existing) {
                $order = ShopifyOrder::create([
                    'user_id' => $userId,
                    'shopify_order_id' => $shopifyOrderId,
                    'order_number' => '#' . (1001 + $i),
                    'customer_name' => $customer['name'],
                    'customer_email' => $customer['email'],
                    'financial_status' => 'paid',
                    'fulfillment_status' => rand(0, 1) ? 'fulfilled' : null,
                    'currency' => $currency,
                    'subtotal_price' => $subtotal,
                    'total_tax' => $tax,
                    'total_price' => $total,
                    'ordered_at' => $orderedAt,
                ]);

                foreach ($items as $item) {
                    ShopifyOrderItem::create([
                        'shopify_order_id' => $order->id,
                        ...$item,
                    ]);

                    // Deduct stock if article tracks inventory
                    $article = Article::find($item['article_id']);
                    if ($article && $article->track_inventory) {
                        $article->deductStock(
                            $item['quantity'],
                            'shopify_order',
                            $order->id,
                            "Shopify order {$order->order_number}"
                        );
                    }
                }
            }

            $shopifyOrderId++;
        }

        $this->info('Created 12 test orders with 5 different customers.');
        $this->newLine();
        $this->info('Done! Now check:');
        $this->info('  - /shopify/profitability  (Sales & customer stats)');
        $this->info('  - /shopify/orders         (Order list)');
        $this->info('  - /settings/shopify       (Connection & mappings)');
        $this->info('  - /warehouse              (Stock movements with shopify_deduction)');
    }
}
