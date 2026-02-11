<?php

namespace App\Console\Commands;

use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringExpenses extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Generate expenses from active recurring expense templates for the current month';

    public function handle(): int
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        $recurringExpenses = RecurringExpense::where('is_active', true)->get();

        $created = 0;
        $currentMonth = $now->copy()->startOfMonth();

        foreach ($recurringExpenses as $recurring) {
            // Skip if current month is before start_date
            if ($recurring->start_date && $currentMonth->lt($recurring->start_date->copy()->startOfMonth())) {
                continue;
            }

            // Skip if current month is after end_date
            if ($recurring->end_date && $currentMonth->gt($recurring->end_date->copy()->startOfMonth())) {
                continue;
            }

            // Check if an expense already exists for this recurring expense this month
            $exists = $recurring->expenses()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->exists();

            if ($exists) {
                continue;
            }

            // Calculate the date, clamping day_of_month to the last day of the month
            $daysInMonth = $now->daysInMonth;
            $day = min($recurring->day_of_month, $daysInMonth);
            $date = Carbon::createFromDate($year, $month, $day);

            $recurring->expenses()->create([
                'user_id' => $recurring->user_id,
                'category_id' => $recurring->category_id,
                'name' => $recurring->name,
                'description' => $recurring->description,
                'amount' => $recurring->amount,
                'date' => $date,
            ]);

            $created++;
        }

        $this->info("Generated {$created} recurring expenses for {$now->format('F Y')}.");

        return Command::SUCCESS;
    }
}
