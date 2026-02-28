<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoiceItem extends Model
{
    protected $fillable = [
        'proforma_invoice_id',
        'bundle_id',
        'article_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $subtotal = $item->quantity * $item->unit_price;
            $discountAmount = $subtotal * ($item->discount / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $item->tax_amount = $afterDiscount * ($item->tax_rate / 100);
            $item->total = $afterDiscount + $item->tax_amount;
        });
    }
}
