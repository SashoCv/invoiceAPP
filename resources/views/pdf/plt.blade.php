<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Приемен лист {{ $receipt->receipt_number }}</title>
    @php
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
        $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',') . '%';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #111827; line-height: 1.35; }
        .page { padding: 20px 25px; }

        .head { display: table; width: 100%; margin-bottom: 12px; }
        .head > div { display: table-cell; vertical-align: top; font-size: 8.5pt; }
        .head .label { color: #4b5563; display: inline-block; min-width: 70px; }
        .form-name { text-align: right; font-weight: bold; font-size: 9pt; }

        .title { text-align: center; font-size: 12pt; font-weight: bold; margin: 6px 0 10px; }

        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th, table.rep td { border: 1px solid #6b7280; padding: 3px 4px; }
        table.rep th { background-color: #f3f4f6; font-size: 7.5pt; font-weight: bold; text-align: center; }
        table.rep td { font-size: 7.5pt; }
        tr.colnum td { text-align: center; font-size: 7pt; color: #6b7280; background: #fafafa; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        tr.total td { font-weight: bold; background-color: #f3f4f6; border-top: 2px solid #111827; }

        .note { font-size: 7pt; color: #6b7280; margin-top: 8px; }
        .signature { margin-top: 36px; display: table; width: 100%; }
        .signature div { display: table-cell; width: 50%; text-align: center; font-size: 8pt; }
        .signature .line { border-top: 1px solid #111827; display: inline-block; padding-top: 3px; min-width: 200px; }
    </style>
</head>
<body>
    <div class="page">
        <div class="head">
            <div style="width: 40%;">
                <div><span class="label">Трговец:</span> {{ $agency->name ?? '' }}</div>
                <div><span class="label">Адреса:</span> {{ $agency->address ?? '' }}</div>
                <div><span class="label">Место:</span> {{ $agency->city ?? '' }}</div>
                <div><span class="label">ЕДБ:</span> {{ $agency->tax_number ?? '' }}</div>
            </div>
            <div style="width: 40%;">
                <div><span class="label">Бр. на док.:</span> {{ $receipt->receipt_number }}</div>
                <div><span class="label">Датум:</span> {{ $receipt->date?->format('d.m.Y') }}</div>
                <div><span class="label">Документ:</span> {{ $typeLabel }}</div>
                <div><span class="label">Седиште:</span> {{ $receipt->notes }}</div>
            </div>
            <div style="width: 20%;" class="form-name">ОБРАЗЕЦ „ПЛТ“</div>
        </div>

        <div class="title">Приемен лист во трговија на мало број {{ $receipt->receipt_number }}</div>

        <table class="rep">
            <thead>
                <tr class="colnum"><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td><td>8</td><td>9</td><td>10</td><td>11</td></tr>
                <tr>
                    <th rowspan="2" style="width: 4%;">Реден бр.</th>
                    <th rowspan="2">Назив на стоките</th>
                    <th rowspan="2" style="width: 5%;">Ед. мера</th>
                    <th rowspan="2" style="width: 6%;">Количина</th>
                    <th colspan="2">Набавна вредност</th>
                    <th style="width: 8%;">6×8</th>
                    <th rowspan="2" style="width: 7%;">Стапка на ДДВ (пропишана)</th>
                    <th colspan="2">Продажна вредност</th>
                    <th style="width: 9%;">Колона 10× пропишана ДДВ</th>
                </tr>
                <tr>
                    <th style="width: 8%;">Единечна цена</th>
                    <th style="width: 9%;">Износ (4×5)</th>
                    <th>ДДВ при набавка</th>
                    <th style="width: 8%;">Единечна цена</th>
                    <th style="width: 9%;">Износ (4×9)</th>
                    <th>Вк. ДДВ во прод. вред.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $r)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $r['name'] }}</td>
                    <td class="center">{{ $r['unit'] }}</td>
                    <td class="right">{{ $fmt($r['quantity']) }}</td>
                    <td class="right">{{ number_format((float) $r['unit_cost'], 4, ',', ' ') }}</td>
                    <td class="right">{{ $fmt($r['cost']) }}</td>
                    <td class="right">{{ $fmt($r['cost_vat']) }}</td>
                    <td class="center">{{ $pct($r['rate']) }}</td>
                    <td class="right">{{ $fmt($r['retail_unit']) }}</td>
                    <td class="right">{{ $fmt($r['retail']) }}</td>
                    <td class="right">{{ $fmt($r['retail_vat']) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td colspan="5" class="right">Вкупно</td>
                    <td class="right">{{ $fmt($totals['cost']) }}</td>
                    <td class="right">{{ $fmt($totals['cost_vat']) }}</td>
                    <td></td>
                    <td></td>
                    <td class="right">{{ $fmt($totals['retail']) }}</td>
                    <td class="right">{{ $fmt($totals['retail_vat']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note">
            @if((float) $receipt->dependent_costs > 0)
                Единечната набавна цена ги вклучува зависните трошоци на набавка (транспорт, шпедиција, царина) од {{ $fmt($receipt->dependent_costs) }} ден., распределени сразмерно на вредноста на ставките.
            @endif
            @if($receipt->type === 'customer_return')
                Поврат од купувач — стоката е вратена по набавната и продажната цена по која излегла{{ $receipt->invoice ? ' со фактура ' . $receipt->invoice->invoice_number : '' }}.
            @endif
            @if($receipt->type === 'customer_return')
                Во Образец ЕТ: кол. 6 = кол. 10 ({{ $fmt($totals['retail']) }} ден.); повратот не е набавка, па нема износ во кол. 5.
            @else
                Во Образец ЕТ: кол. 5 = кол. 6 + 7 ({{ $fmt($totals['cost'] + $totals['cost_vat']) }} ден.), кол. 6 = кол. 10 ({{ $fmt($totals['retail']) }} ден.).
            @endif
        </div>

        <div class="signature">
            <div><span class="line">Печат</span></div>
            <div><span class="line">Потпис на овластено лице{{ $authorizedPerson ? ': ' . $authorizedPerson : '' }}</span></div>
        </div>
    </div>
</body>
</html>
