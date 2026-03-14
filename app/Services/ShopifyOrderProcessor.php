<?php

namespace App\Services;

use App\Models\ShopifyOrder;
use App\Models\ShopifyOrderItem;
use App\Models\ShopifyProductMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopifyOrderProcessor
{
    public function processOrder(int $userId, array $orderData): ?ShopifyOrder
    {
        $shopifyOrderId = $orderData['id'];

        // Idempotent: skip if already processed
        $existing = ShopifyOrder::where('user_id', $userId)
            ->where('shopify_order_id', $shopifyOrderId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($userId, $orderData, $shopifyOrderId) {
            // Extract customer info
            $customer = $orderData['customer'] ?? [];
            $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: null;

            $order = ShopifyOrder::create([
                'user_id' => $userId,
                'shopify_order_id' => $shopifyOrderId,
                'order_number' => $orderData['name'] ?? $orderData['order_number'] ?? '',
                'customer_name' => $customerName,
                'customer_email' => $customer['email'] ?? $orderData['email'] ?? null,
                'financial_status' => $orderData['financial_status'] ?? 'unknown',
                'fulfillment_status' => $orderData['fulfillment_status'] ?? null,
                'currency' => $orderData['currency'] ?? 'USD',
                'subtotal_price' => $orderData['subtotal_price'] ?? 0,
                'total_tax' => $orderData['total_tax'] ?? 0,
                'total_price' => $orderData['total_price'] ?? 0,
                'ordered_at' => Carbon::parse($orderData['created_at']),
            ]);

            $lineItems = $orderData['line_items'] ?? [];

            foreach ($lineItems as $item) {
                $variantId = $item['variant_id'] ?? 0;
                $productId = $item['product_id'] ?? 0;

                // Find mapping for this variant
                $mapping = ShopifyProductMapping::forVariant($userId, $variantId)->first();

                $orderItem = ShopifyOrderItem::create([
                    'shopify_order_id' => $order->id,
                    'article_id' => $mapping?->article_id,
                    'shopify_product_id' => $productId,
                    'shopify_variant_id' => $variantId,
                    'title' => $item['title'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'total_discount' => collect($item['discount_allocations'] ?? [])->sum('amount'),
                ]);

                // Deduct stock if mapped and article tracks inventory
                if ($mapping && $mapping->article && $mapping->article->track_inventory) {
                    $mapping->article->deductStock(
                        $orderItem->quantity,
                        'shopify_order',
                        $order->id,
                        "Shopify order {$order->order_number}"
                    );
                }
            }

            return $order;
        });
    }

    public function processRefund(int $userId, array $refundData): void
    {
        $shopifyOrderId = $refundData['order_id'] ?? null;
        if (!$shopifyOrderId) {
            return;
        }

        $order = ShopifyOrder::where('user_id', $userId)
            ->where('shopify_order_id', $shopifyOrderId)
            ->first();

        if (!$order) {
            Log::warning("Shopify refund for unknown order", ['order_id' => $shopifyOrderId, 'user_id' => $userId]);
            return;
        }

        DB::transaction(function () use ($order, $refundData) {
            $refundLineItems = $refundData['refund_line_items'] ?? [];

            foreach ($refundLineItems as $refundItem) {
                $lineItemId = $refundItem['line_item_id'] ?? null;
                $quantity = $refundItem['quantity'] ?? 0;

                if (!$lineItemId || $quantity <= 0) {
                    continue;
                }

                // Find matching line item by shopify variant
                $lineItem = $refundItem['line_item'] ?? [];
                $variantId = $lineItem['variant_id'] ?? 0;

                $orderItem = $order->items()
                    ->where('shopify_variant_id', $variantId)
                    ->first();

                if ($orderItem && $orderItem->article && $orderItem->article->track_inventory) {
                    $orderItem->article->addStock(
                        $quantity,
                        "Shopify refund for order {$order->order_number}",
                        'adjustment'
                    );
                }
            }

            $order->update(['financial_status' => 'refunded']);
        });
    }
}
