<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProformaInvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $query = $request->user()->proformaInvoices()->with('client');

        $showDeleted = $request->get('deleted') === '1';
        if ($showDeleted) {
            $query->onlyTrashed();
        }

        if ($request->filled('proforma')) {
            $search = $request->proforma;
            $query->where(function ($q) use ($search) {
                $q->where('proforma_number', 'like', '%' . $search . '%')
                  ->orWhere('proforma_prefix', 'like', '%' . $search . '%')
                  ->orWhere('proforma_sequence', 'like', '%' . $search . '%');
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
                $dateFrom = \Carbon\Carbon::createFromFormat('d.m.Y', $request->date_from);
                $query->whereDate('issue_date', '>=', $dateFrom);
            } catch (\Exception $e) {
            }
        }
        if ($request->filled('date_to')) {
            try {
                $dateTo = \Carbon\Carbon::createFromFormat('d.m.Y', $request->date_to);
                $query->whereDate('issue_date', '<=', $dateTo);
            } catch (\Exception $e) {
            }
        }

        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['proforma_number', 'issue_date', 'valid_until', 'status', 'total', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $proformas = $query->paginate($perPage)->withQueryString();

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        return view('proforma-invoices.index', compact('proformas', 'clients', 'showDeleted'));
    }

    public function create(Request $request): View
    {
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = ProformaInvoice::getNextSequence($request->user()->id, $currentYear);

        return view('proforma-invoices.create', compact('clients', 'articles', 'currentYear', 'nextSequence'));
    }

    public function store(Request $request): RedirectResponse
    {
        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $proformaYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'proforma_prefix' => ['nullable', 'string', 'max:20'],
            'proforma_sequence' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $exists = ProformaInvoice::where('user_id', $request->user()->id)
            ->where('proforma_year', $proformaYear)
            ->where('proforma_sequence', $validated['proforma_sequence'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'proforma_sequence' => __('proforma.duplicate_number_error'),
            ]);
        }

        $proforma = $request->user()->proformaInvoices()->create([
            'proforma_number' => uniqid('PRO'),
            'proforma_prefix' => $validated['proforma_prefix'],
            'proforma_sequence' => $validated['proforma_sequence'],
            'proforma_year' => $proformaYear,
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'notes' => $validated['notes'],
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        foreach ($validated['items'] as $item) {
            $proforma->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
            ]);
        }

        $proforma->calculateTotals();

        return redirect()->route('proforma-invoices.show', $proforma)->with('success', __('toast.proforma_created'));
    }

    public function show(ProformaInvoice $proformaInvoice): View
    {
        $this->authorize('view', $proformaInvoice);

        $proformaInvoice->load(['client', 'items', 'user.agency', 'convertedInvoice']);

        return view('proforma-invoices.show', compact('proformaInvoice'));
    }

    public function edit(ProformaInvoice $proformaInvoice, Request $request): View
    {
        $this->authorize('update', $proformaInvoice);

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();
        $proformaInvoice->load('items');

        return view('proforma-invoices.edit', compact('proformaInvoice', 'clients', 'articles'));
    }

    public function update(Request $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $this->authorize('update', $proformaInvoice);

        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $proformaYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'proforma_prefix' => ['nullable', 'string', 'max:20'],
            'proforma_sequence' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,sent,converted_to_invoice'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $exists = ProformaInvoice::where('user_id', $request->user()->id)
            ->where('proforma_year', $proformaYear)
            ->where('proforma_sequence', $validated['proforma_sequence'])
            ->where('id', '!=', $proformaInvoice->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'proforma_sequence' => __('proforma.duplicate_number_error'),
            ]);
        }

        $proformaInvoice->update([
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'proforma_prefix' => $validated['proforma_prefix'],
            'proforma_sequence' => $validated['proforma_sequence'],
            'proforma_year' => $proformaYear,
            'notes' => $validated['notes'],
            'status' => $validated['status'],
        ]);

        $proformaInvoice->items()->delete();

        foreach ($validated['items'] as $item) {
            $proformaInvoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
            ]);
        }

        $proformaInvoice->calculateTotals();

        return redirect()->route('proforma-invoices.show', $proformaInvoice)->with('success', __('toast.proforma_updated'));
    }

    public function destroy(ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $this->authorize('delete', $proformaInvoice);

        $proformaInvoice->items()->delete();
        $proformaInvoice->delete();

        return redirect()->route('proforma-invoices.index')->with('success', __('toast.proforma_deleted'));
    }

    public function duplicate(ProformaInvoice $proformaInvoice, Request $request): View
    {
        $this->authorize('view', $proformaInvoice);

        $proformaInvoice->load('items');
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = ProformaInvoice::getNextSequence($request->user()->id, $currentYear);

        return view('proforma-invoices.duplicate', compact('proformaInvoice', 'clients', 'articles', 'currentYear', 'nextSequence'));
    }

    public function restore(int $id, Request $request): RedirectResponse
    {
        $proforma = ProformaInvoice::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $proforma->restore();

        return redirect()->route('proforma-invoices.show', $proforma)->with('success', __('toast.proforma_restored'));
    }

    public function forceDelete(int $id, Request $request): RedirectResponse
    {
        $proforma = ProformaInvoice::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $proforma->items()->delete();
        $proforma->forceDelete();

        return redirect()->route('proforma-invoices.index', ['deleted' => 1])->with('success', __('toast.proforma_permanently_deleted'));
    }

    public function convertToInvoice(ProformaInvoice $proformaInvoice, Request $request): RedirectResponse
    {
        $this->authorize('update', $proformaInvoice);

        if ($proformaInvoice->isConverted()) {
            return back()->with('error', __('toast.proforma_already_converted'));
        }

        $currentYear = (int) date('Y');
        $nextSequence = Invoice::getNextSequence($request->user()->id, $currentYear);

        $invoice = $request->user()->invoices()->create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_prefix' => $proformaInvoice->proforma_prefix,
            'invoice_sequence' => $nextSequence,
            'invoice_year' => $currentYear,
            'client_id' => $proformaInvoice->client_id,
            'currency' => $proformaInvoice->currency,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'tax_rate' => 0,
            'notes' => $proformaInvoice->notes,
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        foreach ($proformaInvoice->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
            ]);
        }

        $invoice->calculateTotals();

        $proformaInvoice->update([
            'status' => 'converted_to_invoice',
            'converted_invoice_id' => $invoice->id,
            'converted_at' => now(),
        ]);

        return redirect()->route('invoices.edit', $invoice)->with('success', __('toast.proforma_converted'));
    }
}
