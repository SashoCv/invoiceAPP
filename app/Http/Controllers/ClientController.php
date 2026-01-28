<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
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

        return view('clients.index', compact('clients', 'archivedCount', 'cities'));
    }

    public function archived(Request $request): View
    {
        $clients = $request->user()->clients()
            ->onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('clients.archived', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
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
        ]);

        $request->user()->clients()->create($validated);

        return redirect()->route('clients.index')->with('success', __('toast.client_created'));
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        $contracts = $client->contracts()
            ->where('user_id', auth()->id())
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return view('clients.edit', compact('client', 'contracts'));
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
