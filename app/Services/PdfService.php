<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Offer;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
use App\Models\StockMovement;
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
        $invoice->load(['client', 'branch', 'items', 'user.agency', 'user.bankAccounts']);

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
            'totalDecimals' => $invoice->currency === 'MKD' ? 0 : 2,
            'notes' => $invoice->notes,
            'client' => $invoice->client,
            'branch' => $invoice->branch,
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
        $proforma->load(['client', 'branch', 'items', 'user.agency', 'user.bankAccounts']);

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
            'totalDecimals' => $proforma->currency === 'MKD' ? 0 : 2,
            'notes' => $proforma->notes,
            'client' => $proforma->client,
            'branch' => $proforma->branch,
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
        $offer->load(['client', 'branch', 'items', 'user.agency', 'user.bankAccounts']);

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
            'totalDecimals' => $offer->currency === 'MKD' ? 0 : 2,
            'notes' => $offer->notes,
            'client' => $offer->client,
            'branch' => $offer->branch,
            'agency' => $offer->user->agency,
            'items' => $offer->has_items ? $offer->items : collect([]),
            'bankAccount' => null,
        ];

        return $this->generatePdf($data, "offer_{$offer->id}");
    }

    /**
     * Generate PDF for a goods issue
     */
    public function generateGoodsIssuePdf(GoodsIssue $goodsIssue): string
    {
        $goodsIssue->load(['client', 'movements.article', 'user.agency']);

        $agency = $goodsIssue->user->agency;

        $items = $goodsIssue->movements->map(function ($movement) {
            $quantity = (float) $movement->quantity;
            $costPrice = (float) ($movement->cost_price ?? 0);
            $taxRate = (float) ($movement->tax_rate ?? 0);
            $base = $quantity * $costPrice;
            $vat = $base * $taxRate / 100;

            return [
                'name' => $movement->article->name ?? '-',
                'unit' => $movement->article->unit ?? '',
                'quantity' => $quantity,
                'quantity_before' => (float) $movement->quantity_before,
                'quantity_after' => (float) $movement->quantity_after,
                'cost_price' => $costPrice,
                'tax_rate' => $taxRate,
                'base' => $base,
                'vat' => $vat,
                'total' => $base + $vat,
            ];
        });

        $data = [
            'title' => 'Испратница ' . $goodsIssue->issue_number,
            'docTitle' => 'ИСПРАТНИЦА',
            'docNumber' => $goodsIssue->issue_number,
            'issueDate' => $goodsIssue->date?->format('d.m.Y'),
            'notes' => $goodsIssue->notes,
            'client' => $goodsIssue->client,
            'agency' => $agency,
            'items' => $items,
            'subtotal' => $items->sum('base'),
            'totalVat' => $items->sum('vat'),
            'grandTotal' => $items->sum('total'),
        ];

        return $this->generateGoodsIssuePdfFile($data, "goods_issue_{$goodsIssue->id}");
    }

    /**
     * Generate goods issue PDF using configured engine
     */
    protected function generateGoodsIssuePdfFile(array $data, string $filename): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/' . $filename . '_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.goods-issue', $data)->setPaper('a4');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.goods-issue-browsershot', $data)->render();

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
     * Generate a goods receipt (приемница) PDF.
     */
    public function generateGoodsReceiptPdf(GoodsReceipt $goodsReceipt): string
    {
        $goodsReceipt->load(['movements.article', 'user.agency']);

        $items = $goodsReceipt->movements->map(function ($movement) {
            $quantity = (float) $movement->quantity;
            $costPrice = (float) ($movement->cost_price ?? 0);
            $taxRate = (float) ($movement->tax_rate ?? 0);
            $base = $quantity * $costPrice;
            $vat = $base * $taxRate / 100;

            return [
                'name' => $movement->article->name ?? '-',
                'unit' => $movement->article->unit ?? '',
                'quantity' => $quantity,
                'cost_price' => $costPrice,
                'tax_rate' => $taxRate,
                'base' => $base,
                'vat' => $vat,
                'total' => $base + $vat,
            ];
        });

        $data = [
            'agency' => $goodsReceipt->user->agency,
            'docNumber' => $goodsReceipt->receipt_number,
            'receiptDate' => $goodsReceipt->date?->format('d.m.Y'),
            'notes' => $goodsReceipt->notes,
            'items' => $items,
            'subtotal' => $items->sum('base'),
            'totalVat' => $items->sum('vat'),
            'grandTotal' => $items->sum('total'),
        ];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/goods_receipt_' . $goodsReceipt->id . '_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.goods-receipt', $data)->setPaper('a4');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.goods-receipt', $data)->render();

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
     * Generate an output calculation (Излезна калкулација) PDF for an invoice.
     *
     * Purchase cost per article is the weighted average cost from goods receipts
     * (SUM(cost_price*qty)/SUM(qty)) — the same basis as the profitability report.
     */
    public function generateOutputCalculationPdf(Invoice $invoice): string
    {
        $invoice->load(['items.article', 'items.bundle.bundleItems.article', 'client', 'user.agency']);

        $articleIds = $invoice->items->pluck('article_id')->filter();

        // Bundle line items have no article_id of their own — their cost is derived
        // from their component articles, so those need average costs too.
        foreach ($invoice->items as $item) {
            if ($item->bundle) {
                $articleIds = $articleIds->merge($item->bundle->bundleItems->pluck('article_id'));
            }
        }
        $articleIds = $articleIds->unique()->values();

        $avgCosts = $articleIds->isEmpty()
            ? collect()
            : StockMovement::whereIn('article_id', $articleIds)
                ->where('type', 'receipt')
                ->where('cost_price', '>', 0)
                ->selectRaw('article_id, SUM(cost_price * quantity) / SUM(quantity) as avg_cost')
                ->groupBy('article_id')
                ->pluck('avg_cost', 'article_id');

        $rows = [];
        $tariffs = [];

        foreach ($invoice->items as $item) {
            $qty = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;       // selling price, без ДДВ
            $taxRate = (float) $item->tax_rate;
            $discountPct = (float) $item->discount;

            if ($item->bundle) {
                // Bundle cost = sum of each component's average cost × its quantity in the set.
                $avgCost = 0.0;
                foreach ($item->bundle->bundleItems as $bundleItem) {
                    $avgCost += (float) ($avgCosts[$bundleItem->article_id] ?? 0) * (float) $bundleItem->quantity;
                }
                $code = $item->code ?: ($item->bundle->code ?: ($item->bundle->sku ?? ''));
                $unit = 'компл.';
            } else {
                $avgCost = (float) ($avgCosts[$item->article_id] ?? 0); // покупна цена по единица, без ДДВ
                $code = $item->code ?: ($item->article->code ?? '');
                $unit = $item->article->unit ?? '';
            }

            $purchaseAmount = $avgCost * $qty;
            $transferredTax = $purchaseAmount * $taxRate / 100;

            // Recompute from price/qty/discount/tax (same basis as InvoiceItem::booted)
            // so the calculation is always self-consistent regardless of stored totals.
            $grossNoTax = $qty * $unitPrice;
            $discountAmount = $grossNoTax * $discountPct / 100;
            $salesNoTax = $grossNoTax - $discountAmount;           // продажна без ДДВ (по рабат)
            $calcTax = $salesNoTax * $taxRate / 100;               // пресметан данок
            $salesWithTax = $salesNoTax + $calcTax;                // продажна со ДДВ (по рабат)
            $margin = $salesNoTax - $purchaseAmount;

            $row = [
                'code' => $code,
                'unit' => $unit,
                'name' => $item->description ?: ($item->bundle->name ?? $item->article->name ?? ''),
                'quantity' => $qty,
                'purchase_unit' => $avgCost,
                'purchase_amount' => round($purchaseAmount, 2),
                'transferred_tax' => round($transferredTax, 2),
                'discount' => round($discountAmount, 2),
                'sales_unit' => round($unitPrice * (1 + $taxRate / 100), 2),
                'sales_amount' => round($salesWithTax, 2),
                'calc_tax' => round($calcTax, 2),
                'margin' => round($margin, 2),
                'tax_rate' => $taxRate,
            ];
            $rows[] = $row;

            // Accumulate the tariff summary from the rounded row values so the
            // summary totals reconcile exactly with the line-item totals.
            $key = (string) $taxRate;
            if (!isset($tariffs[$key])) {
                $tariffs[$key] = [
                    'rate' => $taxRate,
                    'purchase_amount' => 0,
                    'discount' => 0,
                    'sales_no_tax' => 0,
                    'tax' => 0,
                    'sales_with_tax' => 0,
                    'margin' => 0,
                ];
            }
            $tariffs[$key]['purchase_amount'] += $row['purchase_amount'];
            $tariffs[$key]['discount'] += $row['discount'];
            $tariffs[$key]['sales_no_tax'] += $row['sales_amount'] - $row['calc_tax'];
            $tariffs[$key]['tax'] += $row['calc_tax'];
            $tariffs[$key]['sales_with_tax'] += $row['sales_amount'];
            $tariffs[$key]['margin'] += $row['margin'];
        }

        ksort($tariffs);

        $totals = [
            'purchase_amount' => round(array_sum(array_column($rows, 'purchase_amount')), 2),
            'transferred_tax' => round(array_sum(array_column($rows, 'transferred_tax')), 2),
            'discount' => round(array_sum(array_column($rows, 'discount')), 2),
            'sales_amount' => round(array_sum(array_column($rows, 'sales_amount')), 2),
            'calc_tax' => round(array_sum(array_column($rows, 'calc_tax')), 2),
            'margin' => round(array_sum(array_column($rows, 'margin')), 2),
        ];

        $data = [
            'agency' => $invoice->user->agency,
            'calcNumber' => sprintf('%04d', $invoice->id),
            'documentNumber' => $invoice->invoice_number,
            'date' => $invoice->issue_date?->format('d.m.Y'),
            'client' => $invoice->client,
            'rows' => $rows,
            'tariffs' => array_values($tariffs),
            'totals' => $totals,
        ];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/output_calculation_' . $invoice->id . '_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.output-calculation', $data)->setPaper('a4', 'landscape');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.output-calculation', $data)->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape()
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
     * Generate the daily financial report (дневна продажба на артикли) PDF.
     */
    public function generateDailyFinancialReportPdf(array $data): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/daily_financial_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.daily-financial-report', $data)->setPaper('a4', 'landscape');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.daily-financial-report', $data)->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape()
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
     * Generate the trade ledger (Образец ЕТ — Евиденција во трговија) PDF.
     */
    public function generateTradeLedgerPdf(array $data): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/trade_ledger_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.trade-ledger', $data)->setPaper('a4', 'landscape');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.trade-ledger', $data)->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape()
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
     * Generate an inventory (warehouse) list PDF.
     */
    public function generateInventoryPdf(array $data): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfFile = $tempDir . '/inventory_list_' . Str::random(8) . '.pdf';

        $engine = config('app.pdf_engine', 'browsershot');

        if ($engine === 'dompdf') {
            $pdf = Pdf::loadView('pdf.inventory-list', $data)->setPaper('a4');
            file_put_contents($pdfFile, $pdf->output());
            return $pdfFile;
        }

        $html = view('pdf.inventory-list', $data)->render();

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
