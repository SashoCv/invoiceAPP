<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Дневен финансиски извештај</title>
    @php $fmt = fn ($n) => number_format((float) $n, 2, ',', ' '); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #111827; line-height: 1.35; }
        .page { padding: 20px 25px; }

        .top { display: table; width: 100%; margin-bottom: 12px; }
        .top-left { display: table-cell; width: 60%; vertical-align: top; font-size: 9pt; }
        .top-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; font-size: 8.5pt; color: #374151; }
        .top-left .company { font-weight: bold; font-size: 11pt; }

        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 4px 0 2px; }
        .subtitle { text-align: center; font-size: 10pt; font-weight: bold; color: #4f46e5; margin-bottom: 8px; }
        .period { font-size: 9pt; margin-bottom: 8px; }

        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th, table.rep td { border: 1px solid #6b7280; padding: 4px 5px; }
        table.rep th { background-color: #f3f4f6; font-size: 8pt; font-weight: bold; text-align: center; }
        table.rep td { font-size: 8pt; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        tr.total td { font-weight: bold; background-color: #f9fafb; border-top: 2px solid #111827; }
    </style>
</head>
<body>
    <div class="page">
        <div class="top">
            <div class="top-left">
                @if($agency)
                    <div class="company">{{ $agency->name }}</div>
                    @if($agency->address)<div>{{ $agency->address }} {{ $agency->city }}</div>@endif
                    @if($agency->tax_number)<div>ЕДБ: {{ $agency->tax_number }}</div>@endif
                @endif
            </div>
            <div class="top-right">
                <div>{{ $printedAt }}</div>
            </div>
        </div>

        <div class="title">Дневен финансиски извештај</div>
        <div class="subtitle">{{ $typeLabel }}</div>
        <div class="period">Од датум: {{ $dateFrom }} до датум: {{ $dateTo }}</div>

        <table class="rep">
            <thead>
                <tr>
                    <th style="width: 3%;">Рб</th>
                    <th style="width: 8%;">Шифра</th>
                    <th style="width: 23%;">Назив на артикл</th>
                    <th style="width: 5%;">Ед.</th>
                    <th style="width: 8%;">Количина</th>
                    <th style="width: 11%;">Набавна вредност</th>
                    <th style="width: 11%;">Продажна без ДДВ</th>
                    <th style="width: 10%;">ДДВ</th>
                    <th style="width: 11%;">Продажна со ДДВ</th>
                    <th style="width: 10%;">Маржа</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row['code'] ?: '-' }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="center">{{ $row['unit'] }}</td>
                    <td class="right">{{ $fmt($row['quantity']) }}</td>
                    <td class="right">{{ $fmt($row['purchase_value']) }}</td>
                    <td class="right">{{ $fmt($row['sales_no_tax']) }}</td>
                    <td class="right">{{ $fmt($row['tax']) }}</td>
                    <td class="right">{{ $fmt($row['sales_with_tax']) }}</td>
                    <td class="right">{{ $fmt($row['margin']) }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="center" style="padding: 18px; color: #9ca3af;">Нема продажби за избраниот период.</td></tr>
                @endforelse

                <tr class="total">
                    <td colspan="4" class="right">Вкупно</td>
                    <td class="right">{{ $fmt($totals['quantity']) }}</td>
                    <td class="right">{{ $fmt($totals['purchase_value']) }}</td>
                    <td class="right">{{ $fmt($totals['sales_no_tax']) }}</td>
                    <td class="right">{{ $fmt($totals['tax']) }}</td>
                    <td class="right">{{ $fmt($totals['sales_with_tax']) }}</td>
                    <td class="right">{{ $fmt($totals['margin']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
