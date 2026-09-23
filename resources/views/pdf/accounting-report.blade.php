<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    @php
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
        $date = fn ($d) => \Carbon\Carbon::parse($d)->format('d.m.Y');
        $isOut = $tab === 'outputs';
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
        .subtitle { text-align: center; font-size: 9pt; color: #4b5563; margin-bottom: 8px; }
        .period { font-size: 9pt; margin-bottom: 8px; }
        h3 { font-size: 10pt; margin: 14px 0 6px; }

        table.rep { width: 100%; border-collapse: collapse; }
        table.rep th, table.rep td { border: 1px solid #6b7280; padding: 3px 4px; }
        table.rep th { background-color: #f3f4f6; font-size: 7.5pt; font-weight: bold; text-align: center; }
        table.rep td { font-size: 7.5pt; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        tr.month td { font-weight: bold; background-color: #eef2ff; }
        tr.subtype td { color: #4b5563; font-style: italic; background-color: #fafafa; }
        tr.subtotal td { font-weight: bold; background-color: #f9fafb; }
        tr.total td { font-weight: bold; background-color: #f3f4f6; border-top: 2px solid #111827; }
        .note { font-size: 7pt; color: #6b7280; margin-top: 8px; }
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

        <div class="title">{{ $title }}</div>
        <div class="subtitle">{{ $tab === 'leveling' ? 'Образец ЕТ — продажни цени со ДДВ' : 'Набавни цени по подвижна пондерирана просечна цена; продажни цени со ДДВ по Образец ЕТ' }}{{ $typeLabel ? ' — ' . $typeLabel : '' }}</div>
        <div class="period">Од датум: {{ $dateFrom }} до датум: {{ $dateTo }}</div>

        @if($tab === 'stock')
            <table class="rep">
                <thead>
                    <tr>
                        <th rowspan="2">Месец</th>
                        <th colspan="2">Почетна состојба</th>
                        <th colspan="2">Влез</th>
                        <th colspan="2">Излез</th>
                        <th colspan="2">Крајна состојба</th>
                    </tr>
                    <tr>
                        <th>Кол.</th><th>Вредност</th>
                        <th>Кол.</th><th>Вредност</th>
                        <th>Кол.</th><th>Вредност</th>
                        <th>Кол.</th><th>Вредност</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['months'] as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td class="right">{{ $fmt($m['opening_qty']) }}</td>
                        <td class="right">{{ $fmt($m['opening_value']) }}</td>
                        <td class="right">{{ $fmt($m['in_qty']) }}</td>
                        <td class="right">{{ $fmt($m['in_value']) }}</td>
                        <td class="right">{{ $fmt($m['out_qty']) }}</td>
                        <td class="right">{{ $fmt($m['out_value']) }}</td>
                        <td class="right">{{ $fmt($m['closing_qty']) }}</td>
                        <td class="right">{{ $fmt($m['closing_value']) }}</td>
                    </tr>
                    @endforeach
                    @php $t = $report['totals']; @endphp
                    <tr class="total">
                        <td>Вкупно за период</td>
                        <td class="right">{{ $fmt($t['opening_qty']) }}</td>
                        <td class="right">{{ $fmt($t['opening_value']) }}</td>
                        <td class="right">{{ $fmt($t['in_qty']) }}</td>
                        <td class="right">{{ $fmt($t['in_value']) }}</td>
                        <td class="right">{{ $fmt($t['out_qty']) }}</td>
                        <td class="right">{{ $fmt($t['out_value']) }}</td>
                        <td class="right">{{ $fmt($t['closing_qty']) }}</td>
                        <td class="right">{{ $fmt($t['closing_value']) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="note">
                Контрола: {{ $fmt($t['opening_value']) }} + {{ $fmt($t['in_value']) }} − {{ $fmt($t['out_value']) }} = {{ $fmt($t['closing_value']) }}
            </div>

            <h3>Лагер листа на ден {{ $dateTo }}</h3>
            <table class="rep">
                <thead>
                    <tr>
                        <th style="width: 4%;">Рб</th>
                        <th style="width: 14%;">Шифра</th>
                        <th>Назив на артикл</th>
                        <th style="width: 6%;">Ед.</th>
                        <th style="width: 10%;">Количина</th>
                        <th style="width: 11%;">Просечна набавна цена</th>
                        <th style="width: 12%;">Набавна вредност</th>
                        <th style="width: 11%;">Продажна цена со ДДВ</th>
                        <th style="width: 12%;">Продажна вредност со ДДВ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['list'] as $i => $r)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $r['code'] ?: '-' }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="center">{{ $r['unit'] }}</td>
                        <td class="right">{{ $fmt($r['quantity']) }}</td>
                        <td class="right">{{ number_format((float) $r['avg_cost'], 4, ',', ' ') }}</td>
                        <td class="right">{{ $fmt($r['value']) }}</td>
                        <td class="right">{{ $fmt($r['retail_price']) }}</td>
                        <td class="right">{{ $fmt($r['retail_value']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="center" style="padding: 14px; color: #9ca3af;">Нема залиха.</td></tr>
                    @endforelse
                    <tr class="total">
                        <td colspan="4" class="right">Вкупно</td>
                        <td class="right">{{ $fmt($report['list_totals']['quantity']) }}</td>
                        <td></td>
                        <td class="right">{{ $fmt($report['list_totals']['value']) }}</td>
                        <td></td>
                        <td class="right">{{ $fmt($report['list_totals']['retail_value']) }}</td>
                    </tr>
                </tbody>
            </table>
        @elseif($tab === 'leveling')
            <table class="rep">
                <thead>
                    <tr>
                        <th style="width: 12%;">Број</th>
                        <th style="width: 8%;">Датум</th>
                        <th style="width: 7%;">Документи</th>
                        <th>Полна продажна со ДДВ</th>
                        <th>Продадено со ДДВ</th>
                        <th>Нивелација фактури</th>
                        <th>Нивелација е-трговија</th>
                        <th>Нивелација вкупно</th>
                        <th>ДДВ во нивелацијата</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lv = fn ($t) => [$t['full_value'], $t['sold_value'], $t['leveling_invoice'], $t['leveling_shopify'], $t['leveling'], $t['leveling_tax']]; @endphp
                    @forelse($report['months'] as $m)
                        <tr class="month"><td colspan="9">{{ $m['label'] }}</td></tr>
                        @foreach($m['rows'] as $r)
                        <tr>
                            <td>{{ $r['number'] }}</td>
                            <td class="center">{{ $date($r['date']) }}</td>
                            <td class="center">{{ $r['count'] }}</td>
                            @foreach($lv($r) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                        </tr>
                        @endforeach
                        <tr class="subtotal">
                            <td colspan="2" class="right">Вкупно {{ $m['label'] }}</td>
                            <td class="center">{{ $m['totals']['count'] }}</td>
                            @foreach($lv($m['totals']) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="9" class="center" style="padding: 14px; color: #9ca3af;">Нема нивелации за избраниот период.</td></tr>
                    @endforelse
                    <tr class="total">
                        <td colspan="2" class="right">ВКУПНО ЗА ПЕРИОД</td>
                        <td class="center">{{ $report['totals']['count'] }}</td>
                        @foreach($lv($report['totals']) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                    </tr>
                </tbody>
            </table>
            <div class="note">Нивелација = продадено со ДДВ − полна продажна вредност со ДДВ по која стоката е водена во Образец ЕТ (попусти). Негативен износ ја намалува продажната вредност на залихата. Секој ред е посебен записник за нивелација, книжен во ЕТ под истиот број.</div>
        @else
            @php $cols = $isOut ? 12 : 14; @endphp
            <table class="rep">
                <thead>
                    <tr>
                        <th style="width: 4%;">Рб</th>
                        <th style="width: 12%;">Тип</th>
                        <th style="width: 10%;">Број</th>
                        <th style="width: 7%;">Датум</th>
                        @if($isOut)<th>Партнер</th>@endif
                        <th style="width: 5%;">Ставки</th>
                        <th style="width: 7%;">Количина</th>
                        @if($isOut)
                            <th style="width: 9%;">Набавна вредност</th>
                            <th style="width: 9%;">Продажна без ДДВ</th>
                            <th style="width: 8%;">ДДВ</th>
                            <th style="width: 9%;">Продажна со ДДВ</th>
                            <th style="width: 8%;">РУЦ</th>
                        @else
                            <th style="width: 8%;">Набавна без ДДВ</th>
                            <th style="width: 7%;">ДДВ</th>
                            <th style="width: 8%;">Набавна со ДДВ</th>
                            <th style="width: 8%;">Продажна без ДДВ</th>
                            <th style="width: 7%;">ДДВ</th>
                            <th style="width: 8%;">Продажна со ДДВ</th>
                            <th style="width: 7%;">РУЦ</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $amounts = function ($r) use ($isOut, $fmt) {
                            return $isOut
                                ? [$r['cost_value'], $r['sales_no_tax'], $r['sales_tax'], $r['sales_with_tax'], $r['margin']]
                                : [$r['cost_value'], $r['cost_tax'], $r['cost_with_tax'], $r['sales_no_tax'], $r['sales_tax'], $r['sales_with_tax'], $r['margin']];
                        };
                        $labelSpan = $isOut ? 5 : 4;
                    @endphp

                    @forelse($report['months'] as $m)
                        <tr class="month"><td colspan="{{ $cols }}">{{ $m['label'] }}</td></tr>
                        @foreach($m['rows'] as $r)
                        <tr>
                            <td class="center">{{ $r['rb'] }}</td>
                            <td>{{ $r['type_label'] }}{{ $r['estimated'] ? ' *' : '' }}</td>
                            <td>{{ $r['number'] ?: '-' }}</td>
                            <td class="center">{{ $date($r['date']) }}</td>
                            @if($isOut)<td>{{ $r['partner'] ?: '-' }}</td>@endif
                            <td class="center">{{ $r['items'] }}</td>
                            <td class="right">{{ $fmt($r['quantity']) }}</td>
                            @foreach($amounts($r) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                        </tr>
                        @endforeach
                        @if(count($m['by_type']) > 1)
                            @foreach($m['by_type'] as $t)
                            <tr class="subtype">
                                <td colspan="{{ $labelSpan }}" class="right">{{ $t['label'] }} ({{ $t['count'] }})</td>
                                <td></td>
                                <td class="right">{{ $fmt($t['quantity']) }}</td>
                                @foreach($amounts($t) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                            </tr>
                            @endforeach
                        @endif
                        <tr class="subtotal">
                            <td colspan="{{ $labelSpan }}" class="right">Вкупно {{ $m['label'] }}</td>
                            <td class="center">{{ count($m['rows']) }}</td>
                            <td class="right">{{ $fmt($m['totals']['quantity']) }}</td>
                            @foreach($amounts($m['totals']) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $cols }}" class="center" style="padding: 14px; color: #9ca3af;">Нема документи за избраниот период.</td></tr>
                    @endforelse

                    <tr class="total">
                        <td colspan="{{ $labelSpan }}" class="right">ВКУПНО ЗА ПЕРИОД</td>
                        <td class="center">{{ $report['count'] }}</td>
                        <td class="right">{{ $fmt($report['totals']['quantity']) }}</td>
                        @foreach($amounts($report['totals']) as $v)<td class="right">{{ $fmt($v) }}</td>@endforeach
                    </tr>
                </tbody>
            </table>
            <div class="note">* Проценета набавна цена (почетна состојба или излез без претходен влез — земена е првата позната набавна цена на артиклот).</div>
        @endif
    </div>
</body>
</html>
