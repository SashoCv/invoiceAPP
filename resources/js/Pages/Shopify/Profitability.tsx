import { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
import {
    DollarSign,
    ShoppingCart,
    ArrowUpDown,
    Package,
    ShoppingBag,
    Users,
    Hash,
} from 'lucide-react';

interface ArticleData {
    name: string;
    mapped: boolean;
    qty_sold: number;
    revenue: number;
}

interface CustomerData {
    name: string;
    email: string | null;
    orders: number;
    total: number;
    items: number;
}

interface Props {
    articles: ArticleData[];
    customers: CustomerData[];
    totalRevenue: number;
    totalOrders: number;
    totalItems: number;
    avgOrderValue: number;
    displayCurrency: string;
    from: string;
    to: string;
}

type ArticleSortKey = 'name' | 'qty_sold' | 'revenue';
type CustomerSortKey = 'name' | 'orders' | 'total' | 'items';

export default function Profitability({
    articles,
    customers,
    totalRevenue,
    totalOrders,
    totalItems,
    avgOrderValue,
    displayCurrency,
    from,
    to,
}: Props) {
    const { t } = useTranslation();
    const [fromDate, setFromDate] = useState(from);
    const [toDate, setToDate] = useState(to);
    const [articleSortKey, setArticleSortKey] = useState<ArticleSortKey>('revenue');
    const [articleSortDir, setArticleSortDir] = useState<'asc' | 'desc'>('desc');
    const [customerSortKey, setCustomerSortKey] = useState<CustomerSortKey>('total');
    const [customerSortDir, setCustomerSortDir] = useState<'asc' | 'desc'>('desc');

    const applyFilter = () => {
        router.get('/shopify/profitability', { from: fromDate, to: toDate }, { preserveState: true });
    };

    const handleArticleSort = (key: ArticleSortKey) => {
        if (articleSortKey === key) {
            setArticleSortDir(articleSortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setArticleSortKey(key);
            setArticleSortDir('desc');
        }
    };

    const handleCustomerSort = (key: CustomerSortKey) => {
        if (customerSortKey === key) {
            setCustomerSortDir(customerSortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setCustomerSortKey(key);
            setCustomerSortDir('desc');
        }
    };

    const sortedArticles = useMemo(() => {
        return [...articles].sort((a, b) => {
            const aVal = a[articleSortKey] ?? -Infinity;
            const bVal = b[articleSortKey] ?? -Infinity;
            if (typeof aVal === 'string' && typeof bVal === 'string') {
                return articleSortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
            return articleSortDir === 'asc' ? (aVal as number) - (bVal as number) : (bVal as number) - (aVal as number);
        });
    }, [articles, articleSortKey, articleSortDir]);

    const sortedCustomers = useMemo(() => {
        return [...customers].sort((a, b) => {
            const aVal = a[customerSortKey] ?? -Infinity;
            const bVal = b[customerSortKey] ?? -Infinity;
            if (typeof aVal === 'string' && typeof bVal === 'string') {
                return customerSortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
            return customerSortDir === 'asc' ? (aVal as number) - (bVal as number) : (bVal as number) - (aVal as number);
        });
    }, [customers, customerSortKey, customerSortDir]);

    const SortableArticleHeader = ({ label, field }: { label: string; field: ArticleSortKey }) => (
        <TableHead className="cursor-pointer select-none hover:bg-gray-50" onClick={() => handleArticleSort(field)}>
            <div className="flex items-center gap-1">
                {label}
                <ArrowUpDown className="w-3 h-3 text-gray-400" />
            </div>
        </TableHead>
    );

    const SortableCustomerHeader = ({ label, field }: { label: string; field: CustomerSortKey }) => (
        <TableHead className="cursor-pointer select-none hover:bg-gray-50" onClick={() => handleCustomerSort(field)}>
            <div className="flex items-center gap-1">
                {label}
                <ArrowUpDown className="w-3 h-3 text-gray-400" />
            </div>
        </TableHead>
    );

    return (
        <AppLayout>
            <Head title={t('shopify.profitability_title')} />

            <div>
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <ShoppingBag className="w-6 h-6" />
                            {t('shopify.profitability_title')}
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">{t('shopify.profitability_subtitle')}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="flex items-center gap-2">
                            <Label className="text-sm text-gray-500 whitespace-nowrap">{t('profitability.from')}</Label>
                            <Input
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                className="w-[150px] h-9"
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Label className="text-sm text-gray-500 whitespace-nowrap">{t('profitability.to')}</Label>
                            <Input
                                type="date"
                                value={toDate}
                                onChange={(e) => setToDate(e.target.value)}
                                className="w-[150px] h-9"
                            />
                        </div>
                        <Button size="sm" onClick={applyFilter}>
                            {t('profitability.apply')}
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {/* Total Revenue */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">{t('shopify.total_sales')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalRevenue, 0)}</p>
                                    <p className="text-emerald-200 text-sm">{displayCurrency}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <DollarSign className="h-8 w-8" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Total Orders */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">{t('shopify.total_orders_count')}</p>
                                    <p className="text-3xl font-bold mt-2">{totalOrders}</p>
                                    <p className="text-blue-200 text-sm">{t('shopify.unique_customers')}: {customers.length}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <ShoppingCart className="h-8 w-8" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Total Items Sold */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-violet-100 text-sm font-medium">{t('shopify.total_items_sold')}</p>
                                    <p className="text-3xl font-bold mt-2">{totalItems}</p>
                                    <p className="text-violet-200 text-sm">{t('shopify.products')}: {articles.length}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <Hash className="h-8 w-8" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Avg Order Value */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-amber-100 text-sm font-medium">{t('shopify.avg_order_value')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(avgOrderValue, 0)}</p>
                                    <p className="text-amber-200 text-sm">{displayCurrency}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <Users className="h-8 w-8" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {totalOrders === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <Package className="w-12 h-12 text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900">{t('shopify.no_sales_data')}</h3>
                            <p className="text-sm text-gray-500 mt-1">{t('shopify.no_sales_data_description')}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Sales by Article */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('shopify.sales_by_article')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <SortableArticleHeader label={t('shopify.product')} field="name" />
                                                <SortableArticleHeader label={t('shopify.qty')} field="qty_sold" />
                                                <SortableArticleHeader label={t('shopify.revenue')} field="revenue" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {sortedArticles.map((article, i) => (
                                                <TableRow key={i}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium">{article.name}</span>
                                                            {!article.mapped && (
                                                                <span className="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">
                                                                    {t('shopify.unmapped')}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>{formatNumber(article.qty_sold, 0)}</TableCell>
                                                    <TableCell className="font-medium">{formatNumber(article.revenue, 0)}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Stats by Customer */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('shopify.stats_by_customer')}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <SortableCustomerHeader label={t('shopify.customer')} field="name" />
                                                <SortableCustomerHeader label={t('shopify.orders_count')} field="orders" />
                                                <SortableCustomerHeader label={t('shopify.items_count')} field="items" />
                                                <SortableCustomerHeader label={t('shopify.total')} field="total" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {sortedCustomers.map((customer, i) => (
                                                <TableRow key={i}>
                                                    <TableCell>
                                                        <div>
                                                            <p className="font-medium">{customer.name}</p>
                                                            {customer.email && customer.email !== customer.name && (
                                                                <p className="text-xs text-gray-500">{customer.email}</p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>{customer.orders}</TableCell>
                                                    <TableCell>{customer.items}</TableCell>
                                                    <TableCell className="font-medium">{formatNumber(customer.total, 0)}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
