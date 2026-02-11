<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchExchangeRates extends Command
{
    protected $signature = 'rates:fetch {--date= : Fetch rates for a specific date (Y-m-d format)}';
    protected $description = 'Fetch exchange rates from NBRM (National Bank of Republic of Macedonia)';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $dateFormatted = $date->format('d.m.Y');

        $this->info("Fetching exchange rates for {$dateFormatted}...");

        try {
            $response = Http::timeout(30)->get('https://www.nbrm.mk/KLServiceNOV/GetExchangeRate', [
                'StartDate' => $dateFormatted,
                'EndDate' => $dateFormatted,
                'format' => 'json',
            ]);

            if (!$response->successful()) {
                $this->error("API request failed with status: {$response->status()}");
                return self::FAILURE;
            }

            $data = $response->json();

            if (empty($data)) {
                $this->warn("No rates returned for {$dateFormatted} (likely a weekend or holiday). Skipping.");
                return self::SUCCESS;
            }

            $count = 0;
            foreach ($data as $entry) {
                $currencyCode = trim($entry['oznaka'] ?? '');
                $rate = (float) ($entry['sreden'] ?? 0);
                $unit = (int) ($entry['nomin'] ?? 1);

                if (!$currencyCode || $rate <= 0) {
                    continue;
                }

                ExchangeRate::updateOrCreate(
                    [
                        'date' => $date->toDateString(),
                        'currency_code' => $currencyCode,
                    ],
                    [
                        'rate' => $rate,
                        'unit' => $unit,
                    ]
                );

                $count++;
            }

            $this->info("Saved {$count} exchange rates for {$dateFormatted}.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to fetch exchange rates: {$e->getMessage()}");
            Log::error('FetchExchangeRates failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
