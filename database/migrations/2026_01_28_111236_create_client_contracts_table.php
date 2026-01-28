<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // File info
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);

            // Metadata
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contracts');
    }
};
