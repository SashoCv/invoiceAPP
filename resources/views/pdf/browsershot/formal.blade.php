{{-- Formal Template - Traditional Macedonian invoice style --}}
@php
    $isOffer = $type === 'offer';
    $hasItems = $isOffer ? ($hasItems ?? true) : true;

    // Calculate per-item breakdown
    $itemsData = [];
    $tax5Base = 0;
    $tax18Base = 0;
    $tax5Amount = 0;
    $tax18Amount = 0;
    $totalBase = 0;
    $totalWithVat = 0;

    if ($hasItems && count($items) > 0) {
        foreach ($items as $item) {
            $lineSubtotal = $item->quantity * $item->unit_price;
            $lineDiscount = $lineSubtotal * (($item->discount ?? 0) / 100);
            $lineBase = $lineSubtotal - $lineDiscount; // Износ (before VAT)
            $lineTax = $lineBase * ($item->tax_rate / 100);
            $lineTotal = $lineBase + $lineTax; // Вкупно (with VAT)

            $totalBase += $lineBase;
            $totalWithVat += $lineTotal;

            if ($item->tax_rate == 5) {
                $tax5Base += $lineBase;
                $tax5Amount += $lineTax;
            } else {
                $tax18Base += $lineBase;
                $tax18Amount += $lineTax;
            }

            $itemsData[] = [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'base' => $lineBase,
                'tax_rate' => $item->tax_rate,
                'total' => $lineTotal,
            ];
        }
    }

    $totalTax = $tax5Amount + $tax18Amount;

@endphp

<div class="p-8 text-[11px] leading-relaxed" style="font-family: 'Inter', system-ui, sans-serif; color: #111;">

    {{-- Header: Company info top-right --}}
    <div class="flex justify-between items-start mb-6">
        <div class="w-1/3">
            {{-- Logo placeholder --}}
            @if($agency && $agency->logo)
                <img src="{{ public_path('storage/' . $agency->logo) }}" alt="Logo" style="max-height: 100px; max-width: 180px; margin-bottom: 8px;">
            @endif
        </div>
        <div class="text-right text-[10px] leading-snug">
            @if($agency)
                <div class="font-bold text-[12px] mb-1">{{ $agency->name }}</div>
                @if($agency->address){{ $agency->address }} {{ $agency->city ?? '' }}@endif
                @if($agency->phone) Телефон: {{ $agency->phone }}@endif
                @if($agency->email)<br>email: {{ $agency->email }}@endif
                @if($agency->tax_number)<br>ЕДБ:{{ $agency->tax_number }}@endif
                @if($bankAccount)<br>Жиро сметка: {{ $bankAccount->account_number }} {{ $bankAccount->bank_name }}@endif
            @endif
        </div>
    </div>

    {{-- Document title and info --}}
    <div class="flex justify-between items-start mb-6">
        {{-- Client info (До) --}}
        <div>
            <div class="font-bold mb-1">До</div>
            <div class="font-bold">{{ $client->company ?? $client->name }}</div>
            @if($client->address)<div>{{ $client->address }}</div>@endif
            @if($client->postal_code || $client->city)<div>{{ $client->postal_code }} {{ $client->city }}</div>@endif
        </div>

        {{-- Document title + details --}}
        <div class="text-right">
            <div class="text-[22px] font-bold mb-2">{{ $docTitle }}</div>
            <table class="ml-auto text-[11px]">
                <tr>
                    <td class="pr-4 font-bold">Сериски број</td>
                    <td class="font-bold">{{ $docNumber }}</td>
                </tr>
                <tr>
                    <td class="pr-4">Датум:</td>
                    <td class="font-bold">{{ $issueDate }}</td>
                </tr>
                <tr>
                    <td class="pr-4">Валута:</td>
                    <td class="font-bold">{{ $dueDate ?? $issueDate }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Items Table --}}
    @if($hasItems && count($items) > 0)
    <table class="w-full border-collapse mb-1 text-[10px]">
        <thead>
            <tr class="border-t border-b border-black">
                <th class="text-left py-2 px-1 font-bold" style="width: 4%;">Рб</th>
                <th class="text-left py-2 px-1 font-bold" style="width: 30%;">Опис на артикал - услуга</th>
                <th class="text-right py-2 px-1 font-bold" style="width: 8%;">Кол.</th>
                <th class="text-right py-2 px-1 font-bold" style="width: 12%;">Цена</th>
                <th class="text-right py-2 px-1 font-bold" style="width: 8%;">Рабат</th>
                <th class="text-right py-2 px-1 font-bold" style="width: 14%;">Износ</th>
                <th class="text-center py-2 px-1 font-bold" style="width: 8%;">ддв</th>
                <th class="text-right py-2 px-1 font-bold" style="width: 16%;">Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itemsData as $index => $item)
            <tr class="border-b border-gray-300">
                <td class="py-2 px-1">{{ $index + 1 }}.</td>
                <td class="py-2 px-1">{{ $item['description'] }}</td>
                <td class="py-2 px-1 text-right">{{ number_format($item['quantity'], 0, ',', '.') }}</td>
                <td class="py-2 px-1 text-right">{{ number_format($item['unit_price'], 2, ',', '.') }}</td>
                <td class="py-2 px-1 text-right">{{ number_format($item['discount'], 0) }}%</td>
                <td class="py-2 px-1 text-right">{{ number_format($item['base'], 2, ',', '.') }}</td>
                <td class="py-2 px-1 text-center">{{ number_format($item['tax_rate'], 0) }}%</td>
                <td class="py-2 px-1 text-right">{{ number_format($item['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Closing row number --}}
    <div class="text-[10px] mb-4 mt-2 italic">
        Заклучно со реден број {{ count($itemsData) }}.
    </div>

    {{-- Вкупна цена (total base before VAT) --}}
    <div class="flex justify-end mb-3">
        <table class="text-[11px]">
            <tr class="border-t border-black">
                <td class="py-2 pr-6 font-bold">Вкупна цена:</td>
                <td class="py-2 text-right font-bold">{{ number_format($totalBase, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Tax breakdown and totals --}}
    <div class="flex justify-between items-end mt-4">
        {{-- Left: Payment terms + words --}}
        <div class="text-[10px]">
            <div class="mb-1">
                <span class="font-bold">Рок за плаќање:</span>
            </div>
            <div class="font-bold">
                @if($dueDate && $dueDate !== $issueDate)
                    {{ $dueDate }}
                @else
                    Веднаш
                @endif
            </div>
        </div>

        {{-- Right: Tax breakdown table --}}
        <div>
            <table class="text-[10px] border-collapse">
                <tr class="border-t border-b border-black">
                    <td class="py-1 px-2">Осн. 5%:</td>
                    <td class="py-1 px-2 text-right">{{ number_format($tax5Base, 2, ',', '.') }}</td>
                    <td class="py-1 px-2">Осн. 18%:</td>
                    <td class="py-1 px-2 text-right">{{ number_format($tax18Base, 2, ',', '.') }}</td>
                    <td class="py-1 px-2 font-bold">Основа:</td>
                    <td class="py-1 px-2 text-right font-bold">{{ number_format($totalBase, 2, ',', '.') }}</td>
                </tr>
                <tr class="border-b border-black">
                    <td class="py-1 px-2">ДДВ 5%:</td>
                    <td class="py-1 px-2 text-right">{{ number_format($tax5Amount, 2, ',', '.') }}</td>
                    <td class="py-1 px-2">ДДВ 18%:</td>
                    <td class="py-1 px-2 text-right">{{ number_format($tax18Amount, 2, ',', '.') }}</td>
                    <td class="py-1 px-2 font-bold">ДДВ:</td>
                    <td class="py-1 px-2 text-right font-bold">{{ number_format($totalTax, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="py-2 px-2 font-bold text-[11px]">Вкупно за наплата денари:</td>
                    <td class="py-2 px-2 text-right font-bold text-[11px]">{{ number_format($totalWithVat, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    {{-- Offer Content (when no items) --}}
    @if($isOffer && !$hasItems && !empty($offerContent))
    <div class="mb-8 p-4 border border-gray-300">
        <div class="font-bold mb-2">Опис на понудата</div>
        <div class="text-[10px] leading-relaxed prose prose-sm max-w-none">{!! $offerContent !!}</div>
    </div>
    @endif

    {{-- Notes --}}
    @if($notes)
    <div class="mt-4 mb-4 text-[10px]">
        <span class="font-bold">Забелешка:</span> {{ $notes }}
    </div>
    @endif

    {{-- Signatures --}}
    <div class="flex justify-between mt-16 pt-4 text-[10px]">
        <div class="text-center">
            <div class="border-t border-black w-40 mb-1"></div>
            <div>Примил</div>
        </div>
        <div class="text-center">
            <div class="border-t border-black w-40 mb-1"></div>
            <div>Фактурирал</div>
        </div>
        <div class="text-center">
            <div class="border-t border-black w-40 mb-1"></div>
            <div>Овластено лице</div>
            @if($agency && $agency->name)
                <div class="text-[9px] mt-0.5">{{ auth()->user()?->name ?? '' }}</div>
            @endif
        </div>
    </div>
</div>
