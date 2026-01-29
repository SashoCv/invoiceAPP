import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import Pagination from '@/Components/Pagination';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { Plus, Eye, Copy, Pencil, Trash2, FileText, RotateCcw } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import SortableTableHead from '@/Components/SortableTableHead';
import type { Invoice, Client, PaginatedData } from '@/types';

interface InvoicesIndexProps {
    invoices: PaginatedData<Invoice>;
    clients: Client[];
    showDeleted: boolean;
    filters: {
        invoice?: string;
        client?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        per_page?: number;
        sort?: string;
        dir?: 'asc' | 'desc';
    };
}

const statusVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    draft: 'gray',
    sent: 'info',
    unpaid: 'warning',
    paid: 'success',
    overdue: 'destructive',
    cancelled: 'gray',
};

export default function InvoicesIndex({ invoices, clients, showDeleted, filters }: InvoicesIndexProps) {
    const { t } = useTranslation();
    const [invoiceSearch, setInvoiceSearch] = useState(filters.invoice || '');
    const [clientFilter, setClientFilter] = useState(filters.client || '__all__');
    const [statusFilter, setStatusFilter] = useState(filters.status || '__all__');
    const [deleteInvoice, setDeleteInvoice] = useState<Invoice | null>(null);
    const [forceDeleteInvoice, setForceDeleteInvoice] = useState<Invoice | null>(null);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (invoiceSearch) params.invoice = invoiceSearch;
        if (clientFilter && clientFilter !== '__all__') params.client = clientFilter;
        if (statusFilter && statusFilter !== '__all__') params.status = statusFilter;
        if (showDeleted) params.deleted = '1';
        router.get('/invoices', params, { preserveState: true });
    };

    const clearFilters = () => {
        setInvoiceSearch('');
        setClientFilter('__all__');
        setStatusFilter('__all__');
        router.get('/invoices', showDeleted ? { deleted: '1' } : {});
    };

    const handleRestore = (id: number) => {
        router.post(`/invoices/${id}/restore`);
    };

    const hasFilters = filters.invoice || filters.client || filters.status;

    return (
        <AppLayout>
            <Head title={t('invoices.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('invoices.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('invoices.subtitle')}</p>
                    </div>
                    {!showDeleted && (
                        <Button asChild>
                            <Link href="/invoices/create" className="flex items-center gap-2">
                                <Plus className="w-4 h-4" />
                                {t('invoices.new_invoice')}
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <Tabs value={showDeleted ? 'deleted' : 'active'} className="mb-6">
                    <TabsList>
                        <TabsTrigger value="active" asChild>
                            <Link href="/invoices">{t('invoices.active_invoices')}</Link>
                        </TabsTrigger>
                        <TabsTrigger value="deleted" asChild>
                            <Link href="/invoices?deleted=1">{t('invoices.deleted_invoices')}</Link>
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <form onSubmit={handleFilter}>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('invoices.search')}
                                    </label>
                                    <Input
                                        value={invoiceSearch}
                                        onChange={(e) => setInvoiceSearch(e.target.value)}
                                        placeholder={t('invoices.search_placeholder')}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('invoices.client')}
                                    </label>
                                    <Select value={clientFilter} onValueChange={setClientFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('invoices.all_clients')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('invoices.all_clients')}</SelectItem>
                                            {clients.map((c) => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('invoices.status')}
                                    </label>
                                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('invoices.all_statuses')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('invoices.all_statuses')}</SelectItem>
                                            <SelectItem value="draft">{t('invoices.status_draft')}</SelectItem>
                                            <SelectItem value="sent">{t('invoices.status_sent')}</SelectItem>
                                            <SelectItem value="unpaid">{t('invoices.status_unpaid')}</SelectItem>
                                            <SelectItem value="paid">{t('invoices.status_paid')}</SelectItem>
                                            <SelectItem value="overdue">{t('invoices.status_overdue')}</SelectItem>
                                            <SelectItem value="cancelled">{t('invoices.status_cancelled')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button type="submit">{t('invoices.filter')}</Button>
                                    {hasFilters && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('invoices.clear_filters')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Invoices List */}
                <Card>
                    {invoices.data.length === 0 ? (
                        <CardContent className="p-0">
                            <EmptyState
                                icon={FileText}
                                title={showDeleted ? t('invoices.no_deleted_invoices') : t('invoices.no_invoices')}
                                description={!showDeleted ? t('invoices.create_first_description') : undefined}
                                action={!showDeleted ? {
                                    label: t('invoices.new_invoice'),
                                    href: '/invoices/create',
                                } : undefined}
                            />
                        </CardContent>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <SortableTableHead
                                            column="invoice_number"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/invoices"
                                        >
                                            {t('invoices.invoice')}
                                        </SortableTableHead>
                                        <TableHead className="hidden md:table-cell">{t('invoices.client')}</TableHead>
                                        <SortableTableHead
                                            column="issue_date"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/invoices"
                                            className="hidden lg:table-cell"
                                        >
                                            {t('invoices.date')}
                                        </SortableTableHead>
                                        <TableHead className="text-center">{t('invoices.status')}</TableHead>
                                        <SortableTableHead
                                            column="total"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/invoices"
                                            className="text-right"
                                        >
                                            {t('invoices.total')}
                                        </SortableTableHead>
                                        <TableHead className="text-right">{t('invoices.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.data.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/invoices/${invoice.id}`}
                                                    className="text-sm font-medium text-blue-600 hover:text-blue-800"
                                                >
                                                    {invoice.invoice_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="text-sm text-gray-900">{invoice.client?.name || '-'}</span>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="text-sm text-gray-600">{formatDate(invoice.issue_date)}</span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={statusVariants[invoice.status] || 'gray'}>
                                                    {t(`invoices.status_${invoice.status}`)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className="text-sm font-medium text-gray-900">
                                                    {formatNumber(invoice.total, 2)} {invoice.currency || 'MKD'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {showDeleted ? (
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleRestore(invoice.id)}
                                                            title={t('invoices.restore')}
                                                        >
                                                            <RotateCcw className="w-4 h-4 text-green-600" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => setForceDeleteInvoice(invoice)}
                                                            title={t('invoices.delete_permanently')}
                                                        >
                                                            <Trash2 className="w-4 h-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <ActionDropdown
                                                        actions={[
                                                            {
                                                                label: t('invoices.view'),
                                                                icon: Eye,
                                                                href: `/invoices/${invoice.id}`,
                                                            },
                                                            {
                                                                label: t('invoices.edit_invoice'),
                                                                icon: Pencil,
                                                                href: `/invoices/${invoice.id}/edit`,
                                                            },
                                                            {
                                                                label: t('invoices.duplicate'),
                                                                icon: Copy,
                                                                href: `/invoices/${invoice.id}/duplicate`,
                                                            },
                                                            {
                                                                label: t('invoices.delete'),
                                                                icon: Trash2,
                                                                onClick: () => setDeleteInvoice(invoice),
                                                                variant: 'destructive',
                                                            },
                                                        ]}
                                                    />
                                                )}
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
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteInvoice}
                onOpenChange={() => setDeleteInvoice(null)}
                title={t('invoices.delete_invoice')}
                description={t('invoices.delete_confirm')}
                deleteUrl={deleteInvoice ? `/invoices/${deleteInvoice.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!forceDeleteInvoice}
                onOpenChange={() => setForceDeleteInvoice(null)}
                title={t('invoices.delete_permanently')}
                description={t('invoices.delete_permanently_confirm')}
                deleteUrl={forceDeleteInvoice ? `/invoices/${forceDeleteInvoice.id}/force-delete` : ''}
            />
        </AppLayout>
    );
}
