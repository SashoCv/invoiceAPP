<?php

namespace App\Jobs;

use App\Services\ShopifyOrderProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessShopifyRefund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public array $refundData,
    ) {}

    public function handle(ShopifyOrderProcessor $processor): void
    {
        $processor->processRefund($this->userId, $this->refundData);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to process Shopify refund', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
