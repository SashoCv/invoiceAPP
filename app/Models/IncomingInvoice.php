<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'supplier_name',
        'invoice_number',
        'amount',
        'currency',
        'date',
        'due_date',
        'status',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(IncomingInvoiceItem::class);
    }
}
