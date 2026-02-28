<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('bundle_id')->constrained()->nullOnDelete();
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('bundle_id')->constrained()->nullOnDelete();
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->after('bundle_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
        });
    }
};
