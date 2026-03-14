<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('shopify_product_id');
            $table->unsignedBigInteger('shopify_variant_id');
            $table->string('shopify_product_title');
            $table->string('shopify_variant_title')->nullable();
            $table->string('shopify_sku')->nullable();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'shopify_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_product_mappings');
    }
};
