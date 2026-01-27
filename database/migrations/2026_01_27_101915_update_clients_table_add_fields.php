<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
            if (!Schema::hasColumn('clients', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('tax_number');
            }
        });

        // Set existing clients to first user
        $firstUser = DB::table('users')->first();
        if ($firstUser) {
            DB::table('clients')->whereNull('user_id')->update(['user_id' => $firstUser->id]);
        }

        // Now make user_id required and add foreign key
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Check if foreign key exists
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'clients' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME')->toArray();
            if (!in_array('clients_user_id_foreign', $foreignKeys)) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            // Check if unique on email exists and drop it
            $indexes = collect(DB::select("SHOW INDEX FROM clients WHERE Key_name = 'clients_email_unique'"));
            if ($indexes->isNotEmpty()) {
                $table->dropUnique(['email']);
            }

            // Check if unique on user_id + tax_number exists
            $indexes = collect(DB::select("SHOW INDEX FROM clients WHERE Key_name = 'clients_user_tax_unique'"));
            if ($indexes->isEmpty()) {
                $table->unique(['user_id', 'tax_number'], 'clients_user_tax_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('clients_user_tax_unique');
            $table->dropColumn(['user_id', 'postal_code', 'registration_number']);
            $table->unique('email');
        });
    }
};
