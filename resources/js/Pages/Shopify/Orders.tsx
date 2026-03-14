import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { ShoppingBag, Eye, Package } from 'lucide-react';
import type { PaginatedData } from '@/types';

interface ShopifyOrderData {
    id: number;
    shopify_order_id: number;
    order_number: string;
    customer_name: string | null;
    customer_email: string | null;
    financial_status: string;
    fulfillment_status: string | null;
    currency: string;
    subtotal_price: number;
    total_tax: number;
    total_price: number;
    ordered_at: string;
    items: { id: number }[];
}

interface Props {
    orders: PaginatedData<ShopifyOrderData>;
    filters: {
        from?: string;
        to?: string;
        status?: string;
    };
}

export default function Orders({ orders, filters }: Props) {
    const { t } = useTranslation();
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [status, setStatus] = useState(filters.status || '');

    const applyFilters = () => {
        router.get('/shopify/orders', {
            ...(from && { from }),
            ...(to && { to }),
            ...(status && { status }),
        }, { preserveState: true });
    };

    const statusBadge = (status: string) => {
        const colors: Record<string, string> = {
            paid: 'bg-green-100 text-green-700',
            refunded: 'bg-red-100 text-red-700',
            partially_refunded: 'bg-yellow-100 text-yellow-700',
            pending: 'bg-gray-100 text-gray-700',
        };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-700'}`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout>
            <Head title={t('shopify.orders_title')} />

            <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <ShoppingBag className="w-6 h-6" />
                            {t('shopify.orders_title')}
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">{t('shopify.orders_subtitle')}</p>
                    </div>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div>
                                <Label className="text-sm">{t('profitability.from')}</Label>
                                <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-[160px] mt-1" />
                            </div>
                            <div>
                                <Label className="text-sm">{t('profitability.to')}</Label>
                                <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-[160px] mt-1" />
                            </div>
                            <div>
                                <Label className="text-sm">{t('shopify.financial_status')}</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger className="w-[160px] mt-1">
                                        <SelectValue placeholder={t('shopify.all_statuses')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('shopify.all_statuses')}</SelectItem>
                                        <SelectItem value="paid">{t('shopify.status_paid')}</SelectItem>
                                        <SelectItem value="refunded">{t('shopify.status_refunded')}</SelectItem>
                                        <SelectItem value="partially_refunded">{t('shopify.status_partially_refunded')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button size="sm" onClick={applyFilters}>{t('profitability.apply')}</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Orders table */}
                {orders.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <Package className="w-12 h-12 text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900">{t('shopify.no_orders')}</h3>
                            <p className="text-sm text-gray-500 mt-1">{t('shopify.no_orders_description')}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('shopify.orders_list')} ({orders.total})</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('shopify.order_number')}</TableHead>
                                            <TableHead>{t('shopify.customer')}</TableHead>
                                            <TableHead>{t('shopify.date')}</TableHead>
                                            <TableHead>{t('shopify.status')}</TableHead>
                                            <TableHead>{t('shopify.items_count')}</TableHead>
                                            <TableHead className="text-right">{t('shopify.total')}</TableHead>
                                            <TableHead className="w-[60px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {orders.data.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell className="font-medium">{order.order_number}</TableCell>
                                                <TableCell className="text-gray-600">{order.customer_name || order.customer_email || '-'}</TableCell>
                                                <TableCell>{new Date(order.ordered_at).toLocaleDateString()}</TableCell>
                                                <TableCell>{statusBadge(order.financial_status)}</TableCell>
                                                <TableCell>{order.items.length}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(order.total_price, 2)} {order.currency}
                                                </TableCell>
                                                <TableCell>
                                                    <Button size="sm" variant="ghost" asChild>
                                                        <Link href={`/shopify/orders/${order.id}`}>
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {orders.last_page > 1 && (
                                <div className="flex items-center justify-center gap-1 p-4 border-t">
                                    {orders.links.map((link, i) => (
                                        <Button
                                            key={i}
                                            size="sm"
                                            variant={link.active ? 'default' : 'outline'}
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
