<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Материјална евиденција — {{ $article->name }}</title>
    @php $q = fn ($n) => number_format((float) $n, 2, ',', ' '); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #111827; line-height: 1.35; }
        .page { padding: 20px 25px; }

        .top { display: table; width: 100%; margin-bottom: 10px; }
        .top-left { display: table-cell; width: 60%; vertical-align: top; font-size: 9pt; }
        .top-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; font-size: 8.5pt; color: #374151; }
        .top-left .company { font-weight: bold; font-size: 11pt; }

        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 4px 0 2px; }
        .subtitle { text-align: center; font-size: 9.5pt; margin-bottom: 4px; }
        .period { text-align: center; font-size: 8.5pt; color: #4b5563; margin-bottom: 10px; }

        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th, table.rep td { border: 1px solid #6b7280; padding: 3px 4px; }
        table.rep th { background-color: #f3f4f6; font-size: 7.5pt; font-weight: bold; text-align: center; }
        table.rep td { font-size: 7.5pt; }
        tr.colnum td { text-align: center; font-size: 7pt; color: #6b7280; background: #fafafa; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        tr.opening td { font-style: italic; background: #f9fafb; }
        tr.total td { font-weight: bold; background-color: #f3f4f6; border-top: 2px solid #111827; }

        .signature { margin-top: 30px; text-align: right; font-size: 8pt; }
        .signature .line { border-top: 1px solid #111827; display: inline-block; padding-top: 3px; min-width: 220px; text-align: center; }
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
                <div><strong>ОБРАЗЕЦ „МЕТГ“</strong></div>
                <div>{{ $printedAt }}</div>
            </div>
        </div>

        <div class="title">Материјална евиденција во трговија на големо</div>
        <div class="subtitle">Шифра: <strong>{{ $article->code ?: '-' }}</strong> &nbsp; Назив: <strong>{{ $article->name }}</strong> &nbsp; Ед. мера: <strong>{{ $article->unit }}</strong></div>
        <div class="period">Од {{ $dateFrom }} до {{ $dateTo }}</div>

        <table class="rep">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">Реден бр.</th>
                    <th rowspan="2" style="width: 9%;">Датум на книжење</th>
                    <th colspan="3">Книговодствен документ</th>
                    <th colspan="3">Количина</th>
                </tr>
                <tr>
                    <th style="width: 12%;">Број</th>
                    <th style="width: 9%;">Датум</th>
                    <th>Назив на документот и добавувач / купувач</th>
                    <th style="width: 9%;">Набавена</th>
                    <th style="width: 9%;">Продадена</th>
                    <th style="width: 9%;">Состојба</th>
                </tr>
                <tr class="colnum"><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td></tr>
            </thead>
            <tbody>
                <tr class="opening">
                    <td></td>
                    <td class="center">{{ $dateFrom }}</td>
                    <td colspan="3">Почетна состојба (пренос)</td>
                    <td></td>
                    <td></td>
                    <td class="right">{{ $q($opening) }}</td>
                </tr>
                @foreach($rows as $i => $r)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="center">{{ $r['date'] }}</td>
                    <td>{{ $r['number'] ?: '-' }}</td>
                    <td class="center">{{ $r['date'] }}</td>
                    <td>{{ $r['name'] }}</td>
                    <td class="right">{{ $r['in'] ? $q($r['in']) : '' }}</td>
                    <td class="right">{{ $r['out'] ? $q($r['out']) : '' }}</td>
                    <td class="right">{{ $q($r['balance']) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td colspan="5" class="right">Вкупно</td>
                    <td class="right">{{ $q($totals['in']) }}</td>
                    <td class="right">{{ $q($totals['out']) }}</td>
                    <td class="right">{{ $q($totals['balance']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="signature"><span class="line">Потпис на овластено лице и печат</span></div>
    </div>
</body>
</html>
