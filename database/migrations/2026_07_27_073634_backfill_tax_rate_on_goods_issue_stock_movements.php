<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * GoodsIssueController never set tax_rate on issue stock movements (always 0).
     * Backfill from each article's current tax_rate so the goods-issue VAT/total
     * shown to the user is accurate, same fix pattern as the cost_price backfill.
     */
    public function up(): void
    {
        DB::table('stock_movements as sm')
            ->join('articles as a', 'a.id', '=', 'sm.article_id')
            ->where('sm.type', 'issue')
            ->where('sm.tax_rate', 0)
            ->update(['sm.tax_rate' => DB::raw('a.tax_rate')]);
    }

    /**
     * Data backfill — not reversible (the original values were 0, not meaningful).
     */
    public function down(): void
    {
        //
    }
};
