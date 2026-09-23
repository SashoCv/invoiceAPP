<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Продажна цена со ДДВ по единица на денот на приемот (ПЛТ колона 9). Образец ЕТ
 * ја води залихата по продажни цени, па секој прием мора да ја памети цената по
 * која стоката влегла, независно од подоцнежни промени на цената на артиклот.
 * Existing receipts are backfilled with the article's current price (historical
 * prices were never stored).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('retail_price', 12, 4)->nullable()->after('tax_rate');
        });

        DB::statement("
            UPDATE stock_movements sm
            JOIN articles a ON a.id = sm.article_id
            SET sm.retail_price = ROUND(a.price * (1 + a.tax_rate / 100), 4)
            WHERE sm.type = 'receipt' AND sm.reference_type = 'goods_receipt'
        ");
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('retail_price');
        });
    }
};
