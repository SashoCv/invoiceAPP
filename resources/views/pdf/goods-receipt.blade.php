<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Приемница {{ $docNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #111827;
            line-height: 1.4;
        }

        .page {
            padding: 30px;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 8px;
        }

        .company-info {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .doc-title {
            font-size: 22pt;
            font-weight: bold;
            color: #4f46e5;
        }

        .doc-number {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 5px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .details-table td {
            padding: 3px 0;
            font-size: 9pt;
        }

        .details-table .label {
            color: #6b7280;
            padding-right: 15px;
        }

        .details-table .value {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #4f46e5;
            color: white;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 9px 7px;
            text-align: left;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table td {
            padding: 8px 7px;
            font-size: 8.5pt;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .totals {
            width: 100%;
        }

        .totals-table {
            width: 280px;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 8px;
            font-size: 10pt;
        }

        .totals-table .label {
            text-align: right;
            color: #6b7280;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            width: 130px;
        }

        .grand-total td {
            border-top: 2px solid #4f46e5;
            font-size: 11pt;
            color: #4f46e5;
        }

        .notes-section {
            clear: both;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 25px;
        }

        .notes-text {
            font-size: 9pt;
            color: #6b7280;
            white-space: pre-wrap;
        }

        .footer {
            clear: both;
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
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
                <div class="doc-title">ПРИЕМНИЦА</div>
                <div class="doc-number">Бр. {{ $docNumber }}</div>
            </div>
        </div>

        {{-- Details --}}
        <div class="info-section">
            <div class="section-title">Детали</div>
            <table class="details-table">
                <tr>
                    <td class="label">Датум:</td>
                    <td class="value">{{ $receiptDate }}</td>
                </tr>
                <tr>
                    <td class="label">Број на ставки:</td>
                    <td class="value">{{ count($items) }}</td>
                </tr>
            </table>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 32%;">Артикл</th>
                    <th style="width: 9%;">Ед.</th>
                    <th class="right" style="width: 11%;">Количина</th>
                    <th class="right" style="width: 13%;">Набавна цена</th>
                    <th class="right" style="width: 13%;">Износ</th>
                    <th class="right" style="width: 9%;">ДДВ %</th>
                    <th class="right" style="width: 13%;">ДДВ износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['unit'] }}</td>
                    <td class="right">{{ number_format($item['quantity'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['cost_price'], 4, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['base'], 4, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['tax_rate'], 0) }}%</td>
                    <td class="right">{{ number_format($item['vat'], 4, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="label">Износ (основа):</td>
                    <td class="value">{{ number_format($subtotal, 2, ',', ' ') }} ден.</td>
                </tr>
                <tr>
                    <td class="label">ДДВ:</td>
                    <td class="value">{{ number_format($totalVat, 2, ',', ' ') }} ден.</td>
                </tr>
                <tr class="grand-total">
                    <td class="label">Вкупно:</td>
                    <td class="value">{{ number_format($grandTotal, 2, ',', ' ') }} ден.</td>
                </tr>
            </table>
        </div>

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
    </div>
</body>
</html>
