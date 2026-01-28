<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Numbering
            $table->string('offer_number')->nullable();
            $table->string('offer_prefix', 20)->nullable();
            $table->unsignedInteger('offer_sequence');
            $table->year('offer_year');

            // Content
            $table->string('title');
            $table->longText('content')->nullable();

            // Dates
            $table->date('issue_date');
            $table->date('valid_until');

            // Financial (optional items)
            $table->enum('currency', ['MKD', 'EUR', 'USD'])->default('MKD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('has_items')->default(false);

            // Status
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per user per year
            $table->unique(['user_id', 'offer_year', 'offer_sequence'], 'unique_offer_per_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
