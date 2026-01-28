<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'offer_number',
        'offer_prefix',
        'offer_sequence',
        'offer_year',
        'title',
        'content',
        'currency',
        'issue_date',
        'valid_until',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'has_items',
        'notes',
        'converted_invoice_id',
        'converted_at',
        'accepted_at',
        'rejected_at',
    ];

    public const CURRENCIES = Invoice::CURRENCIES;

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'offer_sequence' => 'integer',
        'offer_year' => 'integer',
        'has_items' => 'boolean',
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
        return $this->hasMany(OfferItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function getCurrencySymbolAttribute(): string
    {
        return self::CURRENCIES[$this->currency] ?? $this->currency;
    }

    public function getFormattedNumberAttribute(): string
    {
        $number = $this->offer_year . '-' . str_pad($this->offer_sequence, 3, '0', STR_PAD_LEFT);
        return $this->offer_prefix ? $this->offer_prefix . ' ' . $number : $number;
    }

    public static function getNextSequence(int $userId, int $year): int
    {
        $maxSequence = self::where('user_id', $userId)
            ->where('offer_year', $year)
            ->max('offer_sequence');

        return ($maxSequence ?? 0) + 1;
    }

    public function calculateTotals(): void
    {
        if ($this->has_items) {
            $this->subtotal = $this->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
            $this->tax_amount = $this->items->sum('tax_amount');
            $this->total = $this->subtotal + $this->tax_amount;
            $this->save();
        }
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast() && $this->status === 'sent';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isConverted(): bool
    {
        return $this->converted_invoice_id !== null;
    }
}
