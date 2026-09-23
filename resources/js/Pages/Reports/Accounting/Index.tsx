import { Fragment, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import { AlertTriangle, ArrowDownToLine, ArrowUpFromLine, Boxes, CheckCircle2, ChevronDown, ChevronRight, Download, FileSpreadsheet, FileText, ShoppingCart, TrendingDown } from 'lucide-react';

type Tab = 'inputs' | 'outputs' | 'leveling' | 'stock';

interface Totals {
    quantity: number;
    cost_value: number;
    cost_tax: number;
    cost_with_tax: number;
    sales_no_tax: number;
    sales_tax: number;
    sales_with_tax: number;
    margin: number;
}

interface TypeTotals extends Totals {
    type: string;
    label: string;
    count: number;
}

interface DocLine {
    code: string;
    name: string;
    unit: string;
    quantity: number;
    unit_cost: number;
    cost_value: number;
    retail_unit: number;
    retail_value: number;
    estimated: boolean;
}

interface DocRow extends Totals {
    key: string;
    rb: number;
    type: string;
    type_label: string;
    number: string | null;
    partner: string | null;
    date: string;
    items: number;
    estimated: boolean;
    lines: DocLine[];
}

interface DocMonth {
    month: string;
    label: string;
    rows: DocRow[];
    totals: Totals;
    by_type: TypeTotals[];
}

interface DocumentsReport {
    months: DocMonth[];
    totals: Totals;
    by_type: TypeTotals[];
    count: number;
}

interface StockMonth {
    month: string;
    label: string;
    opening_qty: number;
    opening_value: number;
    in_qty: number;
    in_value: number;
    out_qty: number;
    out_value: number;
    closing_qty: number;
    closing_value: number;
    in_by_type: { type: string; label: string; value: number }[];
    out_by_type: { type: string; label: string; value: number }[];
}

interface StockReport {
    months: StockMonth[];
    totals: Omit<StockMonth, 'month' | 'label' | 'in_by_type' | 'out_by_type'>;
    list: { code: string; name: string; unit: string; quantity: number; avg_cost: number; value: number; retail_price: number; retail_value: number }[];
    list_totals: { quantity: number; value: number; retail_value: number };
    checks: {
        balanced: boolean;
        discrepancies: { article_id: number; code: string | null; name: string; computed: number; actual: number }[];
        negative: { code: string; name: string }[];
        estimated_count: number;
    };
}

interface LevelingTotals {
    full_value: number;
    sold_value: number;
    leveling_invoice: number;
    leveling_shopify: number;
    leveling: number;
    leveling_tax: number;
    count: number;
}

interface LevelingReport {
    months: { month: string; label: string; rows: (LevelingTotals & { date: string; number: string })[]; totals: LevelingTotals }[];
    totals: LevelingTotals;
    count: number;
}

interface Props {
    tab: Tab;
    report: DocumentsReport | StockReport | LevelingReport;
    typeOptions: { value: string; label: string }[];
    filters: { date_from: string; date_to: string; type: string | null };
}

const pad = (n: number) => String(n).padStart(2, '0');
const ymd = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const n2 = (v: number) => formatNumber(v, 2);

export default function AccountingReportsIndex({ tab, report, typeOptions, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [type, setType] = useState(filters.type ?? '__all__');

    const query = (over: Record<string, string> = {}) => {
        const params: Record<string, string> = { tab, date_from: dateFrom, date_to: dateTo, type: filters.type ?? '__all__', ...over };
        if (params.type === '__all__') {
            delete params.type;
        }
        return params;
    };

    const go = (over: Record<string, string> = {}) => {
        router.get('/accounting-reports', query(over), { preserveState: false, preserveScroll: true });
    };

    const setPeriod = (from: string, to: string) => {
        setDateFrom(from);
        setDateTo(to);
        go({ date_from: from, date_to: to });
    };

    const now = new Date();
    const quickThisMonth = () => setPeriod(ymd(new Date(now.getFullYear(), now.getMonth(), 1)), ymd(now));
    const quickLastMonth = () => setPeriod(
        ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)),
        ymd(new Date(now.getFullYear(), now.getMonth(), 0)),
    );
    const quickThisYear = () => setPeriod(`${now.getFullYear()}-01-01`, ymd(now));

    const exportQuery = new URLSearchParams(query()).toString();

    const TabButton = ({ value, preset, icon: Icon, label, hint }: { value: Tab; preset?: string; icon: any; label: string; hint: string }) => {
        const active = tab === value && (preset ? filters.type === preset : !(value === 'outputs' && (filters.type === 'invoice' || filters.type === 'shopify')));
        return (
        <button
            onClick={() => { setType(preset ?? '__all__'); go({ tab: value, type: preset ?? '__all__' }); }}
            className={`flex items-center gap-2 px-4 py-2.5 rounded-lg border text-left transition-colors ${
                active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
            }`}
        >
            <Icon className="w-5 h-5 shrink-0" />
            <span>
                <span className="block text-sm font-semibold">{label}</span>
                <span className="block text-xs text-gray-400">{hint}</span>
            </span>
        </button>
        );
    };

    return (
        <AppLayout>
            <Head title={t('accounting.title')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('accounting.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('accounting.subtitle')}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href={`/accounting-reports/csv?${exportQuery}`} className="flex items-center gap-2">
                                <FileSpreadsheet className="w-4 h-4" />
                                {t('accounting.export_csv')}
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={`/accounting-reports/pdf?${exportQuery}`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2">
                                <Download className="w-4 h-4" />
                                {t('accounting.export_pdf')}
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-3 mb-6">
                    <TabButton value="inputs" icon={ArrowDownToLine} label={t('accounting.tab_inputs')} hint={t('accounting.tab_inputs_hint')} />
                    <TabButton value="outputs" icon={ArrowUpFromLine} label={t('accounting.tab_outputs')} hint={t('accounting.tab_outputs_hint')} />
                    <TabButton value="outputs" preset="invoice" icon={FileText} label={t('accounting.tab_invoices')} hint={t('accounting.tab_invoices_hint')} />
                    <TabButton value="outputs" preset="shopify" icon={ShoppingCart} label={t('accounting.tab_shopify')} hint={t('accounting.tab_shopify_hint')} />
                    <TabButton value="leveling" icon={TrendingDown} label={t('accounting.tab_leveling')} hint={t('accounting.tab_leveling_hint')} />
                    <TabButton value="stock" icon={Boxes} label={t('accounting.tab_stock')} hint={t('accounting.tab_stock_hint')} />
                </div>

                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('accounting.date_from')}</Label>
                                <Input type="date" value={dateFrom} max={dateTo} onChange={(e) => setDateFrom(e.target.value)} className="w-40 h-9" />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('accounting.date_to')}</Label>
                                <Input type="date" value={dateTo} min={dateFrom} onChange={(e) => setDateTo(e.target.value)} className="w-40 h-9" />
                            </div>
                            {(tab === 'inputs' || tab === 'outputs') && (
                                <div className="space-y-1">
                                    <Label className="text-xs text-gray-500">{t('accounting.type')}</Label>
                                    <Select value={type} onValueChange={setType}>
                                        <SelectTrigger className="w-56 h-9">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('accounting.all_types')}</SelectItem>
                                            {typeOptions.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <Button size="sm" className="h-9" onClick={() => go({ type })}>{t('accounting.filter')}</Button>
                            <div className="flex flex-wrap items-center gap-2 ml-auto">
                                <Button size="sm" variant="outline" className="h-9" onClick={quickThisMonth}>{t('accounting.this_month')}</Button>
                                <Button size="sm" variant="outline" className="h-9" onClick={quickLastMonth}>{t('accounting.last_month')}</Button>
                                <Button size="sm" variant="outline" className="h-9" onClick={quickThisYear}>{t('accounting.this_year')}</Button>
                            </div>
                        </div>
                        <p className="mt-3 text-xs text-gray-400">{t('accounting.method_note')}</p>
                    </CardContent>
                </Card>

                {tab === 'stock' && <StockView report={report as StockReport} dateTo={filters.date_to} />}
                {tab === 'leveling' && <LevelingView report={report as LevelingReport} />}
                {(tab === 'inputs' || tab === 'outputs') && <DocumentsView report={report as DocumentsReport} isOut={tab === 'outputs'} />}
            </div>
        </AppLayout>
    );
}

function DocumentsView({ report, isOut }: { report: DocumentsReport; isOut: boolean }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState<Record<string, boolean>>({});
    const toggle = (key: string) => setOpen((o) => ({ ...o, [key]: !o[key] }));

    const cols = isOut ? 12 : 14;
    const labelSpan = isOut ? 5 : 4;

    const amounts = (r: Totals) => (isOut
        ? [r.cost_value, r.sales_no_tax, r.sales_tax, r.sales_with_tax, r.margin]
        : [r.cost_value, r.cost_tax, r.cost_with_tax, r.sales_no_tax, r.sales_tax, r.sales_with_tax, r.margin]);

    const AmountCells = ({ r }: { r: Totals }) => (
        <>
            {amounts(r).map((v, i) => (
                <TableCell key={i} className="text-right whitespace-nowrap">{n2(v)}</TableCell>
            ))}
        </>
    );

    return (
        <>
            <Card>
                <CardContent className="p-0 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">{t('accounting.rb')}</TableHead>
                                <TableHead>{t('accounting.doc_type')}</TableHead>
                                <TableHead>{t('accounting.number')}</TableHead>
                                <TableHead>{t('accounting.date')}</TableHead>
                                {isOut && <TableHead>{t('accounting.partner')}</TableHead>}
                                <TableHead className="text-center">{t('accounting.items')}</TableHead>
                                <TableHead className="text-right">{t('accounting.quantity')}</TableHead>
                                {isOut ? (
                                    <>
                                        <TableHead className="text-right">{t('accounting.cost_value')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_no_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_with_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.margin')}</TableHead>
                                    </>
                                ) : (
                                    <>
                                        <TableHead className="text-right">{t('accounting.cost_no_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.cost_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.cost_with_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_no_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.sales_with_tax')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.margin')}</TableHead>
                                    </>
                                )}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.months.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={cols} className="text-center text-gray-400 py-10">{t('accounting.no_documents')}</TableCell>
                                </TableRow>
                            )}
                            {report.months.map((m) => (
                                <Fragment key={m.month}>
                                    <TableRow className="bg-indigo-50/60 hover:bg-indigo-50/60">
                                        <TableCell colSpan={cols} className="font-semibold text-indigo-800">{m.label}</TableCell>
                                    </TableRow>
                                    {m.rows.map((r) => (
                                        <Fragment key={r.key}>
                                            <TableRow className="cursor-pointer" onClick={() => toggle(r.key)}>
                                                <TableCell className="text-gray-500">
                                                    <span className="inline-flex items-center gap-1">
                                                        {open[r.key] ? <ChevronDown className="w-3.5 h-3.5" /> : <ChevronRight className="w-3.5 h-3.5" />}
                                                        {r.rb}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {r.type_label}
                                                    {r.estimated && <span className="ml-1 text-amber-600" title={t('accounting.estimated')}>*</span>}
                                                </TableCell>
                                                <TableCell className="font-medium text-gray-900">{r.number || '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(r.date)}</TableCell>
                                                {isOut && <TableCell className="max-w-[220px] truncate">{r.partner || '-'}</TableCell>}
                                                <TableCell className="text-center">{r.items}</TableCell>
                                                <TableCell className="text-right">{n2(r.quantity)}</TableCell>
                                                <AmountCells r={r} />
                                            </TableRow>
                                            {open[r.key] && (
                                                <TableRow className="bg-gray-50/70 hover:bg-gray-50/70">
                                                    <TableCell />
                                                    <TableCell colSpan={cols - 1} className="py-2">
                                                        <table className="w-full text-xs">
                                                            <thead>
                                                                <tr className="text-gray-500">
                                                                    <th className="text-left font-medium py-1">{t('accounting.code')}</th>
                                                                    <th className="text-left font-medium">{t('accounting.name')}</th>
                                                                    <th className="text-left font-medium">{t('accounting.unit')}</th>
                                                                    <th className="text-right font-medium">{t('accounting.quantity')}</th>
                                                                    <th className="text-right font-medium">{t('accounting.unit_cost')}</th>
                                                                    <th className="text-right font-medium">{t('accounting.cost_value')}</th>
                                                                    <th className="text-right font-medium">{t('accounting.retail_unit')}</th>
                                                                    <th className="text-right font-medium">{t('accounting.retail_value')}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {r.lines.map((l, i) => (
                                                                    <tr key={i} className="border-t border-gray-200">
                                                                        <td className="py-1 text-gray-500">{l.code || '-'}</td>
                                                                        <td>{l.name}{l.estimated && <span className="ml-1 text-amber-600">*</span>}</td>
                                                                        <td>{l.unit}</td>
                                                                        <td className="text-right">{n2(l.quantity)}</td>
                                                                        <td className="text-right">{formatNumber(l.unit_cost, 4)}</td>
                                                                        <td className="text-right">{n2(l.cost_value)}</td>
                                                                        <td className="text-right">{n2(l.retail_unit)}</td>
                                                                        <td className="text-right">{n2(l.retail_value)}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </Fragment>
                                    ))}
                                    {m.by_type.length > 1 && m.by_type.map((bt) => (
                                        <TableRow key={bt.type} className="text-gray-500 italic hover:bg-transparent">
                                            <TableCell colSpan={labelSpan} className="text-right">{bt.label} ({bt.count})</TableCell>
                                            <TableCell />
                                            <TableCell className="text-right">{n2(bt.quantity)}</TableCell>
                                            <AmountCells r={bt} />
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-gray-50 font-semibold hover:bg-gray-50">
                                        <TableCell colSpan={labelSpan} className="text-right">{t('accounting.month_total', { month: m.label })}</TableCell>
                                        <TableCell className="text-center">{m.rows.length}</TableCell>
                                        <TableCell className="text-right">{n2(m.totals.quantity)}</TableCell>
                                        <AmountCells r={m.totals} />
                                    </TableRow>
                                </Fragment>
                            ))}
                        </TableBody>
                        <tfoot className="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-900">
                            <TableRow>
                                <TableCell colSpan={labelSpan} className="text-right">{t('accounting.period_total')}</TableCell>
                                <TableCell className="text-center">{report.count}</TableCell>
                                <TableCell className="text-right">{n2(report.totals.quantity)}</TableCell>
                                <AmountCells r={report.totals} />
                            </TableRow>
                        </tfoot>
                    </Table>
                </CardContent>
            </Card>

            {report.by_type.length > 1 && (
                <Card className="mt-6">
                    <CardContent className="p-0 overflow-x-auto">
                        <Table>
                            <TableBody>
                                {report.by_type.map((bt) => (
                                    <TableRow key={bt.type}>
                                        <TableCell className="font-medium">{bt.label}</TableCell>
                                        <TableCell className="text-gray-500">{t('accounting.documents_count', { count: String(bt.count) })}</TableCell>
                                        <TableCell className="text-right">{n2(bt.quantity)}</TableCell>
                                        <AmountCells r={bt} />
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            <p className="mt-3 text-xs text-gray-400">{t('accounting.estimated_note')}</p>
        </>
    );
}

function StockView({ report, dateTo }: { report: StockReport; dateTo: string }) {
    const { t } = useTranslation();
    const tot = report.totals;
    const { checks } = report;
    const hasWarnings = checks.discrepancies.length > 0 || checks.negative.length > 0 || checks.estimated_count > 0;

    return (
        <>
            <Card className="mb-6">
                <CardContent className="p-0 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead rowSpan={2}>{t('accounting.month')}</TableHead>
                                <TableHead colSpan={2} className="text-center border-l">{t('accounting.opening')}</TableHead>
                                <TableHead colSpan={2} className="text-center border-l">{t('accounting.inputs')}</TableHead>
                                <TableHead colSpan={2} className="text-center border-l">{t('accounting.outputs')}</TableHead>
                                <TableHead colSpan={2} className="text-center border-l">{t('accounting.closing')}</TableHead>
                            </TableRow>
                            <TableRow>
                                {[0, 1, 2, 3].map((i) => (
                                    <Fragment key={i}>
                                        <TableHead className="text-right border-l">{t('accounting.qty_short')}</TableHead>
                                        <TableHead className="text-right">{t('accounting.value')}</TableHead>
                                    </Fragment>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.months.map((m) => (
                                <TableRow key={m.month}>
                                    <TableCell className="font-medium">{m.label}</TableCell>
                                    <TableCell className="text-right border-l">{n2(m.opening_qty)}</TableCell>
                                    <TableCell className="text-right">{n2(m.opening_value)}</TableCell>
                                    <TableCell className="text-right border-l">{n2(m.in_qty)}</TableCell>
                                    <TableCell className="text-right" title={m.in_by_type.map((x) => `${x.label}: ${n2(x.value)}`).join('\n')}>{n2(m.in_value)}</TableCell>
                                    <TableCell className="text-right border-l">{n2(m.out_qty)}</TableCell>
                                    <TableCell className="text-right" title={m.out_by_type.map((x) => `${x.label}: ${n2(x.value)}`).join('\n')}>{n2(m.out_value)}</TableCell>
                                    <TableCell className="text-right border-l">{n2(m.closing_qty)}</TableCell>
                                    <TableCell className="text-right font-medium">{n2(m.closing_value)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        <tfoot className="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-900">
                            <TableRow>
                                <TableCell>{t('accounting.period_total')}</TableCell>
                                <TableCell className="text-right border-l">{n2(tot.opening_qty)}</TableCell>
                                <TableCell className="text-right">{n2(tot.opening_value)}</TableCell>
                                <TableCell className="text-right border-l">{n2(tot.in_qty)}</TableCell>
                                <TableCell className="text-right">{n2(tot.in_value)}</TableCell>
                                <TableCell className="text-right border-l">{n2(tot.out_qty)}</TableCell>
                                <TableCell className="text-right">{n2(tot.out_value)}</TableCell>
                                <TableCell className="text-right border-l">{n2(tot.closing_qty)}</TableCell>
                                <TableCell className="text-right">{n2(tot.closing_value)}</TableCell>
                            </TableRow>
                        </tfoot>
                    </Table>
                </CardContent>
            </Card>

            <div className={`mb-6 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm ${checks.balanced ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'}`}>
                {checks.balanced ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
                <span className="font-semibold">{t('accounting.check')}:</span>
                <span>{n2(tot.opening_value)} + {n2(tot.in_value)} − {n2(tot.out_value)} = {n2(tot.closing_value)}</span>
                <span className="ml-auto font-semibold">{checks.balanced ? t('accounting.balanced') : t('accounting.not_balanced')}</span>
            </div>

            {hasWarnings && (
                <Card className="mb-6 border-amber-200">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base flex items-center gap-2 text-amber-800">
                            <AlertTriangle className="w-4 h-4" />
                            {t('accounting.warnings')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-gray-700">
                        {checks.estimated_count > 0 && <p>{t('accounting.estimated_count', { count: String(checks.estimated_count) })}</p>}
                        {checks.discrepancies.length > 0 && (
                            <div>
                                <p className="mb-1">{t('accounting.discrepancies')}</p>
                                <ul className="list-disc pl-5 space-y-0.5">
                                    {checks.discrepancies.map((d) => (
                                        <li key={d.article_id}>
                                            {d.code ? `${d.code} — ` : ''}{d.name}: {t('accounting.computed')} {n2(d.computed)}, {t('accounting.actual')} {n2(d.actual)}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        {checks.negative.length > 0 && (
                            <div>
                                <p className="mb-1">{t('accounting.negative')}</p>
                                <ul className="list-disc pl-5 space-y-0.5">
                                    {checks.negative.map((d, i) => (
                                        <li key={i}>{d.code ? `${d.code} — ` : ''}{d.name}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-base">{t('accounting.stock_list', { date: formatDate(dateTo) })}</CardTitle>
                </CardHeader>
                <CardContent className="p-0 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">{t('accounting.rb')}</TableHead>
                                <TableHead>{t('accounting.code')}</TableHead>
                                <TableHead>{t('accounting.name')}</TableHead>
                                <TableHead>{t('accounting.unit')}</TableHead>
                                <TableHead className="text-right">{t('accounting.quantity')}</TableHead>
                                <TableHead className="text-right">{t('accounting.avg_cost')}</TableHead>
                                <TableHead className="text-right">{t('accounting.value')}</TableHead>
                                <TableHead className="text-right">{t('accounting.retail_unit')}</TableHead>
                                <TableHead className="text-right">{t('accounting.retail_value')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.list.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-center text-gray-400 py-10">{t('accounting.no_stock')}</TableCell>
                                </TableRow>
                            ) : report.list.map((r, i) => (
                                <TableRow key={i}>
                                    <TableCell className="text-gray-500">{i + 1}</TableCell>
                                    <TableCell className="text-gray-500">{r.code || '-'}</TableCell>
                                    <TableCell className="font-medium text-gray-900">{r.name}</TableCell>
                                    <TableCell className="text-gray-500">{r.unit}</TableCell>
                                    <TableCell className={`text-right ${r.quantity < 0 ? 'text-red-600' : ''}`}>{n2(r.quantity)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(r.avg_cost, 4)}</TableCell>
                                    <TableCell className="text-right">{n2(r.value)}</TableCell>
                                    <TableCell className="text-right">{n2(r.retail_price)}</TableCell>
                                    <TableCell className="text-right">{n2(r.retail_value)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        <tfoot className="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-900">
                            <TableRow>
                                <TableCell colSpan={4} className="text-right">{t('accounting.total')}</TableCell>
                                <TableCell className="text-right">{n2(report.list_totals.quantity)}</TableCell>
                                <TableCell />
                                <TableCell className="text-right">{n2(report.list_totals.value)}</TableCell>
                                <TableCell />
                                <TableCell className="text-right">{n2(report.list_totals.retail_value)}</TableCell>
                            </TableRow>
                        </tfoot>
                    </Table>
                </CardContent>
            </Card>
        </>
    );
}

function LevelingView({ report }: { report: LevelingReport }) {
    const { t } = useTranslation();
    const cells = (r: LevelingTotals) => [r.full_value, r.sold_value, r.leveling_invoice, r.leveling_shopify, r.leveling, r.leveling_tax];

    return (
        <>
            <Card>
                <CardContent className="p-0 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('accounting.number')}</TableHead>
                                <TableHead>{t('accounting.date')}</TableHead>
                                <TableHead className="text-center">{t('accounting.documents')}</TableHead>
                                <TableHead className="text-right">{t('accounting.full_value')}</TableHead>
                                <TableHead className="text-right">{t('accounting.sold_value')}</TableHead>
                                <TableHead className="text-right">{t('accounting.leveling_invoice')}</TableHead>
                                <TableHead className="text-right">{t('accounting.leveling_shopify')}</TableHead>
                                <TableHead className="text-right">{t('accounting.leveling_total')}</TableHead>
                                <TableHead className="text-right">{t('accounting.leveling_tax')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.months.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-center text-gray-400 py-10">{t('accounting.no_leveling')}</TableCell>
                                </TableRow>
                            )}
                            {report.months.map((m) => (
                                <Fragment key={m.month}>
                                    <TableRow className="bg-indigo-50/60 hover:bg-indigo-50/60">
                                        <TableCell colSpan={9} className="font-semibold text-indigo-800">{m.label}</TableCell>
                                    </TableRow>
                                    {m.rows.map((r) => (
                                        <TableRow key={r.date}>
                                            <TableCell>
                                                <a href={`/trade-ledger/leveling/${r.date}`} target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:underline">
                                                    {r.number}
                                                </a>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">{formatDate(r.date)}</TableCell>
                                            <TableCell className="text-center">{r.count}</TableCell>
                                            {cells(r).map((v, i) => (
                                                <TableCell key={i} className={`text-right whitespace-nowrap ${i >= 2 && v < 0 ? 'text-red-600' : ''}`}>{n2(v)}</TableCell>
                                            ))}
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-gray-50 font-semibold hover:bg-gray-50">
                                        <TableCell colSpan={2} className="text-right">{t('accounting.month_total', { month: m.label })}</TableCell>
                                        <TableCell className="text-center">{m.totals.count}</TableCell>
                                        {cells(m.totals).map((v, i) => (
                                            <TableCell key={i} className="text-right whitespace-nowrap">{n2(v)}</TableCell>
                                        ))}
                                    </TableRow>
                                </Fragment>
                            ))}
                        </TableBody>
                        <tfoot className="bg-gray-100 font-bold text-gray-900 border-t-2 border-gray-900">
                            <TableRow>
                                <TableCell colSpan={2} className="text-right">{t('accounting.period_total')}</TableCell>
                                <TableCell className="text-center">{report.totals.count}</TableCell>
                                {cells(report.totals).map((v, i) => (
                                    <TableCell key={i} className="text-right whitespace-nowrap">{n2(v)}</TableCell>
                                ))}
                            </TableRow>
                        </tfoot>
                    </Table>
                </CardContent>
            </Card>
            <p className="mt-3 text-xs text-gray-400">{t('accounting.leveling_note')}</p>
        </>
    );
}
