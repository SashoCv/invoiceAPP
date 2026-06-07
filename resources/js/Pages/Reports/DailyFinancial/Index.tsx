import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
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
import { Download, Store, Building2 } from 'lucide-react';

interface ReportRow {
    code: string;
    name: string;
    unit: string;
    quantity: number;
    purchase_value: number;
    sales_no_tax: number;
    tax: number;
    sales_with_tax: number;
    margin: number;
}

interface Totals {
    quantity: number;
    purchase_value: number;
    sales_no_tax: number;
    tax: number;
    sales_with_tax: number;
    margin: number;
}

interface Props {
    rows: ReportRow[];
    totals: Totals;
    type: 'retail' | 'wholesale';
    filters: { date_from: string; date_to: string };
}

const todayStr = () => new Date().toISOString().slice(0, 10);

export default function DailyFinancialIndex({ rows, totals, type, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const go = (params: Record<string, string>) => {
        router.get('/daily-financial-report', { date_from: dateFrom, date_to: dateTo, type, ...params }, { preserveState: true, preserveScroll: true });
    };

    const switchType = (newType: 'retail' | 'wholesale') => {
        router.get('/daily-financial-report', { date_from: dateFrom, date_to: dateTo, type: newType }, { preserveState: true, preserveScroll: true });
    };

    const quickToday = () => {
        const d = todayStr();
        setDateFrom(d); setDateTo(d);
        router.get('/daily-financial-report', { date_from: d, date_to: d, type }, { preserveState: true, preserveScroll: true });
    };
    const quickMonth = () => {
        const now = new Date();
        const from = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
        const to = todayStr();
        setDateFrom(from); setDateTo(to);
        router.get('/daily-financial-report', { date_from: from, date_to: to, type }, { preserveState: true, preserveScroll: true });
    };

    const exportUrl = `/daily-financial-report/pdf?date_from=${dateFrom}&date_to=${dateTo}&type=${type}`;

    const TabButton = ({ value, icon: Icon, label, hint }: { value: 'retail' | 'wholesale'; icon: any; label: string; hint: string }) => (
        <button
            onClick={() => switchType(value)}
            className={`flex items-center gap-2 px-4 py-2.5 rounded-lg border text-left transition-colors ${
                type === value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
            }`}
        >
            <Icon className="w-5 h-5 shrink-0" />
            <span>
                <span className="block text-sm font-semibold">{label}</span>
                <span className="block text-xs text-gray-400">{hint}</span>
            </span>
        </button>
    );

    return (
        <AppLayout>
            <Head title={t('daily_report.title')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('daily_report.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('daily_report.subtitle')}</p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href={exportUrl} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2">
                            <Download className="w-4 h-4" />
                            {t('daily_report.export_pdf')}
                        </a>
                    </Button>
                </div>

                {/* Type tabs */}
                <div className="flex flex-wrap gap-3 mb-6">
                    <TabButton value="retail" icon={Store} label={t('daily_report.retail')} hint={t('daily_report.retail_hint')} />
                    <TabButton value="wholesale" icon={Building2} label={t('daily_report.wholesale')} hint={t('daily_report.wholesale_hint')} />
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('daily_report.date_from')}</Label>
                                <Input type="date" value={dateFrom} max={dateTo} onChange={(e) => setDateFrom(e.target.value)} className="w-40 h-9" />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('daily_report.date_to')}</Label>
                                <Input type="date" value={dateTo} min={dateFrom} onChange={(e) => setDateTo(e.target.value)} className="w-40 h-9" />
                            </div>
                            <Button size="sm" className="h-9" onClick={() => go({})}>{t('daily_report.filter')}</Button>
                            <div className="flex items-center gap-2 ml-auto">
                                <Button size="sm" variant="outline" className="h-9" onClick={quickToday}>{t('daily_report.today')}</Button>
                                <Button size="sm" variant="outline" className="h-9" onClick={quickMonth}>{t('daily_report.this_month')}</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Report table */}
                <Card>
                    <CardContent className="p-0 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('daily_report.code')}</TableHead>
                                    <TableHead>{t('daily_report.name')}</TableHead>
                                    <TableHead>{t('daily_report.unit')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.quantity')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.purchase_value')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.sales_no_tax')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.tax')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.sales_with_tax')}</TableHead>
                                    <TableHead className="text-right">{t('daily_report.margin')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-center text-gray-400 py-10">{t('daily_report.no_sales')}</TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((row, i) => (
                                        <TableRow key={i}>
                                            <TableCell className="text-gray-500">{row.code || '-'}</TableCell>
                                            <TableCell className="font-medium text-gray-900">{row.name}</TableCell>
                                            <TableCell className="text-gray-500">{row.unit}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.quantity, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.purchase_value, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.sales_no_tax, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.tax, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.sales_with_tax, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(row.margin, 2)}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                            <tfoot className="bg-gray-50 font-semibold text-gray-900">
                                <TableRow>
                                    <TableCell colSpan={3} className="text-right">{t('daily_report.total')}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.quantity, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.purchase_value, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.sales_no_tax, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.tax, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.sales_with_tax, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(totals.margin, 2)}</TableCell>
                                </TableRow>
                            </tfoot>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
