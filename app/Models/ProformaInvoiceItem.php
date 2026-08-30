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
        'code',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount',
        'additional_discount',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'additional_discount' => 'decimal:2',
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
            $afterDiscount = $subtotal * (1 - $item->discount / 100);
            $afterAdditionalDiscount = $afterDiscount * (1 - ($item->additional_discount ?? 0) / 100);
            $item->tax_amount = $afterAdditionalDiscount * ($item->tax_rate / 100);
            $item->total = $afterAdditionalDiscount + $item->tax_amount;
        });
    }
}
