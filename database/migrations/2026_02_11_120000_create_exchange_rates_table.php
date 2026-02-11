<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('currency_code', 3);
            $table->decimal('rate', 15, 6);
            $table->integer('unit')->default(1);
            $table->timestamps();

            $table->unique(['date', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
