<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Goods that left the stock before any receipt (an issue or invoices dated before the first
 * receipt) leave the articles negative from the very start. This enters the missing opening
 * stock as a manual input (Почетна состојба) before the first movement, so the stock is never
 * negative before the receipts.
 *
 * If an inventory count (попис) was posted later, the stock after it is the counted quantity,
 * so the count's difference is reduced by the same amount — the stock from the count on, and
 * today's stock, stay as they are.
 *
 * Runs as a dry run unless --apply is given.
 */
class FixOpeningStock extends Command
{
    private const OPENING_NOTE = 'Почетна состојба (корекција на минус залиха)';

    protected $signature = 'stock:fix-opening
        {email : The user whose stock to fix}
        {--before=2026-03-01 : Fix the negative stock as of this date (the first receipts)}
        {--date= : Date of the opening input (default: the day of the first movement)}
        {--count-date=2026-07-09 : Date of the inventory count (попис) to correct; "none" to skip}
        {--apply : Write the movements (otherwise only shows what would change)}';

    protected $description = 'Enter the missing opening stock for articles that went negative before the first receipts';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('No user with that email.');
            return self::FAILURE;
        }

        if (StockMovement::where('user_id', $user->id)->where('notes', self::OPENING_NOTE)->exists()) {
            $this->error('The opening stock was already entered for this user (movements with note "' . self::OPENING_NOTE . '").');
            return self::FAILURE;
        }

        $before = Carbon::parse($this->option('before'))->toDateString();
        $countDate = $this->option('count-date') === 'none' ? null : Carbon::parse($this->option('count-date'))->toDateString();

        $valuation = app(StockValuationService::class);
        $events = $valuation->events($user->id);
        if (!$events) {
            $this->info('No stock movements.');
            return self::SUCCESS;
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : $events[0]['date'];
        if ($date >= $before) {
            $this->error("The opening date {$date} must be before {$before}.");
            return self::FAILURE;
        }

        // Missing quantity per article = how far it is below zero just before the first receipts
        $deficits = [];
        foreach ($valuation->balancesBefore($user->id, $before) as $articleId => $bal) {
            if ($bal['qty'] < -0.001) {
                $deficits[$articleId] = round(-$bal['qty'], 4);
            }
        }
        if (!$deficits) {
            $this->info("No article is negative before {$before}. Nothing to fix.");
            return self::SUCCESS;
        }

        // Purchase price: the article's first receipt (the same price the reports would estimate with)
        $firstCost = [];
        foreach ($events as $e) {
            if ($e['doc_type'] === 'receipt' && !isset($firstCost[$e['article_id']])) {
                $firstCost[$e['article_id']] = $e['unit_cost'];
            }
        }

        // Articles with a difference posted on the count date (entered by hand, not by a document)
        $counted = $countDate ? StockMovement::where('user_id', $user->id)
            ->whereNull('reference_type')
            ->whereRaw('DATE(COALESCE(document_date, created_at)) = ?', [$countDate])
            ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '!=', self::OPENING_NOTE))
            ->distinct()->pluck('article_id')->flip()->all() : [];

        $articles = Article::withTrashed()->where('user_id', $user->id)->whereIn('id', array_keys($deficits))->get()->keyBy('id');
        $retailBefore = $this->retailTotals(app(StockValuationService::class), $user->id, $before);

        DB::beginTransaction();
        try {
            $rows = [];
            foreach ($deficits as $articleId => $qty) {
                $article = Article::withTrashed()->lockForUpdate()->find($articleId);
                $cost = $firstCost[$articleId] ?? null;
                $offset = isset($counted[$articleId]);

                $stock = (float) $article->stock_quantity;
                $article->stockMovements()->create([
                    'user_id' => $user->id,
                    'type' => 'receipt',
                    'quantity' => $qty,
                    'quantity_before' => $stock,
                    'quantity_after' => $stock + $qty,
                    'cost_price' => $cost,
                    'document_date' => $date,
                    'reason' => 'opening',
                    'notes' => self::OPENING_NOTE,
                ]);

                if ($offset) {
                    // The count found the real quantity, so its difference shrinks by what was missing
                    $article->stockMovements()->create([
                        'user_id' => $user->id,
                        'type' => 'issue',
                        'quantity' => -$qty,
                        'quantity_before' => $stock + $qty,
                        'quantity_after' => $stock,
                        'document_date' => $countDate,
                        'reason' => 'shortage',
                        'notes' => 'Корекција на попис ' . Carbon::parse($countDate)->format('d.m.Y') . ' (по внесена почетна состојба)',
                    ]);
                } else {
                    $article->stock_quantity = $stock + $qty;
                    $article->save();
                }

                $rows[] = [
                    $articles[$articleId]->name ?? "#{$articleId}",
                    $this->num($qty, 0),
                    $cost !== null ? $this->num($cost) : 'проценета',
                    $offset ? 'да — залихата денес останува иста' : 'нема попис — залихата денес +' . $this->num($qty, 0),
                ];
            }

            $this->line("Почетна состојба на " . Carbon::parse($date)->format('d.m.Y') . ' за ' . $user->name . ' (' . $user->email . ')');
            $this->table(['Артикл', 'Количина', 'Набавна цена', 'Корекција на попис ' . ($countDate ? Carbon::parse($countDate)->format('d.m.Y') : '—')], $rows);

            $retailAfter = $this->retailTotals(app(StockValuationService::class), $user->id, $before);
            $this->line(sprintf('Залиха на %s по продажни цени: %s → %s (артикли во минус: %d → %d)',
                Carbon::parse($before)->subDay()->format('d.m.Y'),
                $this->num($retailBefore['retail']), $this->num($retailAfter['retail']),
                $retailBefore['negative'], $retailAfter['negative']));

            if ($this->option('apply')) {
                DB::commit();
                $this->info('Внесено: ' . count($rows) . ' почетни состојби.');
            } else {
                DB::rollBack();
                $this->warn('Само преглед — ништо не е запишано. Пуштете со --apply за да се внесе.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return self::SUCCESS;
    }

    private function retailTotals(StockValuationService $valuation, int $userId, string $before): array
    {
        $bal = $valuation->balancesBefore($userId, $before);

        return [
            'retail' => array_sum(array_column($bal, 'retail')),
            'negative' => count(array_filter($bal, fn ($b) => $b['qty'] < -0.001)),
        ];
    }

    private function num(float $v, int $decimals = 2): string
    {
        return number_format($v, $decimals, ',', '.');
    }
}
