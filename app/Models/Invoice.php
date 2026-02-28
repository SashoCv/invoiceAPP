<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'invoice_number',
        'invoice_prefix',
        'invoice_sequence',
        'invoice_year',
        'client_id',
        'currency',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'paid_date',
        'last_sent_at',
    ];

    public const CURRENCIES = [
        'MKD' => 'ден.',
        'EUR' => '€',
        'USD' => '$',
    ];

    public function getCurrencySymbolAttribute(): string
    {
        return self::CURRENCIES[$this->currency] ?? $this->currency;
    }

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'last_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'invoice_sequence' => 'integer',
        'invoice_year' => 'integer',
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(function ($item) {
            $lineTotal = $item->quantity * $item->unit_price;
            return $lineTotal - ($lineTotal * $item->discount / 100);
        });
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    /**
     * Get the formatted invoice number for display.
     * Format: [PREFIX] YEAR-SEQUENCE (e.g., "FA 2026-001" or "2026-001")
     */
    public function getFormattedNumberAttribute(): string
    {
        $number = $this->invoice_year . '-' . str_pad($this->invoice_sequence, 3, '0', STR_PAD_LEFT);

        return $this->invoice_prefix ? $this->invoice_prefix . ' ' . $number : $number;
    }

    /**
     * Get the next available sequence number for a user in a given year.
     */
    public static function getNextSequence(int $userId, int $year): int
    {
        $maxSequence = self::withTrashed()
            ->where('user_id', $userId)
            ->where('invoice_year', $year)
            ->max('invoice_sequence');

        return ($maxSequence ?? 0) + 1;
    }

    /**
     * Format invoice number from prefix, year and sequence.
     */
    public static function formatInvoiceNumber(?string $prefix, int $year, int $sequence): string
    {
        $number = $year . '-' . $sequence;
        if ($prefix) {
            return trim($prefix) . ' ' . $number;
        }
        return $number;
    }

    /**
     * Generate a legacy invoice number for backward compatibility.
     * @deprecated Use invoice_prefix, invoice_sequence, invoice_year instead
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return 'INV-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
