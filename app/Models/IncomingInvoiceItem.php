<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingInvoiceItem extends Model
{
    protected $fillable = [
        'incoming_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function incomingInvoice(): BelongsTo
    {
        return $this->belongsTo(IncomingInvoice::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $subtotal * ($item->tax_rate / 100);
            $item->total = $subtotal + $item->tax_amount;
        });
    }
}
