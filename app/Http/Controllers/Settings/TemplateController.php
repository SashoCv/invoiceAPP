<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public static array $templates = [
        'classic' => [
            'name' => 'Classic',
            'description' => 'Clean and professional layout',
        ],
        'modern' => [
            'name' => 'Modern',
            'description' => 'Contemporary design with accent colors',
        ],
        'minimal' => [
            'name' => 'Minimal',
            'description' => 'Simple and elegant style',
        ],
    ];

    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Templates', [
            'templates' => self::$templates,
            'currentInvoiceTemplate' => $request->user()->invoice_template ?? 'classic',
            'currentProformaTemplate' => $request->user()->proforma_template ?? 'classic',
            'currentOfferTemplate' => $request->user()->offer_template ?? 'classic',
            'agency' => $request->user()->agency,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_template' => ['required', 'in:' . implode(',', array_keys(self::$templates))],
            'proforma_template' => ['required', 'in:' . implode(',', array_keys(self::$templates))],
            'offer_template' => ['required', 'in:' . implode(',', array_keys(self::$templates))],
        ]);

        $request->user()->update($validated);

        return back()->with('success', __('toast.template_updated'));
    }
}
