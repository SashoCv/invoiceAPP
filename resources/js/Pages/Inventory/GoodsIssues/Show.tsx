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
import { Button } from '@/Components/ui/button';
import { ArrowLeft, PackageMinus, Pencil } from 'lucide-react';

interface Movement {
    id: number;
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    article: { id: number; name: string; unit: string } | null;
}

interface GoodsIssue {
    id: number;
    issue_number: string;
    date: string;
    notes: string | null;
    client_id: number | null;
    client: { id: number; name: string; company: string | null } | null;
    created_at: string;
}

interface Props {
    issue: GoodsIssue;
    movements: Movement[];
}

export default function GoodsIssueShow({ issue, movements }: Props) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={`${t('inventory.goods_issue')} ${issue.issue_number}`} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href="/goods-issues"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        {t('inventory.back_to_issues')}
                    </Link>
                </div>

                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-orange-100 rounded-lg">
                            <PackageMinus className="w-6 h-6 text-orange-600" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{issue.issue_number}</h1>
                            <p className="text-sm text-gray-500">{formatDate(issue.date)}</p>
                        </div>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <Link href={`/goods-issues/${issue.id}/edit`} className="gap-1.5">
                            <Pencil className="w-4 h-4" />
                            {t('inventory.edit_goods_issue')}
                        </Link>
                    </Button>
                </div>

                <div className={`grid grid-cols-1 gap-4 mb-6 ${issue.client ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500">{t('inventory.receipt_date')}</p>
                            <p className="text-lg font-bold text-gray-900">{formatDate(issue.date)}</p>
                        </CardContent>
                    </Card>
                    {issue.client && (
                        <Card>
                            <CardContent className="pt-6">
                                <p className="text-sm text-gray-500">{t('inventory.issue_client')}</p>
                                <p className="text-lg font-bold text-gray-900">{issue.client.company || issue.client.name}</p>
                            </CardContent>
                        </Card>
                    )}
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500">{t('inventory.receipt_items')}</p>
                            <p className="text-lg font-bold text-gray-900">{movements.length}</p>
                        </CardContent>
                    </Card>
                </div>

                {issue.notes && (
                    <Card className="mb-6">
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-500 mb-1">{t('inventory.receipt_notes')}</p>
                            <p className="text-gray-900">{issue.notes}</p>
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
                                            <span className="font-bold text-red-600">
                                                -{formatNumber(movement.quantity)}
                                            </span>
                                            {movement.article?.unit && (
                                                <span className="text-gray-500 ml-1">{movement.article.unit}</span>
                                            )}
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
