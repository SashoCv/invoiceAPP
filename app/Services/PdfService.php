<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Offer;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Str;

class PdfService
{
    /**
     * Currency symbols
     */
    protected array $currencySymbols = [
        'MKD' => 'ден.',
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF',
    ];

    /**
     * Generate PDF for an invoice
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load(['client', 'items', 'user.agency', 'user.bankAccounts']);

        $template = $invoice->user->invoice_template ?? 'classic';
        $bankAccount = $this->getDefaultBankAccount($invoice->user, $invoice->currency);

        $data = [
            'type' => 'invoice',
            'template' => $template,
            'title' => 'Фактура ' . $invoice->invoice_number,
            'docTitle' => 'ФАКТУРА',
            'docNumber' => $invoice->invoice_number,
            'issueDate' => $invoice->issue_date?->format('d.m.Y'),
            'dueDate' => $invoice->due_date?->format('d.m.Y'),
            'dueDateLabel' => 'Датум на доспевање',
            'currency' => $invoice->currency,
            'currencySymbol' => $this->currencySymbols[$invoice->currency] ?? $invoice->currency,
            'subtotal' => $invoice->subtotal,
            'taxAmount' => $invoice->tax_amount,
            'total' => $invoice->total,
            'notes' => $invoice->notes,
            'client' => $invoice->client,
            'agency' => $invoice->user->agency,
            'items' => $invoice->items,
            'bankAccount' => $bankAccount,
        ];

        return $this->generatePdf($data, "invoice_{$invoice->id}");
    }

    /**
     * Generate PDF for a proforma invoice
     */
    public function generateProformaPdf(ProformaInvoice $proforma): string
    {
        $proforma->load(['client', 'items', 'user.agency', 'user.bankAccounts']);

        $template = $proforma->user->proforma_template ?? $proforma->user->invoice_template ?? 'classic';
        $bankAccount = $this->getDefaultBankAccount($proforma->user, $proforma->currency);

        $data = [
            'type' => 'proforma',
            'template' => $template,
            'title' => 'Профактура ' . $proforma->proforma_number,
            'docTitle' => 'ПРОФАКТУРА',
            'docNumber' => $proforma->proforma_number,
            'issueDate' => $proforma->issue_date?->format('d.m.Y'),
            'dueDate' => $proforma->valid_until?->format('d.m.Y'),
            'dueDateLabel' => 'Важи до',
            'currency' => $proforma->currency,
            'currencySymbol' => $this->currencySymbols[$proforma->currency] ?? $proforma->currency,
            'subtotal' => $proforma->subtotal,
            'taxAmount' => $proforma->tax_amount,
            'total' => $proforma->total,
            'notes' => $proforma->notes,
            'client' => $proforma->client,
            'agency' => $proforma->user->agency,
            'items' => $proforma->items,
            'bankAccount' => $bankAccount,
        ];

        return $this->generatePdf($data, "proforma_{$proforma->id}");
    }

    /**
     * Generate PDF for an offer
     */
    public function generateOfferPdf(Offer $offer): string
    {
        $offer->load(['client', 'items', 'user.agency', 'user.bankAccounts']);

        $template = $offer->user->offer_template ?? 'classic';

        $data = [
            'type' => 'offer',
            'template' => $template,
            'title' => 'Понуда ' . $offer->offer_number,
            'docTitle' => 'ПОНУДА',
            'docNumber' => $offer->offer_number,
            'offerTitle' => $offer->title,
            'offerContent' => $offer->content,
            'hasItems' => $offer->has_items,
            'issueDate' => $offer->issue_date?->format('d.m.Y'),
            'dueDate' => $offer->valid_until?->format('d.m.Y'),
            'dueDateLabel' => 'Важи до',
            'currency' => $offer->currency,
            'currencySymbol' => $this->currencySymbols[$offer->currency] ?? $offer->currency,
            'subtotal' => $offer->subtotal,
            'taxAmount' => $offer->tax_amount,
            'total' => $offer->total,
            'notes' => $offer->notes,
            'client' => $offer->client,
            'agency' => $offer->user->agency,
            'items' => $offer->has_items ? $offer->items : collect([]),
            'bankAccount' => null,
        ];

        return $this->generatePdf($data, "offer_{$offer->id}");
    }

    /**
     * Get default bank account for currency
     */
    protected function getDefaultBankAccount($user, string $currency)
    {
        // First try to get default account with matching currency
        $account = $user->bankAccounts()
            ->where('currency', $currency)
            ->where('is_default', true)
            ->first();

        if ($account) {
            return $account;
        }

        // Try any account with matching currency
        $account = $user->bankAccounts()
            ->where('currency', $currency)
            ->first();

        if ($account) {
            return $account;
        }

        // Fall back to any default account
        return $user->bankAccounts()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Generate PDF using configured engine (browsershot or dompdf)
     */
    protected function generatePdf(array $data, string $filename): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/' . $filename . '_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            return $this->generateWithDompdf($data, $pdfFile);
        }

        return $this->generateWithBrowsershot($data, $pdfFile);
    }

    /**
     * Generate PDF using DomPDF
     */
    protected function generateWithDompdf(array $data, string $pdfFile): string
    {
        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4');

        file_put_contents($pdfFile, $pdf->output());

        return $pdfFile;
    }

    /**
     * Generate PDF using Browsershot (Puppeteer)
     */
    protected function generateWithBrowsershot(array $data, string $pdfFile): string
    {
        $html = view('pdf.browsershot', $data)->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(60);

        if ($nodeBinary = config('app.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }
        if ($npmBinary = config('app.npm_binary')) {
            $browsershot->setNpmBinary($npmBinary);
        }

        $npmPath = base_path('node_modules');
        if (is_dir($npmPath)) {
            $browsershot->setNodeModulePath($npmPath);
        }

        $browsershot->save($pdfFile);

        return $pdfFile;
    }

    /**
     * Clean up temporary PDF file
     */
    public function cleanup(string $pdfPath): void
    {
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    }
}
