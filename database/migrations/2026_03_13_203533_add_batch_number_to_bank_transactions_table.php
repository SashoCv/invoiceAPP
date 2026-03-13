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
            $table->unsignedInteger('batch_number')->nullable()->after('batch_id');
        });

        $users = DB::table('bank_transactions')->distinct()->pluck('user_id');
        foreach ($users as $userId) {
            $batches = DB::table('bank_transactions')
                ->where('user_id', $userId)
                ->selectRaw('batch_id, MIN(date) as batch_date, MIN(id) as first_id')
                ->groupBy('batch_id')
                ->orderBy('batch_date')
                ->orderBy('first_id')
                ->get();

            foreach ($batches as $i => $batch) {
                DB::table('bank_transactions')
                    ->where('batch_id', $batch->batch_id)
                    ->update(['batch_number' => $i + 1]);
            }
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->unsignedInteger('batch_number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });
    }
};
