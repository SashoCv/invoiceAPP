<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: [
                'create', 'store', 'edit', 'update', 'destroy',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->clients()
            ->withCount(['invoices', 'proformaInvoices', 'contracts']);

        // Search by name, company, email, or tax number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('company', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('tax_number', 'like', '%' . $search . '%');
            });
        }

        // Filter by city
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        // Sorting
        $sortBy = $request->get('sort', 'company');
        $sortDir = $request->get('dir', 'asc');
        $allowedSorts = ['name', 'company', 'city', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }
        if ($sortBy === 'company') {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $clients = $query->paginate($perPage)->withQueryString();

        $archivedCount = $request->user()->clients()->onlyTrashed()->count();

        // Get unique cities for filter dropdown
        $cities = $request->user()->clients()->whereNotNull('city')->distinct()->pluck('city')->sort();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'archivedCount' => $archivedCount,
            'cities' => $cities->values()->all(),
            'filters' => $request->only(['search', 'city', 'per_page', 'sort', 'dir']),
        ]);
    }

    public function archived(Request $request): Response
    {
        $clients = $request->user()->clients()
            ->onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return Inertia::render('Clients/Archived', [
            'clients' => $clients,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id)->whereNull('deleted_at');
                }),
            ],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $request->user()->clients()->create($validated);

        return redirect()->route('clients.index')->with('success', __('toast.client_created'));
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        // Invoices paginated, recent first
        $invoices = $client->invoices()
            ->select('id', 'client_id', 'invoice_number', 'issue_date', 'due_date', 'status', 'currency', 'total')
            ->orderBy('issue_date', 'desc')
            ->paginate(10);

        // Statistics
        $baseQuery = $client->invoices()->where('status', '!=', 'cancelled');
        $stats = [
            'total_invoiced' => (float) (clone $baseQuery)->sum('total'),
            'total_paid' => (float) $client->invoices()->where('status', 'paid')->sum('total'),
            'total_unpaid' => (float) $client->invoices()->whereIn('status', ['sent', 'unpaid', 'overdue'])->sum('total'),
            'invoice_count' => (int) (clone $baseQuery)->count(),
            'currency' => $client->invoices()
                ->where('status', '!=', 'cancelled')
                ->selectRaw('currency, COUNT(*) as cnt')
                ->groupBy('currency')
                ->orderByDesc('cnt')
                ->value('currency') ?? 'MKD',
        ];

        // Article breakdown - group by description
        $articleBreakdown = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.client_id', $client->id)
            ->where('invoices.status', '!=', 'cancelled')
            ->whereNull('invoices.deleted_at')
            ->select(
                'invoice_items.description as name',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total) as total_amount')
            )
            ->groupBy('invoice_items.description')
            ->orderByDesc('total_amount')
            ->get();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'invoices' => $invoices,
            'stats' => $stats,
            'articleBreakdown' => $articleBreakdown,
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        $contracts = $client->contracts()
            ->where('user_id', auth()->id())
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'contracts' => $contracts,
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'tax_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id)->whereNull('deleted_at');
                })->ignore($client->id),
            ],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', __('toast.client_updated'));
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete(); // Soft delete - moves to archive

        return redirect()->route('clients.index')->with('success', __('toast.client_archived'));
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $client = $request->user()->clients()->onlyTrashed()->findOrFail($id);

        $client->restore();

        return redirect()->route('clients.archived')->with('success', __('toast.client_restored'));
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $client = $request->user()->clients()->onlyTrashed()->findOrFail($id);

        // Check if client has invoices
        if ($client->invoices()->count() > 0) {
            return back()->with('error', __('toast.client_has_invoices'));
        }

        $client->forceDelete();

        return redirect()->route('clients.archived')->with('success', __('toast.client_deleted'));
    }
}
