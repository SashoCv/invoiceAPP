<?php

namespace App\Jobs;

use App\Services\ShopifyOrderProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessShopifyOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public array $orderData,
    ) {}

    public function handle(ShopifyOrderProcessor $processor): void
    {
        $processor->processOrder($this->userId, $this->orderData);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to process Shopify order', [
            'user_id' => $this->userId,
            'shopify_order_id' => $this->orderData['id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
