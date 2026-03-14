<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->unsignedSmallInteger('batch_year')->after('batch_number')->nullable();
        });

        // Backfill batch_year from the date column
        DB::statement('UPDATE bank_transactions SET batch_year = YEAR(date)');

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->unsignedSmallInteger('batch_year')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('batch_year');
        });
    }
};
