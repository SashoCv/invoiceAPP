<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function index(Request $request): View
    {
        return view('settings.templates', [
            'templates' => self::$templates,
            'currentTemplate' => $request->user()->invoice_template,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['required', 'in:' . implode(',', array_keys(self::$templates))],
        ]);

        $request->user()->update([
            'invoice_template' => $validated['template'],
        ]);

        return back()->with('success', __('toast.template_updated'));
    }
}
