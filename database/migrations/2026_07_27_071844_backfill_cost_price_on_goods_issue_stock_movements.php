<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * GoodsIssueController used to hardcode cost_price=0 on issue stock movements.
     * Backfill them with the weighted average receipt cost per article, same basis
     * used everywhere else (output calculation, profitability, purchase prices).
     */
    public function up(): void
    {
        $articleIds = DB::table('stock_movements')
            ->where('type', 'issue')
            ->where(function ($q) {
                $q->whereNull('cost_price')->orWhere('cost_price', 0);
            })
            ->distinct()
            ->pluck('article_id');

        foreach ($articleIds as $articleId) {
            $avgCost = DB::table('stock_movements')
                ->where('article_id', $articleId)
                ->where('type', 'receipt')
                ->where('cost_price', '>', 0)
                ->selectRaw('SUM(cost_price * quantity) / SUM(quantity) as avg_cost')
                ->value('avg_cost');

            if (! $avgCost) {
                continue;
            }

            DB::table('stock_movements')
                ->where('article_id', $articleId)
                ->where('type', 'issue')
                ->where(function ($q) {
                    $q->whereNull('cost_price')->orWhere('cost_price', 0);
                })
                ->update(['cost_price' => $avgCost]);
        }
    }

    /**
     * Data backfill — not reversible (the original values were 0, not meaningful).
     */
    public function down(): void
    {
        //
    }
};
