<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ShopifyProductMapping extends Model
{
    protected $fillable = [
        'user_id',
        'shopify_product_id',
        'shopify_variant_id',
        'shopify_product_title',
        'shopify_variant_title',
        'shopify_sku',
        'article_id',
    ];

    protected $casts = [
        'shopify_product_id' => 'integer',
        'shopify_variant_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function scopeForVariant(Builder $query, int $userId, int $variantId): Builder
    {
        return $query->where('user_id', $userId)->where('shopify_variant_id', $variantId);
    }
}
