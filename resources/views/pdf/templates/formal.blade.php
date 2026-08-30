{{-- Formal Template (DomPDF) - Traditional Macedonian invoice style --}}
@php
    $isOffer = $type === 'offer';
    $hasItemsFlag = $isOffer ? ($hasItems ?? true) : true;

    $itemsData = [];
    $tax5Base = 0;
    $tax18Base = 0;
    $tax5Amount = 0;
    $tax18Amount = 0;
    $totalBase = 0;
    $totalWithVat = 0;
    $showAdditionalDiscount = $hasItemsFlag ? collect($items)->contains(fn($i) => ($i->additional_discount ?? 0) > 0) : false;

    if ($hasItemsFlag && count($items) > 0) {
        foreach ($items as $item) {
            $lineSubtotal = $item->quantity * $item->unit_price;
            $effDiscountPct = 1 - (1 - ($item->discount ?? 0) / 100) * (1 - ($item->additional_discount ?? 0) / 100);
            $lineDiscount = $lineSubtotal * $effDiscountPct;
            $lineBase = round($lineSubtotal - $lineDiscount, 2);
            $lineTax = round($lineBase * ($item->tax_rate / 100), 2);
            $lineTotal = $lineBase + $lineTax;

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
                'code' => $item->code,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'additional_discount' => $item->additional_discount ?? 0,
                'discounted_price' => round($item->unit_price * (1 - $effDiscountPct), 2),
                'base' => $lineBase,
                'tax_rate' => $item->tax_rate,
                'tax' => $lineTax,
                'total' => $lineTotal,
            ];
        }
    }

    $totalTax = $tax5Amount + $tax18Amount;
@endphp

{{-- Header --}}
<div class="header">
    <div class="header-left">
        @if($agency && $agency->logo)
            <img src="{{ public_path('storage/' . $agency->logo) }}" alt="Logo" style="max-height: 100px; max-width: 180px; margin-bottom: 5px;">
        @endif
    </div>
    <div class="header-right" style="text-align: right; font-size: 7pt;">
        @if($agency)
            <div style="font-weight: bold; font-size: 8pt; margin-bottom: 3px;">{{ $agency->name }}</div>
            @if($agency->address){{ $agency->address }} {{ $agency->city ?? '' }}@endif
            @if($agency->phone) Телефон: {{ $agency->phone }}@endif
            @if($agency->email)<br>email: {{ $agency->email }}@endif
            @if($agency->tax_number)<br>ЕДБ:{{ $agency->tax_number }}@endif
            @if($bankAccount)<br>Жиро сметка: {{ $bankAccount->account_number }} {{ $bankAccount->bank_name }}@endif
        @endif
    </div>
</div>

{{-- Document title and client info --}}
<div style="display: table; width: 100%; margin-bottom: 15px;">
    <div style="display: table-cell; width: 50%; vertical-align: top;">
        <div style="font-weight: bold; margin-bottom: 3px;">До</div>
        <div style="font-weight: bold;">{{ $client->company ?? $client->name }}</div>
        @if(!empty($branch))<div style="font-weight: bold;">{{ $branch->name }}</div>@endif
        @if(!empty($branch) && $branch->address)
            <div>{{ $branch->address }}</div>
            @if($branch->postal_code || $branch->city)<div>{{ $branch->postal_code }} {{ $branch->city }}</div>@endif
        @else
            @if($client->address)<div>{{ $client->address }}</div>@endif
            @if($client->postal_code || $client->city)<div>{{ $client->postal_code }} {{ $client->city }}</div>@endif
        @endif
    </div>
    <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;">
        <div style="font-size: 16pt; font-weight: bold; margin-bottom: 8px;">{{ $docTitle }}</div>
        <table style="margin-left: auto; font-size: 7pt;">
            <tr>
                <td style="padding-right: 10px; font-weight: bold;">Сериски број</td>
                <td style="font-weight: bold;">{{ $docNumber }}</td>
            </tr>
            <tr>
                <td style="padding-right: 10px;">Датум:</td>
                <td style="font-weight: bold;">{{ $issueDate }}</td>
            </tr>
            <tr>
                <td style="padding-right: 10px;">Валута:</td>
                <td style="font-weight: bold;">{{ $dueDate ?? $issueDate }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Items Table --}}
@if($hasItemsFlag && count($items) > 0)
<table class="items-table" style="margin-bottom: 5px;">
    <thead>
        <tr>
            <th style="width: 3%; text-align: left; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Рб</th>
            <th style="width: 19%; text-align: left; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Опис на артикал - услуга</th>
            <th class="right" style="width: 6%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Кол.</th>
            <th class="right" style="width: 9%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Цена</th>
            <th class="right" style="width: 6%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Рабат</th>
            @if($showAdditionalDiscount)<th class="right" style="width: 6%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Доп. попуст</th>@endif
            <th class="right" style="width: 10%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Цена со рабат</th>
            <th class="right" style="width: 11%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Износ без ДДВ</th>
            <th style="width: 6%; text-align: center; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">ддв</th>
            <th class="right" style="width: 11%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">ДДВ износ</th>
            <th class="right" style="width: 12%; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 3px;">Вкупно со ДДВ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($itemsData as $index => $item)
        <tr>
            <td style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ $index + 1 }}.</td>
            <td style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ $item['code'] ? '[' . $item['code'] . '] ' : '' }}{{ $item['description'] }}</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['quantity'], 0, ',', '.') }}</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['unit_price'], 2, ',', '.') }}</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['discount'], 0) }}%</td>
            @if($showAdditionalDiscount)<td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ $item['additional_discount'] > 0 ? number_format($item['additional_discount'], 0) . '%' : '-' }}</td>@endif
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['discounted_price'], 2, ',', '.') }}</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['base'], 2, ',', '.') }}</td>
            <td style="text-align: center; padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['tax_rate'], 0) }}%</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['tax'], 2, ',', '.') }}</td>
            <td class="right" style="padding: 5px 3px; border-bottom: 1px solid #ddd;">{{ number_format($item['total'], 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="font-size: 6pt; font-style: italic; margin-bottom: 10px;">
    Заклучно со реден број {{ count($itemsData) }}.
</div>

{{-- Total with VAT --}}
<div style="text-align: right; border-top: 1px solid #000; padding-top: 5px; margin-bottom: 15px;">
    <span style="font-weight: bold;">Вкупна цена со ДДВ:</span>
    <span style="font-weight: bold; margin-left: 15px;">{{ number_format($totalWithVat, 2, ',', '.') }}</span>
</div>

{{-- Tax breakdown --}}
<div style="display: table; width: 100%; margin-top: 10px;">
    <div style="display: table-cell; width: 40%; vertical-align: bottom; font-size: 7pt;">
        <div style="margin-bottom: 3px;">
            <span style="font-weight: bold;">Рок за плаќање:</span>
        </div>
        <div style="font-weight: bold;">
            @if($dueDate && $dueDate !== $issueDate)
                {{ $dueDate }}
            @else
                Веднаш
            @endif
        </div>
    </div>
    <div style="display: table-cell; width: 60%; vertical-align: top;">
        <table style="width: 100%; font-size: 6pt; border-collapse: collapse;">
            <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
                <td style="padding: 4px;">Осн. 5%:</td>
                <td style="padding: 4px; text-align: right;">{{ number_format($tax5Base, 2, ',', '.') }}</td>
                <td style="padding: 4px;">Осн. 18%:</td>
                <td style="padding: 4px; text-align: right;">{{ number_format($tax18Base, 2, ',', '.') }}</td>
                <td style="padding: 4px; font-weight: bold;">Основа:</td>
                <td style="padding: 4px; text-align: right; font-weight: bold;">{{ number_format($totalBase, 2, ',', '.') }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #000;">
                <td style="padding: 4px;">ДДВ 5%:</td>
                <td style="padding: 4px; text-align: right;">{{ number_format($tax5Amount, 2, ',', '.') }}</td>
                <td style="padding: 4px;">ДДВ 18%:</td>
                <td style="padding: 4px; text-align: right;">{{ number_format($tax18Amount, 2, ',', '.') }}</td>
                <td style="padding: 4px; font-weight: bold;">ДДВ:</td>
                <td style="padding: 4px; text-align: right; font-weight: bold;">{{ number_format($totalTax, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td style="padding: 6px 4px; font-weight: bold; font-size: 7pt;">Вкупно за наплата денари:</td>
                <td style="padding: 6px 4px; text-align: right; font-weight: bold; font-size: 7pt;">{{ number_format($totalWithVat, $totalDecimals, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>
@endif

{{-- Notes --}}
@if($notes)
<div style="margin-top: 10px; font-size: 6pt;">
    <span style="font-weight: bold;">Забелешка:</span> {{ $notes }}
</div>
@endif

{{-- Signatures --}}
<div style="display: table; width: 100%; margin-top: 60px; font-size: 7pt;">
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-top: 1px solid #000; width: 150px; margin: 0 auto 5px;"></div>
        <div>Примил</div>
    </div>
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-top: 1px solid #000; width: 150px; margin: 0 auto 5px;"></div>
        <div>Фактурирал</div>
    </div>
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-top: 1px solid #000; width: 150px; margin: 0 auto 5px;"></div>
        <div>Овластено лице</div>
        @if($agency && $agency->authorized_person)
            <div style="font-size: 8pt; font-weight: bold; margin-top: 2px;">{{ $agency->authorized_person }}</div>
        @endif
    </div>
</div>
