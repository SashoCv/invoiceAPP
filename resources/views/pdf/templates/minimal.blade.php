{{-- Minimal Template --}}

{{-- Header --}}
<div class="header">
    <div class="header-left">
        @if($agency)
            <div class="company-name">{{ $agency->name }}</div>
            <div class="company-info">
                @if($agency->address){{ $agency->address }}<br>@endif
                @if($agency->city){{ $agency->city }}<br>@endif
                @if($agency->tax_number)ЕДБ {{ $agency->tax_number }}@endif
            </div>
        @endif
    </div>
    <div class="header-right">
        <div class="doc-label">{{ $docTitle }}</div>
        <div class="doc-number">{{ $docNumber }}</div>
    </div>
</div>

{{-- Info Section --}}
<div class="info-section">
    <div class="info-left">
        <div class="section-label">Клиент</div>
        <div class="client-name">{{ $client->company ?? $client->name }}</div>
        <div class="client-info">
            @if($client->address){{ $client->address }}<br>@endif
            @if($client->city){{ $client->city }}<br>@endif
            @if($client->tax_number)ЕДБ {{ $client->tax_number }}@endif
        </div>
    </div>
    <div class="info-right">
        <div class="section-label">Датум</div>
        <div class="client-name">{{ $issueDate }}</div>
        @if($dueDate)
        <div class="client-info">{{ $dueDateLabel }}: {{ $dueDate }}</div>
        @endif
    </div>
</div>

{{-- Items Table --}}
@if(count($items) > 0)
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 42%;">Опис</th>
            <th class="right" style="width: 10%;">Кол.</th>
            <th class="right" style="width: 16%;">Цена</th>
            <th class="right" style="width: 12%;">Рабат</th>
            <th class="right" style="width: 20%;">Износ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($item->discount, 0) }}%</td>
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
        <span class="label">Меѓузбир</span>
        <span class="value">{{ number_format($grossSubtotal, 2, ',', ' ') }}</span>
    </div>
    @if($discountTotal > 0)
    <div class="totals-row">
        <span class="label">Рабат</span>
        <span class="value">-{{ number_format($discountTotal, 2, ',', ' ') }}</span>
    </div>
    @endif
    <div class="totals-row">
        <span class="label">ДДВ</span>
        <span class="value">{{ number_format($taxAmount, 2, ',', ' ') }}</span>
    </div>
    <div class="totals-row total">
        <span class="label">Вкупно {{ $currencySymbol }}</span>
        <span class="value">{{ number_format($total, $totalDecimals, ',', ' ') }}</span>
    </div>
</div>

{{-- Bank Account --}}
@if($bankAccount && $type !== 'offer')
<div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
    <div class="section-label">Банкарска сметка</div>
    <div style="font-size: 9pt; color: #9ca3af; margin-top: 8px;">
        {{ $bankAccount->bank_name }} · {{ $bankAccount->account_number }}
        @if($bankAccount->iban) · {{ $bankAccount->iban }}@endif
    </div>
</div>
@endif

{{-- Notes --}}
@if($notes)
<div style="margin-top: 20px;">
    <div class="section-label">Забелешки</div>
    <div style="font-size: 9pt; color: #6b7280; margin-top: 8px; white-space: pre-wrap;">{{ $notes }}</div>
</div>
@endif

{{-- Footer --}}
@if($agency)
<div class="footer">
    {{ $agency->name }}
</div>
@endif
