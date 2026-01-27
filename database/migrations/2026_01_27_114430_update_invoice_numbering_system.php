<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_prefix')->nullable()->after('invoice_number');
            $table->unsignedInteger('invoice_sequence')->after('invoice_prefix');
            $table->year('invoice_year')->after('invoice_sequence');
        });

        // Migrate existing data - extract sequence from old invoice_number format (INV-2026-0001)
        DB::table('invoices')->orderBy('id')->chunk(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                $parts = explode('-', $invoice->invoice_number);
                $year = isset($parts[1]) ? (int)$parts[1] : date('Y');
                $sequence = isset($parts[2]) ? (int)$parts[2] : 1;

                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'invoice_year' => $year,
                        'invoice_sequence' => $sequence,
                    ]);
            }
        });

        // Add unique constraint after data migration
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['user_id', 'invoice_year', 'invoice_sequence'], 'unique_invoice_per_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('unique_invoice_per_year');
            $table->dropColumn(['invoice_prefix', 'invoice_sequence', 'invoice_year']);
        });
    }
};
