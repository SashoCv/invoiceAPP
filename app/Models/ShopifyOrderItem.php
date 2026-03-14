<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopify_order_id',
        'article_id',
        'shopify_product_id',
        'shopify_variant_id',
        'title',
        'quantity',
        'price',
        'total_discount',
    ];

    protected $casts = [
        'shopify_product_id' => 'integer',
        'shopify_variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopifyOrder::class, 'shopify_order_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
