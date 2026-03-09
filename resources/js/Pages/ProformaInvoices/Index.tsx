import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { useSubscription } from '@/hooks/use-subscription';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import Pagination from '@/Components/Pagination';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { Plus, Eye, Copy, Pencil, Trash2, FileText, RotateCcw, ArrowRightLeft, Download, Send, RefreshCw, Printer } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import SortableTableHead from '@/Components/SortableTableHead';
import type { ProformaInvoice, Client, PaginatedData } from '@/types';

interface ProformaInvoicesIndexProps {
    proformas: PaginatedData<ProformaInvoice>;
    clients: Client[];
    showDeleted: boolean;
    filters: {
        proforma?: string;
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
    converted_to_invoice: 'success',
};

export default function ProformaInvoicesIndex({ proformas, clients, showDeleted, filters }: ProformaInvoicesIndexProps) {
    const { isActive } = useSubscription();
    const { t } = useTranslation();
    const [proformaSearch, setProformaSearch] = useState(filters.proforma || '');
    const [clientFilter, setClientFilter] = useState(filters.client || '__all__');
    const [statusFilter, setStatusFilter] = useState(filters.status || '__all__');
    const [deleteProforma, setDeleteProforma] = useState<ProformaInvoice | null>(null);
    const [forceDeleteProforma, setForceDeleteProforma] = useState<ProformaInvoice | null>(null);

    // Status change dialog
    const [statusProforma, setStatusProforma] = useState<ProformaInvoice | null>(null);
    const [newStatus, setNewStatus] = useState('');

    const openStatusDialog = (proforma: ProformaInvoice) => {
        setStatusProforma(proforma);
        setNewStatus(proforma.status);
    };

    const submitStatusChange = () => {
        if (!statusProforma) return;
        router.patch(`/proforma-invoices/${statusProforma.id}/status`, { status: newStatus }, {
            preserveScroll: true,
            onSuccess: () => setStatusProforma(null),
        });
    };

    // Send email dialog
    const [sendProforma, setSendProforma] = useState<ProformaInvoice | null>(null);
    const [sendForm, setSendForm] = useState({ to: '', subject: '', body: '' });
    const [sendErrors, setSendErrors] = useState<Record<string, string>>({});
    const [sendLoading, setSendLoading] = useState(false);

    const openSendDialog = (proforma: ProformaInvoice) => {
        setSendProforma(proforma);
        setSendForm({
            to: proforma.client?.email || '',
            subject: `${t('proforma.proforma')} ${proforma.proforma_number}`,
            body: t('proforma.email_default_body').replace(/\\n/g, '\n'),
        });
        setSendErrors({});
    };

    const submitSend = () => {
        if (!sendProforma) return;
        setSendLoading(true);
        router.post(`/proforma-invoices/${sendProforma.id}/send`, sendForm, {
            preserveScroll: true,
            onSuccess: () => {
                setSendProforma(null);
                setSendLoading(false);
            },
            onError: (errors) => {
                setSendErrors(errors);
                setSendLoading(false);
            },
        });
    };

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (proformaSearch) params.proforma = proformaSearch;
        if (clientFilter && clientFilter !== '__all__') params.client = clientFilter;
        if (statusFilter && statusFilter !== '__all__') params.status = statusFilter;
        if (showDeleted) params.deleted = '1';
        router.get('/proforma-invoices', params, { preserveState: true });
    };

    const clearFilters = () => {
        setProformaSearch('');
        setClientFilter('__all__');
        setStatusFilter('__all__');
        router.get('/proforma-invoices', showDeleted ? { deleted: '1' } : {});
    };

    const handleRestore = (id: number) => {
        router.post(`/proforma-invoices/${id}/restore`);
    };

    const handleConvertToInvoice = (id: number) => {
        router.post(`/proforma-invoices/${id}/convert`);
    };

    const hasFilters = filters.proforma || filters.client || filters.status;

    return (
        <AppLayout>
            <Head title={t('proforma.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('proforma.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('proforma.subtitle')}</p>
                    </div>
                    {!showDeleted && (
                        <Button asChild={isActive} disabled={!isActive}>
                            {isActive ? (
                                <Link href="/proforma-invoices/create" className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('proforma.new_proforma')}
                                </Link>
                            ) : (
                                <span className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('proforma.new_proforma')}
                                </span>
                            )}
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <Tabs value={showDeleted ? 'deleted' : 'active'} className="mb-6">
                    <TabsList>
                        <TabsTrigger value="active" asChild>
                            <Link href="/proforma-invoices">{t('proforma.active_proformas')}</Link>
                        </TabsTrigger>
                        <TabsTrigger value="deleted" asChild>
                            <Link href="/proforma-invoices?deleted=1">{t('proforma.deleted_proformas')}</Link>
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
                                        {t('proforma.search')}
                                    </label>
                                    <Input
                                        value={proformaSearch}
                                        onChange={(e) => setProformaSearch(e.target.value)}
                                        placeholder={t('proforma.search_placeholder')}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('proforma.client')}
                                    </label>
                                    <Select value={clientFilter} onValueChange={setClientFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('proforma.all_clients')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('proforma.all_clients')}</SelectItem>
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
                                        {t('proforma.status')}
                                    </label>
                                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('proforma.all_statuses')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('proforma.all_statuses')}</SelectItem>
                                            <SelectItem value="draft">{t('proforma.status_draft')}</SelectItem>
                                            <SelectItem value="sent">{t('proforma.status_sent')}</SelectItem>
                                            <SelectItem value="converted_to_invoice">{t('proforma.status_converted')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button type="submit">{t('proforma.filter')}</Button>
                                    {hasFilters && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('proforma.clear_filters')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Proformas List */}
                <Card>
                    {proformas.data.length === 0 ? (
                        <CardContent className="p-0">
                            <EmptyState
                                icon={FileText}
                                title={showDeleted ? t('proforma.no_deleted_proformas') : t('proforma.no_proformas')}
                                description={!showDeleted ? t('proforma.create_first_description') : undefined}
                                action={!showDeleted ? {
                                    label: t('proforma.new_proforma'),
                                    href: '/proforma-invoices/create',
                                } : undefined}
                            />
                        </CardContent>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <SortableTableHead
                                            column="proforma_number"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/proforma-invoices"
                                        >
                                            {t('proforma.proforma')}
                                        </SortableTableHead>
                                        <TableHead className="hidden md:table-cell">{t('proforma.client')}</TableHead>
                                        <SortableTableHead
                                            column="issue_date"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/proforma-invoices"
                                            className="hidden lg:table-cell"
                                        >
                                            {t('proforma.date')}
                                        </SortableTableHead>
                                        <TableHead className="text-center">{t('proforma.status')}</TableHead>
                                        <SortableTableHead
                                            column="total"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/proforma-invoices"
                                            className="text-right"
                                        >
                                            {t('proforma.total')}
                                        </SortableTableHead>
                                        <TableHead className="text-right">{t('proforma.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {proformas.data.map((proforma) => (
                                        <TableRow key={proforma.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/proforma-invoices/${proforma.id}`}
                                                    className="text-sm font-medium text-blue-600 hover:text-blue-800"
                                                >
                                                    {proforma.proforma_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="text-sm text-gray-900">{proforma.client?.name || '-'}</span>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="text-sm text-gray-600">{formatDate(proforma.issue_date)}</span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={statusVariants[proforma.status] || 'gray'}>
                                                    {t(`proforma.status_${proforma.status}`)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className="text-sm font-medium text-gray-900">
                                                    {formatNumber(proforma.total, 2)} {proforma.currency}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {showDeleted ? (
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleRestore(proforma.id)}
                                                            title={t('proforma.restore')}
                                                        >
                                                            <RotateCcw className="w-4 h-4 text-green-600" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => setForceDeleteProforma(proforma)}
                                                            title={t('proforma.delete_permanently')}
                                                        >
                                                            <Trash2 className="w-4 h-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <ActionDropdown
                                                        actions={[
                                                            {
                                                                label: t('proforma.view'),
                                                                icon: Eye,
                                                                href: `/proforma-invoices/${proforma.id}/pdf/preview`,
                                                                external: true,
                                                                target: '_blank',
                                                            },
                                                            {
                                                                label: t('proforma.edit'),
                                                                icon: Pencil,
                                                                href: `/proforma-invoices/${proforma.id}/edit`,
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('proforma.convert_to_invoice'),
                                                                icon: ArrowRightLeft,
                                                                onClick: () => handleConvertToInvoice(proforma.id),
                                                                hidden: proforma.status === 'converted_to_invoice',
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('proforma.duplicate'),
                                                                icon: Copy,
                                                                href: `/proforma-invoices/${proforma.id}/duplicate`,
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('proforma.download_pdf'),
                                                                icon: Download,
                                                                href: `/proforma-invoices/${proforma.id}/pdf`,
                                                                external: true,
                                                            },
                                                            {
                                                                label: t('proforma.print_pdf'),
                                                                icon: Printer,
                                                                href: `/proforma-invoices/${proforma.id}/pdf/preview`,
                                                                external: true,
                                                                target: '_blank',
                                                            },
                                                            {
                                                                label: t('proforma.change_status'),
                                                                icon: RefreshCw,
                                                                onClick: () => openStatusDialog(proforma),
                                                                disabled: !isActive,
                                                            },
                                                            // TODO: Enable when email sending is implemented
                                                            // {
                                                            //     label: t('proforma.send_proforma'),
                                                            //     icon: Send,
                                                            //     onClick: () => openSendDialog(proforma),
                                                            //     disabled: !isActive,
                                                            // },
                                                            {
                                                                label: t('proforma.delete'),
                                                                icon: Trash2,
                                                                onClick: () => setDeleteProforma(proforma),
                                                                variant: 'destructive',
                                                                disabled: !isActive,
                                                            },
                                                        ]}
                                                    />
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {proformas.last_page > 1 && (
                                <div className="px-6 py-4 border-t">
                                    <Pagination
                                        links={proformas.links}
                                        from={proformas.from}
                                        to={proformas.to}
                                        total={proformas.total}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteProforma}
                onOpenChange={() => setDeleteProforma(null)}
                title={t('proforma.delete_proforma')}
                description={t('proforma.delete_confirm')}
                deleteUrl={deleteProforma ? `/proforma-invoices/${deleteProforma.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!forceDeleteProforma}
                onOpenChange={() => setForceDeleteProforma(null)}
                title={t('proforma.delete_permanently')}
                description={t('proforma.delete_permanently_confirm')}
                deleteUrl={forceDeleteProforma ? `/proforma-invoices/${forceDeleteProforma.id}/force-delete` : ''}
            />

            {/* Change Status Dialog */}
            <Dialog open={!!statusProforma} onOpenChange={() => setStatusProforma(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('proforma.change_status')}</DialogTitle>
                        <DialogDescription>
                            {statusProforma?.proforma_number}
                        </DialogDescription>
                    </DialogHeader>
                    <div>
                        <Label className="mb-2 block">{t('proforma.status')}</Label>
                        <Select value={newStatus} onValueChange={setNewStatus}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">{t('proforma.status_draft')}</SelectItem>
                                <SelectItem value="sent">{t('proforma.status_sent')}</SelectItem>
                                <SelectItem value="converted_to_invoice">{t('proforma.status_converted')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setStatusProforma(null)}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitStatusChange}>
                            {t('general.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* TODO: Send Email Dialog - Enable when email sending is implemented */}
        </AppLayout>
    );
}
