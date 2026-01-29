{{-- Minimal Template - Identical to React InvoicePreview --}}
@php
    $isOffer = $type === 'offer';
    $hasItems = $isOffer ? ($hasItems ?? true) : true;
@endphp
<div class="p-12 bg-white" style="font-family: Georgia, serif;">
    {{-- Minimal Header --}}
    <div class="flex justify-between items-start mb-16">
        <div>
            @if($agency)
                @if($agency->name)<h2 class="text-lg font-normal tracking-wide text-gray-900 mb-4">{{ $agency->name }}</h2>@endif
                <div class="text-sm text-gray-400 space-y-1">
                    @if($agency->address)<div>{{ $agency->address }}</div>@endif
                    @if($agency->city)<div>{{ $agency->city }}</div>@endif
                    @if($agency->tax_number)<div>ЕДБ {{ $agency->tax_number }}</div>@endif
                </div>
            @endif
        </div>
        <div class="text-right">
            <h1 class="text-sm uppercase tracking-[0.3em] text-gray-400 mb-2">{{ $docTitle }}</h1>
            <div class="text-2xl font-light text-gray-900">{{ $docNumber }}</div>
        </div>
    </div>

    {{-- Offer Title --}}
    @if($isOffer && !empty($offerTitle))
    <div class="mb-12">
        <h2 class="text-xl font-normal text-gray-900">{{ $offerTitle }}</h2>
    </div>
    @endif

    {{-- Two Column Info --}}
    <div class="grid grid-cols-2 gap-16 mb-16">
        <div>
            <div class="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Клиент</div>
            <div class="text-gray-900">{{ $client->company ?? $client->name }}</div>
            <div class="text-sm text-gray-400 mt-2 space-y-1">
                @if($client->address)<div>{{ $client->address }}</div>@endif
                @if($client->city)<div>{{ $client->city }}</div>@endif
                @if($client->tax_number)<div>ЕДБ {{ $client->tax_number }}</div>@endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Детали</div>
            <div class="text-sm text-gray-600 space-y-2">
                <div>Датум: <span class="text-gray-900">{{ $issueDate }}</span></div>
                @if($dueDate)
                <div>{{ $type === 'invoice' ? 'Доспева' : 'Важи до' }}: <span class="text-gray-900">{{ $dueDate }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Offer Content (when no items) --}}
    @if($isOffer && !$hasItems && !empty($offerContent))
    <div class="mb-12 pb-8 border-b border-gray-100">
        <div class="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Опис на понудата</div>
        <div class="text-gray-700 leading-relaxed prose prose-sm max-w-none">{!! $offerContent !!}</div>
    </div>
    @endif

    {{-- Minimal Items - only show if has items --}}
    @if($hasItems && count($items) > 0)
    <div class="mb-12">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Опис</th>
                    <th class="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Кол.</th>
                    <th class="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Цена</th>
                    <th class="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                @php
                    $itemSubtotal = $item->quantity * $item->unit_price;
                    $itemTax = $itemSubtotal * ($item->tax_rate / 100);
                    $itemTotal = $itemSubtotal + $itemTax;
                @endphp
                <tr class="border-b border-gray-100">
                    <td class="py-5 text-gray-900">{{ $item->description }}</td>
                    <td class="py-5 text-right text-gray-600">{{ number_format($item->quantity, 0, ',', ' ') }}</td>
                    <td class="py-5 text-right text-gray-600">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                    <td class="py-5 text-right text-gray-900">{{ number_format($itemTotal, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Minimal Totals - only show if has items --}}
    @if($hasItems)
    <div class="flex justify-end mb-16">
        <div class="w-72">
            <div class="flex justify-between py-2 text-sm text-gray-400">
                <span>Меѓузбир</span><span class="text-gray-600">{{ number_format($subtotal, 2, ',', ' ') }}</span>
            </div>
            <div class="flex justify-between py-2 text-sm text-gray-400">
                <span>ДДВ</span><span class="text-gray-600">{{ number_format($taxAmount, 2, ',', ' ') }}</span>
            </div>
            <div class="flex justify-between py-4 border-t border-gray-900 mt-2">
                <span class="text-xs uppercase tracking-[0.2em] text-gray-400">Вкупно</span>
                <span class="text-2xl font-light text-gray-900">{{ number_format($total, 2, ',', ' ') }} <span class="text-sm">{{ $currencySymbol }}</span></span>
            </div>
        </div>
    </div>
    @endif

    {{-- Total for text-only offers --}}
    @if($isOffer && !$hasItems && $total > 0)
    <div class="flex justify-end mb-16">
        <div class="border-t border-gray-900 pt-4">
            <span class="text-xs uppercase tracking-[0.2em] text-gray-400 mr-8">Вкупна вредност</span>
            <span class="text-2xl font-light text-gray-900">{{ number_format($total, 2, ',', ' ') }} <span class="text-sm">{{ $currencySymbol }}</span></span>
        </div>
    </div>
    @endif

    {{-- Bank & Notes - Minimal --}}
    <div class="grid grid-cols-2 gap-16 pt-8 border-t border-gray-100">
        @if($bankAccount && $type !== 'offer')
        <div>
            <div class="text-xs uppercase tracking-[0.2em] text-gray-400 mb-3">Плаќање</div>
            <div class="text-sm text-gray-600 space-y-1">
                <div>{{ $bankAccount->bank_name }}</div>
                <div>{{ $bankAccount->account_number }}</div>
                @if($bankAccount->iban)<div class="text-gray-400">{{ $bankAccount->iban }}</div>@endif
            </div>
        </div>
        @endif
        @if($notes)
        <div class="{{ !$bankAccount || $type === 'offer' ? 'col-span-2' : '' }}">
            <div class="text-xs uppercase tracking-[0.2em] text-gray-400 mb-3">Забелешки</div>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $notes }}</p>
        </div>
        @endif
    </div>

    {{-- Footer --}}
    @if($agency)
    <div class="mt-16 pt-8 border-t border-gray-100 text-center text-xs text-gray-300 tracking-wide">
        {{ $agency->name }}
    </div>
    @endif
</div>
