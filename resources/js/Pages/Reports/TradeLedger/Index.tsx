import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
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
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import { Download, Plus, Pencil, Trash2, BookText } from 'lucide-react';

interface LedgerRow {
    type: 'receipt' | 'issue' | 'invoice' | 'shopify' | 'fiscal';
    row_no: number;
    booking_date: string;
    doc_number: string;
    doc_date: string;
    purchase_value: number;
    sales_value: number;
    daily_turnover: number;
}

interface Totals {
    purchase_value: number;
    sales_value: number;
    daily_turnover: number;
}

interface FiscalReport {
    id: number;
    date: string;
    report_number: string | null;
    amount: number;
    notes: string | null;
}

interface Props {
    rows: LedgerRow[];
    periodTotals: Totals;
    grandTotals: Totals;
    reports: FiscalReport[];
    filters: {
        date_from: string;
        date_to: string;
    };
}

const todayStr = () => new Date().toISOString().slice(0, 10);

export default function TradeLedgerIndex({ rows, periodTotals, grandTotals, reports, filters }: Props) {
    const { t } = useTranslation();

    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const applyFilters = (from: string, to: string) => {
        router.get('/trade-ledger', { date_from: from, date_to: to }, { preserveState: true, preserveScroll: true });
    };

    const setRange = (from: string, to: string) => {
        setDateFrom(from);
        setDateTo(to);
        applyFilters(from, to);
    };

    const quickToday = () => {
        const d = todayStr();
        setRange(d, d);
    };
    const quickMonth = () => {
        const now = new Date();
        const from = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
        setRange(from, todayStr());
    };
    const quickYear = () => {
        const now = new Date();
        setRange(`${now.getFullYear()}-01-01`, todayStr());
    };

    const exportUrl = `/trade-ledger/pdf?date_from=${dateFrom}&date_to=${dateTo}`;

    const typeLabel = (type: LedgerRow['type']) => t(`ledger.type_${type}`);

    // --- Fiscal report dialog ---
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<FiscalReport | null>(null);
    const [deleteReport, setDeleteReport] = useState<FiscalReport | null>(null);

    const form = useForm({
        date: todayStr(),
        report_number: '',
        amount: '',
        notes: '',
    });

    const openAdd = () => {
        setEditing(null);
        form.setData({ date: dateTo || todayStr(), report_number: '', amount: '', notes: '' });
        form.clearErrors();
        setDialogOpen(true);
    };

    const openEdit = (report: FiscalReport) => {
        setEditing(report);
        form.setData({
            date: report.date.slice(0, 10),
            report_number: report.report_number || '',
            amount: String(report.amount),
            notes: report.notes || '',
        });
        form.clearErrors();
        setDialogOpen(true);
    };

    const submit = () => {
        const opts = {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        };
        if (editing) {
            form.put(`/trade-ledger/reports/${editing.id}`, opts);
        } else {
            form.post('/trade-ledger/reports', opts);
        }
    };

    return (
        <AppLayout>
            <Head title={t('ledger.title')} />

            <div>
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('ledger.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('ledger.subtitle')}</p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href={exportUrl} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2">
                            <Download className="w-4 h-4" />
                            {t('ledger.export_pdf')}
                        </a>
                    </Button>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('ledger.date_from')}</Label>
                                <Input type="date" value={dateFrom} max={dateTo} onChange={(e) => setDateFrom(e.target.value)} className="w-40 h-9" />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs text-gray-500">{t('ledger.date_to')}</Label>
                                <Input type="date" value={dateTo} min={dateFrom} onChange={(e) => setDateTo(e.target.value)} className="w-40 h-9" />
                            </div>
                            <Button size="sm" className="h-9" onClick={() => applyFilters(dateFrom, dateTo)}>
                                {t('ledger.filter')}
                            </Button>
                            <div className="flex items-center gap-2 ml-auto">
                                <Button size="sm" variant="outline" className="h-9" onClick={quickToday}>{t('ledger.today')}</Button>
                                <Button size="sm" variant="outline" className="h-9" onClick={quickMonth}>{t('ledger.this_month')}</Button>
                                <Button size="sm" variant="outline" className="h-9" onClick={quickYear}>{t('ledger.this_year')}</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Ledger table */}
                <Card className="mb-6">
                    <CardContent className="p-0 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[60px]">{t('ledger.row_no')}</TableHead>
                                    <TableHead>{t('ledger.booking_date')}</TableHead>
                                    <TableHead>{t('ledger.document')}</TableHead>
                                    <TableHead>{t('ledger.document_number')}</TableHead>
                                    <TableHead>{t('ledger.document_date')}</TableHead>
                                    <TableHead className="text-right">{t('ledger.purchase_value')}</TableHead>
                                    <TableHead className="text-right">{t('ledger.sales_value')}</TableHead>
                                    <TableHead className="text-right bg-indigo-50 border-l-2 border-indigo-200">{t('ledger.daily_turnover')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center text-gray-400 py-10">
                                            {t('ledger.no_rows')}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((row) => {
                                        const isFiscal = row.type === 'fiscal';
                                        return (
                                            <TableRow key={row.row_no} className={isFiscal ? 'bg-indigo-50/60' : undefined}>
                                                <TableCell className={isFiscal ? 'text-indigo-500 font-semibold' : 'text-gray-500'}>{row.row_no}</TableCell>
                                                <TableCell className={isFiscal ? 'font-semibold text-indigo-900' : undefined}>{row.booking_date}</TableCell>
                                                <TableCell className={isFiscal ? 'font-bold text-indigo-900' : 'font-medium text-gray-900'}>{typeLabel(row.type)}</TableCell>
                                                <TableCell className={isFiscal ? 'font-semibold text-indigo-900' : 'text-gray-600'}>{row.doc_number}</TableCell>
                                                <TableCell className={isFiscal ? 'font-semibold text-indigo-900' : undefined}>{row.doc_date}</TableCell>
                                                <TableCell className="text-right">{formatNumber(row.purchase_value, 2)}</TableCell>
                                                <TableCell className="text-right">{formatNumber(row.sales_value, 2)}</TableCell>
                                                <TableCell
                                                    className={
                                                        isFiscal
                                                            ? 'text-right font-bold text-indigo-900 bg-indigo-100 border-l-2 border-indigo-200'
                                                            : 'text-right text-gray-500 bg-indigo-50/40 border-l-2 border-indigo-200'
                                                    }
                                                >
                                                    {formatNumber(row.daily_turnover, 2)}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                            <tfoot className="bg-gray-50 font-semibold text-gray-900">
                                <TableRow>
                                    <TableCell colSpan={5} className="text-right">{t('ledger.period_total')}</TableCell>
                                    <TableCell className="text-right">{formatNumber(periodTotals.purchase_value, 2)}</TableCell>
                                    <TableCell className="text-right">{formatNumber(periodTotals.sales_value, 2)}</TableCell>
                                    <TableCell className="text-right bg-indigo-100 border-l-2 border-indigo-200">{formatNumber(periodTotals.daily_turnover, 2)}</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell colSpan={5} className="text-right text-indigo-700">
                                        <div>{t('ledger.grand_total')}</div>
                                        <div className="text-xs font-normal text-indigo-400">{t('ledger.grand_total_hint')}</div>
                                    </TableCell>
                                    <TableCell className="text-right text-indigo-700">{formatNumber(grandTotals.purchase_value, 2)}</TableCell>
                                    <TableCell className="text-right text-indigo-700">{formatNumber(grandTotals.sales_value, 2)}</TableCell>
                                    <TableCell className="text-right text-indigo-700 bg-indigo-100 border-l-2 border-indigo-200">{formatNumber(grandTotals.daily_turnover, 2)}</TableCell>
                                </TableRow>
                                <TableRow className="bg-transparent">
                                    <TableCell colSpan={8} className="text-xs font-normal text-gray-400 pt-1 pb-2">
                                        {t('ledger.margin_hint', { amount: formatNumber(grandTotals.sales_value - grandTotals.purchase_value, 2) })}
                                    </TableCell>
                                </TableRow>
                            </tfoot>
                        </Table>
                    </CardContent>
                </Card>

                {/* Daily fiscal reports management */}
                <Card>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-4 border-b">
                        <div>
                            <h3 className="font-semibold text-gray-900 flex items-center gap-2">
                                <BookText className="w-4 h-4" />
                                {t('ledger.fiscal_reports')}
                            </h3>
                            <p className="mt-1 text-xs text-gray-500 max-w-2xl">{t('ledger.fiscal_reports_hint')}</p>
                        </div>
                        <Button size="sm" onClick={openAdd} className="gap-1.5 shrink-0">
                            <Plus className="w-4 h-4" />
                            {t('ledger.add_fiscal_report')}
                        </Button>
                    </div>
                    <CardContent className="p-0">
                        {reports.length === 0 ? (
                            <div className="py-8 text-center text-sm text-gray-400">{t('ledger.no_fiscal_reports')}</div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('ledger.fiscal_date')}</TableHead>
                                        <TableHead>{t('ledger.fiscal_number')}</TableHead>
                                        <TableHead className="text-right">{t('ledger.fiscal_amount')}</TableHead>
                                        <TableHead>{t('ledger.fiscal_notes')}</TableHead>
                                        <TableHead className="w-[100px]" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reports.map((report) => (
                                        <TableRow key={report.id}>
                                            <TableCell>{formatDate(report.date)}</TableCell>
                                            <TableCell className="text-gray-600">{report.report_number || '—'}</TableCell>
                                            <TableCell className="text-right font-medium">{formatNumber(report.amount, 2)}</TableCell>
                                            <TableCell className="text-gray-500 max-w-[240px] truncate">{report.notes || '-'}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(report)}>
                                                        <Pencil className="w-4 h-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8 text-red-600 hover:text-red-700" onClick={() => setDeleteReport(report)}>
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Add/Edit fiscal report dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? t('ledger.edit_fiscal_report') : t('ledger.add_fiscal_report')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-1.5 block">{t('ledger.fiscal_date')}</Label>
                            <Input type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                            {form.errors.date && <p className="text-sm text-red-600 mt-1">{form.errors.date}</p>}
                        </div>
                        <div>
                            <Label className="mb-1.5 block">{t('ledger.fiscal_number')}</Label>
                            <Input value={form.data.report_number} onChange={(e) => form.setData('report_number', e.target.value)} placeholder="6285" />
                        </div>
                        <div>
                            <Label className="mb-1.5 block">{t('ledger.fiscal_amount')}</Label>
                            <Input type="number" step="0.01" min="0" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            {form.errors.amount && <p className="text-sm text-red-600 mt-1">{form.errors.amount}</p>}
                        </div>
                        <div>
                            <Label className="mb-1.5 block">{t('ledger.fiscal_notes')}</Label>
                            <Textarea value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} rows={2} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>{t('general.cancel')}</Button>
                        <Button onClick={submit} disabled={form.processing || !form.data.amount}>{t('general.save')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteReport}
                onOpenChange={(open) => !open && setDeleteReport(null)}
                deleteUrl={deleteReport ? `/trade-ledger/reports/${deleteReport.id}` : ''}
                title={t('ledger.delete_fiscal_title')}
                description={t('ledger.delete_fiscal_confirm')}
            />
        </AppLayout>
    );
}
