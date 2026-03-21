<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
        });

        Schema::table('shopify_product_mappings', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('article_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('article_id')->nullable()->change();
        });

        Schema::table('shopify_order_items', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('article_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_id');
        });

        Schema::table('shopify_product_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_id');
            $table->unsignedBigInteger('article_id')->nullable(false)->change();
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
    }
};
