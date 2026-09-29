<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Извештај за нивелација на цени {{ $number }}</title>
    @php
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
        $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') . '%';
    @endphp
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

        table.rates { width: 60%; margin: 10px 0 0 auto; border-collapse: collapse; }
        table.rates th, table.rates td { border: 1px solid #6b7280; padding: 3px 6px; font-size: 7.5pt; }
        table.rates th { background-color: #f3f4f6; }

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

        <div class="title">Извештај за нивелација на цени бр. {{ $number }}</div>
        <div class="subtitle">Намалување на малопродажната цена (МПЦ) при продажба со попуст — {{ $date }}</div>

        <table class="rep">
            <thead>
                <tr>
                    <th style="width: 3%;">Р. бр.</th>
                    <th>Артикал</th>
                    <th style="width: 5%;">Кол.</th>
                    <th style="width: 4%;">Е.М.</th>
                    <th style="width: 5%;">ДДВ %</th>
                    <th style="width: 8%;">Стара МПЦ</th>
                    <th style="width: 9%;">Вкупно стара МПЦ</th>
                    <th style="width: 8%;">ДДВ во стара МПЦ</th>
                    <th style="width: 8%;">Нова МПЦ</th>
                    <th style="width: 9%;">Вкупно нова МПЦ</th>
                    <th style="width: 8%;">ДДВ во нова МПЦ</th>
                    <th style="width: 9%;">Разлика</th>
                </tr>
            </thead>
            <tbody>
                @php $rb = 0; @endphp
                @forelse($docs as $doc)
                    <tr class="doc"><td colspan="12">{{ $doc['label'] }}{{ $doc['partner'] ? ' — ' . $doc['partner'] : '' }}</td></tr>
                    @foreach($doc['lines'] as $l)
                    <tr>
                        <td class="center">{{ ++$rb }}</td>
                        <td>{{ $l['code'] ? $l['code'] . ' — ' : '' }}{{ $l['name'] }}</td>
                        <td class="right">{{ $fmt($l['quantity']) }}</td>
                        <td class="center">{{ $l['unit'] }}</td>
                        <td class="center">{{ $pct($l['rate']) }}</td>
                        <td class="right">{{ $fmt($l['old_price']) }}</td>
                        <td class="right">{{ $fmt($l['old_value']) }}</td>
                        <td class="right">{{ $fmt($l['old_vat']) }}</td>
                        <td class="right">{{ $fmt($l['new_price']) }}</td>
                        <td class="right">{{ $fmt($l['new_value']) }}</td>
                        <td class="right">{{ $fmt($l['new_vat']) }}</td>
                        <td class="right {{ $l['difference'] < 0 ? 'neg' : '' }}">{{ $fmt($l['difference']) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="6" class="right">Вкупно {{ $doc['label'] }}</td>
                        <td class="right">{{ $fmt($doc['totals']['old_value']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['old_vat']) }}</td>
                        <td></td>
                        <td class="right">{{ $fmt($doc['totals']['new_value']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['new_vat']) }}</td>
                        <td class="right">{{ $fmt($doc['totals']['difference']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="center" style="padding: 14px; color: #9ca3af;">Нема нивелација за овој ден.</td></tr>
                @endforelse
                <tr class="total">
                    <td colspan="6" class="right">ВКУПНО</td>
                    <td class="right">{{ $fmt($totals['old_value']) }}</td>
                    <td class="right">{{ $fmt($totals['old_vat']) }}</td>
                    <td></td>
                    <td class="right">{{ $fmt($totals['new_value']) }}</td>
                    <td class="right">{{ $fmt($totals['new_vat']) }}</td>
                    <td class="right">{{ $fmt($totals['difference']) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="rates">
            <thead>
                <tr>
                    <th>ДДВ стапка</th>
                    <th>Разлика помеѓу старо и ново ДДВ</th>
                    <th>Разлика помеѓу стара и нова МПЦ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byRate as $r)
                <tr>
                    <td class="center">{{ $pct($r['rate']) }}</td>
                    <td class="right">{{ $fmt($r['vat_difference']) }}</td>
                    <td class="right">{{ $fmt($r['price_difference']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="note">
            Разликата помеѓу стара и нова МПЦ ({{ $fmt($totals['difference']) }} ден.) е книжена во Образец ЕТ, колона 6 (продажна вредност) како црвено сторно, под број НИВ {{ $number }}.
            Стара МПЦ = продажна цена со ДДВ по која стоката е водена во евиденцијата; нова МПЦ = цена по која е продадена.
            Набавната вредност на залихата не се менува со нивелацијата.
        </div>

        <div class="signature">
            <div><span class="line">Составил</span></div>
            <div><span class="line">Одговорно лице{{ $authorizedPerson ? ': ' . $authorizedPerson : '' }}</span></div>
        </div>
    </div>
</body>
</html>
