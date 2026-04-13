<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('code')->nullable()->after('description');
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->string('code')->nullable()->after('description');
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->string('code')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
