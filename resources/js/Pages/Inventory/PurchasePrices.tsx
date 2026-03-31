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
import { Download, Search, BarChart3 } from 'lucide-react';

interface ArticleData {
    id: number;
    name: string;
    sku: string | null;
    unit: string;
    purchased_qty: number;
    avg_purchase_price: number;
    total_purchase_cost: number;
    invoice_sold_qty: number;
    invoice_avg_price: number;
    invoice_revenue: number;
    shopify_sold_qty: number;
    shopify_avg_price: number;
    shopify_revenue: number;
    total_sold_qty: number;
    total_revenue: number;
    margin_percent: number;
}

interface Props {
    articles: ArticleData[];
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

    const totalPurchased = articles.reduce((sum, a) => sum + a.total_purchase_cost, 0);
    const totalRevenue = articles.reduce((sum, a) => sum + a.total_revenue, 0);

    return (
        <AppLayout>
            <Head title={t('inventory.pp_title')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('inventory.pp_title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('inventory.pp_subtitle')}</p>
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
                        <div className="relative overflow-hidden bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-red-100 text-sm font-medium">{t('inventory.pp_total_purchased')}</p>
                            <p className="text-3xl font-bold mt-1">{formatNumber(totalPurchased)}</p>
                        </div>
                        <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-emerald-100 text-sm font-medium">{t('inventory.pp_total_revenue')}</p>
                            <p className="text-3xl font-bold mt-1">{formatNumber(totalRevenue)}</p>
                        </div>
                        <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-5 text-white">
                            <div className="absolute top-0 right-0 -mt-3 -mr-3 w-20 h-20 bg-white/10 rounded-full" />
                            <p className="text-blue-100 text-sm font-medium">{t('inventory.pp_difference')}</p>
                            <p className="text-3xl font-bold mt-1">{formatNumber(totalRevenue - totalPurchased)}</p>
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
                                <BarChart3 className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-3 text-sm font-medium text-gray-900">{t('inventory.pp_no_data')}</h3>
                                <p className="mt-1 text-sm text-gray-500">{t('inventory.pp_no_data_desc')}</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead rowSpan={2} className="align-bottom border-r">{t('inventory.name')}</TableHead>
                                            <TableHead colSpan={3} className="text-center border-b border-r bg-red-50 text-red-700">{t('inventory.pp_purchases')}</TableHead>
                                            <TableHead colSpan={3} className="text-center border-b border-r bg-green-50 text-green-700">{t('inventory.pp_sales')}</TableHead>
                                            <TableHead rowSpan={2} className="text-right align-bottom">{t('inventory.margin')}</TableHead>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead className="text-right bg-red-50/50">{t('inventory.pp_qty')}</TableHead>
                                            <TableHead className="text-right bg-red-50/50">{t('inventory.pp_avg_price')}</TableHead>
                                            <TableHead className="text-right bg-red-50/50 border-r">{t('inventory.pp_total')}</TableHead>
                                            <TableHead className="text-right bg-green-50/50">{t('inventory.pp_qty')}</TableHead>
                                            <TableHead className="text-right bg-green-50/50">{t('inventory.pp_avg_price')}</TableHead>
                                            <TableHead className="text-right bg-green-50/50 border-r">{t('inventory.pp_total')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {articles.map((article) => {
                                            const soldDetail: string[] = [];
                                            if (article.invoice_sold_qty > 0) {
                                                soldDetail.push(`F: ${formatNumber(article.invoice_sold_qty, 0)}`);
                                            }
                                            if (article.shopify_sold_qty > 0) {
                                                soldDetail.push(`S: ${formatNumber(article.shopify_sold_qty, 0)}`);
                                            }

                                            // Weighted avg selling price
                                            const avgSellingPrice = article.total_sold_qty > 0
                                                ? article.total_revenue / article.total_sold_qty
                                                : 0;

                                            return (
                                                <TableRow key={article.id}>
                                                    <TableCell className="border-r">
                                                        <div className="font-medium text-gray-900">{article.name}</div>
                                                        {article.sku && (
                                                            <div className="text-xs text-gray-400">{article.sku}</div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">{article.purchased_qty > 0 ? formatNumber(article.purchased_qty, 0) : '-'}</TableCell>
                                                    <TableCell className="text-right">{article.purchased_qty > 0 ? formatNumber(article.avg_purchase_price) : '-'}</TableCell>
                                                    <TableCell className="text-right border-r font-medium">{article.purchased_qty > 0 ? formatNumber(article.total_purchase_cost) : '-'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {article.total_sold_qty > 0 ? (
                                                            <div>
                                                                <span>{formatNumber(article.total_sold_qty, 0)}</span>
                                                                {soldDetail.length > 0 && (
                                                                    <div className="text-xs text-gray-400">{soldDetail.join(' / ')}</div>
                                                                )}
                                                            </div>
                                                        ) : '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">{article.total_sold_qty > 0 ? formatNumber(avgSellingPrice) : '-'}</TableCell>
                                                    <TableCell className="text-right border-r font-medium">{article.total_sold_qty > 0 ? formatNumber(article.total_revenue) : '-'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {article.margin_percent !== 0 ? (
                                                            <span className={
                                                                article.margin_percent >= 30
                                                                    ? 'text-green-600 font-medium'
                                                                    : article.margin_percent >= 10
                                                                        ? 'text-yellow-600 font-medium'
                                                                        : 'text-red-600 font-medium'
                                                            }>
                                                                {formatNumber(article.margin_percent, 1)}%
                                                            </span>
                                                        ) : '-'}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
