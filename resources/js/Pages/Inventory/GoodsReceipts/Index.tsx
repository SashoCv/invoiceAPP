import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
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
import { formatNumber, formatDate } from '@/lib/utils';
import type { PaginatedData } from '@/types';
import { Plus, Package, Eye, ArrowLeft } from 'lucide-react';

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
}

export default function GoodsReceiptsIndex({ receipts }: Props) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={t('inventory.goods_receipts')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div className="mb-2">
                            <Link
                                href="/inventory"
                                className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                            >
                                <ArrowLeft className="w-4 h-4 mr-1" />
                                {t('inventory.back_to_list')}
                            </Link>
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.goods_receipts')}</h1>
                    </div>
                    <Button asChild>
                        <Link href="/goods-receipts/create" className="flex items-center gap-1.5">
                            <Plus className="w-4 h-4" />
                            {t('inventory.new_goods_receipt')}
                        </Link>
                    </Button>
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
