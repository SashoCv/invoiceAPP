<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - type: purchase (набавка од добавувач), customer_return (поврат од купувач — valued at
 *   the cost the goods left at, not as a purchase), opening (почетна состојба / пренос)
 * - invoice_id: for a customer return, the invoice the goods were sold on
 * - dependent_costs: зависни трошоци на набавка (транспорт, шпедиција, царина) — part of
 *   набавна вредност under МСС 2, allocated to the lines by their value
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('type', 20)->default('purchase')->after('receipt_number');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('type');
            $table->decimal('dependent_costs', 12, 2)->default(0)->after('total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['type', 'invoice_id', 'dependent_costs']);
        });
    }
};
