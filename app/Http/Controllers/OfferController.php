<?php

namespace App\Http\Controllers;

use App\Mail\DocumentMail;
use App\Models\Invoice;
use App\Models\Offer;
use App\Services\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $query = $request->user()->offers()->with('client');

        $showDeleted = $request->get('deleted') === '1';
        if ($showDeleted) {
            $query->onlyTrashed();
        }

        if ($request->filled('offer')) {
            $search = $request->offer;
            $query->where(function ($q) use ($search) {
                $q->where('offer_number', 'like', '%' . $search . '%')
                  ->orWhere('offer_prefix', 'like', '%' . $search . '%')
                  ->orWhere('offer_sequence', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%');
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
        $allowedSorts = ['offer_number', 'issue_date', 'valid_until', 'status', 'total', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $offers = $query->paginate($perPage)->withQueryString();

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        return Inertia::render('Offers/Index', [
            'offers' => $offers,
            'clients' => $clients,
            'showDeleted' => $showDeleted,
            'filters' => $request->only(['offer', 'client', 'status', 'date_from', 'date_to', 'per_page', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = Offer::getNextSequence($request->user()->id, $currentYear);

        return Inertia::render('Offers/Create', [
            'clients' => $clients,
            'currentYear' => $currentYear,
            'nextSequence' => $nextSequence,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $offerYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'offer_prefix' => ['nullable', 'string', 'max:20'],
            'offer_sequence' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        $exists = Offer::withTrashed()
            ->where('user_id', $request->user()->id)
            ->where('offer_year', $offerYear)
            ->where('offer_sequence', $validated['offer_sequence'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'offer_sequence' => __('offers.duplicate_number_error'),
            ]);
        }

        $request->user()->offers()->create([
            'offer_number' => Offer::formatOfferNumber($validated['offer_prefix'] ?? null, $offerYear, $validated['offer_sequence']),
            'offer_prefix' => $validated['offer_prefix'],
            'offer_sequence' => $validated['offer_sequence'],
            'offer_year' => $offerYear,
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'has_items' => false,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => $validated['total'],
        ]);

        return redirect()->route('offers.index')->with('success', __('toast.offer_created'));
    }

    public function show(Offer $offer): Response
    {
        $this->authorize('view', $offer);

        $offer->load(['client', 'items', 'user.agency', 'user.bankAccounts', 'convertedInvoice']);

        return Inertia::render('Offers/Show', [
            'offer' => $offer,
        ]);
    }

    public function edit(Offer $offer, Request $request): Response
    {
        $this->authorize('update', $offer);

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        return Inertia::render('Offers/Edit', [
            'offer' => $offer,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);

        $issueDate = \Carbon\Carbon::parse($request->issue_date);
        $offerYear = (int) $issueDate->year;

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issue_date'],
            'offer_prefix' => ['nullable', 'string', 'max:20'],
            'offer_sequence' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        $exists = Offer::withTrashed()
            ->where('user_id', $request->user()->id)
            ->where('offer_year', $offerYear)
            ->where('offer_sequence', $validated['offer_sequence'])
            ->where('id', '!=', $offer->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'offer_sequence' => __('offers.duplicate_number_error'),
            ]);
        }

        $offer->items()->delete();

        $offer->update([
            'offer_number' => Offer::formatOfferNumber($validated['offer_prefix'] ?? null, $offerYear, $validated['offer_sequence']),
            'client_id' => $validated['client_id'],
            'currency' => $validated['currency'],
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'offer_prefix' => $validated['offer_prefix'],
            'offer_sequence' => $validated['offer_sequence'],
            'offer_year' => $offerYear,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'has_items' => false,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => $validated['total'],
        ]);

        return redirect()->route('offers.show', $offer)->with('success', __('toast.offer_updated'));
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);

        $offer->items()->delete();
        $offer->delete();

        return redirect()->route('offers.index')->with('success', __('toast.offer_deleted'));
    }

    public function duplicate(Offer $offer, Request $request): Response
    {
        $this->authorize('view', $offer);

        $clients = $request->user()->clients()->orderBy('company')->orderBy('name')->get();

        $currentYear = (int) date('Y');
        $nextSequence = Offer::getNextSequence($request->user()->id, $currentYear);

        return Inertia::render('Offers/Create', [
            'offer' => $offer,
            'clients' => $clients,
            'currentYear' => $currentYear,
            'nextSequence' => $nextSequence,
            'isDuplicate' => true,
        ]);
    }

    public function updateStatus(Request $request, Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
        ]);

        $offer->update(['status' => $validated['status']]);

        return back()->with('success', __('toast.offer_updated'));
    }

    public function send(Request $request, Offer $offer): RedirectResponse
    {
        $this->authorize('view', $offer);

        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $pdfService = new PdfService();
        $pdfPath = $pdfService->generateOfferPdf($offer);

        try {
            $mail = new DocumentMail(
                docNumber: $offer->offer_number,
                docLabel: __('offers.offer'),
                issueDate: $offer->issue_date?->format('d.m.Y'),
                dueDate: $offer->valid_until?->format('d.m.Y'),
                dueDateLabel: __('offers.valid_until'),
                total: number_format($offer->total, 2),
                currency: $offer->currency,
                bodyText: $validated['body'],
                pdfPath: $pdfPath,
                pdfFilename: $offer->offer_number . '.pdf',
            );
            $mail->subject($validated['subject']);

            Mail::to($validated['to'])->send($mail);

            if ($offer->status === 'draft') {
                $offer->update(['status' => 'sent']);
            }
        } finally {
            $pdfService->cleanup($pdfPath);
        }

        return back()->with('success', __('toast.offer_sent_email'));
    }

    public function restore(int $id, Request $request): RedirectResponse
    {
        $offer = Offer::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $offer->restore();

        return redirect()->route('offers.show', $offer)->with('success', __('toast.offer_restored'));
    }

    public function forceDelete(int $id, Request $request): RedirectResponse
    {
        $offer = Offer::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $offer->items()->delete();
        $offer->forceDelete();

        return redirect()->route('offers.index', ['deleted' => 1])->with('success', __('toast.offer_permanently_deleted'));
    }

    public function accept(Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);

        $offer->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return back()->with('success', __('toast.offer_accepted'));
    }

    public function reject(Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);

        $offer->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return back()->with('success', __('toast.offer_rejected'));
    }

    public function convertToInvoice(Offer $offer, Request $request): RedirectResponse
    {
        $this->authorize('update', $offer);

        if ($offer->isConverted()) {
            return back()->with('error', __('toast.offer_already_converted'));
        }

        $currentYear = (int) date('Y');
        $nextSequence = Invoice::getNextSequence($request->user()->id, $currentYear);

        $invoice = $request->user()->invoices()->create([
            'invoice_number' => Invoice::formatInvoiceNumber($offer->offer_prefix, $currentYear, $nextSequence),
            'invoice_prefix' => $offer->offer_prefix,
            'invoice_sequence' => $nextSequence,
            'invoice_year' => $currentYear,
            'client_id' => $offer->client_id,
            'currency' => $offer->currency,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'tax_rate' => 0,
            'notes' => $offer->notes,
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        if ($offer->has_items) {
            foreach ($offer->items as $item) {
                $invoice->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                ]);
            }
            $invoice->calculateTotals();
        }

        $offer->update([
            'converted_invoice_id' => $invoice->id,
            'converted_at' => now(),
        ]);

        return redirect()->route('invoices.edit', $invoice)->with('success', __('toast.offer_converted'));
    }
}
