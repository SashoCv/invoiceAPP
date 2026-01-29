<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Offer;
use App\Services\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfController extends Controller
{
    use AuthorizesRequests;

    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generate and download invoice PDF
     */
    public function invoice(Invoice $invoice): BinaryFileResponse
    {
        $this->authorize('view', $invoice);

        $pdfPath = $this->pdfService->generateInvoicePdf($invoice);

        $filename = "Faktura_{$invoice->invoice_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate and download proforma PDF
     */
    public function proforma(ProformaInvoice $proformaInvoice): BinaryFileResponse
    {
        $this->authorize('view', $proformaInvoice);

        $pdfPath = $this->pdfService->generateProformaPdf($proformaInvoice);

        $filename = "Profaktura_{$proformaInvoice->proforma_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate and download offer PDF
     */
    public function offer(Offer $offer): BinaryFileResponse
    {
        $this->authorize('view', $offer);

        $pdfPath = $this->pdfService->generateOfferPdf($offer);

        $filename = "Ponuda_{$offer->offer_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview invoice PDF in browser
     */
    public function invoicePreview(Invoice $invoice): BinaryFileResponse
    {
        $this->authorize('view', $invoice);

        $pdfPath = $this->pdfService->generateInvoicePdf($invoice);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Faktura_' . $invoice->invoice_number . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview proforma PDF in browser
     */
    public function proformaPreview(ProformaInvoice $proformaInvoice): BinaryFileResponse
    {
        $this->authorize('view', $proformaInvoice);

        $pdfPath = $this->pdfService->generateProformaPdf($proformaInvoice);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Profaktura_' . $proformaInvoice->proforma_number . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview offer PDF in browser
     */
    public function offerPreview(Offer $offer): BinaryFileResponse
    {
        $this->authorize('view', $offer);

        $pdfPath = $this->pdfService->generateOfferPdf($offer);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ponuda_' . $offer->offer_number . '.pdf"',
        ])->deleteFileAfterSend(true);
    }
}
