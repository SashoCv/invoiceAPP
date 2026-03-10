<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reformat document numbers from "YEAR-SEQ" to "SEQ-YEAR"
     * e.g. "2026-1" becomes "1-2026", "PREFIX 2026-1" becomes "PREFIX 1-2026"
     */
    public function up(): void
    {
        $this->reformatNumbers('invoices', 'invoice_number', 'invoice_prefix', 'invoice_sequence', 'invoice_year');
        $this->reformatNumbers('proforma_invoices', 'proforma_number', 'proforma_prefix', 'proforma_sequence', 'proforma_year');
        $this->reformatNumbers('offers', 'offer_number', 'offer_prefix', 'offer_sequence', 'offer_year');
    }

    public function down(): void
    {
        $this->reverseNumbers('invoices', 'invoice_number', 'invoice_prefix', 'invoice_sequence', 'invoice_year');
        $this->reverseNumbers('proforma_invoices', 'proforma_number', 'proforma_prefix', 'proforma_sequence', 'proforma_year');
        $this->reverseNumbers('offers', 'offer_number', 'offer_prefix', 'offer_sequence', 'offer_year');
    }

    private function reformatNumbers(string $table, string $numberCol, string $prefixCol, string $seqCol, string $yearCol): void
    {
        $rows = DB::table($table)->select(['id', $prefixCol, $seqCol, $yearCol])->get();

        foreach ($rows as $row) {
            $number = $row->$seqCol . '-' . $row->$yearCol;
            if ($row->$prefixCol) {
                $number = trim($row->$prefixCol) . ' ' . $number;
            }
            DB::table($table)->where('id', $row->id)->update([$numberCol => $number]);
        }
    }

    private function reverseNumbers(string $table, string $numberCol, string $prefixCol, string $seqCol, string $yearCol): void
    {
        $rows = DB::table($table)->select(['id', $prefixCol, $seqCol, $yearCol])->get();

        foreach ($rows as $row) {
            $number = $row->$yearCol . '-' . $row->$seqCol;
            if ($row->$prefixCol) {
                $number = trim($row->$prefixCol) . ' ' . $number;
            }
            DB::table($table)->where('id', $row->id)->update([$numberCol => $number]);
        }
    }
};
