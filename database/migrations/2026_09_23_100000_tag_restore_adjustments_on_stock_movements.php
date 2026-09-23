<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stock restores (invoice edit/delete, Shopify refunds) were written as unreferenced
 * 'adjustment' rows, indistinguishable from inventory-count corrections. Tag them so
 * the accounting reports can tell a restore (ignored — the document is the source)
 * from a real surplus/shortage (вишок/кусок по попис).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('stock_movements')
            ->where('type', 'adjustment')
            ->whereNull('reference_type')
            ->where(function ($q) {
                $q->where('notes', 'like', 'Restored: invoice #%')
                    ->orWhere('notes', 'like', 'Restored from bundle:%')
                    ->orWhere('notes', 'like', 'Shopify refund for order %');
            })
            ->get(['id', 'user_id', 'notes']);

        foreach ($rows as $row) {
            if (str_starts_with($row->notes, 'Shopify refund for order ')) {
                $orderNumber = substr($row->notes, strlen('Shopify refund for order '));
                $orderId = DB::table('shopify_orders')
                    ->where('user_id', $row->user_id)
                    ->where('order_number', $orderNumber)
                    ->value('id');

                DB::table('stock_movements')->where('id', $row->id)->update([
                    'reference_type' => 'shopify_refund',
                    'reference_id' => $orderId,
                ]);
                continue;
            }

            $invoiceId = null;
            if (str_starts_with($row->notes, 'Restored: invoice #')) {
                $invoiceNumber = substr($row->notes, strlen('Restored: invoice #'));
                $invoiceId = DB::table('invoices')
                    ->where('user_id', $row->user_id)
                    ->where('invoice_number', $invoiceNumber)
                    ->value('id');
            }

            DB::table('stock_movements')->where('id', $row->id)->update([
                'reference_type' => 'invoice_restore',
                'reference_id' => $invoiceId,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('stock_movements')
            ->where('type', 'adjustment')
            ->whereIn('reference_type', ['invoice_restore', 'shopify_refund'])
            ->update(['reference_type' => null, 'reference_id' => null]);
    }
};
