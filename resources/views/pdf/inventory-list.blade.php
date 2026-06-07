<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Лагер листа</title>
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
            border-bottom: 3px solid #2563eb;
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
            color: #2563eb;
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
            color: #2563eb;
        }

        .doc-date {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 5px;
        }

        .badge-historical {
            display: inline-block;
            margin-top: 6px;
            font-size: 8pt;
            font-weight: bold;
            color: #b45309;
            background-color: #fef3c7;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #2563eb;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table td {
            padding: 8px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px;
            font-size: 10pt;
        }

        .totals-table .label {
            text-align: right;
            color: #6b7280;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            width: 160px;
        }

        .grand-total td {
            border-top: 2px solid #2563eb;
            font-size: 11pt;
            color: #2563eb;
        }

        .footer {
            margin-top: 25px;
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
                <div class="doc-title">ЛАГЕР ЛИСТА</div>
                <div class="doc-date">Состојба на {{ $reportDate }}</div>
                @if($isHistorical)
                    <span class="badge-historical">Историска состојба</span>
                @endif
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Шифра</th>
                    <th style="width: 38%;">Име</th>
                    <th style="width: 12%;">Ед. мерка</th>
                    <th class="right" style="width: 12%;">Залиха</th>
                    <th class="right" style="width: 13%;">Цена</th>
                    <th class="right" style="width: 13%;">Вредност</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row['code'] ?? '-' }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td class="right">{{ number_format($row['quantity'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($row['price'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($row['value'], 2, ',', ' ') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af; padding: 20px;">
                        Нема артикли во лагерот.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tr>
                <td class="label">Број на артикли:</td>
                <td class="value">{{ $totalItems }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Вкупна вредност на лагер:</td>
                <td class="value">{{ number_format($totalValue, 2, ',', ' ') }} ден.</td>
            </tr>
        </table>

        {{-- Footer --}}
        @if($agency)
        <div class="footer">
            {{ collect([$agency->name, $agency->website, $agency->email])->filter()->implode(' | ') }}
        </div>
        @endif
    </div>
</body>
</html>
