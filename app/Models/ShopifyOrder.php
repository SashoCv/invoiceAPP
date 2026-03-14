<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopifyOrder extends Model
{
    protected $fillable = [
        'user_id',
        'shopify_order_id',
        'order_number',
        'customer_name',
        'customer_email',
        'financial_status',
        'fulfillment_status',
        'currency',
        'subtotal_price',
        'total_tax',
        'total_price',
        'ordered_at',
    ];

    protected $casts = [
        'shopify_order_id' => 'integer',
        'subtotal_price' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_price' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopifyOrderItem::class);
    }
}
