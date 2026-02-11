<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'date',
        'currency_code',
        'rate',
        'unit',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:6',
        'unit' => 'integer',
    ];

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('date', $date);
    }

    public function scopeForCurrency(Builder $query, string $code): Builder
    {
        return $query->where('currency_code', $code);
    }

    /**
     * Get rate for a currency on a given date, with fallback to the most recent previous date.
     */
    public static function getRate(string $currencyCode, $date = null): ?float
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $rate = static::where('currency_code', $currencyCode)
            ->where('date', '<=', $date->toDateString())
            ->orderByDesc('date')
            ->first();

        if (!$rate) {
            return null;
        }

        return (float) $rate->rate / $rate->unit;
    }
}
