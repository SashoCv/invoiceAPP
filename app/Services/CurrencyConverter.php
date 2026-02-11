<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;

class CurrencyConverter
{
    private array $rateCache = [];

    /**
     * Convert an amount from one currency to another using exchange rates for the given date.
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if ($fromCurrency === 'MKD') {
            $toRate = $this->getRate($toCurrency, $date);
            return $toRate ? $amount / $toRate : $amount;
        }

        if ($toCurrency === 'MKD') {
            $fromRate = $this->getRate($fromCurrency, $date);
            return $fromRate ? $amount * $fromRate : $amount;
        }

        // Cross-currency: convert via MKD
        $fromRate = $this->getRate($fromCurrency, $date);
        $toRate = $this->getRate($toCurrency, $date);

        if (!$fromRate || !$toRate) {
            return $amount;
        }

        return ($amount * $fromRate) / $toRate;
    }

    /**
     * Get the exchange rate for a currency (rate per 1 unit in MKD), cached in memory.
     */
    public function getRate(string $currency, $date = null): ?float
    {
        $dateStr = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        $key = "{$currency}:{$dateStr}";

        if (!array_key_exists($key, $this->rateCache)) {
            $this->rateCache[$key] = ExchangeRate::getRate($currency, $dateStr);
        }

        return $this->rateCache[$key];
    }
}
