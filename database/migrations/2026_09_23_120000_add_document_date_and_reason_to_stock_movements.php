<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual stock corrections (Магацин → Корекција на залиха) get the date of the event
 * (e.g. the inventory count) and a reason, so the accounting reports and Образец ЕТ
 * book them on the right day under the right document type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->date('document_date')->nullable()->after('retail_price');
            $table->string('reason', 30)->nullable()->after('document_date');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['document_date', 'reason']);
        });
    }
};
