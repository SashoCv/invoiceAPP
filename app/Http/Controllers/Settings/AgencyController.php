<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: ['update']),
        ];
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Agency', [
            'agency' => $request->user()->agency,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'display_currency' => ['nullable', 'string', 'in:MKD,EUR,USD'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $agency = $request->user()->agency;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($agency?->logo) {
                Storage::disk('public')->delete($agency->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        } else {
            unset($validated['logo']);
        }

        // Handle logo removal
        if ($request->boolean('remove_logo') && $agency?->logo) {
            Storage::disk('public')->delete($agency->logo);
            $validated['logo'] = null;
        }

        if ($agency) {
            $agency->update($validated);
        } else {
            $request->user()->agency()->create($validated);
        }

        return back()->with('success', __('toast.agency_updated'));
    }
}
