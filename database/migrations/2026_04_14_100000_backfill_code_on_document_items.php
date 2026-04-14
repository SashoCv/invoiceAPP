<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoice_items', 'proforma_invoice_items', 'offer_items'] as $table) {
            DB::statement("
                UPDATE {$table} i
                INNER JOIN articles a ON a.id = i.article_id
                SET i.code = a.code
                WHERE i.code IS NULL
                  AND i.article_id IS NOT NULL
                  AND a.code IS NOT NULL
            ");

            DB::statement("
                UPDATE {$table} i
                INNER JOIN bundles b ON b.id = i.bundle_id
                SET i.code = b.code
                WHERE i.code IS NULL
                  AND i.bundle_id IS NOT NULL
                  AND b.code IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
    }
};
