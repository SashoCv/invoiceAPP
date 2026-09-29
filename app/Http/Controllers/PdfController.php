<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Offer;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
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

    /**
     * Generate and download goods issue PDF
     */
    public function goodsIssue(GoodsIssue $goodsIssue): BinaryFileResponse
    {
        abort_if($goodsIssue->user_id !== auth()->id(), 403);

        $pdfPath = $this->pdfService->generateGoodsIssuePdf($goodsIssue);

        $filename = "Ispratnica_{$goodsIssue->issue_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview goods issue PDF in browser
     */
    public function goodsIssuePreview(GoodsIssue $goodsIssue): BinaryFileResponse
    {
        abort_if($goodsIssue->user_id !== auth()->id(), 403);

        $pdfPath = $this->pdfService->generateGoodsIssuePdf($goodsIssue);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ispratnica_' . $goodsIssue->issue_number . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate and download the output calculation (Излезна калкулација) for an invoice
     */
    public function outputCalculation(Invoice $invoice): BinaryFileResponse
    {
        $this->authorize('view', $invoice);

        $pdfPath = $this->pdfService->generateOutputCalculationPdf($invoice);

        $filename = "Izlezna_kalkulacija_{$invoice->invoice_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview the output calculation PDF in browser
     */
    public function outputCalculationPreview(Invoice $invoice): BinaryFileResponse
    {
        $this->authorize('view', $invoice);

        $pdfPath = $this->pdfService->generateOutputCalculationPdf($invoice);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Izlezna_kalkulacija_' . str_replace(['/', '\\', ' '], '_', $invoice->invoice_number) . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Приемен лист во трговија на мало (Образец ПЛТ) for a goods receipt: набавна вредност
     * (incl. the allocated зависни трошоци), ДДВ при набавка at the prescribed rate, продажна
     * вредност со ДДВ and the ДДВ contained in it — the source of ЕТ кол. 5 and 6.
     */
    public function goodsReceiptPlt(GoodsReceipt $goodsReceipt): BinaryFileResponse
    {
        abort_if($goodsReceipt->user_id !== auth()->id(), 403);

        $user = auth()->user();
        $articles = $user->articles()->withTrashed()->get(['id', 'code', 'name', 'unit'])->keyBy('id');

        $rows = [];
        foreach (app(\App\Services\StockValuationService::class)->documentEvents($user->id, 'receipt:' . $goodsReceipt->id) as $e) {
            $a = $articles->get($e['article_id']);
            $rate = (float) $e['tax_rate'];
            $retail = $e['retail_value'];
            $rows[] = [
                'name' => ($a->code ?? '') ? $a->code . ' — ' . $a->name : ($a->name ?? ''),
                'unit' => $a->unit ?? '',
                'quantity' => $e['qty'],
                'unit_cost' => $e['unit_cost'],
                'cost' => $e['cost_value'],                                     // кол. 6 = 4 × 5
                'cost_vat' => round($e['cost_value'] * $rate / 100, 2),         // кол. 7 = 6 × 8
                'rate' => $rate,                                                // кол. 8
                'retail_unit' => $e['retail_unit'],                             // кол. 9
                'retail' => $retail,                                            // кол. 10 = 4 × 9
                'retail_vat' => round($retail * $rate / (100 + $rate), 2),      // кол. 11
            ];
        }

        $totals = [];
        foreach (['cost', 'cost_vat', 'retail', 'retail_vat'] as $f) {
            $totals[$f] = round(array_sum(array_column($rows, $f)), 2);
        }

        $typeLabels = ['purchase' => 'Набавка', 'customer_return' => 'Поврат од купувач', 'opening' => 'Почетна состојба'];

        $pdfPath = $this->pdfService->generatePltPdf([
            'agency' => $user->agency,
            'authorizedPerson' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
            'receipt' => $goodsReceipt,
            'typeLabel' => $typeLabels[$goodsReceipt->type] ?? 'Набавка',
            'rows' => $rows,
            'totals' => $totals,
        ]);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PLT_' . str_replace(['/', '\\', ' '], '_', $goodsReceipt->receipt_number) . '.pdf"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate and download goods receipt (приемница) PDF
     */
    public function goodsReceipt(GoodsReceipt $goodsReceipt): BinaryFileResponse
    {
        abort_if($goodsReceipt->user_id !== auth()->id(), 403);

        $pdfPath = $this->pdfService->generateGoodsReceiptPdf($goodsReceipt);

        $filename = "Priemnica_{$goodsReceipt->receipt_number}.pdf";
        $filename = str_replace(['/', '\\', ' '], '_', $filename);

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview goods receipt PDF in browser
     */
    public function goodsReceiptPreview(GoodsReceipt $goodsReceipt): BinaryFileResponse
    {
        abort_if($goodsReceipt->user_id !== auth()->id(), 403);

        $pdfPath = $this->pdfService->generateGoodsReceiptPdf($goodsReceipt);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Priemnica_' . $goodsReceipt->receipt_number . '.pdf"',
        ])->deleteFileAfterSend(true);
    }
}
