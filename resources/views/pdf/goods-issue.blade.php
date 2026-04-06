<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
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
            border-bottom: 3px solid #ea580c;
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
            color: #ea580c;
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
            color: #ea580c;
        }

        .doc-number {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 5px;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-left,
        .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #ea580c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .client-name {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
            margin-bottom: 5px;
        }

        .client-info {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .details-table {
            font-size: 9pt;
        }

        .details-table td {
            padding: 3px 0;
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
            background-color: #ea580c;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 12px 10px;
            text-align: left;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table td {
            padding: 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .notes-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 15px;
        }

        .notes-text {
            font-size: 9pt;
            color: #6b7280;
            white-space: pre-wrap;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
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
                <div class="doc-title">{{ $docTitle }}</div>
                <div class="doc-number">Бр. {{ $docNumber }}</div>
            </div>
        </div>

        {{-- Info Section --}}
        <div class="info-section">
            @if($client)
            <div class="info-left">
                <div class="section-title">Клиент</div>
                <div class="client-name">{{ $client->company ?? $client->name }}</div>
                <div class="client-info">
                    @if($client->address){{ $client->address }}<br>@endif
                    @if($client->postal_code || $client->city){{ $client->postal_code }} {{ $client->city }}<br>@endif
                    @if($client->tax_number)ЕДБ: {{ $client->tax_number }}@endif
                </div>
            </div>
            @endif
            <div class="{{ $client ? 'info-right' : 'info-left' }}">
                <div class="section-title">Детали</div>
                <table class="details-table">
                    <tr>
                        <td class="label">Датум:</td>
                        <td class="value">{{ $issueDate }}</td>
                    </tr>
                    <tr>
                        <td class="label">Број на ставки:</td>
                        <td class="value">{{ count($movements) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Items Table --}}
        @if(count($movements) > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Име</th>
                    <th class="right" style="width: 16%;">Количина</th>
                    <th class="right" style="width: 17%;">Пред</th>
                    <th class="right" style="width: 17%;">После</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $movement)
                <tr>
                    <td>{{ $movement->article->name ?? '-' }}</td>
                    <td class="right">{{ number_format($movement->quantity, 2, ',', ' ') }} {{ $movement->article->unit ?? '' }}</td>
                    <td class="right">{{ number_format($movement->quantity_before, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($movement->quantity_after, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
    </div>
</body>
</html>
