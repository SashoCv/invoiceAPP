<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6 print:hidden">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('proforma.proforma_details') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $proformaInvoice->formatted_number }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if(!$proformaInvoice->isConverted())
                    <form action="{{ route('proforma-invoices.convert', $proformaInvoice) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('proforma.convert_to_invoice') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('proforma-invoices.duplicate', $proformaInvoice) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ __('proforma.duplicate') }}
                </a>
                <a href="{{ route('proforma-invoices.edit', $proformaInvoice) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    {{ __('proforma.edit_proforma') }}
                </a>
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('proforma.print') }}
                </button>
            </div>
        </div>

        <!-- Converted Invoice Notice -->
        @if($proformaInvoice->isConverted() && $proformaInvoice->convertedInvoice)
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg print:hidden">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">{{ __('proforma.converted_notice') }}</p>
                        <a href="{{ route('invoices.show', $proformaInvoice->convertedInvoice) }}" class="text-sm text-green-600 hover:text-green-800 underline">
                            {{ __('proforma.view_invoice') }}: {{ $proformaInvoice->convertedInvoice->formatted_number }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Proforma Document -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 print:shadow-none print:border-0 print:p-0">
            <!-- Header -->
            <div class="flex justify-between items-start mb-8 pb-8 border-b border-gray-200">
                <div>
                    @if($proformaInvoice->user->agency?->logo_url)
                        <img src="{{ $proformaInvoice->user->agency->logo_url }}" alt="{{ $proformaInvoice->user->agency->name }}" class="h-16 mb-4">
                    @endif
                    <h2 class="text-xl font-bold text-gray-900">{{ $proformaInvoice->user->agency?->name ?? $proformaInvoice->user->name }}</h2>
                    @if($proformaInvoice->user->agency)
                        <div class="mt-2 text-sm text-gray-600 space-y-1">
                            @if($proformaInvoice->user->agency->address)
                                <p>{{ $proformaInvoice->user->agency->address }}</p>
                            @endif
                            @if($proformaInvoice->user->agency->city || $proformaInvoice->user->agency->postal_code)
                                <p>{{ $proformaInvoice->user->agency->postal_code }} {{ $proformaInvoice->user->agency->city }}, {{ $proformaInvoice->user->agency->country ?? 'Македонија' }}</p>
                            @endif
                            @if($proformaInvoice->user->agency->tax_number)
                                <p>ЕДБ: {{ $proformaInvoice->user->agency->tax_number }}</p>
                            @endif
                            @if($proformaInvoice->user->agency->phone)
                                <p>Тел: {{ $proformaInvoice->user->agency->phone }}</p>
                            @endif
                            @if($proformaInvoice->user->agency->email)
                                <p>{{ $proformaInvoice->user->agency->email }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <h1 class="text-3xl font-bold text-gray-900 uppercase">{{ __('proforma.proforma') }}</h1>
                    <p class="mt-2 text-lg font-semibold text-gray-700">{{ $proformaInvoice->formatted_number }}</p>
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-700',
                            'sent' => 'bg-blue-100 text-blue-700',
                            'converted_to_invoice' => 'bg-green-100 text-green-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$proformaInvoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ __('proforma.status_' . $proformaInvoice->status) }}
                    </span>
                </div>
            </div>

            <!-- Client & Dates -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Client Info -->
                <div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('proforma.to') }}</h3>
                    <div class="text-sm text-gray-900">
                        <p class="font-semibold text-base">{{ $proformaInvoice->client?->display_name }}</p>
                        @if($proformaInvoice->client?->address)
                            <p class="mt-1">{{ $proformaInvoice->client->address }}</p>
                        @endif
                        @if($proformaInvoice->client?->city || $proformaInvoice->client?->postal_code)
                            <p>{{ $proformaInvoice->client->postal_code }} {{ $proformaInvoice->client->city }}</p>
                        @endif
                        @if($proformaInvoice->client?->country)
                            <p>{{ $proformaInvoice->client->country }}</p>
                        @endif
                        @if($proformaInvoice->client?->tax_number)
                            <p class="mt-2">ЕДБ: {{ $proformaInvoice->client->tax_number }}</p>
                        @endif
                    </div>
                </div>

                <!-- Proforma Dates -->
                <div class="text-right">
                    <div class="inline-block text-left">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <span class="text-gray-500">{{ __('proforma.issue_date') }}:</span>
                            <span class="font-medium text-gray-900">{{ $proformaInvoice->issue_date->format('d.m.Y') }}</span>

                            <span class="text-gray-500">{{ __('proforma.valid_until') }}:</span>
                            <span class="font-medium text-gray-900">{{ $proformaInvoice->valid_until->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('proforma.description') }}</th>
                            <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('proforma.quantity') }}</th>
                            <th class="py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">{{ __('proforma.unit_price') }}</th>
                            <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">{{ __('proforma.tax') }}</th>
                            <th class="py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">{{ __('proforma.item_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($proformaInvoice->items as $item)
                            @php
                                $itemSubtotal = $item->quantity * $item->unit_price;
                                $itemTaxRate = $item->tax_rate ?? 0;
                                $itemTax = $itemSubtotal * ($itemTaxRate / 100);
                                $itemTotal = $itemSubtotal + $itemTax;
                            @endphp
                            <tr>
                                <td class="py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="py-4 text-sm text-gray-900 text-center">{{ number_format($item->quantity, 2) }}</td>
                                <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($item->unit_price, 2) }} {{ $proformaInvoice->currency_symbol }}</td>
                                <td class="py-4 text-sm text-gray-900 text-center">{{ number_format($itemTaxRate, 0) }}%</td>
                                <td class="py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($itemTotal, 2) }} {{ $proformaInvoice->currency_symbol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-64">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('proforma.subtotal') }}</span>
                            <span class="font-medium text-gray-900">{{ number_format($proformaInvoice->subtotal, 2) }} {{ $proformaInvoice->currency_symbol }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ __('proforma.tax') }}</span>
                            <span class="font-medium text-gray-900">{{ number_format($proformaInvoice->tax_amount, 2) }} {{ $proformaInvoice->currency_symbol }}</span>
                        </div>
                        <div class="flex justify-between text-lg pt-2 border-t border-gray-200">
                            <span class="font-semibold text-gray-900">{{ __('proforma.total') }}</span>
                            <span class="font-bold text-gray-900">{{ number_format($proformaInvoice->total, 2) }} {{ $proformaInvoice->currency_symbol }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($proformaInvoice->notes)
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('proforma.notes') }}</h3>
                    <p class="text-sm text-gray-600">{{ $proformaInvoice->notes }}</p>
                </div>
            @endif

            <!-- Bank Account Info -->
            @if($proformaInvoice->user->bankAccounts->isNotEmpty())
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Банкарска сметка</h3>
                    @php $bankAccount = $proformaInvoice->user->bankAccounts->first(); @endphp
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><span class="font-medium">Банка:</span> {{ $bankAccount->bank_name }}</p>
                        <p><span class="font-medium">Сметка:</span> {{ $bankAccount->account_number }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Back Link -->
        <div class="mt-6 print:hidden">
            <a href="{{ route('proforma-invoices.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('proforma.title') }}
            </a>
        </div>
    </div>

    @push('styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .bg-white.rounded-xl, .bg-white.rounded-xl * {
                visibility: visible;
            }
            .bg-white.rounded-xl {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>
    @endpush
</x-app-layout>
