{{-- Classic Template --}}

{{-- Header --}}
<div class="header">
    <div class="header-left">
        @if($agency)
            <div class="company-name">{{ $agency->name }}</div>
            <div class="company-info">
                @if($agency->address){{ $agency->address }}<br>@endif
                @if($agency->postal_code || $agency->city){{ $agency->postal_code }} {{ $agency->city }}<br>@endif
                @if($agency->phone)Тел: {{ $agency->phone }}<br>@endif
                @if($agency->email)Email: {{ $agency->email }}<br>@endif
                @if($agency->tax_number)ЕДБ: {{ $agency->tax_number }}@endif
            </div>
        @endif
    </div>
    <div class="header-right">
        <div class="doc-title">{{ $docTitle }}</div>
        <div class="doc-number">Бр. {{ $docNumber }}</div>
    </div>
</div>

{{-- Info Section --}}
<div class="info-section">
    <div class="info-left">
        <div class="section-title">Клиент</div>
        <div class="client-name">{{ $client->company ?? $client->name }}</div>
        <div class="client-info">
            @if($client->address){{ $client->address }}<br>@endif
            @if($client->postal_code || $client->city){{ $client->postal_code }} {{ $client->city }}<br>@endif
            @if($client->tax_number)ЕДБ: {{ $client->tax_number }}@endif
        </div>
    </div>
    <div class="info-right">
        <div class="section-title">Детали</div>
        <table class="details-table">
            <tr>
                <td class="label">Датум:</td>
                <td class="value">{{ $issueDate }}</td>
            </tr>
            @if($dueDate)
            <tr>
                <td class="label">{{ $dueDateLabel }}:</td>
                <td class="value">{{ $dueDate }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Валута:</td>
                <td class="value">{{ $currency }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- Items Table --}}
@if(count($items) > 0)
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 35%;">Опис</th>
            <th class="right" style="width: 10%;">Кол.</th>
            <th class="right" style="width: 13%;">Цена</th>
            <th class="right" style="width: 10%;">Рабат</th>
            <th class="right" style="width: 10%;">ДДВ</th>
            <th class="right" style="width: 16%;">Вкупно</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($item->discount, 0) }}%</td>
            <td class="right">{{ number_format($item->tax_rate, 0) }}%</td>
            <td class="right">{{ number_format($item->quantity * $item->unit_price * (1 - $item->discount / 100) * (1 + $item->tax_rate / 100), 2, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Totals --}}
@php
    $grossSubtotal = $items->sum(fn($i) => $i->quantity * $i->unit_price);
    $discountTotal = $grossSubtotal - $subtotal;
@endphp
<div class="totals">
    <div class="totals-row">
        <span class="label">Меѓузбир:</span>
        <span class="value">{{ number_format($grossSubtotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
    </div>
    @if($discountTotal > 0)
    <div class="totals-row">
        <span class="label">Рабат:</span>
        <span class="value">-{{ number_format($discountTotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
    </div>
    @endif
    <div class="totals-row">
        <span class="label">ДДВ:</span>
        <span class="value">{{ number_format($taxAmount, 2, ',', ' ') }} {{ $currencySymbol }}</span>
    </div>
    <div class="totals-row total">
        <span class="label">ВКУПНО:</span>
        <span class="value">{{ number_format($total, 2, ',', ' ') }} {{ $currencySymbol }}</span>
    </div>
</div>

{{-- Bank Account --}}
@if($bankAccount && $type !== 'offer')
<div class="bank-section">
    <div class="section-title">Банкарска сметка</div>
    <div class="bank-info">
        <div class="bank-name">{{ $bankAccount->bank_name }}</div>
        <div>Сметка: {{ $bankAccount->account_number }}</div>
        @if($bankAccount->iban)<div>IBAN: {{ $bankAccount->iban }}</div>@endif
    </div>
</div>
@endif

{{-- Notes --}}
@if($notes)
<div class="notes-section">
    <div class="section-title">Забелешки</div>
    <div class="notes-text">{{ $notes }}</div>
</div>
@endif

{{-- Footer --}}
@if($agency)
<div class="footer">
    {{ collect([$agency->name, $agency->website, $agency->email])->filter()->implode(' | ') }}
</div>
@endif
