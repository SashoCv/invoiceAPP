<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Записник за нивелација {{ $number }}</title>
    @php $fmt = fn ($n) => number_format((float) $n, 2, ',', ' '); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #111827; line-height: 1.35; }
        .page { padding: 20px 25px; }

        .top { display: table; width: 100%; margin-bottom: 12px; }
        .top-left { display: table-cell; width: 60%; vertical-align: top; font-size: 9pt; }
        .top-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; font-size: 8.5pt; color: #374151; }
        .top-left .company { font-weight: bold; font-size: 11pt; }

        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 4px 0 2px; }
        .subtitle { text-align: center; font-size: 9pt; color: #4b5563; margin-bottom: 10px; }

        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th, table.rep td { border: 1px solid #6b7280; padding: 3px 4px; }
        table.rep th { background-color: #f3f4f6; font-size: 7.5pt; font-weight: bold; text-align: center; }
        table.rep td { font-size: 7.5pt; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .neg { color: #b91c1c; }
        tr.doc td { font-weight: bold; background-color: #eef2ff; }
        tr.subtotal td { font-weight: bold; background-color: #f9fafb; }
        tr.total td { font-weight: bold; background-color: #f3f4f6; border-top: 2px solid #111827; }
        .note { font-size: 7pt; color: #6b7280; margin-top: 8px; }
        .signature { margin-top: 36px; display: table; width: 100%; }
        .signature div { display: table-cell; width: 50%; text-align: center; font-size: 8pt; }
        .signature .line { border-top: 1px solid #111827; display: inline-block; padding-top: 3px; min-width: 200px; }
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
                <div>Број: <strong>{{ $number }}</strong></div>
                <div>Датум: {{ $date }}</div>
                <div>{{ $printedAt }}</div>
            </div>
        </div>

        <div class="title">Записник за нивелација (промена на продажни цени)</div>
        <div class="subtitle">Разлика меѓу полната продажна вредност со ДДВ на стоката во евиденцијата и продадената вредност (попусти) — за {{ $date }}</div>

        <table class="rep">
            <thead>
                <tr>
                    <th style="width: 3%;">Рб</th>
                    <th style="width: 11%;">Шифра</th>
                    <th>Назив на артикл</th>
                    <th style="width: 4%;">Ед.</th>
                    <th style="width: 6%;">Кол.</th>
                    <th style="width: 9%;">Полна прод. цена со ДДВ</th>
                    <th style="width: 10%;">Полна прод. вредност со ДДВ</th>
                    <th style="width: 10%;">Продадено со ДДВ</th>
                    <th style="width: 10%;">Нивелација (разлика)</th>
                    <th style="width: 9%;">ДДВ во разликата</th>
                </tr>
            </thead>
            <tbody>
                @php $rb = 0; @endphp
                @forelse($docs as $doc)
                    <tr class="doc"><td colspan="10">{{ $doc['label'] }}{{ $doc['partner'] ? ' — ' . $doc['partner'] : '' }}</td></tr>
                    @foreach($doc['lines'] as $l)
                    <tr>
                        <td class="center">{{ ++$rb }}</td>
                        <td>{{ $l['code'] ?: '-' }}</td>
                        <td>{{ $l['name'] }}</td>
                        <td class="center">{{ $l['unit'] }}</td>
                        <td class="right">{{ $fmt($l['quantity']) }}</td>
                        <td class="right">{{ $fmt($l['full_unit']) }}</td>
                        <td class="right">{{ $fmt($l['full_value']) }}</td>
                        <td class="right">{{ $fmt($l['sold_value']) }}</td>
                        <td class="right {{ $l['difference'] < 0 ? 'neg' : '' }}">{{ $fmt($l['difference']) }}</td>
                        <td class="right">{{ $fmt($l['difference_tax']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="6" class="right">Вкупно {{ $doc['label'] }}</td>
                        <td class="right">{{ $fmt($doc['totals']['full_value']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['sold_value']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['difference']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['difference_tax']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="center" style="padding: 14px; color: #9ca3af;">Нема нивелација за овој ден.</td></tr>
                @endforelse
                <tr class="total">
                    <td colspan="6" class="right">ВКУПНО НИВЕЛАЦИЈА</td>
                    <td class="right">{{ $fmt($totals['full_value']) }}</td>
                    <td class="right">{{ $fmt($totals['sold_value']) }}</td>
                    <td class="right">{{ $fmt($totals['difference']) }}</td>
                    <td class="right">{{ $fmt($totals['difference_tax']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note">
            Износот „Вкупно нивелација“ е книжен во Образец ЕТ, колона 6 (продажна вредност), под број {{ $number }}.
            Негативен износ = намалување на продажната вредност на залихата (црвено сторно).
        </div>

        <div class="signature">
            <div><span class="line">Составил</span></div>
            <div><span class="line">Одговорно лице{{ $authorizedPerson ? ': ' . $authorizedPerson : '' }}</span></div>
        </div>
    </div>
</body>
</html>
