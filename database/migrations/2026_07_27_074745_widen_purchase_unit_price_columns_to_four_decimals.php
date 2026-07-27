<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Purchase/unit price fields were decimal(*,2), silently rounding away the
     * 4-decimal precision the UI now enters/displays for them. Fiscal document
     * line items (invoice_items, offer_items, proforma_invoice_items) are
     * deliberately left at 2 decimals — they're not in scope.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE stock_movements MODIFY cost_price DECIMAL(12,4) NULL');
        DB::statement('ALTER TABLE articles MODIFY price DECIMAL(12,4) NOT NULL');
        DB::statement('ALTER TABLE bundles MODIFY price DECIMAL(12,4) NOT NULL');
        DB::statement('ALTER TABLE incoming_invoice_items MODIFY unit_price DECIMAL(14,4) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE stock_movements MODIFY cost_price DECIMAL(10,2) NULL');
        DB::statement('ALTER TABLE articles MODIFY price DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE bundles MODIFY price DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE incoming_invoice_items MODIFY unit_price DECIMAL(12,2) NOT NULL');
    }
};
