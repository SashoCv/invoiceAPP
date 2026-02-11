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
import SortableTableHead from '@/Components/SortableTableHead';
import Pagination from '@/Components/Pagination';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { Plus, Eye, Copy, Pencil, Trash2, FileText, RotateCcw, Check, X, ArrowRightLeft, Download, Send, RefreshCw, Printer } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import type { Offer, Client, PaginatedData } from '@/types';

interface OffersIndexProps {
    offers: PaginatedData<Offer>;
    clients: Client[];
    showDeleted: boolean;
    filters: {
        offer?: string;
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
    accepted: 'success',
    rejected: 'destructive',
};

export default function OffersIndex({ offers, clients, showDeleted, filters }: OffersIndexProps) {
    const { isActive } = useSubscription();
    const { t } = useTranslation();
    const [offerSearch, setOfferSearch] = useState(filters.offer || '');
    const [clientFilter, setClientFilter] = useState(filters.client || '__all__');
    const [statusFilter, setStatusFilter] = useState(filters.status || '__all__');
    const [deleteOffer, setDeleteOffer] = useState<Offer | null>(null);
    const [forceDeleteOffer, setForceDeleteOffer] = useState<Offer | null>(null);

    // Status change dialog
    const [statusOffer, setStatusOffer] = useState<Offer | null>(null);
    const [newStatus, setNewStatus] = useState('');

    const openStatusDialog = (offer: Offer) => {
        setStatusOffer(offer);
        setNewStatus(offer.status);
    };

    const submitStatusChange = () => {
        if (!statusOffer) return;
        router.patch(`/offers/${statusOffer.id}/status`, { status: newStatus }, {
            preserveScroll: true,
            onSuccess: () => setStatusOffer(null),
        });
    };

    // Send email dialog
    const [sendOffer, setSendOffer] = useState<Offer | null>(null);
    const [sendForm, setSendForm] = useState({ to: '', subject: '', body: '' });
    const [sendErrors, setSendErrors] = useState<Record<string, string>>({});
    const [sendLoading, setSendLoading] = useState(false);

    const openSendDialog = (offer: Offer) => {
        setSendOffer(offer);
        setSendForm({
            to: offer.client?.email || '',
            subject: `${t('offers.offer')} ${offer.offer_number}`,
            body: t('offers.email_default_body').replace(/\\n/g, '\n'),
        });
        setSendErrors({});
    };

    const submitSend = () => {
        if (!sendOffer) return;
        setSendLoading(true);
        router.post(`/offers/${sendOffer.id}/send`, sendForm, {
            preserveScroll: true,
            onSuccess: () => {
                setSendOffer(null);
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
        if (offerSearch) params.offer = offerSearch;
        if (clientFilter && clientFilter !== '__all__') params.client = clientFilter;
        if (statusFilter && statusFilter !== '__all__') params.status = statusFilter;
        if (showDeleted) params.deleted = '1';
        router.get('/offers', params, { preserveState: true });
    };

    const clearFilters = () => {
        setOfferSearch('');
        setClientFilter('__all__');
        setStatusFilter('__all__');
        router.get('/offers', showDeleted ? { deleted: '1' } : {});
    };

    const handleRestore = (id: number) => {
        router.post(`/offers/${id}/restore`);
    };

    const handleAccept = (id: number) => {
        router.post(`/offers/${id}/accept`);
    };

    const handleReject = (id: number) => {
        router.post(`/offers/${id}/reject`);
    };

    const handleConvertToInvoice = (id: number) => {
        router.post(`/offers/${id}/convert`);
    };

    const hasFilters = filters.offer || filters.client || filters.status;

    return (
        <AppLayout>
            <Head title={t('offers.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('offers.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('offers.subtitle')}</p>
                    </div>
                    {!showDeleted && (
                        <Button asChild={isActive} disabled={!isActive}>
                            {isActive ? (
                                <Link href="/offers/create" className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('offers.new_offer')}
                                </Link>
                            ) : (
                                <span className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('offers.new_offer')}
                                </span>
                            )}
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <Tabs value={showDeleted ? 'deleted' : 'active'} className="mb-6">
                    <TabsList>
                        <TabsTrigger value="active" asChild>
                            <Link href="/offers">{t('offers.active_offers')}</Link>
                        </TabsTrigger>
                        <TabsTrigger value="deleted" asChild>
                            <Link href="/offers?deleted=1">{t('offers.deleted_offers')}</Link>
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
                                        {t('offers.search')}
                                    </label>
                                    <Input
                                        value={offerSearch}
                                        onChange={(e) => setOfferSearch(e.target.value)}
                                        placeholder={t('offers.search_placeholder')}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('offers.client')}
                                    </label>
                                    <Select value={clientFilter} onValueChange={setClientFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('offers.all_clients')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('offers.all_clients')}</SelectItem>
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
                                        {t('offers.status')}
                                    </label>
                                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('offers.all_statuses')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('offers.all_statuses')}</SelectItem>
                                            <SelectItem value="draft">{t('offers.status_draft')}</SelectItem>
                                            <SelectItem value="sent">{t('offers.status_sent')}</SelectItem>
                                            <SelectItem value="accepted">{t('offers.status_accepted')}</SelectItem>
                                            <SelectItem value="rejected">{t('offers.status_rejected')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button type="submit">{t('offers.filter')}</Button>
                                    {hasFilters && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('offers.clear_filters')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Offers List */}
                <Card>
                    {offers.data.length === 0 ? (
                        <CardContent className="p-0">
                            <EmptyState
                                icon={FileText}
                                title={showDeleted ? t('offers.no_deleted_offers') : t('offers.no_offers')}
                                description={!showDeleted ? t('offers.create_first_description') : undefined}
                                action={!showDeleted ? {
                                    label: t('offers.new_offer'),
                                    href: '/offers/create',
                                } : undefined}
                            />
                        </CardContent>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <SortableTableHead
                                            column="offer_number"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/offers"
                                        >
                                            {t('offers.offer')}
                                        </SortableTableHead>
                                        <TableHead>{t('offers.title')}</TableHead>
                                        <TableHead className="hidden md:table-cell">{t('offers.client')}</TableHead>
                                        <SortableTableHead
                                            column="issue_date"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/offers"
                                            className="hidden lg:table-cell"
                                        >
                                            {t('offers.date')}
                                        </SortableTableHead>
                                        <TableHead className="text-center">{t('offers.status')}</TableHead>
                                        <SortableTableHead
                                            column="total"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/offers"
                                            className="text-right"
                                        >
                                            {t('offers.total')}
                                        </SortableTableHead>
                                        <TableHead className="text-right">{t('offers.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {offers.data.map((offer) => (
                                        <TableRow key={offer.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/offers/${offer.id}`}
                                                    className="text-sm font-medium text-blue-600 hover:text-blue-800"
                                                >
                                                    {offer.offer_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-sm text-gray-900">{offer.title}</span>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="text-sm text-gray-900">{offer.client?.name || '-'}</span>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="text-sm text-gray-600">{formatDate(offer.issue_date)}</span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={statusVariants[offer.status] || 'gray'}>
                                                    {t(`offers.status_${offer.status}`)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {offer.has_items ? (
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {formatNumber(offer.total, 2)} {offer.currency}
                                                    </span>
                                                ) : (
                                                    <span className="text-sm text-gray-400">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {showDeleted ? (
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleRestore(offer.id)}
                                                            title={t('offers.restore')}
                                                        >
                                                            <RotateCcw className="w-4 h-4 text-green-600" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => setForceDeleteOffer(offer)}
                                                            title={t('offers.delete_permanently')}
                                                        >
                                                            <Trash2 className="w-4 h-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <ActionDropdown
                                                        actions={[
                                                            {
                                                                label: t('offers.view'),
                                                                icon: Eye,
                                                                href: `/offers/${offer.id}/pdf/preview`,
                                                                external: true,
                                                                target: '_blank',
                                                            },
                                                            {
                                                                label: t('offers.edit'),
                                                                icon: Pencil,
                                                                href: `/offers/${offer.id}/edit`,
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.accept'),
                                                                icon: Check,
                                                                onClick: () => handleAccept(offer.id),
                                                                hidden: offer.status !== 'sent',
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.reject'),
                                                                icon: X,
                                                                onClick: () => handleReject(offer.id),
                                                                hidden: offer.status !== 'sent',
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.convert_to_invoice'),
                                                                icon: ArrowRightLeft,
                                                                onClick: () => handleConvertToInvoice(offer.id),
                                                                hidden: !(offer.status === 'accepted' && !offer.converted_invoice_id && offer.has_items),
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.duplicate'),
                                                                icon: Copy,
                                                                href: `/offers/${offer.id}/duplicate`,
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.download_pdf'),
                                                                icon: Download,
                                                                href: `/offers/${offer.id}/pdf`,
                                                                external: true,
                                                            },
                                                            {
                                                                label: t('offers.print_pdf'),
                                                                icon: Printer,
                                                                href: `/offers/${offer.id}/pdf/preview`,
                                                                external: true,
                                                                target: '_blank',
                                                            },
                                                            {
                                                                label: t('offers.change_status'),
                                                                icon: RefreshCw,
                                                                onClick: () => openStatusDialog(offer),
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.send_offer'),
                                                                icon: Send,
                                                                onClick: () => openSendDialog(offer),
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('offers.delete'),
                                                                icon: Trash2,
                                                                onClick: () => setDeleteOffer(offer),
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

                            {offers.last_page > 1 && (
                                <div className="px-6 py-4 border-t">
                                    <Pagination
                                        links={offers.links}
                                        from={offers.from}
                                        to={offers.to}
                                        total={offers.total}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteOffer}
                onOpenChange={() => setDeleteOffer(null)}
                title={t('offers.delete_offer')}
                description={t('offers.delete_confirm')}
                deleteUrl={deleteOffer ? `/offers/${deleteOffer.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!forceDeleteOffer}
                onOpenChange={() => setForceDeleteOffer(null)}
                title={t('offers.delete_permanently')}
                description={t('offers.delete_permanently_confirm')}
                deleteUrl={forceDeleteOffer ? `/offers/${forceDeleteOffer.id}/force-delete` : ''}
            />

            {/* Change Status Dialog */}
            <Dialog open={!!statusOffer} onOpenChange={() => setStatusOffer(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('offers.change_status')}</DialogTitle>
                        <DialogDescription>
                            {statusOffer?.offer_number}
                        </DialogDescription>
                    </DialogHeader>
                    <div>
                        <Label className="mb-2 block">{t('offers.status')}</Label>
                        <Select value={newStatus} onValueChange={setNewStatus}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">{t('offers.status_draft')}</SelectItem>
                                <SelectItem value="sent">{t('offers.status_sent')}</SelectItem>
                                <SelectItem value="accepted">{t('offers.status_accepted')}</SelectItem>
                                <SelectItem value="rejected">{t('offers.status_rejected')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setStatusOffer(null)}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitStatusChange}>
                            {t('general.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Send Email Dialog */}
            <Dialog open={!!sendOffer} onOpenChange={() => setSendOffer(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('offers.send_offer')}</DialogTitle>
                        <DialogDescription>
                            {sendOffer?.offer_number}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-2 block">{t('offers.email_to')}</Label>
                            <Input
                                type="email"
                                value={sendForm.to}
                                onChange={(e) => setSendForm({ ...sendForm, to: e.target.value })}
                            />
                            {sendErrors.to && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.to}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('offers.email_subject')}</Label>
                            <Input
                                value={sendForm.subject}
                                onChange={(e) => setSendForm({ ...sendForm, subject: e.target.value })}
                            />
                            {sendErrors.subject && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.subject}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('offers.email_body')}</Label>
                            <Textarea
                                rows={6}
                                value={sendForm.body}
                                onChange={(e) => setSendForm({ ...sendForm, body: e.target.value })}
                            />
                            {sendErrors.body && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.body}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setSendOffer(null)} disabled={sendLoading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitSend} loading={sendLoading}>
                            <Send className="w-4 h-4 mr-2" />
                            {t('offers.send_email')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
