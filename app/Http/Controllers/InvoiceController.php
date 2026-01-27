<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $query = $request->user()->invoices()->with('client');

        // Filter by deleted status
        $showDeleted = $request->get('deleted') === '1';
        if ($showDeleted) {
            $query->onlyTrashed();
        }

        // Filter by invoice number (search in formatted number parts)
        if ($request->filled('invoice')) {
            $search = $request->invoice;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('invoice_prefix', 'like', '%' . $search . '%')
                  ->orWhere('invoice_sequence', 'like', '%' . $search . '%');
            });
        }

        // Filter by client
        if ($request->filled('client')) {
            $query->where('client_id', $request->client);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range (format: d.m.Y)
        if ($request->filled('date_from')) {
            try {
                $dateFrom = \Carbon\Carbon::createFromFormat('d.m.Y', $request->date_from);
                $query->whereDate('issue_date', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }
        if ($request->filled('date_to')) {
            try {
                $dateTo = \Carbon\Carbon::createFromFormat('d.m.Y', $request->date_to);
                $query->whereDate('issue_date', '<=', $dateTo);
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['invoice_number', 'issue_date', 'due_date', 'status', 'total', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $invoices = $query->paginate($perPage)->withQueryString();

        // Get clients for filter dropdown
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        return view('invoices.index', compact('invoices', 'clients', 'showDeleted'));
    }

    public function create(Request $request): View
    {
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = Invoice::getNextSequence($request->user()->id, $currentYear);

        return view('invoices.create', compact('clients', 'articles', 'currentYear', 'nextSequence'));
    }

    public function store(Request $request): RedirectResponse
    {
        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $invoiceYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_sequence' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // Check for duplicate sequence number
        $exists = Invoice::where('user_id', $request->user()->id)
            ->where('invoice_year', $invoiceYear)
            ->where('invoice_sequence', $validated['invoice_sequence'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'invoice_sequence' => __('invoices.duplicate_number_error'),
            ]);
        }

        $invoice = $request->user()->invoices()->create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_prefix' => $validated['invoice_prefix'],
            'invoice_sequence' => $validated['invoice_sequence'],
            'invoice_year' => $invoiceYear,
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'tax_rate' => 0,
            'notes' => $validated['notes'],
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
            ]);
        }

        $invoice->calculateTotals();

        return redirect()->route('invoices.show', $invoice)->with('success', __('toast.invoice_created'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'user.agency']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice, Request $request): View
    {
        $this->authorize('update', $invoice);

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();
        $invoice->load('items');

        return view('invoices.edit', compact('invoice', 'clients', 'articles'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $invoiceYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_sequence' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,sent,unpaid,paid,overdue,cancelled'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // Check for duplicate sequence number (excluding current invoice)
        $exists = Invoice::where('user_id', $request->user()->id)
            ->where('invoice_year', $invoiceYear)
            ->where('invoice_sequence', $validated['invoice_sequence'])
            ->where('id', '!=', $invoice->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'invoice_sequence' => __('invoices.duplicate_number_error'),
            ]);
        }

        $invoice->update([
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'invoice_prefix' => $validated['invoice_prefix'],
            'invoice_sequence' => $validated['invoice_sequence'],
            'invoice_year' => $invoiceYear,
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'paid_date' => $validated['status'] === 'paid' ? now() : null,
        ]);

        // Delete old items and create new ones
        $invoice->items()->delete();

        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
            ]);
        }

        $invoice->calculateTotals();

        return redirect()->route('invoices.show', $invoice)->with('success', __('toast.invoice_updated'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->items()->delete();
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', __('toast.invoice_deleted'));
    }

    public function duplicate(Invoice $invoice, Request $request): View
    {
        $this->authorize('view', $invoice);

        $invoice->load('items');
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = Invoice::getNextSequence($request->user()->id, $currentYear);

        return view('invoices.duplicate', compact('invoice', 'clients', 'articles', 'currentYear', 'nextSequence'));
    }

    public function restore(int $id, Request $request): RedirectResponse
    {
        $invoice = Invoice::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $invoice->restore();

        return redirect()->route('invoices.show', $invoice)->with('success', __('toast.invoice_restored'));
    }

    public function forceDelete(int $id, Request $request): RedirectResponse
    {
        $invoice = Invoice::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $invoice->items()->delete();
        $invoice->forceDelete();

        return redirect()->route('invoices.index', ['deleted' => 1])->with('success', __('toast.invoice_permanently_deleted'));
    }
}
