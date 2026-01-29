<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $agency = $user->agency;

        // Get all bank accounts (both user and agency)
        $bankAccounts = BankAccount::where('user_id', $user->id)
            ->orWhere(function ($query) use ($agency) {
                if ($agency) {
                    $query->where('agency_id', $agency->id);
                }
            })
            ->get();

        return Inertia::render('Settings/BankAccounts', [
            'bankAccounts' => $bankAccounts,
            'hasAgency' => $agency !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'swift' => ['nullable', 'string', 'max:20'],
            'currency' => ['required', 'in:MKD,EUR,USD,GBP,CHF'],
            'type' => ['required', 'in:denar,foreign'],
            'is_default' => ['nullable', 'boolean'],
            'owner_type' => ['required', 'in:user,agency'],
        ]);

        $user = $request->user();
        $ownerType = $validated['owner_type'];
        unset($validated['owner_type']);

        // If this is set as default, unset other defaults for same owner
        if (!empty($validated['is_default'])) {
            if ($ownerType === 'user') {
                $user->bankAccounts()->update(['is_default' => false]);
            } elseif ($ownerType === 'agency' && $user->agency) {
                $user->agency->bankAccounts()->update(['is_default' => false]);
            }
        }

        if ($ownerType === 'user') {
            $user->bankAccounts()->create($validated);
        } elseif ($ownerType === 'agency') {
            if (!$user->agency) {
                return back()->with('error', __('toast.no_agency'));
            }
            $user->agency->bankAccounts()->create($validated);
        }

        return back()->with('success', __('toast.bank_account_created'));
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'swift' => ['nullable', 'string', 'max:20'],
            'currency' => ['required', 'in:MKD,EUR,USD,GBP,CHF'],
            'type' => ['required', 'in:denar,foreign'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        // If this is set as default, unset other defaults for same owner
        if (!empty($validated['is_default'])) {
            if ($bankAccount->user_id) {
                BankAccount::where('user_id', $bankAccount->user_id)
                    ->where('id', '!=', $bankAccount->id)
                    ->update(['is_default' => false]);
            } elseif ($bankAccount->agency_id) {
                BankAccount::where('agency_id', $bankAccount->agency_id)
                    ->where('id', '!=', $bankAccount->id)
                    ->update(['is_default' => false]);
            }
        }

        $bankAccount->update($validated);

        return back()->with('success', __('toast.bank_account_updated'));
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return back()->with('success', __('toast.bank_account_deleted'));
    }
}
