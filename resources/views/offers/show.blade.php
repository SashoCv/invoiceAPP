<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6 print:hidden">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('offers.offer_details') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $offer->formatted_number }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($offer->status === 'sent' && !$offer->isConverted())
                    <form action="{{ route('offers.accept', $offer) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('offers.accept') }}
                        </button>
                    </form>
                    <form action="{{ route('offers.reject', $offer) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ __('offers.reject') }}
                        </button>
                    </form>
                @endif
                @if($offer->isAccepted() && !$offer->isConverted())
                    <form action="{{ route('offers.convert', $offer) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('offers.convert_to_invoice') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('offers.duplicate', $offer) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ __('offers.duplicate') }}
                </a>
                <a href="{{ route('offers.edit', $offer) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    {{ __('offers.edit_offer') }}
                </a>
                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('offers.print') }}
                </button>
            </div>
        </div>

        <!-- Converted Invoice Notice -->
        @if($offer->isConverted() && $offer->convertedInvoice)
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg print:hidden">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">{{ __('offers.converted_notice') }}</p>
                        <a href="{{ route('invoices.show', $offer->convertedInvoice) }}" class="text-sm text-green-600 hover:text-green-800 underline">
                            {{ __('offers.view_invoice') }}: {{ $offer->convertedInvoice->formatted_number }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Offer Document -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 print:shadow-none print:border-0 print:p-0">
            <!-- Header -->
            <div class="flex justify-between items-start mb-8 pb-8 border-b border-gray-200">
                <div>
                    @if($offer->user->agency?->logo_url)
                        <img src="{{ $offer->user->agency->logo_url }}" alt="{{ $offer->user->agency->name }}" class="h-16 mb-4">
                    @endif
                    <h2 class="text-xl font-bold text-gray-900">{{ $offer->user->agency?->name ?? $offer->user->name }}</h2>
                    @if($offer->user->agency)
                        <div class="mt-2 text-sm text-gray-600 space-y-1">
                            @if($offer->user->agency->address)
                                <p>{{ $offer->user->agency->address }}</p>
                            @endif
                            @if($offer->user->agency->city || $offer->user->agency->postal_code)
                                <p>{{ $offer->user->agency->postal_code }} {{ $offer->user->agency->city }}, {{ $offer->user->agency->country ?? 'Македонија' }}</p>
                            @endif
                            @if($offer->user->agency->tax_number)
                                <p>ЕДБ: {{ $offer->user->agency->tax_number }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <h1 class="text-3xl font-bold text-gray-900 uppercase">{{ __('offers.offer') }}</h1>
                    <p class="mt-2 text-lg font-semibold text-gray-700">{{ $offer->formatted_number }}</p>
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-700',
                            'sent' => 'bg-blue-100 text-blue-700',
                            'accepted' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$offer->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ __('offers.status_' . $offer->status) }}
                    </span>
                </div>
            </div>

            <!-- Client & Dates -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('offers.to') }}</h3>
                    <div class="text-sm text-gray-900">
                        <p class="font-semibold text-base">{{ $offer->client?->display_name }}</p>
                        @if($offer->client?->address)
                            <p class="mt-1">{{ $offer->client->address }}</p>
                        @endif
                        @if($offer->client?->city || $offer->client?->postal_code)
                            <p>{{ $offer->client->postal_code }} {{ $offer->client->city }}</p>
                        @endif
                        @if($offer->client?->tax_number)
                            <p class="mt-2">ЕДБ: {{ $offer->client->tax_number }}</p>
                        @endif
                    </div>
                </div>

                <div class="text-right">
                    <div class="inline-block text-left">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <span class="text-gray-500">{{ __('offers.issue_date') }}:</span>
                            <span class="font-medium text-gray-900">{{ $offer->issue_date->format('d.m.Y') }}</span>

                            <span class="text-gray-500">{{ __('offers.valid_until') }}:</span>
                            <span class="font-medium text-gray-900">{{ $offer->valid_until->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900">{{ $offer->title }}</h2>
            </div>

            <!-- Content -->
            @if($offer->content)
                <div class="mb-8 prose prose-sm max-w-none">
                    {!! nl2br(e($offer->content)) !!}
                </div>
            @endif

            <!-- Items Table (if has items) -->
            @if($offer->has_items && $offer->items->isNotEmpty())
                <div class="mb-8">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('offers.description') }}</th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">{{ __('offers.quantity') }}</th>
                                <th class="py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">{{ __('offers.unit_price') }}</th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">{{ __('offers.tax') }}</th>
                                <th class="py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">{{ __('offers.item_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($offer->items as $item)
                                @php
                                    $itemSubtotal = $item->quantity * $item->unit_price;
                                    $itemTaxRate = $item->tax_rate ?? 0;
                                    $itemTax = $itemSubtotal * ($itemTaxRate / 100);
                                    $itemTotal = $itemSubtotal + $itemTax;
                                @endphp
                                <tr>
                                    <td class="py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                    <td class="py-4 text-sm text-gray-900 text-center">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="py-4 text-sm text-gray-900 text-right">{{ number_format($item->unit_price, 2) }} {{ $offer->currency_symbol }}</td>
                                    <td class="py-4 text-sm text-gray-900 text-center">{{ number_format($itemTaxRate, 0) }}%</td>
                                    <td class="py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($itemTotal, 2) }} {{ $offer->currency_symbol }}</td>
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
                                <span class="text-gray-600">{{ __('offers.subtotal') }}</span>
                                <span class="font-medium text-gray-900">{{ number_format($offer->subtotal, 2) }} {{ $offer->currency_symbol }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ __('offers.tax') }}</span>
                                <span class="font-medium text-gray-900">{{ number_format($offer->tax_amount, 2) }} {{ $offer->currency_symbol }}</span>
                            </div>
                            <div class="flex justify-between text-lg pt-2 border-t border-gray-200">
                                <span class="font-semibold text-gray-900">{{ __('offers.total') }}</span>
                                <span class="font-bold text-gray-900">{{ number_format($offer->total, 2) }} {{ $offer->currency_symbol }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if($offer->notes)
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('offers.notes') }}</h3>
                    <p class="text-sm text-gray-600">{{ $offer->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Back Link -->
        <div class="mt-6 print:hidden">
            <a href="{{ route('offers.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('offers.title') }}
            </a>
        </div>
    </div>

    @push('styles')
    <style>
        @media print {
            body * { visibility: hidden; }
            .bg-white.rounded-xl, .bg-white.rounded-xl * { visibility: visible; }
            .bg-white.rounded-xl { position: absolute; left: 0; top: 0; width: 100%; }
            .print\:hidden { display: none !important; }
        }
    </style>
    @endpush
</x-app-layout>
