<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->default(0)->after('tax_rate');
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->default(0)->after('tax_rate');
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
