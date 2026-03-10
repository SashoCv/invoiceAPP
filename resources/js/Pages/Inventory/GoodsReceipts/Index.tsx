import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
import Pagination from '@/Components/Pagination';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import type { PaginatedData } from '@/types';
import { Plus, Package, Eye, Search, ClipboardList } from 'lucide-react';

interface GoodsReceipt {
    id: number;
    receipt_number: string;
    date: string;
    notes: string | null;
    total_cost: number;
    created_at: string;
}

interface Props {
    receipts: PaginatedData<GoodsReceipt>;
    totalCost: number;
    filters: {
        date_from: string;
        date_to: string;
    };
}

export default function GoodsReceiptsIndex({ receipts, totalCost, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const applyFilters = () => {
        router.get('/goods-receipts', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true });
    };

    const hasFilters = filters.date_from || filters.date_to;

    const clearFilters = () => {
        setDateFrom('');
        setDateTo('');
        router.get('/goods-receipts', {}, { preserveState: true });
    };

    return (
        <AppLayout>
            <Head title={t('inventory.goods_receipts')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.goods_receipts')}</h1>
                    </div>
                    <Button asChild>
                        <Link href="/goods-receipts/create" className="flex items-center gap-1.5">
                            <Plus className="w-4 h-4" />
                            {t('inventory.new_goods_receipt')}
                        </Link>
                    </Button>
                </div>

                {/* Summary Card */}
                <div className="mb-6">
                    <div className="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-indigo-100 text-sm font-medium">{t('inventory.total_cost')}</p>
                                <p className="text-3xl font-bold mt-1">{formatNumber(totalCost)} MKD</p>
                                {hasFilters && (
                                    <p className="text-indigo-200 text-xs mt-1">{t('inventory.date_from')}: {filters.date_from || '...'} — {t('inventory.date_to')}: {filters.date_to || '...'}</p>
                                )}
                            </div>
                            <div className="bg-white/20 rounded-xl p-3">
                                <ClipboardList className="h-8 w-8" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('inventory.date_from')}</Label>
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="w-40 h-9"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('inventory.date_to')}</Label>
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="w-40 h-9"
                            />
                        </div>
                        <Button size="sm" onClick={applyFilters} className="h-9">
                            <Search className="w-4 h-4 mr-1" />
                            {t('inventory.filter')}
                        </Button>
                        {hasFilters && (
                            <Button size="sm" variant="ghost" onClick={clearFilters} className="h-9 text-gray-500">
                                ✕
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {receipts.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-3 text-sm font-medium text-gray-900">{t('inventory.no_receipts')}</h3>
                                <p className="mt-1 text-sm text-gray-500">{t('inventory.no_receipts_desc')}</p>
                                <div className="mt-4">
                                    <Button asChild>
                                        <Link href="/goods-receipts/create">
                                            <Plus className="w-4 h-4 mr-1" />
                                            {t('inventory.new_goods_receipt')}
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.receipt_number')}</TableHead>
                                            <TableHead>{t('inventory.receipt_date')}</TableHead>
                                            <TableHead>{t('inventory.receipt_notes')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.total_cost')}</TableHead>
                                            <TableHead className="w-[80px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {receipts.data.map((receipt) => (
                                            <TableRow key={receipt.id}>
                                                <TableCell>
                                                    <span className="font-medium text-gray-900">
                                                        {receipt.receipt_number}
                                                    </span>
                                                </TableCell>
                                                <TableCell>{formatDate(receipt.date)}</TableCell>
                                                <TableCell className="text-gray-500 max-w-[200px] truncate">
                                                    {receipt.notes || '-'}
                                                </TableCell>
                                                <TableCell className="text-right font-bold text-gray-900">
                                                    {formatNumber(receipt.total_cost)} MKD
                                                </TableCell>
                                                <TableCell>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/goods-receipts/${receipt.id}`}>
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {receipts.last_page > 1 && (
                                    <div className="px-6 py-4 border-t">
                                        <Pagination
                                            links={receipts.links}
                                            from={receipts.from}
                                            to={receipts.to}
                                            total={receipts.total}
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
