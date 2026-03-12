<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IncomingInvoice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $invoices = IncomingInvoice::whereNotIn('id', Expense::whereNotNull('incoming_invoice_id')->pluck('incoming_invoice_id'))
            ->with('client')
            ->get();

        foreach ($invoices as $invoice) {
            $category = ExpenseCategory::firstOrCreate(
                ['user_id' => $invoice->user_id, 'name' => 'Влезни фактури'],
                ['user_id' => $invoice->user_id, 'color' => '#f59e0b'],
            );

            $name = $invoice->supplier_name;
            if ($invoice->invoice_number) {
                $name .= ' #' . $invoice->invoice_number;
            }
            if ($invoice->client) {
                $name .= ' - ' . ($invoice->client->company ?: $invoice->client->name);
            }

            Expense::create([
                'user_id' => $invoice->user_id,
                'category_id' => $category->id,
                'incoming_invoice_id' => $invoice->id,
                'name' => $name,
                'amount' => $invoice->amount,
                'date' => $invoice->date,
            ]);
        }
    }

    public function down(): void
    {
        Expense::whereNotNull('incoming_invoice_id')->delete();
    }
};
