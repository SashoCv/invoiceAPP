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
    ArrowUp,
    ArrowDown,
    TrendingUp,
    DollarSign,
    ShoppingCart,
    Percent,
    ArrowUpDown,
    Package,
} from 'lucide-react';

interface ArticleData {
    id: number;
    name: string;
    unit: string;
    selling_price: number;
    avg_cost: number | null;
    theoretical_margin: number | null;
    qty_sold: number;
    revenue: number;
    qty_purchased: number;
    cost: number;
    profit: number;
    actual_margin: number | null;
}

interface ProfitabilityProps {
    articles: ArticleData[];
    totalRevenue: number;
    totalCost: number;
    totalProfit: number;
    overallMargin: number | null;
    displayCurrency: string;
    from: string;
    to: string;
}

type SortKey = 'name' | 'selling_price' | 'avg_cost' | 'theoretical_margin' | 'qty_sold' | 'revenue' | 'qty_purchased' | 'cost' | 'profit' | 'actual_margin';

export default function Index({
    articles,
    totalRevenue,
    totalCost,
    totalProfit,
    overallMargin,
    displayCurrency,
    from,
    to,
}: ProfitabilityProps) {
    const { t } = useTranslation();
    const [fromDate, setFromDate] = useState(from);
    const [toDate, setToDate] = useState(to);
    const [sortKey, setSortKey] = useState<SortKey>('revenue');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');

    const applyFilter = () => {
        router.get('/profitability', { from: fromDate, to: toDate }, { preserveState: true });
    };

    const handleSort = (key: SortKey) => {
        if (sortKey === key) {
            setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setSortKey(key);
            setSortDir('desc');
        }
    };

    const sortedArticles = useMemo(() => {
        return [...articles].sort((a, b) => {
            const aVal = a[sortKey] ?? -Infinity;
            const bVal = b[sortKey] ?? -Infinity;
            if (typeof aVal === 'string' && typeof bVal === 'string') {
                return sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
            return sortDir === 'asc' ? (aVal as number) - (bVal as number) : (bVal as number) - (aVal as number);
        });
    }, [articles, sortKey, sortDir]);

    const SortableHeader = ({ label, field }: { label: string; field: SortKey }) => (
        <TableHead
            className="cursor-pointer select-none hover:bg-gray-50"
            onClick={() => handleSort(field)}
        >
            <div className="flex items-center gap-1">
                {label}
                <ArrowUpDown className="w-3 h-3 text-gray-400" />
            </div>
        </TableHead>
    );

    const MarginBadge = ({ value }: { value: number | null }) => {
        if (value === null) return <span className="text-gray-400">-</span>;
        return (
            <span className={`text-sm font-medium ${value >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                {value}%
            </span>
        );
    };

    return (
        <AppLayout>
            <Head title={t('profitability.title')} />

            <div>
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('profitability.title')}</h1>
                        <p className="text-sm text-gray-500 mt-1">{t('profitability.subtitle')}</p>
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
                                    <p className="text-emerald-100 text-sm font-medium">{t('profitability.total_revenue')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalRevenue, 0)}</p>
                                    <p className="text-emerald-200 text-sm">{displayCurrency}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <DollarSign className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-emerald-100 text-sm">
                                <span className="font-medium">{t('profitability.from_paid_invoices')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Total Cost */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-rose-100 text-sm font-medium">{t('profitability.total_cost')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalCost, 0)}</p>
                                    <p className="text-rose-200 text-sm">{displayCurrency}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <ShoppingCart className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-rose-100 text-sm">
                                <span className="font-medium">{t('profitability.from_goods_receipts')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Total Profit */}
                    <div className={`relative overflow-hidden rounded-2xl shadow-lg p-6 text-white ${totalProfit >= 0 ? 'bg-gradient-to-br from-violet-500 to-purple-600' : 'bg-gradient-to-br from-amber-500 to-orange-500'}`}>
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className={`text-sm font-medium ${totalProfit >= 0 ? 'text-violet-100' : 'text-amber-100'}`}>{t('profitability.total_profit')}</p>
                                    <p className="text-3xl font-bold mt-2">{formatNumber(totalProfit, 0)}</p>
                                    <p className={`text-sm ${totalProfit >= 0 ? 'text-violet-200' : 'text-amber-200'}`}>{displayCurrency}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    {totalProfit >= 0 ? <ArrowUp className="h-8 w-8" /> : <ArrowDown className="h-8 w-8" />}
                                </div>
                            </div>
                            <div className={`mt-4 flex items-center text-sm ${totalProfit >= 0 ? 'text-violet-100' : 'text-amber-100'}`}>
                                <span className="font-medium">{t('profitability.revenue_minus_cost')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Overall Margin */}
                    <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full" />
                        <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full" />
                        <div className="relative">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">{t('profitability.overall_margin')}</p>
                                    <p className="text-4xl font-bold mt-2">{overallMargin !== null ? `${overallMargin}%` : '-'}</p>
                                </div>
                                <div className="bg-white/20 rounded-xl p-3">
                                    <Percent className="h-8 w-8" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center text-blue-100 text-sm">
                                <TrendingUp className="w-4 h-4 mr-1" />
                                <span className="font-medium">{t('profitability.actual')} </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Articles Table */}
                {articles.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <Package className="w-12 h-12 text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900">{t('profitability.no_data')}</h3>
                            <p className="text-sm text-gray-500 mt-1">{t('profitability.no_data_description')}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('profitability.title')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <SortableHeader label={t('profitability.article')} field="name" />
                                            <TableHead>{t('profitability.unit')}</TableHead>
                                            <TableHead colSpan={3} className="text-center border-l bg-gray-50/50">
                                                {t('profitability.theoretical')}
                                            </TableHead>
                                            <TableHead colSpan={5} className="text-center border-l bg-blue-50/50">
                                                {t('profitability.actual')}
                                            </TableHead>
                                        </TableRow>
                                        <TableRow>
                                            <TableHead />
                                            <TableHead />
                                            <SortableHeader label={t('profitability.selling_price')} field="selling_price" />
                                            <SortableHeader label={t('profitability.avg_cost')} field="avg_cost" />
                                            <SortableHeader label={t('profitability.theoretical_margin')} field="theoretical_margin" />
                                            <SortableHeader label={t('profitability.qty_sold')} field="qty_sold" />
                                            <SortableHeader label={t('profitability.revenue')} field="revenue" />
                                            <SortableHeader label={t('profitability.cost')} field="cost" />
                                            <SortableHeader label={t('profitability.profit')} field="profit" />
                                            <SortableHeader label={t('profitability.actual_margin')} field="actual_margin" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {sortedArticles.map((article) => (
                                            <TableRow key={article.id}>
                                                <TableCell className="font-medium">{article.name}</TableCell>
                                                <TableCell className="text-gray-500">{article.unit}</TableCell>
                                                <TableCell className="border-l">{formatNumber(article.selling_price, 0)}</TableCell>
                                                <TableCell>{article.avg_cost !== null ? formatNumber(article.avg_cost, 0) : '-'}</TableCell>
                                                <TableCell><MarginBadge value={article.theoretical_margin} /></TableCell>
                                                <TableCell className="border-l">{article.qty_sold > 0 ? formatNumber(article.qty_sold, 0) : '-'}</TableCell>
                                                <TableCell>{article.revenue > 0 ? formatNumber(article.revenue, 0) : '-'}</TableCell>
                                                <TableCell>{article.cost > 0 ? formatNumber(article.cost, 0) : '-'}</TableCell>
                                                <TableCell className={article.profit >= 0 ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium'}>
                                                    {article.revenue > 0 || article.cost > 0 ? formatNumber(article.profit, 0) : '-'}
                                                </TableCell>
                                                <TableCell><MarginBadge value={article.actual_margin} /></TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
