import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
import { ArrowLeft, Package } from 'lucide-react';

interface Movement {
    id: number;
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    cost_price: number | null;
    article: { id: number; name: string; unit: string } | null;
}

interface GoodsReceipt {
    id: number;
    receipt_number: string;
    date: string;
    notes: string | null;
    total_cost: number;
    created_at: string;
}

interface Props {
    receipt: GoodsReceipt;
    movements: Movement[];
}

export default function GoodsReceiptShow({ receipt, movements }: Props) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={`${t('inventory.goods_receipt')} ${receipt.receipt_number}`} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href="/goods-receipts"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        {t('inventory.back_to_receipts')}
                    </Link>
                </div>

                <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-blue-100 rounded-lg">
                        <Package className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{receipt.receipt_number}</h1>
                        <p className="text-sm text-gray-500">{formatDate(receipt.date)}</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500">{t('inventory.receipt_date')}</p>
                            <p className="text-lg font-bold text-gray-900">{formatDate(receipt.date)}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500">{t('inventory.receipt_items')}</p>
                            <p className="text-lg font-bold text-gray-900">{movements.length}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500">{t('inventory.total_cost')}</p>
                            <p className="text-lg font-bold text-emerald-600">{formatNumber(receipt.total_cost)} MKD</p>
                        </CardContent>
                    </Card>
                </div>

                {receipt.notes && (
                    <Card className="mb-6">
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500 mb-1">{t('inventory.receipt_notes')}</p>
                            <p className="text-gray-900">{receipt.notes}</p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>{t('inventory.receipt_items')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('inventory.name')}</TableHead>
                                    <TableHead className="text-right">{t('inventory.quantity')}</TableHead>
                                    <TableHead className="text-right">{t('inventory.cost_price')}</TableHead>
                                    <TableHead className="text-right">{t('inventory.line_total')}</TableHead>
                                    <TableHead className="text-right">{t('inventory.movement_before')}</TableHead>
                                    <TableHead className="text-right">{t('inventory.movement_after')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.map((movement) => (
                                    <TableRow key={movement.id}>
                                        <TableCell>
                                            <span className="font-medium text-gray-900">
                                                {movement.article?.name || '-'}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <span className="font-bold text-emerald-600">
                                                +{formatNumber(movement.quantity)}
                                            </span>
                                            {movement.article?.unit && (
                                                <span className="text-gray-500 ml-1">{movement.article.unit}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-gray-600">
                                            {movement.cost_price ? formatNumber(movement.cost_price) : '-'}
                                        </TableCell>
                                        <TableCell className="text-right font-bold text-gray-900">
                                            {movement.cost_price
                                                ? formatNumber(movement.quantity * movement.cost_price)
                                                : '-'}
                                        </TableCell>
                                        <TableCell className="text-right text-gray-500">
                                            {formatNumber(movement.quantity_before)}
                                        </TableCell>
                                        <TableCell className="text-right text-gray-500">
                                            {formatNumber(movement.quantity_after)}
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
