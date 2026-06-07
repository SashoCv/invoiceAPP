<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Евиденција во трговија</title>
    @php
        $typeLabels = [
            'receipt' => 'Прием од магацин',
            'issue' => 'Испратница',
            'invoice' => 'Фактура',
            'fiscal' => 'Дн. фис. извештај',
        ];
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #111827;
            line-height: 1.35;
        }

        .page { padding: 20px 25px; }

        .top {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .top-left { display: table-cell; width: 60%; vertical-align: top; font-size: 9pt; }
        .top-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; font-size: 8.5pt; color: #374151; }

        .top-left .company { font-weight: bold; font-size: 10pt; }

        .doc-form { font-weight: bold; margin-top: 4px; }

        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 6px 0 10px;
        }

        .period { font-size: 9pt; margin-bottom: 8px; }

        table.ledger {
            width: 100%;
            border-collapse: collapse;
        }

        table.ledger th,
        table.ledger td {
            border: 1px solid #374151;
            padding: 4px 5px;
            vertical-align: middle;
        }

        table.ledger th {
            background-color: #f3f4f6;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
        }

        table.ledger td { font-size: 8pt; }

        .right { text-align: right; }
        .center { text-align: center; }

        .colnum td {
            text-align: center;
            font-size: 7pt;
            font-style: italic;
            color: #6b7280;
            padding: 1px;
        }

        tr.total td {
            font-weight: bold;
            background-color: #f9fafb;
        }

        .signature {
            margin-top: 50px;
            float: right;
            width: 320px;
            text-align: center;
        }

        .signature .line {
            border-top: 1px solid #111827;
            padding-top: 5px;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="top">
            <div class="top-left">
                @if($agency)
                    <div class="company">{{ $agency->name }}</div>
                    <div>Продавница</div>
                    @if($agency->address)<div>{{ $agency->address }}</div>@endif
                    @if($agency->postal_code || $agency->city)<div>{{ $agency->postal_code }} {{ $agency->city }}</div>@endif
                    @if($agency->tax_number)<div>ЕДБ: {{ $agency->tax_number }}</div>@endif
                @endif
            </div>
            <div class="top-right">
                @if($authorizedPerson)<div>{{ $authorizedPerson }}</div>@endif
                <div>{{ $printedAt }}</div>
                <div class="doc-form">Образец ЕТ</div>
            </div>
        </div>

        <div class="title">Евиденција во трговија за {{ $year }} година</div>
        <div class="period">Од датум: {{ $dateFrom }} до датум: {{ $dateTo }}</div>

        {{-- Ledger table --}}
        <table class="ledger">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 4%;">Ред. бр.</th>
                    <th rowspan="2" style="width: 9%;">Датум на книжење</th>
                    <th colspan="2" style="width: 40%;">Книговодствен документ<br>Назив и број на документот</th>
                    <th rowspan="2" style="width: 9%;">Датум на документот</th>
                    <th rowspan="2" style="width: 13%;">Набавна вред. на стоките</th>
                    <th rowspan="2" style="width: 13%;">Продажна вред. на стоките</th>
                    <th rowspan="2" style="width: 12%;">Дневен промет</th>
                </tr>
                <tr>
                    <th style="width: 24%;">Назив</th>
                    <th style="width: 16%;">Број</th>
                </tr>
                <tr class="colnum">
                    <td>1</td><td>2</td><td colspan="2">3</td><td>4</td><td>5</td><td>6</td><td>7</td>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="center">{{ $row['row_no'] }}</td>
                    <td class="center">{{ $row['booking_date'] }}</td>
                    <td>{{ $typeLabels[$row['type']] ?? $row['type'] }}</td>
                    <td>{{ $row['doc_number'] }}</td>
                    <td class="center">{{ $row['doc_date'] }}</td>
                    <td class="right">{{ $row['purchase_value'] > 0 ? $fmt($row['purchase_value']) : '0,00' }}</td>
                    <td class="right">{{ $row['sales_value'] > 0 ? $fmt($row['sales_value']) : '0,00' }}</td>
                    <td class="right">{{ $row['daily_turnover'] > 0 ? $fmt($row['daily_turnover']) : '0,00' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="center" style="padding: 18px; color: #9ca3af;">Нема записи за избраниот период.</td>
                </tr>
                @endforelse

                <tr class="total">
                    <td colspan="5" class="right">Вкупно за период</td>
                    <td class="right">{{ $fmt($periodTotals['purchase_value']) }}</td>
                    <td class="right">{{ $fmt($periodTotals['sales_value']) }}</td>
                    <td class="right">{{ $fmt($periodTotals['daily_turnover']) }}</td>
                </tr>
                <tr class="total">
                    <td colspan="5" class="right">Вкупно</td>
                    <td class="right">{{ $fmt($grandTotals['purchase_value']) }}</td>
                    <td class="right">{{ $fmt($grandTotals['sales_value']) }}</td>
                    <td class="right">{{ $fmt($grandTotals['daily_turnover']) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Signature --}}
        <div class="signature">
            <div class="line">Потпис на овластено лице</div>
        </div>
    </div>
</body>
</html>
