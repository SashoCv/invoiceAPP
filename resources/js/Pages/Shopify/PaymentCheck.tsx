import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { AlertTriangle, CheckCircle2, Search, ShieldCheck } from 'lucide-react';

interface Transaction {
    kind: string;
    status: string;
    gateway: string | null;
    amount: number;
    at: string;
}

interface OrderRow {
    shopify_id: number;
    local_id: number | null;
    number: string;
    created_at: string;
    customer: string | null;
    status: string;
    cancelled: boolean;
    local_status: string | null;
    total: number;
    charged: number;
    refunded: number;
    transactions: Transaction[];
}

interface CheckoutRow {
    number: string;
    created_at: string;
    customer: string | null;
    email: string | null;
    total: number;
}

type FindingKey = 'double_charge' | 'amount_mismatch' | 'missing' | 'status_differs' | 'failed_attempts';

interface AuditResult {
    error: 'access_denied' | 'api' | null;
    message?: string;
    limited?: boolean;
    summary?: Record<string, number>;
    findings?: Record<FindingKey, OrderRow[]> & { abandoned: CheckoutRow[] };
}

interface Props {
    connected: boolean;
    result: AuditResult | null;
    filters: { date_from: string; date_to: string };
}

const pad = (n: number) => String(n).padStart(2, '0');
const ymd = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

// Most serious first: these need checking against the bank
const ORDER_SECTIONS: { key: FindingKey; tone: 'red' | 'amber' | 'gray' }[] = [
    { key: 'double_charge', tone: 'red' },
    { key: 'missing', tone: 'red' },
    { key: 'amount_mismatch', tone: 'amber' },
    { key: 'status_differs', tone: 'amber' },
    { key: 'failed_attempts', tone: 'gray' },
];

export default function PaymentCheck({ connected, result, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [running, setRunning] = useState(false);

    const run = (from = dateFrom, to = dateTo) => {
        setDateFrom(from);
        setDateTo(to);
        setRunning(true);
        router.get('/shopify/payment-check', { date_from: from, date_to: to, run: 1 }, { preserveScroll: true, onFinish: () => setRunning(false) });
    };

    const now = new Date();
    const thisMonth = () => run(ymd(new Date(now.getFullYear(), now.getMonth(), 1)), ymd(now));
    const lastMonth = () => run(ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)), ymd(new Date(now.getFullYear(), now.getMonth(), 0)));

    const findings = result?.findings;
    const issues = findings
        ? findings.double_charge.length + findings.missing.length + findings.amount_mismatch.length + findings.status_differs.length
        : 0;

    return (
        <AppLayout>
            <Head title={t('shopify.audit_title')} />

            <div>
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <ShieldCheck className="w-6 h-6 text-indigo-600" />
                        {t('shopify.audit_title')}
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">{t('shopify.audit_subtitle')}</p>
                </div>

                {!connected ? (
                    <Card>
                        <CardContent className="py-10 text-center text-gray-500">
                            {t('shopify.not_connected')} — <Link href="/settings/shopify" className="text-indigo-600 hover:underline">{t('shopify.title')}</Link>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card className="mb-6">
                            <CardContent className="pt-6">
                                <div className="flex flex-wrap items-end gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs text-gray-500">{t('shopify.audit_from')}</Label>
                                        <Input type="date" value={dateFrom} max={dateTo} onChange={(e) => setDateFrom(e.target.value)} className="w-40 h-9" />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-xs text-gray-500">{t('shopify.audit_to')}</Label>
                                        <Input type="date" value={dateTo} min={dateFrom} onChange={(e) => setDateTo(e.target.value)} className="w-40 h-9" />
                                    </div>
                                    <Button size="sm" className="h-9 gap-1.5" onClick={() => run()} disabled={running} loading={running}>
                                        <Search className="w-4 h-4" />
                                        {t('shopify.audit_run')}
                                    </Button>
                                    <div className="flex items-center gap-2 ml-auto">
                                        <Button size="sm" variant="outline" className="h-9" onClick={thisMonth} disabled={running}>{t('shopify.audit_this_month')}</Button>
                                        <Button size="sm" variant="outline" className="h-9" onClick={lastMonth} disabled={running}>{t('shopify.audit_last_month')}</Button>
                                    </div>
                                </div>
                                <p className="mt-3 text-xs text-gray-400">{t('shopify.audit_hint')}</p>
                            </CardContent>
                        </Card>

                        {result?.error && (
                            <div className="mb-6 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                                <span>{result.error === 'access_denied' ? t('shopify.audit_access_denied') : `${t('shopify.audit_api_error')} ${result.message ?? ''}`}</span>
                            </div>
                        )}

                        {result?.limited && (
                            <div className="mb-6 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                                <span>{t('shopify.audit_limited')}</span>
                            </div>
                        )}

                        {findings && result?.summary && (
                            <>
                                <div className={`mb-6 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm ${issues === 0 ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'}`}>
                                    {issues === 0 ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
                                    <span>
                                        {t('shopify.audit_summary', {
                                            orders: String(result.summary.orders),
                                            total: formatNumber(result.summary.paid_orders_total, 2),
                                        })}{' '}
                                        {issues === 0 ? t('shopify.audit_all_ok') : t('shopify.audit_issues', { count: String(issues) })}
                                    </span>
                                </div>

                                {ORDER_SECTIONS.map(({ key, tone }) => (
                                    <OrderSection key={key} sectionKey={key} tone={tone} rows={findings[key]} />
                                ))}

                                <Card className="mb-6">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base">
                                            {t('shopify.audit_abandoned')} ({findings.abandoned.length})
                                        </CardTitle>
                                        <p className="text-sm text-gray-500">{t('shopify.audit_abandoned_hint')}</p>
                                    </CardHeader>
                                    {findings.abandoned.length > 0 && (
                                        <CardContent className="p-0 overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>{t('shopify.audit_checkout')}</TableHead>
                                                        <TableHead>{t('shopify.audit_time')}</TableHead>
                                                        <TableHead>{t('shopify.audit_customer')}</TableHead>
                                                        <TableHead className="text-right">{t('shopify.audit_amount')}</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {findings.abandoned.map((c, i) => (
                                                        <TableRow key={i}>
                                                            <TableCell className="font-medium">{c.number || '-'}</TableCell>
                                                            <TableCell className="whitespace-nowrap">{c.created_at}</TableCell>
                                                            <TableCell>{[c.customer, c.email].filter(Boolean).join(' · ') || '-'}</TableCell>
                                                            <TableCell className="text-right whitespace-nowrap">{formatNumber(c.total, 2)}</TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    )}
                                </Card>
                            </>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function OrderSection({ sectionKey, tone, rows }: { sectionKey: FindingKey; tone: 'red' | 'amber' | 'gray'; rows: OrderRow[] }) {
    const { t } = useTranslation();
    const [importing, setImporting] = useState<number | null>(null);
    const titleColor = rows.length === 0 ? 'text-gray-700' : tone === 'red' ? 'text-red-700' : tone === 'amber' ? 'text-amber-700' : 'text-gray-700';

    const importOrder = (id: number) => {
        setImporting(id);
        router.post(`/shopify/payment-check/import/${id}`, {}, { preserveScroll: true, onFinish: () => setImporting(null) });
    };

    return (
        <Card className="mb-6">
            <CardHeader className="pb-2">
                <CardTitle className={`text-base flex items-center gap-2 ${titleColor}`}>
                    {rows.length === 0 ? <CheckCircle2 className="w-4 h-4 text-green-600" /> : <AlertTriangle className="w-4 h-4" />}
                    {t(`shopify.audit_${sectionKey}`)} ({rows.length})
                </CardTitle>
                <p className="text-sm text-gray-500">{t(`shopify.audit_${sectionKey}_hint`)}</p>
            </CardHeader>
            {rows.length > 0 && (
                <CardContent className="p-0 overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('shopify.audit_order')}</TableHead>
                                <TableHead>{t('shopify.audit_time')}</TableHead>
                                <TableHead>{t('shopify.audit_customer')}</TableHead>
                                <TableHead>{t('shopify.audit_status')}</TableHead>
                                <TableHead className="text-right">{t('shopify.audit_amount')}</TableHead>
                                <TableHead className="text-right">{t('shopify.audit_charged')}</TableHead>
                                <TableHead>{t('shopify.audit_transactions')}</TableHead>
                                {sectionKey === 'missing' && <TableHead />}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((r) => (
                                <TableRow key={r.shopify_id}>
                                    <TableCell className="font-medium whitespace-nowrap">
                                        {r.local_id ? (
                                            <Link href={`/shopify/orders/${r.local_id}`} className="text-indigo-600 hover:underline">{r.number}</Link>
                                        ) : r.number}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">{r.created_at}</TableCell>
                                    <TableCell>{r.customer || '-'}</TableCell>
                                    <TableCell className="whitespace-nowrap text-xs">
                                        {r.status}{r.cancelled ? ` · ${t('shopify.audit_cancelled')}` : ''}
                                        {r.local_status && <span className="block text-gray-400">{t('shopify.audit_in_program')}: {r.local_status}</span>}
                                    </TableCell>
                                    <TableCell className="text-right whitespace-nowrap">{formatNumber(r.total, 2)}</TableCell>
                                    <TableCell className={`text-right whitespace-nowrap ${Math.abs(r.charged - r.total) > 1 ? 'text-red-600 font-semibold' : ''}`}>{formatNumber(r.charged, 2)}</TableCell>
                                    <TableCell className="text-xs text-gray-600">
                                        {r.transactions.map((tx, i) => (
                                            <div key={i} className={`whitespace-nowrap ${tx.status !== 'SUCCESS' ? 'text-gray-400' : ''}`}>
                                                {tx.at} · {tx.kind} · {tx.status} · {formatNumber(tx.amount, 2)}{tx.gateway ? ` · ${tx.gateway}` : ''}
                                            </div>
                                        ))}
                                    </TableCell>
                                    {sectionKey === 'missing' && (
                                        <TableCell>
                                            <Button size="sm" variant="outline" onClick={() => importOrder(r.shopify_id)} disabled={importing !== null} loading={importing === r.shopify_id}>
                                                {t('shopify.audit_import')}
                                            </Button>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            )}
        </Card>
    );
}
