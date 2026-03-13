{{-- Modern Template - Identical to React InvoicePreview --}}
@php
    $isOffer = $type === 'offer';
    $hasItems = $isOffer ? ($hasItems ?? true) : true;
@endphp
<div>
    {{-- Gradient Header --}}
    <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 text-white p-8 rounded-t-lg">
        <div class="flex justify-between items-start">
            <div>
                @if($agency)
                    <h2 class="text-2xl font-bold mb-1">{{ $agency->name }}</h2>
                    <div class="text-sm text-white/80 space-y-0.5">
                        @if($agency->address)<div>{{ $agency->address }}</div>@endif
                        @if($agency->city)<div>{{ $agency->city }}</div>@endif
                        @if($agency->email)<div>{{ $agency->email }}</div>@endif
                    </div>
                @endif
            </div>
            <div class="text-right">
                <div class="inline-block bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4">
                    <h1 class="text-2xl font-bold">{{ $docTitle }}</h1>
                    <div class="text-white/80 text-sm mt-1">{{ $docNumber }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-8 bg-gray-50">
        {{-- Info Cards --}}
        <div class="grid grid-cols-3 gap-4 -mt-12 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-5">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Клиент</div>
                <div class="font-bold text-gray-900">{{ $client->company ?? $client->name }}</div>
                <div class="text-sm text-gray-500 mt-1">
                    @if($client->address)<div>{{ $client->address }}</div>@endif
                    @if($client->city)<div>{{ $client->city }}</div>@endif
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-5">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Датум</div>
                <div class="font-bold text-gray-900">{{ $issueDate }}</div>
                @if($dueDate)
                <div class="text-sm text-gray-500 mt-1">
                    {{ $type === 'invoice' ? 'Доспева' : 'Важи до' }}: {{ $dueDate }}
                </div>
                @endif
            </div>
            <div class="bg-white rounded-xl shadow-lg p-5">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Износ</div>
                <div class="font-bold text-2xl text-gray-900">{{ number_format($total, $totalDecimals, ',', ' ') }}</div>
                <div class="text-sm text-gray-500">{{ $currencySymbol }}</div>
            </div>
        </div>

        {{-- Offer Title --}}
        @if($isOffer && !empty($offerTitle))
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900">{{ $offerTitle }}</h2>
        </div>
        @endif

        {{-- Offer Content (when no items) --}}
        @if($isOffer && !$hasItems && !empty($offerContent))
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-4">Опис на понудата</div>
            <div class="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none">{!! $offerContent !!}</div>
        </div>
        @endif

        {{-- Items Table - only show if has items --}}
        @if($hasItems && count($items) > 0)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-violet-500 to-purple-500 text-white">
                        <th class="text-left px-6 py-4 text-sm font-semibold">Опис</th>
                        <th class="text-right px-4 py-4 text-sm font-semibold">Кол.</th>
                        <th class="text-right px-4 py-4 text-sm font-semibold">Цена</th>
                        <th class="text-right px-4 py-4 text-sm font-semibold">Рабат</th>
                        <th class="text-right px-4 py-4 text-sm font-semibold">ДДВ</th>
                        <th class="text-right px-6 py-4 text-sm font-semibold">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                    @php
                        $itemSubtotal = $item->quantity * $item->unit_price;
                        $itemDiscount = $itemSubtotal * ($item->discount / 100);
                        $afterDiscount = round($itemSubtotal - $itemDiscount, 2);
                        $itemTax = round($afterDiscount * ($item->tax_rate / 100), 2);
                        $itemTotal = $afterDiscount + $itemTax;
                    @endphp
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                        <td class="px-4 py-4 text-sm text-right text-gray-600">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                        <td class="px-4 py-4 text-sm text-right text-gray-600">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                        <td class="px-4 py-4 text-sm text-right text-gray-600">{{ number_format($item->discount, 0) }}%</td>
                        <td class="px-4 py-4 text-sm text-right text-gray-600">{{ number_format($item->tax_rate, 0) }}%</td>
                        <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">{{ number_format($itemTotal, 2, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals inside card --}}
            @php
                $grossSubtotal = $items->sum(fn($i) => $i->quantity * $i->unit_price);
                $discountTotal = $grossSubtotal - $subtotal;
            @endphp
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex justify-end">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Меѓузбир</span><span>{{ number_format($grossSubtotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
                        </div>
                        @if($discountTotal > 0)
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Рабат</span><span>-{{ number_format($discountTotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>ДДВ</span><span>{{ number_format($taxAmount, 2, ',', ' ') }} {{ $currencySymbol }}</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-purple-600 pt-2 border-t border-purple-200">
                            <span>Вкупно</span><span>{{ number_format($total, $totalDecimals, ',', ' ') }} {{ $currencySymbol }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Bank & Notes --}}
        <div class="grid grid-cols-2 gap-4">
            @if($bankAccount && $type !== 'offer')
            <div class="bg-white rounded-xl shadow-lg p-5">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-3">Банкарска сметка</div>
                <div class="text-sm text-gray-700 space-y-1">
                    <div class="font-semibold">{{ $bankAccount->bank_name }}</div>
                    <div>{{ $bankAccount->account_number }}</div>
                    @if($bankAccount->iban)<div class="text-gray-500">IBAN: {{ $bankAccount->iban }}</div>@endif
                </div>
            </div>
            @endif
            @if($notes)
            <div class="bg-white rounded-xl shadow-lg p-5 {{ !$bankAccount || $type === 'offer' ? 'col-span-2' : '' }}">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-3">Забелешки</div>
                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $notes }}</p>
            </div>
            @endif
        </div>

        {{-- Footer --}}
        @if($agency)
        <div class="mt-8 text-center text-xs text-gray-400">
            {{ collect([$agency->name, $agency->website, $agency->email])->filter()->implode(' • ') }}
        </div>
        @endif
    </div>
</div>
