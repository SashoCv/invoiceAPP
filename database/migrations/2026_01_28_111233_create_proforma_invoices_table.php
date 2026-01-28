<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Numbering
            $table->string('proforma_number')->nullable();
            $table->string('proforma_prefix', 20)->nullable();
            $table->unsignedInteger('proforma_sequence');
            $table->year('proforma_year');

            // Dates
            $table->date('issue_date');
            $table->date('valid_until');

            // Financial
            $table->enum('currency', ['MKD', 'EUR', 'USD'])->default('MKD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Status
            $table->enum('status', ['draft', 'sent', 'converted_to_invoice'])->default('draft');
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();

            // Content
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per user per year
            $table->unique(['user_id', 'proforma_year', 'proforma_sequence'], 'unique_proforma_per_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
