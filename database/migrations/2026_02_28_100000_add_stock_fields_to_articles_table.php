<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('track_inventory')->default(false)->after('is_active');
            $table->decimal('stock_quantity', 10, 2)->default(0)->after('track_inventory');
            $table->decimal('low_stock_threshold', 10, 2)->default(5)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['track_inventory', 'stock_quantity', 'low_stock_threshold']);
        });
    }
};
