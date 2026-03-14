<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_connections', function (Blueprint $table) {
            $table->text('access_token')->nullable()->change();
            $table->text('webhook_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shopify_connections', function (Blueprint $table) {
            $table->text('access_token')->nullable(false)->change();
            $table->text('webhook_secret')->nullable(false)->change();
        });
    }
};
