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
import { formatDate } from '@/lib/utils';
import type { PaginatedData } from '@/types';
import { Plus, PackageMinus, Eye, Search } from 'lucide-react';

interface GoodsIssue {
    id: number;
    issue_number: string;
    date: string;
    notes: string | null;
    movements_count: number;
    client: { id: number; name: string; company: string | null } | null;
    created_at: string;
}

interface Props {
    issues: PaginatedData<GoodsIssue>;
    filters: {
        date_from: string;
        date_to: string;
    };
}

export default function GoodsIssuesIndex({ issues, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const applyFilters = () => {
        router.get('/goods-issues', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true });
    };

    const hasFilters = filters.date_from || filters.date_to;

    const clearFilters = () => {
        setDateFrom('');
        setDateTo('');
        router.get('/goods-issues', {}, { preserveState: true });
    };

    return (
        <AppLayout>
            <Head title={t('inventory.goods_issues')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.goods_issues')}</h1>
                    </div>
                    <Button asChild>
                        <Link href="/goods-issues/create" className="flex items-center gap-1.5">
                            <Plus className="w-4 h-4" />
                            {t('inventory.new_goods_issue')}
                        </Link>
                    </Button>
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
                        {issues.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <PackageMinus className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-3 text-sm font-medium text-gray-900">{t('inventory.no_issues')}</h3>
                                <p className="mt-1 text-sm text-gray-500">{t('inventory.no_issues_desc')}</p>
                                <div className="mt-4">
                                    <Button asChild>
                                        <Link href="/goods-issues/create">
                                            <Plus className="w-4 h-4 mr-1" />
                                            {t('inventory.new_goods_issue')}
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('inventory.issue_number')}</TableHead>
                                            <TableHead>{t('inventory.receipt_date')}</TableHead>
                                            <TableHead>{t('inventory.issue_client')}</TableHead>
                                            <TableHead>{t('inventory.receipt_notes')}</TableHead>
                                            <TableHead className="text-right">{t('inventory.receipt_items')}</TableHead>
                                            <TableHead className="w-[80px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {issues.data.map((issue) => (
                                            <TableRow key={issue.id}>
                                                <TableCell>
                                                    <span className="font-medium text-gray-900">
                                                        {issue.issue_number}
                                                    </span>
                                                </TableCell>
                                                <TableCell>{formatDate(issue.date)}</TableCell>
                                                <TableCell className="text-gray-500">
                                                    {issue.client ? (issue.client.company || issue.client.name) : '-'}
                                                </TableCell>
                                                <TableCell className="text-gray-500 max-w-[200px] truncate">
                                                    {issue.notes || '-'}
                                                </TableCell>
                                                <TableCell className="text-right text-gray-900">
                                                    {issue.movements_count}
                                                </TableCell>
                                                <TableCell>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/goods-issues/${issue.id}`}>
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {issues.last_page > 1 && (
                                    <div className="px-6 py-4 border-t">
                                        <Pagination
                                            links={issues.links}
                                            from={issues.from}
                                            to={issues.to}
                                            total={issues.total}
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
