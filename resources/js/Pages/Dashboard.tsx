import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
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
import {
    FileText,
    CheckCircle,
    Clock,
    AlertTriangle,
    ChevronRight,
    ArrowUp,
    Check,
} from 'lucide-react';
import type { Invoice } from '@/types';

interface MonthlyRevenue {
    month: string;
    revenue: number;
}

interface StatusDistribution {
    paid: number;
    pending: number;
    overdue: number;
}

interface DashboardProps {
    totalInvoices: number;
    paidInvoices: number;
    pendingInvoices: number;
    overdueInvoices: number;
    totalRevenue: number;
    pendingAmount: number;
    overdueAmount: number;
    totalClients: number;
    monthlyRevenue: MonthlyRevenue[];
    recentInvoices: Invoice[];
    statusDistribution: StatusDistribution;
}

const statusBadgeVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    paid: 'success',
    sent: 'info',
    draft: 'gray',
    overdue: 'destructive',
    cancelled: 'gray',
};

export default function Dashboard({
    totalInvoices,
    paidInvoices,
    pendingInvoices,
    overdueInvoices,
    totalRevenue,
    pendingAmount,
    overdueAmount,
    totalClients,
    monthlyRevenue,
    recentInvoices,
    statusDistribution,
}: DashboardProps) {
    const { t } = useTranslation();
    const maxRevenue = Math.max(...monthlyRevenue.map((m) => m.revenue)) || 1;
    const successRate = totalInvoices > 0 ? Math.round((statusDistribution.paid / totalInvoices) * 100) : 0;

    return (
        <AppLayout>
            <Head title={t('dashboard.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('dashboard.title')}</h1>
                        <p className="text-sm text-gray-500 mt-1">{t('dashboard.subtitle')}</p>
                    </div>
                    <p className="hidden sm:block text-sm text-gray-400">
                        {new Date().toLocaleDateString('mk-MK', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        })}
                    </p>
                </div>

                {/* Main Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {/* Total Invoices */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">{t('dashboard.total_invoices')}</p>
                                    <p className="text-4xl font-bold mt-2">{totalInvoices}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <FileText className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-blue-100 text-sm">
                                <span className="font-medium">{totalClients} {t('dashboard.clients')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Total Revenue */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">{t('dashboard.collected')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalRevenue, 0)}</p>
                                    <p className="text-emerald-200 text-sm">{t('dashboard.currency')}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <CheckCircle className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-emerald-100 text-sm">
                                <ArrowUp className="w-4 h-4 mr-1" />
                                <span className="font-medium">{paidInvoices} {t('dashboard.paid')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Pending Amount */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-amber-100 text-sm font-medium">{t('dashboard.pending_payment')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(pendingAmount, 0)}</p>
                                    <p className="text-amber-200 text-sm">{t('dashboard.currency')}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <Clock className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-amber-100 text-sm">
                                <span className="font-medium">{pendingInvoices} {t('dashboard.invoices')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Overdue Amount */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-rose-100 text-sm font-medium">{t('dashboard.overdue')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(overdueAmount, 0)}</p>
                                    <p className="text-rose-200 text-sm">{t('dashboard.currency')}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <AlertTriangle className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-rose-100 text-sm">
                                <span className="font-medium">{overdueInvoices} {t('dashboard.invoices')}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    {/* Monthly Revenue Chart */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>{t('dashboard.monthly_revenue')}</CardTitle>
                                    <CardDescription>{t('dashboard.last_6_months')}</CardDescription>
                                </div>
                                <Badge variant="success" className="flex items-center gap-1.5">
                                    <span className="w-2 h-2 bg-emerald-500 rounded-full" />
                                    {t('dashboard.revenue')}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-5">
                                {monthlyRevenue.map((month, index) => {
                                    const percentage = (month.revenue / maxRevenue) * 100;
                                    return (
                                        <div key={index} className="group">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium text-gray-700">{month.month}</span>
                                                <span className="text-sm font-bold text-gray-900">
                                                    {formatNumber(month.revenue, 0)} {t('dashboard.currency')}
                                                </span>
                                            </div>
                                            <div className="overflow-hidden h-3 rounded-full bg-gray-100">
                                                <div
                                                    className="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-500 ease-out group-hover:from-emerald-500 group-hover:to-emerald-600"
                                                    style={{ width: `${percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Status Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('dashboard.invoice_status')}</CardTitle>
                            <CardDescription>{t('dashboard.status_distribution')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {/* Paid */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                        <Check className="w-6 h-6 text-emerald-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('dashboard.paid_invoices')}</p>
                                            <p className="text-lg font-bold text-emerald-600">{statusDistribution.paid}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-emerald-500"
                                                style={{ width: `${totalInvoices > 0 ? (statusDistribution.paid / totalInvoices) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Pending */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                        <Clock className="w-6 h-6 text-amber-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('dashboard.pending_invoices')}</p>
                                            <p className="text-lg font-bold text-amber-600">{statusDistribution.pending}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-amber-500"
                                                style={{ width: `${totalInvoices > 0 ? (statusDistribution.pending / totalInvoices) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Overdue */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                                        <AlertTriangle className="w-6 h-6 text-rose-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('dashboard.overdue_invoices')}</p>
                                            <p className="text-lg font-bold text-rose-600">{statusDistribution.overdue}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-rose-500"
                                                style={{ width: `${totalInvoices > 0 ? (statusDistribution.overdue / totalInvoices) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Summary */}
                            <div className="mt-6 pt-6 border-t border-gray-100">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-gray-500">{t('dashboard.collection_rate')}</span>
                                    <span className="font-bold text-gray-900">{successRate}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Invoices */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>{t('dashboard.recent_invoices')}</CardTitle>
                                <CardDescription>{t('dashboard.recent_invoices_subtitle')}</CardDescription>
                            </div>
                            <Button variant="secondary" asChild>
                                <Link href="/invoices" className="flex items-center gap-1.5">
                                    {t('dashboard.view_all')}
                                    <ChevronRight className="w-4 h-4" />
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="hidden md:table-cell">{t('dashboard.invoice')}</TableHead>
                                    <TableHead>{t('dashboard.client')}</TableHead>
                                    <TableHead className="hidden md:table-cell">{t('dashboard.date')}</TableHead>
                                    <TableHead>{t('dashboard.amount')}</TableHead>
                                    <TableHead>{t('dashboard.status')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentInvoices.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="hidden md:table-cell">
                                            <div className="flex items-center">
                                                <div className="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <FileText className="w-5 h-5 text-blue-600" />
                                                </div>
                                                <div className="ml-3">
                                                    <p className="text-sm font-semibold text-gray-900">{invoice.invoice_number}</p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{invoice.client?.name}</p>
                                                <p className="text-xs text-gray-500">{invoice.client?.email}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            <p className="text-sm text-gray-900">{formatDate(invoice.issue_date)}</p>
                                            <p className="text-xs text-gray-500">{t('dashboard.due')}: {formatDate(invoice.due_date)}</p>
                                        </TableCell>
                                        <TableCell>
                                            <p className="text-sm font-bold text-gray-900">
                                                {formatNumber(invoice.total, 0)} {t('dashboard.currency')}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={statusBadgeVariants[invoice.status] || 'gray'}>
                                                {t(`dashboard.status_${invoice.status}`)}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
