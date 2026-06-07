<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Излезна калкулација {{ $documentNumber }}</title>
    @php $fmt = fn ($n) => number_format((float) $n, 2, ',', ' '); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #111827;
            line-height: 1.3;
        }

        .page { padding: 18px 22px; }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 2px solid #111827;
            padding-bottom: 4px;
        }
        .company-info {
            text-align: center;
            font-size: 8pt;
            color: #374151;
            padding: 3px 0 10px;
            border-bottom: 1px solid #9ca3af;
            margin-bottom: 12px;
        }

        .doc-title { font-size: 13pt; font-weight: bold; margin-bottom: 8px; }

        .meta {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            font-size: 8.5pt;
        }
        .meta-left { display: table-cell; width: 50%; vertical-align: top; }
        .meta-right { display: table-cell; width: 50%; vertical-align: top; }
        .meta .label { color: #6b7280; display: inline-block; width: 90px; }
        .meta-right .label { width: 110px; }

        table.calc { width: 100%; border-collapse: collapse; }
        table.calc th, table.calc td { border: 1px solid #6b7280; padding: 3px 4px; }
        table.calc thead th {
            background-color: #f3f4f6;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        table.calc td { font-size: 7.5pt; }
        .right { text-align: right; }
        .center { text-align: center; }

        tr.grand td {
            font-weight: bold;
            background-color: #f9fafb;
            border-top: 2px solid #111827;
        }

        .summary {
            margin-top: 18px;
            width: 70%;
            border-collapse: collapse;
        }
        .summary th, .summary td { border: 1px solid #6b7280; padding: 4px 6px; font-size: 8pt; }
        .summary th { background-color: #f3f4f6; font-weight: bold; text-align: right; }
        .summary th:first-child { text-align: left; }
        .summary td { text-align: right; }
        .summary td:first-child { text-align: left; font-weight: bold; }
        .summary tr.total td { font-weight: bold; background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        @if($agency)
            <div class="company-name">{{ $agency->name }}</div>
            <div class="company-info">
                {{ collect([
                    $agency->address,
                    $agency->tax_number ? 'ЕДБ: ' . $agency->tax_number : null,
                    $agency->phone ? 'Тел: ' . $agency->phone : null,
                ])->filter()->implode(' * ') }}
            </div>
        @endif

        <div class="doc-title">Излезна калкулација</div>

        <div class="meta">
            <div class="meta-left">
                <div><span class="label">Број:</span> {{ $calcNumber }}</div>
                <div><span class="label">Документ:</span> {{ $documentNumber }}</div>
                <div><span class="label">Датум:</span> {{ $date }}</div>
            </div>
            <div class="meta-right">
                <div><span class="label">Од магацин:</span> 1 - главен магацин</div>
                <div><span class="label">За комитент:</span> {{ $client->company ?? $client->name ?? '' }}</div>
                <div><span class="label">Страна:</span> 1</div>
            </div>
        </div>

        {{-- Calculation table --}}
        <table class="calc">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">Шифра<br>Е.м.</th>
                    <th rowspan="2" style="width: 17%;">Назив<br>Количина</th>
                    <th colspan="2" style="width: 14%;">Набавна вред. без данок</th>
                    <th rowspan="2" style="width: 8%;">Пренесен данок</th>
                    <th rowspan="2" style="width: 6%;">Рабат</th>
                    <th colspan="2" style="width: 16%;">Продажна вред. со данок</th>
                    <th rowspan="2" style="width: 9%;">Пресметан данок</th>
                    <th rowspan="2" style="width: 9%;">Маржа износ</th>
                    <th rowspan="2" style="width: 5%;">ДДВ тар.</th>
                </tr>
                <tr>
                    <th>По единица</th>
                    <th>Износ</th>
                    <th>По единица</th>
                    <th>Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ $row['code'] ?: '-' }}<br>{{ $row['unit'] }}</td>
                    <td>{{ $row['name'] }}<br>{{ $fmt($row['quantity']) }}</td>
                    <td class="right">{{ $fmt($row['purchase_unit']) }}</td>
                    <td class="right">{{ $fmt($row['purchase_amount']) }}</td>
                    <td class="right">{{ $fmt($row['transferred_tax']) }}</td>
                    <td class="right">{{ $fmt($row['discount']) }}</td>
                    <td class="right">{{ $fmt($row['sales_unit']) }}</td>
                    <td class="right">{{ $fmt($row['sales_amount']) }}</td>
                    <td class="right">{{ $fmt($row['calc_tax']) }}</td>
                    <td class="right">{{ $fmt($row['margin']) }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($row['tax_rate'], 2, '.', ''), '0'), '.') }}%</td>
                </tr>
                @endforeach
                <tr class="grand">
                    <td colspan="3" class="right">Вкупно:</td>
                    <td class="right">{{ $fmt($totals['purchase_amount']) }}</td>
                    <td class="right">{{ $fmt($totals['transferred_tax']) }}</td>
                    <td class="right">{{ $fmt($totals['discount']) }}</td>
                    <td></td>
                    <td class="right">{{ $fmt($totals['sales_amount']) }}</td>
                    <td class="right">{{ $fmt($totals['calc_tax']) }}</td>
                    <td class="right">{{ $fmt($totals['margin']) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        {{-- Tariff summary --}}
        <table class="summary">
            <thead>
                <tr>
                    <th>Тарифа</th>
                    <th>Набавен износ</th>
                    <th>Рабат</th>
                    <th>Прод.из.без ДДВ</th>
                    <th>ДДВ</th>
                    <th>Прод.из.со ДДВ</th>
                    <th>Маржа</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tariffs as $tf)
                <tr>
                    <td>{{ rtrim(rtrim(number_format($tf['rate'], 2, '.', ''), '0'), '.') }}%</td>
                    <td>{{ $fmt($tf['purchase_amount']) }}</td>
                    <td>{{ $fmt($tf['discount']) }}</td>
                    <td>{{ $fmt($tf['sales_no_tax']) }}</td>
                    <td>{{ $fmt($tf['tax']) }}</td>
                    <td>{{ $fmt($tf['sales_with_tax']) }}</td>
                    <td>{{ $fmt($tf['margin']) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td>Вкупно</td>
                    <td>{{ $fmt($totals['purchase_amount']) }}</td>
                    <td>{{ $fmt($totals['discount']) }}</td>
                    <td>{{ $fmt($totals['sales_amount'] - $totals['calc_tax']) }}</td>
                    <td>{{ $fmt($totals['calc_tax']) }}</td>
                    <td>{{ $fmt($totals['sales_amount']) }}</td>
                    <td>{{ $fmt($totals['margin']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
