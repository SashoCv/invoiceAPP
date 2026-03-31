import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
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
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { Download, Search, DollarSign } from 'lucide-react';

interface ArticlePriceData {
    id: number;
    name: string;
    sku: string | null;
    unit: string;
    tax_rate: number;
    total_quantity: number;
    avg_cost_price: number;
    last_cost_price: number;
    selling_price: number;
    margin_percent: number;
    total_cost_with_tax: number;
}

interface Props {
    articles: ArticlePriceData[];
    filters: {
        date_from: string;
        date_to: string;
    };
}

export default function PurchasePrices({ articles, filters }: Props) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    const applyFilters = () => {
        router.get('/purchase-prices', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true });
    };

    const hasFilters = filters.date_from || filters.date_to;

    const clearFilters = () => {
        setDateFrom('');
        setDateTo('');
        router.get('/purchase-prices', {}, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        const query = params.toString();
        window.location.href = `/purchase-prices/export${query ? '?' + query : ''}`;
    };

    const avgOverallMargin = articles.length > 0
        ? articles.reduce((sum, a) => sum + a.margin_percent, 0) / articles.length
        : 0;

    return (
        <AppLayout>
            <Head title={t('inventory.purchase_prices_title')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.purchase_prices_title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('inventory.purchase_prices_subtitle')}</p>
                    </div>
                    {articles.length > 0 && (
                        <Button variant="outline" onClick={handleExport} className="flex items-center gap-1.5">
                            <Download className="w-4 h-4" />
                            {t('inventory.export_csv')}
                        </Button>
                    )}
                </div>

                {/* Summary */}
                {articles.length > 0 && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-blue-100 text-sm font-medium">{t('inventory.total_articles')}</p>
                            <p className="text-3xl font-bold mt-1">{articles.length}</p>
                        </div>
                        <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-emerald-100 text-sm font-medium">{t('inventory.avg_margin')}</p>
                            <p className="text-3xl font-bold mt-1">{formatNumber(avgOverallMargin, 1)}%</p>
                        </div>
                        <div className="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-indigo-100 text-sm font-medium">{t('inventory.total_purchased_value')}</p>
                            <p className="text-3xl font-bold mt-1">
                                {formatNumber(articles.reduce((sum, a) => sum + a.total_cost_with_tax, 0))}
                            </p>
                        </div>
                    </div>
                )}

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
                        {articles.length === 0 ? (
                            <div className="py-12 text-center">
                                <DollarSign className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-3 text-sm font-medium text-gray-900">{t('inventory.no_purchase_data')}</h3>
                                <p className="mt-1 text-sm text-gray-500">{t('inventory.no_purchase_data_desc')}</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('inventory.name')}</TableHead>
                                        <TableHead className="hidden md:table-cell">{t('inventory.sku')}</TableHead>
                                        <TableHead className="hidden lg:table-cell">{t('inventory.unit')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.received_qty')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.avg_purchase_price')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.last_purchase_price')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.tax_rate')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.selling_price')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.margin')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {articles.map((article) => (
                                        <TableRow key={article.id}>
                                            <TableCell className="font-medium text-gray-900">{article.name}</TableCell>
                                            <TableCell className="hidden md:table-cell text-gray-500">{article.sku || '-'}</TableCell>
                                            <TableCell className="hidden lg:table-cell text-gray-500">{article.unit}</TableCell>
                                            <TableCell className="text-right">{formatNumber(article.total_quantity, 0)}</TableCell>
                                            <TableCell className="text-right font-medium">{formatNumber(article.avg_cost_price)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(article.last_cost_price)}</TableCell>
                                            <TableCell className="text-right text-gray-500">{formatNumber(article.tax_rate, 0)}%</TableCell>
                                            <TableCell className="text-right">{formatNumber(article.selling_price)}</TableCell>
                                            <TableCell className="text-right">
                                                <span className={
                                                    article.margin_percent >= 30
                                                        ? 'text-green-600 font-medium'
                                                        : article.margin_percent >= 10
                                                            ? 'text-yellow-600 font-medium'
                                                            : 'text-red-600 font-medium'
                                                }>
                                                    {formatNumber(article.margin_percent, 1)}%
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
