import { Head, Link, router } from '@inertiajs/react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import Pagination from '@/Components/Pagination';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import type { PaginatedData } from '@/types';
import {
    Package,
    Warehouse,
    AlertTriangle,
    XCircle,
    CheckCircle,
    ArrowUpRight,
    ArrowDownRight,
    ChevronRight,
    TrendingUp,
} from 'lucide-react';

interface Article {
    id: number;
    name: string;
    unit: string;
    stock_quantity: number;
    price: number;
    stock_value?: number;
    low_stock_threshold: number;
    stock_status: string;
}

interface Movement {
    id: number;
    type: string;
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    notes: string | null;
    created_at: string;
    article: { id: number; name: string } | null;
    reference_type: string | null;
    reference_id: number | null;
    invoice_number?: string;
    invoice_id?: number;
}

interface MonthlyMovement {
    month: string;
    receipts: number;
    issues: number;
}

interface StockDistribution {
    in_stock: number;
    low_stock: number;
    out_of_stock: number;
}

interface Props {
    totalItems: number;
    totalStockValue: number;
    lowStockCount: number;
    outOfStockCount: number;
    stockDistribution: StockDistribution;
    topItemsByValue: Article[];
    lowStockItems: Article[];
    recentMovements: PaginatedData<Movement>;
    monthlyMovements: MonthlyMovement[];
}

const movementTypeVariants: Record<string, 'success' | 'destructive' | 'warning' | 'info'> = {
    receipt: 'success',
    issue: 'destructive',
    adjustment: 'warning',
    invoice_deduction: 'info',
};

const stockStatusVariants: Record<string, 'success' | 'warning' | 'destructive'> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'destructive',
};

export default function WarehouseDashboard({
    totalItems,
    totalStockValue,
    lowStockCount,
    outOfStockCount,
    stockDistribution,
    topItemsByValue,
    lowStockItems,
    recentMovements,
    monthlyMovements,
}: Props) {
    const { t } = useTranslation();

    const maxMovement = Math.max(...monthlyMovements.map((m) => Math.max(m.receipts, m.issues))) || 1;
    const inStockRate = totalItems > 0 ? Math.round((stockDistribution.in_stock / totalItems) * 100) : 0;

    return (
        <AppLayout>
            <Head title={t('inventory.dashboard_title')} />

            <div>
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.dashboard_title')}</h1>
                        <p className="text-sm text-gray-500 mt-1">{t('inventory.dashboard_subtitle')}</p>
                    </div>
                    <Button variant="secondary" asChild>
                        <Link href="/inventory" className="flex items-center gap-1.5">
                            {t('inventory.go_to_warehouse')}
                            <ChevronRight className="w-4 h-4" />
                        </Link>
                    </Button>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {/* Total Items */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">{t('inventory.dashboard_total_items')}</p>
                                    <p className="text-4xl font-bold mt-2">{totalItems}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <Package className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-blue-100 text-sm">
                                <span className="font-medium">{t('inventory.dashboard_tracked')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Stock Value */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">{t('inventory.dashboard_stock_value')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalStockValue, 0)}</p>
                                    <p className="text-emerald-200 text-sm">MKD</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <TrendingUp className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-emerald-100 text-sm">
                                <span className="font-medium">{t('inventory.dashboard_total_value')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Low Stock */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-amber-100 text-sm font-medium">{t('inventory.dashboard_low_stock')}</p>
                                    <p className="text-4xl font-bold mt-2">{lowStockCount}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <AlertTriangle className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-amber-100 text-sm">
                                <span className="font-medium">{t('inventory.dashboard_needs_restock')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Out of Stock */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-rose-100 text-sm font-medium">{t('inventory.dashboard_out_of_stock')}</p>
                                    <p className="text-4xl font-bold mt-2">{outOfStockCount}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <XCircle className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-rose-100 text-sm">
                                <span className="font-medium">{t('inventory.dashboard_unavailable')}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    {/* Monthly Movement Chart */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>{t('inventory.dashboard_monthly_movement')}</CardTitle>
                                    <CardDescription>{t('inventory.dashboard_receipts_issues')}</CardDescription>
                                </div>
                                <div className="flex items-center gap-4">
                                    <Badge variant="success" className="flex items-center gap-1.5">
                                        <span className="w-2 h-2 bg-emerald-500 rounded-full" />
                                        {t('inventory.dashboard_receipts')}
                                    </Badge>
                                    <Badge variant="destructive" className="flex items-center gap-1.5">
                                        <span className="w-2 h-2 bg-rose-500 rounded-full" />
                                        {t('inventory.dashboard_issues')}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-5">
                                {monthlyMovements.map((item, index) => {
                                    const recPercent = (item.receipts / maxMovement) * 100;
                                    const issPercent = (item.issues / maxMovement) * 100;
                                    return (
                                        <div key={index} className="group">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium text-gray-700">{item.month}</span>
                                                <div className="flex items-center gap-4 text-sm">
                                                    <span className="font-bold text-emerald-600">
                                                        +{formatNumber(item.receipts, 0)}
                                                    </span>
                                                    <span className="font-bold text-rose-500">
                                                        -{formatNumber(item.issues, 0)}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="space-y-1.5">
                                                <div className="overflow-hidden h-2.5 rounded-full bg-gray-100">
                                                    <div
                                                        className="h-2.5 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-500 ease-out"
                                                        style={{ width: `${recPercent}%` }}
                                                    />
                                                </div>
                                                <div className="overflow-hidden h-2.5 rounded-full bg-gray-100">
                                                    <div
                                                        className="h-2.5 rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-500 ease-out"
                                                        style={{ width: `${issPercent}%` }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stock Distribution */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('inventory.dashboard_stock_status')}</CardTitle>
                            <CardDescription>{t('inventory.dashboard_status_overview')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {/* In Stock */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                        <CheckCircle className="w-6 h-6 text-emerald-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('inventory.in_stock')}</p>
                                            <p className="text-lg font-bold text-emerald-600">{stockDistribution.in_stock}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-emerald-500"
                                                style={{ width: `${totalItems > 0 ? (stockDistribution.in_stock / totalItems) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Low Stock */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                        <AlertTriangle className="w-6 h-6 text-amber-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('inventory.low_stock')}</p>
                                            <p className="text-lg font-bold text-amber-600">{stockDistribution.low_stock}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-amber-500"
                                                style={{ width: `${totalItems > 0 ? (stockDistribution.low_stock / totalItems) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Out of Stock */}
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                                        <XCircle className="w-6 h-6 text-rose-600" />
                                    </div>
                                    <div className="ml-4 flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium text-gray-900">{t('inventory.out_of_stock')}</p>
                                            <p className="text-lg font-bold text-rose-600">{stockDistribution.out_of_stock}</p>
                                        </div>
                                        <div className="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                            <div
                                                className="h-2 rounded-full bg-rose-500"
                                                style={{ width: `${totalItems > 0 ? (stockDistribution.out_of_stock / totalItems) * 100 : 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Summary */}
                            <div className="mt-6 pt-6 border-t border-gray-100">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-gray-500">{t('inventory.dashboard_in_stock_rate')}</span>
                                    <span className="font-bold text-gray-900">{inStockRate}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Low Stock Alerts */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>{t('inventory.dashboard_alerts')}</CardTitle>
                                    <CardDescription>{t('inventory.dashboard_alerts_desc')}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {lowStockItems.length === 0 ? (
                                <div className="py-8 text-center">
                                    <CheckCircle className="mx-auto h-10 w-10 text-emerald-400" />
                                    <p className="mt-3 text-sm text-gray-500">{t('inventory.dashboard_all_stocked')}</p>
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.name')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.current_stock')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.low_stock_threshold')}</TableHead>
                                            <TableHead>{t('inventory.status')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lowStockItems.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <span className="font-medium text-gray-900">{item.name}</span>
                                                </TableCell>
                                                <TableCell className="text-right font-bold">
                                                    {formatNumber(item.stock_quantity)} {item.unit}
                                                </TableCell>
                                                <TableCell className="text-right text-gray-500">
                                                    {formatNumber(item.low_stock_threshold)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={stockStatusVariants[item.stock_status] || 'gray'}>
                                                        {t(`inventory.${item.stock_status}`)}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    {/* Top Items by Value */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>{t('inventory.dashboard_top_items')}</CardTitle>
                                    <CardDescription>{t('inventory.dashboard_top_items_desc')}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {topItemsByValue.length === 0 ? (
                                <div className="py-8 text-center">
                                    <Warehouse className="mx-auto h-10 w-10 text-gray-400" />
                                    <p className="mt-3 text-sm text-gray-500">{t('inventory.no_items')}</p>
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.name')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.stock_quantity')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.price')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.dashboard_value')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {topItemsByValue.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <span className="font-medium text-gray-900">{item.name}</span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatNumber(item.stock_quantity)} {item.unit}
                                                </TableCell>
                                                <TableCell className="text-right text-gray-500">
                                                    {formatNumber(item.price)}
                                                </TableCell>
                                                <TableCell className="text-right font-bold text-gray-900">
                                                    {formatNumber(item.stock_value ?? item.stock_quantity * item.price, 0)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Movements */}
                <Card className="mt-6">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>{t('inventory.dashboard_recent_movements')}</CardTitle>
                                <CardDescription>{t('inventory.dashboard_recent_movements_desc')}</CardDescription>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-500">{t('inventory.per_page')}:</span>
                                    <Select
                                        value={String(recentMovements.per_page)}
                                        onValueChange={(value) => {
                                            router.get('/warehouse', { per_page: value }, { preserveState: true, preserveScroll: true });
                                        }}
                                    >
                                        <SelectTrigger className="w-[75px] h-8">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="25">25</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                            <SelectItem value="100">100</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button variant="secondary" asChild>
                                    <Link href="/inventory" className="flex items-center gap-1.5">
                                        {t('dashboard.view_all')}
                                        <ChevronRight className="w-4 h-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {recentMovements.data.length === 0 ? (
                            <div className="py-8 text-center">
                                <Warehouse className="mx-auto h-10 w-10 text-gray-400" />
                                <p className="mt-3 text-sm text-gray-500">{t('inventory.no_movements')}</p>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.movement_date')}</TableHead>
                                            <TableHead>{t('inventory.movement_item')}</TableHead>
                                            <TableHead>{t('inventory.movement_type')}</TableHead>
                                            <TableHead>{t('inventory.dashboard_reference')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.movement_quantity')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.movement_after')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentMovements.data.map((movement) => (
                                            <TableRow key={movement.id}>
                                                <TableCell className="text-sm text-gray-900">
                                                    {formatDate(movement.created_at)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium text-gray-900">
                                                        {movement.article?.name || '-'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={movementTypeVariants[movement.type] || 'gray'}>
                                                        {t(`inventory.type_${movement.type}`)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {movement.invoice_number ? (
                                                        <Link
                                                            href={`/invoices/${movement.invoice_id}`}
                                                            className="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                        >
                                                            {movement.invoice_number}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-sm text-gray-400">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <span className={`font-bold ${Number(movement.quantity) >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                        {Number(movement.quantity) >= 0 ? '+' : ''}{formatNumber(movement.quantity)}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right text-gray-500">
                                                    {formatNumber(movement.quantity_after)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {recentMovements.last_page > 1 && (
                                    <div className="px-6 py-4 border-t">
                                        <Pagination
                                            links={recentMovements.links}
                                            from={recentMovements.from}
                                            to={recentMovements.to}
                                            total={recentMovements.total}
                                        />
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
