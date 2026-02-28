{{-- Classic Template - Identical to React InvoicePreview --}}
@php
    $isOffer = $type === 'offer';
    $hasItems = $isOffer ? ($hasItems ?? true) : true;
@endphp
<div class="p-8">
    {{-- Header --}}
    <div class="flex justify-between items-start mb-8 pb-4 border-b-2 border-blue-800">
        <div>
            @if($agency)
                <h2 class="text-xl font-bold text-blue-800 mb-2">{{ $agency->name }}</h2>
                <div class="text-sm text-gray-500 space-y-0.5">
                    @if($agency->address)<div>{{ $agency->address }}</div>@endif
                    @if($agency->city)<div>{{ $agency->postal_code }} {{ $agency->city }}</div>@endif
                    @if($agency->phone)<div>Тел: {{ $agency->phone }}</div>@endif
                    @if($agency->email)<div>Email: {{ $agency->email }}</div>@endif
                    @if($agency->tax_number)<div>ЕДБ: {{ $agency->tax_number }}</div>@endif
                </div>
            @endif
        </div>
        <div class="text-right">
            <h1 class="text-3xl font-bold text-blue-800">{{ $docTitle }}</h1>
            <div class="text-gray-500 mt-1">Бр. {{ $docNumber }}</div>
        </div>
    </div>

    {{-- Offer Title --}}
    @if($isOffer && !empty($offerTitle))
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">{{ $offerTitle }}</h2>
    </div>
    @endif

    {{-- Info Section --}}
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Клиент</h3>
            <div class="font-semibold text-gray-900">{{ $client->company ?? $client->name }}</div>
            <div class="text-sm text-gray-500 mt-1 space-y-0.5">
                @if($client->address)<div>{{ $client->address }}</div>@endif
                @if($client->city)<div>{{ $client->postal_code }} {{ $client->city }}</div>@endif
                @if($client->tax_number)<div>ЕДБ: {{ $client->tax_number }}</div>@endif
            </div>
        </div>
        <div>
            <h3 class="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Детали</h3>
            <table class="text-sm">
                <tbody>
                    <tr><td class="text-gray-500 pr-4 py-0.5">Датум:</td><td class="font-medium">{{ $issueDate }}</td></tr>
                    @if($dueDate)<tr><td class="text-gray-500 pr-4 py-0.5">{{ $dueDateLabel }}:</td><td class="font-medium">{{ $dueDate }}</td></tr>@endif
                    <tr><td class="text-gray-500 pr-4 py-0.5">Валута:</td><td class="font-medium">{{ $currency }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Offer Content (when no items) --}}
    @if($isOffer && !$hasItems && !empty($offerContent))
    <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Опис на понудата</h3>
        <div class="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none">{!! $offerContent !!}</div>
    </div>
    @endif

    {{-- Items Table - only show if has items --}}
    @if($hasItems && count($items) > 0)
    <div class="mb-6">
        <table class="w-full">
            <thead>
                <tr class="bg-blue-800 text-white">
                    <th class="text-left px-4 py-3 text-sm font-semibold">Опис</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold">Кол.</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold">Цена</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold">Рабат</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold">ДДВ</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                @php
                    $itemSubtotal = $item->quantity * $item->unit_price;
                    $itemDiscount = $itemSubtotal * ($item->discount / 100);
                    $afterDiscount = $itemSubtotal - $itemDiscount;
                    $itemTax = $afterDiscount * ($item->tax_rate / 100);
                    $itemTotal = $afterDiscount + $itemTax;
                @endphp
                <tr class="{{ $index % 2 === 1 ? 'bg-gray-50' : 'bg-white' }}">
                    <td class="px-4 py-3 text-sm border-b border-gray-200">{{ $item->description }}</td>
                    <td class="px-4 py-3 text-sm text-right border-b border-gray-200">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm text-right border-b border-gray-200">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm text-right border-b border-gray-200">{{ number_format($item->discount, 0) }}%</td>
                    <td class="px-4 py-3 text-sm text-right border-b border-gray-200">{{ number_format($item->tax_rate, 0) }}%</td>
                    <td class="px-4 py-3 text-sm text-right font-medium border-b border-gray-200">{{ number_format($itemTotal, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Totals - only show if has items --}}
    @if($hasItems)
    @php
        $grossSubtotal = $items->sum(fn($i) => $i->quantity * $i->unit_price);
        $discountTotal = $grossSubtotal - $subtotal;
    @endphp
    <div class="flex justify-end mb-8">
        <div class="w-64">
            <div class="flex justify-between text-sm text-gray-500 py-1">
                <span>Меѓузбир:</span><span>{{ number_format($grossSubtotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
            </div>
            @if($discountTotal > 0)
            <div class="flex justify-between text-sm text-gray-500 py-1">
                <span>Рабат:</span><span>-{{ number_format($discountTotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm text-gray-500 py-1">
                <span>ДДВ:</span><span>{{ number_format($taxAmount, 2, ',', ' ') }} {{ $currencySymbol }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold text-blue-800 py-2 border-t-2 border-blue-800 mt-2">
                <span>ВКУПНО:</span><span>{{ number_format($total, 2, ',', ' ') }} {{ $currencySymbol }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Total for text-only offers --}}
    @if($isOffer && !$hasItems && $total > 0)
    <div class="flex justify-end mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-4">
            <div class="text-sm text-blue-600 mb-1">Вкупна вредност</div>
            <div class="text-2xl font-bold text-blue-800">{{ number_format($total, 2, ',', ' ') }} {{ $currencySymbol }}</div>
        </div>
    </div>
    @endif

    {{-- Bank Account --}}
    @if($bankAccount && $type !== 'offer')
    <div class="mb-6">
        <h3 class="text-sm font-bold text-blue-800 mb-2 uppercase tracking-wide">Банкарска сметка</h3>
        <div class="text-sm text-gray-600">
            <div class="font-medium">{{ $bankAccount->bank_name }}</div>
            <div>Сметка: {{ $bankAccount->account_number }}</div>
            @if($bankAccount->iban)<div>IBAN: {{ $bankAccount->iban }}</div>@endif
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if($notes)
    <div class="mt-6 pt-4 border-t border-gray-200">
        <h3 class="text-sm font-bold text-blue-800 mb-2 uppercase">Забелешки</h3>
        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $notes }}</p>
    </div>
    @endif

    {{-- Footer --}}
    @if($agency)
    <div class="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
        {{ collect([$agency->name, $agency->website, $agency->email])->filter()->implode(' | ') }}
    </div>
    @endif
</div>
