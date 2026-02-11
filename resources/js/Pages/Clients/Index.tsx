import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { useSubscription } from '@/hooks/use-subscription';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
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
import { Plus, Archive, Pencil, Trash2, Users, Download } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import SortableTableHead from '@/Components/SortableTableHead';
import type { Client, PaginatedData } from '@/types';

interface ClientWithCounts extends Client {
    invoices_count: number;
    proforma_invoices_count: number;
    contracts_count: number;
}

interface ClientsIndexProps {
    clients: PaginatedData<ClientWithCounts>;
    archivedCount: number;
    cities: string[];
    filters: {
        search?: string;
        city?: string;
        per_page?: number;
        sort?: string;
        dir?: 'asc' | 'desc';
    };
}

export default function ClientsIndex({ clients, archivedCount, cities, filters }: ClientsIndexProps) {
    const { isActive } = useSubscription();
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.search || '');
    const [city, setCity] = useState(filters.city || '__all__');
    const [deleteClient, setDeleteClient] = useState<ClientWithCounts | null>(null);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string | number> = { per_page: filters.per_page || 10 };
        if (search) params.search = search;
        if (city && city !== '__all__') params.city = city;
        router.get('/clients', params, { preserveState: true });
    };

    const clearFilters = () => {
        setSearch('');
        setCity('__all__');
        router.get('/clients');
    };

    return (
        <AppLayout>
            <Head title={t('clients.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('clients.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('clients.subtitle')}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        {archivedCount > 0 && (
                            <Button variant="outline" asChild>
                                <Link href="/clients/archived" className="flex items-center gap-2">
                                    <Archive className="w-4 h-4" />
                                    {t('clients.archived')}
                                    <Badge variant="secondary">{archivedCount}</Badge>
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <a href="/clients/export/csv" className="flex items-center gap-2">
                                <Download className="w-4 h-4" />
                                {t('general.export_csv')}
                            </a>
                        </Button>
                        <Button asChild={isActive} disabled={!isActive}>
                            {isActive ? (
                                <Link href="/clients/create" className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('clients.add_client')}
                                </Link>
                            ) : (
                                <span className="flex items-center gap-2">
                                    <Plus className="w-4 h-4" />
                                    {t('clients.add_client')}
                                </span>
                            )}
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <form onSubmit={handleFilter}>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('clients.search')}
                                    </label>
                                    <Input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder={t('clients.search_placeholder')}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('clients.city')}
                                    </label>
                                    <Select value={city} onValueChange={setCity}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('clients.all_cities')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('clients.all_cities')}</SelectItem>
                                            {cities.map((c) => (
                                                <SelectItem key={c} value={c}>{c}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button type="submit">{t('clients.filter')}</Button>
                                    {(filters.search || filters.city) && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('clients.clear_filters')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Clients List */}
                <Card>
                    {clients.data.length === 0 ? (
                        <CardContent className="p-0">
                            <EmptyState
                                icon={Users}
                                title={t('clients.no_clients')}
                                description={t('clients.add_first_client_description')}
                                action={{
                                    label: t('clients.add_client'),
                                    href: '/clients/create',
                                }}
                            />
                        </CardContent>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <SortableTableHead
                                            column="name"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/clients"
                                        >
                                            {t('clients.client')}
                                        </SortableTableHead>
                                        <TableHead className="hidden md:table-cell">{t('clients.contact')}</TableHead>
                                        <TableHead className="hidden lg:table-cell">{t('clients.tax_number')}</TableHead>
                                        <TableHead className="hidden lg:table-cell text-center">{t('clients.invoices_count')}</TableHead>
                                        <TableHead className="hidden lg:table-cell text-center">{t('clients.proformas_count')}</TableHead>
                                        <TableHead className="hidden lg:table-cell text-center">{t('clients.contracts_count')}</TableHead>
                                        <TableHead className="text-right">{t('clients.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {clients.data.map((client) => (
                                        <TableRow key={client.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {client.name}
                                                    </p>
                                                    {client.city && (
                                                        <p className="text-xs text-gray-400">{client.city}, {client.country}</p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="text-sm text-gray-600">
                                                    {client.email && <p>{client.email}</p>}
                                                    {client.phone && <p className="text-gray-400">{client.phone}</p>}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="text-sm text-gray-600">{client.tax_number || '-'}</span>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell text-center">
                                                <Badge variant={client.invoices_count > 0 ? 'info' : 'gray'}>
                                                    {client.invoices_count}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell text-center">
                                                <Badge variant={client.proforma_invoices_count > 0 ? 'secondary' : 'gray'}>
                                                    {client.proforma_invoices_count}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell text-center">
                                                <Badge variant={client.contracts_count > 0 ? 'success' : 'gray'}>
                                                    {client.contracts_count}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ActionDropdown
                                                    actions={[
                                                        {
                                                            label: t('clients.edit'),
                                                            icon: Pencil,
                                                            href: `/clients/${client.id}/edit`,
                                                            disabled: !isActive,
                                                        },
                                                        {
                                                            label: t('clients.archive'),
                                                            icon: Trash2,
                                                            onClick: () => setDeleteClient(client),
                                                            variant: 'destructive',
                                                            disabled: !isActive,
                                                        },
                                                    ]}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {clients.last_page > 1 && (
                                <div className="px-6 py-4 border-t">
                                    <Pagination
                                        links={clients.links}
                                        from={clients.from}
                                        to={clients.to}
                                        total={clients.total}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteClient}
                onOpenChange={() => setDeleteClient(null)}
                title={t('clients.archive_title')}
                description={t('clients.archive_description')}
                deleteUrl={deleteClient ? `/clients/${deleteClient.id}` : ''}
            />
        </AppLayout>
    );
}
