import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import Pagination from '@/Components/Pagination';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { ArrowLeft, Pencil, FileText, Users, DollarSign, CheckCircle, AlertCircle, Package } from 'lucide-react';
import type { Client, Invoice, PaginatedData } from '@/types';

interface ArticleBreakdown {
    name: string;
    total_quantity: number;
    total_amount: number;
}

interface Stats {
    total_invoiced: number;
    total_paid: number;
    total_unpaid: number;
    invoice_count: number;
    currency: string;
}

interface ShowClientProps {
    client: Client;
    invoices: PaginatedData<Pick<Invoice, 'id' | 'client_id' | 'invoice_number' | 'issue_date' | 'due_date' | 'status' | 'currency' | 'total'>>;
    stats: Stats;
    articleBreakdown: ArticleBreakdown[];
}

const statusVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    draft: 'gray',
    sent: 'info',
    unpaid: 'warning',
    paid: 'success',
    overdue: 'warning',
    cancelled: 'destructive',
};

export default function ShowClient({ client, invoices, stats, articleBreakdown }: ShowClientProps) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={client.company || client.name} />

            <div className="max-w-5xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/clients" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('clients.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <Users className="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    {client.company || client.name}
                                </h1>
                                {client.company && (
                                    <p className="text-sm text-gray-500">{client.name}</p>
                                )}
                            </div>
                        </div>

                        <Button variant="outline" asChild>
                            <Link href={`/clients/${client.id}/edit`} className="flex items-center gap-2">
                                <Pencil className="w-4 h-4" />
                                {t('clients.edit')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                    <DollarSign className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">{t('clients.total_invoiced')}</p>
                                    <p className="text-lg font-bold text-gray-900">
                                        {formatNumber(stats.total_invoiced)} <span className="text-sm font-normal text-gray-500">{stats.currency}</span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                                    <CheckCircle className="w-5 h-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">{t('clients.total_paid')}</p>
                                    <p className="text-lg font-bold text-gray-900">
                                        {formatNumber(stats.total_paid)} <span className="text-sm font-normal text-gray-500">{stats.currency}</span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                                    <AlertCircle className="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">{t('clients.total_unpaid')}</p>
                                    <p className="text-lg font-bold text-gray-900">
                                        {formatNumber(stats.total_unpaid)} <span className="text-sm font-normal text-gray-500">{stats.currency}</span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">{t('clients.invoice_count')}</p>
                                    <p className="text-lg font-bold text-gray-900">{stats.invoice_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Article Breakdown */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Package className="w-4 h-4" />
                            {t('clients.article_breakdown')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {articleBreakdown.length === 0 ? (
                            <p className="text-sm text-gray-500 px-6 pb-6">{t('clients.no_articles')}</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('clients.article_name')}</TableHead>
                                        <TableHead className="text-right">{t('clients.total_quantity')}</TableHead>
                                        <TableHead className="text-right">{t('clients.total_amount')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {articleBreakdown.map((item, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="font-medium">{item.name}</TableCell>
                                            <TableCell className="text-right">{formatNumber(Number(item.total_quantity), 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(Number(item.total_amount), 2)} {stats.currency}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Recent Invoices */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <FileText className="w-4 h-4" />
                            {t('clients.recent_invoices')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {invoices.data.length === 0 ? (
                            <p className="text-sm text-gray-500 px-6 pb-6">{t('clients.no_invoices')}</p>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('invoices.invoice_number')}</TableHead>
                                            <TableHead>{t('invoices.issue_date')}</TableHead>
                                            <TableHead>{t('invoices.status')}</TableHead>
                                            <TableHead className="text-right">{t('invoices.total')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoices.data.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>
                                                    <Link
                                                        href={`/invoices/${invoice.id}`}
                                                        className="text-blue-600 hover:text-blue-800 font-medium"
                                                    >
                                                        {invoice.invoice_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-gray-600">{formatDate(invoice.issue_date)}</TableCell>
                                                <TableCell>
                                                    <Badge variant={statusVariants[invoice.status] || 'gray'}>
                                                        {t(`invoices.status_${invoice.status}`)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(invoice.total, 2)} {invoice.currency}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {invoices.last_page > 1 && (
                                    <div className="px-6 py-4 border-t">
                                        <Pagination
                                            links={invoices.links}
                                            from={invoices.from}
                                            to={invoices.to}
                                            total={invoices.total}
                                        />
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Client Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{t('clients.contact_info')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-3">
                                <h3 className="text-sm font-medium text-gray-900">{t('clients.contact')}</h3>
                                {client.email && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.email')}:</span>
                                        <a href={`mailto:${client.email}`} className="text-blue-600 hover:text-blue-800">{client.email}</a>
                                    </div>
                                )}
                                {client.phone && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.phone')}:</span>
                                        <span className="font-medium">{client.phone}</span>
                                    </div>
                                )}
                                {client.tax_number && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.tax_number')}:</span>
                                        <span className="font-medium">{client.tax_number}</span>
                                    </div>
                                )}
                                {client.registration_number && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.registration_number')}:</span>
                                        <span className="font-medium">{client.registration_number}</span>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3">
                                <h3 className="text-sm font-medium text-gray-900">{t('clients.address')}</h3>
                                {client.address && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.address')}:</span>
                                        <span className="font-medium">{client.address}</span>
                                    </div>
                                )}
                                {client.city && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.city')}:</span>
                                        <span className="font-medium">{client.city}</span>
                                    </div>
                                )}
                                {(client.postal_code || client.zip_code) && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.postal_code')}:</span>
                                        <span className="font-medium">{client.postal_code || client.zip_code}</span>
                                    </div>
                                )}
                                {client.country && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-500">{t('clients.country')}:</span>
                                        <span className="font-medium">{client.country}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
