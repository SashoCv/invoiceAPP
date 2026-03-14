<?php

use App\Http\Controllers\Api\ShopifyWebhookController;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/shopify/{userId}', [ShopifyWebhookController::class, 'handle'])
    ->middleware(VerifyShopifyWebhook::class);
