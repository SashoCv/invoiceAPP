import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { useSubscription } from '@/hooks/use-subscription';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import Pagination from '@/Components/Pagination';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import SortableTableHead from '@/Components/SortableTableHead';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import {
    Plus, Pencil, Trash2, Warehouse, Eye,
    ArrowUpDown, Package, Layers
} from 'lucide-react';
import type { Article, Bundle, StockMovement, PaginatedData } from '@/types';

interface InventoryIndexProps {
    items: PaginatedData<Article>;
    untrackedArticles: Article[];
    bundles: Bundle[];
    movements: StockMovement[];
    filters: {
        search?: string;
        stock_status?: string;
        per_page?: number;
        sort?: string;
        dir?: 'asc' | 'desc';
        movement_type?: string;
        movement_from?: string;
        movement_to?: string;
    };
}

function StockStatusBadge({ status, t }: { status: string; t: (key: string) => string }) {
    const variants: Record<string, string> = {
        in_stock: 'bg-green-100 text-green-800',
        low_stock: 'bg-yellow-100 text-yellow-800',
        out_of_stock: 'bg-red-100 text-red-800',
    };
    const labels: Record<string, string> = {
        in_stock: t('inventory.in_stock'),
        low_stock: t('inventory.low_stock'),
        out_of_stock: t('inventory.out_of_stock'),
    };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[status] || ''}`}>
            {labels[status] || status}
        </span>
    );
}

function MovementTypeBadge({ type, t }: { type: string; t: (key: string) => string }) {
    const variants: Record<string, string> = {
        receipt: 'bg-green-100 text-green-800',
        issue: 'bg-orange-100 text-orange-800',
        adjustment: 'bg-blue-100 text-blue-800',
        invoice_deduction: 'bg-purple-100 text-purple-800',
        shopify_deduction: 'bg-indigo-100 text-indigo-800',
    };
    const labels: Record<string, string> = {
        receipt: t('inventory.type_receipt'),
        issue: t('inventory.type_issue'),
        adjustment: t('inventory.type_adjustment'),
        invoice_deduction: t('inventory.type_invoice_deduction'),
        shopify_deduction: t('inventory.type_shopify_deduction'),
    };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[type] || ''}`}>
            {labels[type] || type}
        </span>
    );
}

export default function InventoryIndex({ items, untrackedArticles, bundles, movements, filters }: InventoryIndexProps) {
    const { isActive } = useSubscription();
    const { t } = useTranslation();

    // Items filters
    const [search, setSearch] = useState(filters.search || '');
    const [stockStatus, setStockStatus] = useState(filters.stock_status || '__all__');
    const [deleteItem, setDeleteItem] = useState<Article | null>(null);
    const [deleteBundle, setDeleteBundle] = useState<Bundle | null>(null);

    // Add to warehouse dialog
    const [addDialogOpen, setAddDialogOpen] = useState(false);
    const addForm = useForm({
        article_id: '',
        stock_quantity: 0,
        low_stock_threshold: 5,
    });

    // Movements filters
    const [movementType, setMovementType] = useState(filters.movement_type || '__all__');
    const [movementFrom, setMovementFrom] = useState(filters.movement_from || '');
    const [movementTo, setMovementTo] = useState(filters.movement_to || '');

    const hasItemFilters = search || (stockStatus && stockStatus !== '__all__');
    const hasMovementFilters = (movementType && movementType !== '__all__') || movementFrom || movementTo;

    const handleItemFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (search) params.search = search;
        if (stockStatus && stockStatus !== '__all__') params.stock_status = stockStatus;
        router.get('/inventory', params, { preserveState: true });
    };

    const clearItemFilters = () => {
        setSearch('');
        setStockStatus('__all__');
        router.get('/inventory', {});
    };

    const handleMovementFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (movementType && movementType !== '__all__') params.movement_type = movementType;
        if (movementFrom) params.movement_from = movementFrom;
        if (movementTo) params.movement_to = movementTo;
        router.get('/inventory', params, { preserveState: true });
    };

    const clearMovementFilters = () => {
        setMovementType('__all__');
        setMovementFrom('');
        setMovementTo('');
        router.get('/inventory', {});
    };

    const handleAddToWarehouse = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post('/inventory', {
            onSuccess: () => {
                setAddDialogOpen(false);
                addForm.reset();
            },
        });
    };

    return (
        <AppLayout>
            <Head title={t('inventory.title')} />

            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('inventory.subtitle')}</p>
                    </div>
                </div>

                <Tabs defaultValue="items">
                    <TabsList className="mb-6">
                        <TabsTrigger value="items" className="flex items-center gap-2">
                            <Package className="w-4 h-4" />
                            {t('inventory.items_tab')}
                        </TabsTrigger>
                        <TabsTrigger value="bundles" className="flex items-center gap-2">
                            <Layers className="w-4 h-4" />
                            {t('inventory.bundles_tab')}
                        </TabsTrigger>
                        <TabsTrigger value="movements" className="flex items-center gap-2">
                            <ArrowUpDown className="w-4 h-4" />
                            {t('inventory.movements_tab')}
                        </TabsTrigger>
                    </TabsList>

                    {/* Items Tab */}
                    <TabsContent value="items">
                        <div className="flex justify-end mb-4">
                            <Button
                                disabled={!isActive || untrackedArticles.length === 0}
                                onClick={() => setAddDialogOpen(true)}
                                className="flex items-center gap-2"
                            >
                                <Plus className="w-4 h-4" />
                                {t('inventory.add_to_warehouse')}
                            </Button>
                        </div>

                        <Card className="mb-6">
                            <CardContent className="pt-6">
                                <form onSubmit={handleItemFilter}>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <Input
                                            placeholder={t('inventory.search_placeholder')}
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                        />
                                        <Select value={stockStatus} onValueChange={setStockStatus}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('inventory.all_stock_statuses')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__all__">{t('inventory.all_stock_statuses')}</SelectItem>
                                                <SelectItem value="in_stock">{t('inventory.in_stock')}</SelectItem>
                                                <SelectItem value="low_stock">{t('inventory.low_stock')}</SelectItem>
                                                <SelectItem value="out_of_stock">{t('inventory.out_of_stock')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div className="flex items-end gap-2">
                                            <Button type="submit">{t('inventory.filter')}</Button>
                                            {hasItemFilters && (
                                                <Button type="button" variant="outline" onClick={clearItemFilters}>
                                                    {t('inventory.clear_filters')}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            {items.data.length === 0 ? (
                                <CardContent className="p-0">
                                    <EmptyState
                                        icon={Warehouse}
                                        title={t('inventory.no_items')}
                                        description={t('inventory.create_first_description')}
                                    />
                                </CardContent>
                            ) : (
                                <>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <SortableTableHead column="name" currentSort={filters.sort} currentDirection={filters.dir} baseUrl="/inventory">
                                                    {t('inventory.name')}
                                                </SortableTableHead>
                                                <TableHead>{t('inventory.unit')}</TableHead>
                                                <SortableTableHead column="stock_quantity" currentSort={filters.sort} currentDirection={filters.dir} baseUrl="/inventory">
                                                    {t('inventory.stock_quantity')}
                                                </SortableTableHead>
                                                <SortableTableHead column="price" currentSort={filters.sort} currentDirection={filters.dir} baseUrl="/inventory">
                                                    {t('inventory.price')}
                                                </SortableTableHead>
                                                <TableHead>{t('inventory.status')}</TableHead>
                                                <TableHead className="text-right">{t('inventory.actions')}</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {items.data.map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        <Link href={`/inventory/${item.id}`} className="font-medium text-gray-900 hover:text-blue-600">
                                                            {item.name}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell className="text-gray-500">{item.unit}</TableCell>
                                                    <TableCell>
                                                        <span className="font-medium">{formatNumber(item.stock_quantity, 0)}</span>
                                                    </TableCell>
                                                    <TableCell>{formatNumber(item.price)}</TableCell>
                                                    <TableCell>
                                                        <StockStatusBadge status={item.stock_status} t={t} />
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <ActionDropdown
                                                            actions={[
                                                                {
                                                                    label: t('inventory.view'),
                                                                    icon: Eye,
                                                                    href: `/inventory/${item.id}`,
                                                                },
                                                                {
                                                                    label: t('inventory.disable_tracking'),
                                                                    icon: Trash2,
                                                                    onClick: () => setDeleteItem(item),
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
                                    {items.last_page > 1 && (
                                        <div className="px-6 py-4 border-t">
                                            <Pagination links={items.links} from={items.from} to={items.to} total={items.total} />
                                        </div>
                                    )}
                                </>
                            )}
                        </Card>
                    </TabsContent>

                    {/* Bundles Tab */}
                    <TabsContent value="bundles">
                        <div className="flex justify-end mb-4">
                            <Button asChild={isActive} disabled={!isActive}>
                                {isActive ? (
                                    <Link href="/bundles/create" className="flex items-center gap-2">
                                        <Plus className="w-4 h-4" />
                                        {t('inventory.new_bundle')}
                                    </Link>
                                ) : (
                                    <span className="flex items-center gap-2">
                                        <Plus className="w-4 h-4" />
                                        {t('inventory.new_bundle')}
                                    </span>
                                )}
                            </Button>
                        </div>

                        <Card>
                            {bundles.length === 0 ? (
                                <CardContent className="p-0">
                                    <EmptyState
                                        icon={Layers}
                                        title={t('inventory.no_bundles')}
                                        description={t('inventory.create_first_bundle')}
                                        action={isActive ? { label: t('inventory.new_bundle'), href: '/bundles/create' } : undefined}
                                    />
                                </CardContent>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.name')}</TableHead>
                                            <TableHead className="hidden md:table-cell">{t('inventory.description')}</TableHead>
                                            <TableHead>{t('inventory.component_count')}</TableHead>
                                            <TableHead>{t('inventory.price')}</TableHead>
                                            <TableHead>{t('inventory.status')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bundles.map((bundle) => (
                                            <TableRow key={bundle.id}>
                                                <TableCell className="font-medium text-gray-900">{bundle.name}</TableCell>
                                                <TableCell className="hidden md:table-cell text-gray-500">
                                                    {bundle.description || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">{bundle.bundle_items?.length || 0}</Badge>
                                                </TableCell>
                                                <TableCell>{formatNumber(bundle.price)}</TableCell>
                                                <TableCell>
                                                    <Badge variant={bundle.is_active ? 'default' : 'secondary'}>
                                                        {bundle.is_active ? t('inventory.active') : t('inventory.inactive')}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <ActionDropdown
                                                        actions={[
                                                            {
                                                                label: t('inventory.edit'),
                                                                icon: Pencil,
                                                                href: `/bundles/${bundle.id}/edit`,
                                                                disabled: !isActive,
                                                            },
                                                            {
                                                                label: t('inventory.delete'),
                                                                icon: Trash2,
                                                                onClick: () => setDeleteBundle(bundle),
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
                            )}
                        </Card>
                    </TabsContent>

                    {/* Movements Tab */}
                    <TabsContent value="movements">
                        <Card className="mb-6">
                            <CardContent className="pt-6">
                                <form onSubmit={handleMovementFilter}>
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <Select value={movementType} onValueChange={setMovementType}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('inventory.movement_all_types')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__all__">{t('inventory.movement_all_types')}</SelectItem>
                                                <SelectItem value="receipt">{t('inventory.type_receipt')}</SelectItem>
                                                <SelectItem value="issue">{t('inventory.type_issue')}</SelectItem>
                                                <SelectItem value="adjustment">{t('inventory.type_adjustment')}</SelectItem>
                                                <SelectItem value="invoice_deduction">{t('inventory.type_invoice_deduction')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            type="date"
                                            value={movementFrom}
                                            onChange={(e) => setMovementFrom(e.target.value)}
                                            placeholder={t('inventory.movement_from')}
                                        />
                                        <Input
                                            type="date"
                                            value={movementTo}
                                            onChange={(e) => setMovementTo(e.target.value)}
                                            placeholder={t('inventory.movement_to')}
                                        />
                                        <div className="flex items-end gap-2">
                                            <Button type="submit">{t('inventory.filter')}</Button>
                                            {hasMovementFilters && (
                                                <Button type="button" variant="outline" onClick={clearMovementFilters}>
                                                    {t('inventory.clear_filters')}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            {movements.length === 0 ? (
                                <CardContent className="py-12 text-center text-gray-500">
                                    {t('inventory.no_movements')}
                                </CardContent>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.movement_date')}</TableHead>
                                            <TableHead>{t('inventory.movement_item')}</TableHead>
                                            <TableHead>{t('inventory.movement_type')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.movement_quantity')}</TableHead>
                                            <TableHead className="text-right hidden md:table-cell">{t('inventory.movement_before')}</TableHead>
                                            <TableHead className="text-right hidden md:table-cell">{t('inventory.movement_after')}</TableHead>
                                            <TableHead className="hidden lg:table-cell">{t('inventory.movement_notes')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {movements.map((movement) => (
                                            <TableRow key={movement.id}>
                                                <TableCell className="text-gray-500 whitespace-nowrap">
                                                    {formatDate(movement.created_at)}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {movement.article?.name || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <MovementTypeBadge type={movement.type} t={t} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <span className={movement.quantity < 0 ? 'text-red-600' : 'text-green-600'}>
                                                        {movement.quantity > 0 ? '+' : ''}{formatNumber(movement.quantity, 0)}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right hidden md:table-cell text-gray-500">
                                                    {formatNumber(movement.quantity_before, 0)}
                                                </TableCell>
                                                <TableCell className="text-right hidden md:table-cell text-gray-500">
                                                    {formatNumber(movement.quantity_after, 0)}
                                                </TableCell>
                                                <TableCell className="hidden lg:table-cell text-gray-500 max-w-xs truncate">
                                                    {movement.notes || '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Add to Warehouse Dialog */}
            <Dialog open={addDialogOpen} onOpenChange={setAddDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('inventory.add_to_warehouse')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleAddToWarehouse} className="space-y-4">
                        <div>
                            <Label>{t('inventory.select_article')}</Label>
                            <Select
                                value={addForm.data.article_id}
                                onValueChange={(val) => addForm.setData('article_id', val)}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue placeholder={t('inventory.select_article')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {untrackedArticles.map((article) => (
                                        <SelectItem key={article.id} value={String(article.id)}>
                                            {article.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {addForm.errors.article_id && (
                                <p className="text-sm text-red-600 mt-1">{addForm.errors.article_id}</p>
                            )}
                        </div>
                        <div>
                            <Label>{t('inventory.initial_stock')}</Label>
                            <Input
                                type="number"
                                step="1"
                                min="0"
                                value={addForm.data.stock_quantity}
                                onChange={(e) => addForm.setData('stock_quantity', parseFloat(e.target.value) || 0)}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>{t('inventory.low_stock_threshold')}</Label>
                            <Input
                                type="number"
                                step="1"
                                min="0"
                                value={addForm.data.low_stock_threshold}
                                onChange={(e) => addForm.setData('low_stock_threshold', parseFloat(e.target.value) || 0)}
                                className="mt-1"
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAddDialogOpen(false)}>
                                {t('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={addForm.processing} loading={addForm.processing}>
                                {t('inventory.enable_tracking')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteItem}
                onOpenChange={() => setDeleteItem(null)}
                title={t('inventory.disable_tracking')}
                description={t('inventory.disable_tracking_confirm')}
                deleteUrl={deleteItem ? `/inventory/${deleteItem.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!deleteBundle}
                onOpenChange={() => setDeleteBundle(null)}
                title={t('inventory.delete_bundle')}
                description={t('inventory.delete_bundle_confirm')}
                deleteUrl={deleteBundle ? `/bundles/${deleteBundle.id}` : ''}
            />
        </AppLayout>
    );
}
