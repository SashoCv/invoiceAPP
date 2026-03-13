<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('reference');
            $table->index('batch_id');
        });

        // Give each existing transaction its own batch_id
        DB::table('bank_transactions')->orderBy('id')->each(function ($transaction) {
            DB::table('bank_transactions')
                ->where('id', $transaction->id)
                ->update(['batch_id' => (string) Str::uuid()]);
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
