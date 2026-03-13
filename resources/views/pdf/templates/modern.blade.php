{{-- Modern Template --}}

{{-- Header --}}
<div class="header">
    <div class="header-content">
        <div class="header-left">
            @if($agency)
                <div class="company-name">{{ $agency->name }}</div>
                <div class="company-info">
                    @if($agency->address){{ $agency->address }}<br>@endif
                    @if($agency->city){{ $agency->city }}<br>@endif
                    @if($agency->email){{ $agency->email }}@endif
                </div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-badge">
                <div class="doc-title">{{ $docTitle }}</div>
                <div class="doc-number">{{ $docNumber }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Cards Section --}}
<div class="cards-section">
    <div class="card">
        <div class="card-label">Клиент</div>
        <div class="card-value">{{ $client->company ?? $client->name }}</div>
        <div class="card-subvalue">
            @if($client->address){{ $client->address }}<br>@endif
            @if($client->city){{ $client->city }}@endif
        </div>
    </div>
    <div class="card">
        <div class="card-label">Датум</div>
        <div class="card-value">{{ $issueDate }}</div>
        @if($dueDate)
        <div class="card-subvalue">{{ $dueDateLabel }}: {{ $dueDate }}</div>
        @endif
    </div>
    <div class="card">
        <div class="card-label">Износ</div>
        <div class="card-value card-amount">{{ number_format($total, $totalDecimals, ',', ' ') }}</div>
        <div class="card-subvalue">{{ $currencySymbol }}</div>
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

@php
    $grossSubtotal = $items->sum(fn($i) => $i->quantity * $i->unit_price);
    $discountTotal = $grossSubtotal - $subtotal;
@endphp
<div class="totals-section">
    <div class="totals">
        <div class="totals-row">
            <span class="label">Меѓузбир</span>
            <span class="value">{{ number_format($grossSubtotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
        </div>
        @if($discountTotal > 0)
        <div class="totals-row">
            <span class="label">Рабат</span>
            <span class="value">-{{ number_format($discountTotal, 2, ',', ' ') }} {{ $currencySymbol }}</span>
        </div>
        @endif
        <div class="totals-row">
            <span class="label">ДДВ</span>
            <span class="value">{{ number_format($taxAmount, 2, ',', ' ') }} {{ $currencySymbol }}</span>
        </div>
        <div class="totals-row total">
            <span class="label">Вкупно</span>
            <span class="value">{{ number_format($total, $totalDecimals, ',', ' ') }} {{ $currencySymbol }}</span>
        </div>
    </div>
</div>
@endif

{{-- Bank & Notes --}}
@if(($bankAccount && $type !== 'offer') || $notes)
<div class="bottom-cards">
    @if($bankAccount && $type !== 'offer')
    <div class="bottom-card">
        <div class="bottom-card-label">Банкарска сметка</div>
        <div style="font-weight: bold; margin-bottom: 3px;">{{ $bankAccount->bank_name }}</div>
        <div style="font-size: 9pt; color: #6b7280;">
            Сметка: {{ $bankAccount->account_number }}<br>
            @if($bankAccount->iban)IBAN: {{ $bankAccount->iban }}@endif
        </div>
    </div>
    @endif
    @if($notes)
    <div class="bottom-card" @if(!$bankAccount || $type === 'offer') style="width: 100%; border-radius: 8px;" @endif>
        <div class="bottom-card-label">Забелешки</div>
        <div style="font-size: 9pt; color: #6b7280; white-space: pre-wrap;">{{ $notes }}</div>
    </div>
    @endif
</div>
@endif
