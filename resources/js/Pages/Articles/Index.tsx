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
import { formatNumber } from '@/lib/utils';
import { Plus, Pencil, Trash2, Package } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import EmptyState from '@/Components/EmptyState';
import SortableTableHead from '@/Components/SortableTableHead';
import type { Article, PaginatedData } from '@/types';

interface ArticlesIndexProps {
    articles: PaginatedData<Article>;
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
        sort?: string;
        dir?: 'asc' | 'desc';
    };
}

export default function ArticlesIndex({ articles, filters }: ArticlesIndexProps) {
    const { isActive } = useSubscription();
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '__all__');
    const [deleteArticle, setDeleteArticle] = useState<Article | null>(null);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (search) params.search = search;
        if (statusFilter && statusFilter !== '__all__') params.status = statusFilter;
        router.get('/articles', params, { preserveState: true });
    };

    const clearFilters = () => {
        setSearch('');
        setStatusFilter('__all__');
        router.get('/articles', {});
    };

    const hasFilters = filters.search || filters.status;

    return (
        <AppLayout>
            <Head title={t('articles.title')} />

            <div>
                {/* Page Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('articles.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('articles.subtitle')}</p>
                    </div>
                    <Button asChild={isActive} disabled={!isActive}>
                        {isActive ? (
                            <Link href="/articles/create" className="flex items-center gap-2">
                                <Plus className="w-4 h-4" />
                                {t('articles.new_article')}
                            </Link>
                        ) : (
                            <span className="flex items-center gap-2">
                                <Plus className="w-4 h-4" />
                                {t('articles.new_article')}
                            </span>
                        )}
                    </Button>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <form onSubmit={handleFilter}>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('articles.search')}
                                    </label>
                                    <Input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder={t('articles.search_placeholder')}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        {t('articles.status')}
                                    </label>
                                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('articles.all_statuses')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">{t('articles.all_statuses')}</SelectItem>
                                            <SelectItem value="active">{t('articles.status_active')}</SelectItem>
                                            <SelectItem value="inactive">{t('articles.status_inactive')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button type="submit">{t('articles.filter')}</Button>
                                    {hasFilters && (
                                        <Button type="button" variant="outline" onClick={clearFilters}>
                                            {t('articles.clear_filters')}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Articles List */}
                <Card>
                    {articles.data.length === 0 ? (
                        <CardContent className="p-0">
                            <EmptyState
                                icon={Package}
                                title={t('articles.no_articles')}
                                description={t('articles.create_first_description')}
                                action={{
                                    label: t('articles.new_article'),
                                    href: '/articles/create',
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
                                            baseUrl="/articles"
                                        >
                                            {t('articles.name')}
                                        </SortableTableHead>
                                        <TableHead className="hidden md:table-cell">{t('articles.description')}</TableHead>
                                        <TableHead>{t('articles.unit')}</TableHead>
                                        <SortableTableHead
                                            column="price"
                                            currentSort={filters.sort}
                                            currentDirection={filters.dir}
                                            baseUrl="/articles"
                                            className="text-right"
                                        >
                                            {t('articles.price')}
                                        </SortableTableHead>
                                        <TableHead className="text-right">{t('articles.tax_rate')}</TableHead>
                                        <TableHead className="text-center">{t('articles.status')}</TableHead>
                                        <TableHead className="text-right">{t('articles.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {articles.data.map((article) => (
                                        <TableRow key={article.id}>
                                            <TableCell>
                                                <span className="font-medium text-gray-900">{article.name}</span>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="text-sm text-gray-600 truncate max-w-xs block">
                                                    {article.description || '-'}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-sm text-gray-600">{article.unit}</span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className="text-sm font-medium text-gray-900">
                                                    {formatNumber(article.price, 2)}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className="text-sm text-gray-600">{article.tax_rate}%</span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={article.is_active ? 'success' : 'gray'}>
                                                    {article.is_active ? t('articles.status_active') : t('articles.status_inactive')}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <ActionDropdown
                                                    actions={[
                                                        {
                                                            label: t('articles.edit'),
                                                            icon: Pencil,
                                                            href: `/articles/${article.id}/edit`,
                                                            disabled: !isActive,
                                                        },
                                                        {
                                                            label: t('articles.delete'),
                                                            icon: Trash2,
                                                            onClick: () => setDeleteArticle(article),
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

                            {articles.last_page > 1 && (
                                <div className="px-6 py-4 border-t">
                                    <Pagination
                                        links={articles.links}
                                        from={articles.from}
                                        to={articles.to}
                                        total={articles.total}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteArticle}
                onOpenChange={() => setDeleteArticle(null)}
                title={t('articles.delete_article')}
                description={t('articles.delete_confirm')}
                deleteUrl={deleteArticle ? `/articles/${deleteArticle.id}` : ''}
            />
        </AppLayout>
    );
}
