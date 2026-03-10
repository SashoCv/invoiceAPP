<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProformaInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'proforma_number',
        'proforma_prefix',
        'proforma_sequence',
        'proforma_year',
        'currency',
        'issue_date',
        'valid_until',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'notes',
        'converted_invoice_id',
        'converted_at',
    ];

    public const CURRENCIES = Invoice::CURRENCIES;

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'proforma_sequence' => 'integer',
        'proforma_year' => 'integer',
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
        return $this->hasMany(ProformaInvoiceItem::class);
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
        $number = $this->proforma_year . '-' . str_pad($this->proforma_sequence, 3, '0', STR_PAD_LEFT);
        return $this->proforma_prefix ? $this->proforma_prefix . ' ' . $number : $number;
    }

    public static function getNextSequence(int $userId, int $year): int
    {
        $maxSequence = self::withTrashed()
            ->where('user_id', $userId)
            ->where('proforma_year', $year)
            ->max('proforma_sequence');

        return ($maxSequence ?? 0) + 1;
    }

    /**
     * Format proforma number from prefix, year and sequence.
     */
    public static function formatProformaNumber(?string $prefix, int $year, int $sequence): string
    {
        $number = $sequence . '-' . $year;
        if ($prefix) {
            return trim($prefix) . ' ' . $number;
        }
        return $number;
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

    public function isConverted(): bool
    {
        return $this->status === 'converted_to_invoice';
    }
}
