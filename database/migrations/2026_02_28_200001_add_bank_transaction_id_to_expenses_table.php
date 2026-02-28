<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable()->after('recurring_expense_id')
                ->constrained('bank_transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_transaction_id']);
            $table->dropColumn('bank_transaction_id');
        });
    }
};
