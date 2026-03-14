<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('shopify_order_id');
            $table->string('order_number');
            $table->string('financial_status');
            $table->string('fulfillment_status')->nullable();
            $table->char('currency', 3);
            $table->decimal('subtotal_price', 12, 2);
            $table->decimal('total_tax', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->unique(['user_id', 'shopify_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_orders');
    }
};
