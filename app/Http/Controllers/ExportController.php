<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function exportInvoices(Request $request): StreamedResponse
    {
        $query = $request->user()->invoices()->with('client');

        // Reuse filter logic from InvoiceController::index
        if ($request->filled('invoice')) {
            $search = $request->invoice;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('invoice_prefix', 'like', '%' . $search . '%')
                  ->orWhere('invoice_sequence', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('client')) {
            $query->where('client_id', $request->client);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            try {
                $dateFrom = Carbon::createFromFormat('d.m.Y', $request->date_from);
                $query->whereDate('issue_date', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        if ($request->filled('date_to')) {
            try {
                $dateTo = Carbon::createFromFormat('d.m.Y', $request->date_to);
                $query->whereDate('issue_date', '<=', $dateTo);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        $statusLabels = [
            'draft' => __('invoices.status_draft'),
            'sent' => __('invoices.status_sent'),
            'unpaid' => __('invoices.status_unpaid'),
            'paid' => __('invoices.status_paid'),
            'overdue' => __('invoices.status_overdue'),
            'cancelled' => __('invoices.status_cancelled'),
        ];

        $headers = [
            __('invoices.number'),
            __('invoices.client'),
            __('invoices.tax_number'),
            __('invoices.issue_date'),
            __('invoices.due_date'),
            __('invoices.status'),
            __('invoices.currency'),
            __('invoices.subtotal'),
            __('invoices.tax'),
            __('invoices.total'),
            __('invoices.paid_date'),
        ];

        return $this->streamCsv('invoices.csv', $headers, $invoices, function ($invoice) use ($statusLabels) {
            return [
                $invoice->invoice_number,
                $invoice->client?->name ?? '',
                $invoice->client?->tax_number ?? '',
                $invoice->issue_date?->format('d.m.Y') ?? '',
                $invoice->due_date?->format('d.m.Y') ?? '',
                $statusLabels[$invoice->status] ?? $invoice->status,
                $invoice->currency,
                $invoice->subtotal,
                $invoice->tax_amount,
                $invoice->total,
                $invoice->paid_date?->format('d.m.Y') ?? '',
            ];
        });
    }

    public function exportExpenses(Request $request): StreamedResponse
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $expenses = $request->user()->expenses()
            ->with('category')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->get();

        $headers = [
            __('expenses.date'),
            __('expenses.name'),
            __('expenses.category'),
            __('expenses.amount'),
            __('expenses.description'),
        ];

        return $this->streamCsv("expenses_{$month}.csv", $headers, $expenses, function ($expense) {
            return [
                $expense->date?->format('d.m.Y') ?? '',
                $expense->name,
                $expense->category?->name ?? '',
                $expense->amount,
                $expense->description ?? '',
            ];
        });
    }

    public function exportClients(Request $request): StreamedResponse
    {
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        $headers = [
            __('clients.name'),
            __('clients.company'),
            __('clients.email'),
            __('clients.phone'),
            __('clients.address'),
            __('clients.city'),
            __('clients.tax_number'),
        ];

        return $this->streamCsv('clients.csv', $headers, $clients, function ($client) {
            return [
                $client->name,
                $client->company ?? '',
                $client->email ?? '',
                $client->phone ?? '',
                $client->address ?? '',
                $client->city ?? '',
                $client->tax_number ?? '',
            ];
        });
    }

    private function streamCsv(string $filename, array $headers, $rows, callable $rowMapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $rowMapper) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel Cyrillic support
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $rowMapper($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
