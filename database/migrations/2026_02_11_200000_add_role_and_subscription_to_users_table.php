<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password');
            $table->timestamp('subscription_expires_at')->nullable()->after('role');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_expires_at');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'subscription_expires_at', 'trial_ends_at']);
        });
    }
};
