<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: ['update']),
        ];
    }

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
        'formal' => [
            'name' => 'Formal',
            'description' => 'Traditional Macedonian invoice format',
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

    public function preview(Request $request): View
    {
        $request->validate([
            'template' => ['required', 'in:classic,modern,minimal,formal'],
            'type' => ['required', 'in:invoice,offer'],
        ]);

        $template = $request->get('template');
        $type = $request->get('type');
        $agency = $request->user()->agency;

        $currencySymbols = [
            'MKD' => 'ден.', 'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF',
        ];

        $sampleClient = (object) [
            'name' => 'Примерна Компанија ДООЕЛ',
            'company' => 'Примерна Компанија ДООЕЛ',
            'address' => 'ул. Примерна бр. 123',
            'city' => 'Скопје',
            'postal_code' => '1000',
            'email' => 'info@primer.mk',
            'tax_number' => 'MK1234567890',
        ];

        $sampleItems = collect([
            (object) ['description' => 'Веб дизајн услуги', 'quantity' => 1, 'unit_price' => 30000, 'tax_rate' => 18, 'discount' => 0],
            (object) ['description' => 'Хостинг (годишен)', 'quantity' => 1, 'unit_price' => 12000, 'tax_rate' => 18, 'discount' => 0],
            (object) ['description' => 'Одржување на веб страна', 'quantity' => 2, 'unit_price' => 4000, 'tax_rate' => 18, 'discount' => 0],
        ]);

        $sampleBankAccount = (object) [
            'bank_name' => 'Стопанска Банка АД Скопје',
            'account_number' => '200-1234567890-12',
            'iban' => 'MK07200123456789012',
        ];

        $isOffer = $type === 'offer';

        $sampleAgency = $agency ?? (object) [
            'name' => 'Моја Агенција',
            'address' => 'ул. Центар бр. 1',
            'city' => 'Скопје',
            'postal_code' => '1000',
            'phone' => '+389 2 123 456',
            'email' => 'info@agencija.mk',
            'tax_number' => 'MK9876543210',
            'website' => null,
        ];

        if ($isOffer) {
            $offerContent = '<h3>1. Опис на проектот</h3>'
                . '<p>Изработка на целосна веб апликација со модерен дизајн, responsive приказ и интеграција со платежни системи.</p>'
                . '<h3>2. Опфат на работа</h3>'
                . '<ul>'
                . '<li><p>Дизајн на корисничкиот интерфејс (UI/UX)</p></li>'
                . '<li><p>Развој на frontend и backend</p></li>'
                . '<li><p>Тестирање и оптимизација</p></li>'
                . '<li><p>Поставување на продукциски сервер</p></li>'
                . '</ul>'
                . '<h3>3. Временска рамка</h3>'
                . '<p>Проектот ќе биде завршен во рок од <strong>30 работни дена</strong> од датумот на прифаќање на понудата.</p>';

            $data = [
                'type' => 'offer',
                'template' => $template,
                'title' => 'Понуда ПОН-2025/001',
                'docTitle' => 'ПОНУДА',
                'docNumber' => 'ПОН-2025/001',
                'offerTitle' => 'Понуда за изработка на веб апликација',
                'offerContent' => $offerContent,
                'hasItems' => false,
                'issueDate' => now()->format('d.m.Y'),
                'dueDate' => now()->addDays(30)->format('d.m.Y'),
                'dueDateLabel' => 'Важи до',
                'currency' => 'EUR',
                'currencySymbol' => $currencySymbols['EUR'],
                'subtotal' => 0,
                'taxAmount' => 0,
                'total' => 2500,
                'notes' => 'Понудата важи 30 дена од датумот на издавање.',
                'client' => $sampleClient,
                'agency' => $sampleAgency,
                'items' => collect([]),
                'bankAccount' => null,
            ];
        } else {
            $data = [
                'type' => 'invoice',
                'template' => $template,
                'title' => 'Фактура ФА-2025/001',
                'docTitle' => 'ФАКТУРА',
                'docNumber' => 'ФА-2025/001',
                'offerTitle' => null,
                'offerContent' => null,
                'hasItems' => true,
                'issueDate' => now()->format('d.m.Y'),
                'dueDate' => now()->addDays(15)->format('d.m.Y'),
                'dueDateLabel' => 'Датум на доспевање',
                'currency' => 'MKD',
                'currencySymbol' => $currencySymbols['MKD'],
                'subtotal' => 50000,
                'taxAmount' => 9000,
                'total' => 59000,
                'notes' => 'Ви благодариме за соработката!',
                'client' => $sampleClient,
                'agency' => $sampleAgency,
                'items' => $sampleItems,
                'bankAccount' => $sampleBankAccount,
            ];
        }

        return view('pdf.browsershot', $data);
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
