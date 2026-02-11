<?php

namespace App\Http\Controllers;

use App\Mail\DocumentMail;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ProformaInvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
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

        return Inertia::render('ProformaInvoices/Index', [
            'proformas' => $proformas,
            'clients' => $clients,
            'showDeleted' => $showDeleted,
            'filters' => $request->only(['proforma', 'client', 'status', 'date_from', 'date_to', 'per_page', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = ProformaInvoice::getNextSequence($request->user()->id, $currentYear);

        return Inertia::render('ProformaInvoices/Create', [
            'clients' => $clients,
            'articles' => $articles,
            'currentYear' => $currentYear,
            'nextSequence' => $nextSequence,
        ]);
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

        $exists = ProformaInvoice::withTrashed()
            ->where('user_id', $request->user()->id)
            ->where('proforma_year', $proformaYear)
            ->where('proforma_sequence', $validated['proforma_sequence'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'proforma_sequence' => __('proforma.duplicate_number_error'),
            ]);
        }

        $proforma = $request->user()->proformaInvoices()->create([
            'proforma_number' => ProformaInvoice::formatProformaNumber($validated['proforma_prefix'] ?? null, $proformaYear, $validated['proforma_sequence']),
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

    public function show(ProformaInvoice $proformaInvoice): Response
    {
        $this->authorize('view', $proformaInvoice);

        $proformaInvoice->load(['client', 'items', 'user.agency', 'user.bankAccounts', 'convertedInvoice']);

        return Inertia::render('ProformaInvoices/Show', [
            'proforma' => $proformaInvoice,
        ]);
    }

    public function edit(ProformaInvoice $proformaInvoice, Request $request): Response
    {
        $this->authorize('update', $proformaInvoice);

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();
        $proformaInvoice->load('items');

        return Inertia::render('ProformaInvoices/Edit', [
            'proforma' => $proformaInvoice,
            'clients' => $clients,
            'articles' => $articles,
        ]);
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

        $exists = ProformaInvoice::withTrashed()
            ->where('user_id', $request->user()->id)
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
            'proforma_number' => ProformaInvoice::formatProformaNumber($validated['proforma_prefix'] ?? null, $proformaYear, $validated['proforma_sequence']),
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

    public function duplicate(ProformaInvoice $proformaInvoice, Request $request): Response
    {
        $this->authorize('view', $proformaInvoice);

        $proformaInvoice->load('items');
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();
        $articles = $request->user()->articles()->where('is_active', true)->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = ProformaInvoice::getNextSequence($request->user()->id, $currentYear);

        return Inertia::render('ProformaInvoices/Create', [
            'proforma' => $proformaInvoice,
            'clients' => $clients,
            'articles' => $articles,
            'currentYear' => $currentYear,
            'nextSequence' => $nextSequence,
            'isDuplicate' => true,
        ]);
    }

    public function updateStatus(Request $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $this->authorize('update', $proformaInvoice);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,converted_to_invoice'],
        ]);

        $proformaInvoice->update(['status' => $validated['status']]);

        return back()->with('success', __('toast.proforma_updated'));
    }

    public function send(Request $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $this->authorize('view', $proformaInvoice);

        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $pdfService = new PdfService();
        $pdfPath = $pdfService->generateProformaPdf($proformaInvoice);

        try {
            $mail = new DocumentMail(
                docNumber: $proformaInvoice->proforma_number,
                docLabel: __('proforma.proforma'),
                issueDate: $proformaInvoice->issue_date?->format('d.m.Y'),
                dueDate: $proformaInvoice->valid_until?->format('d.m.Y'),
                dueDateLabel: __('proforma.valid_until'),
                total: number_format($proformaInvoice->total, 2),
                currency: $proformaInvoice->currency,
                bodyText: $validated['body'],
                pdfPath: $pdfPath,
                pdfFilename: $proformaInvoice->proforma_number . '.pdf',
            );
            $mail->subject($validated['subject']);

            Mail::to($validated['to'])->send($mail);

            if ($proformaInvoice->status === 'draft') {
                $proformaInvoice->update(['status' => 'sent']);
            }
        } finally {
            $pdfService->cleanup($pdfPath);
        }

        return back()->with('success', __('toast.proforma_sent_email'));
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
            'invoice_number' => Invoice::formatInvoiceNumber($proformaInvoice->proforma_prefix, $currentYear, $nextSequence),
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
