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
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { ArrowLeft, ShoppingBag } from 'lucide-react';

interface OrderItem {
    id: number;
    title: string;
    quantity: number;
    price: number;
    total_discount: number;
    shopify_variant_id: number;
    article?: { id: number; name: string; sku: string | null; unit: string } | null;
}

interface Order {
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
    items: OrderItem[];
}

interface Props {
    order: Order;
}

export default function OrderShow({ order }: Props) {
    const { t } = useTranslation();

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
            <Head title={`${t('shopify.order')} ${order.order_number}`} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/shopify/orders" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('shopify.back_to_orders')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <ShoppingBag className="w-6 h-6" />
                        {t('shopify.order')} {order.order_number}
                    </h1>
                </div>

                {/* Order Info */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>{t('shopify.order_details')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p className="text-sm text-gray-500">{t('shopify.order_number')}</p>
                                <p className="font-medium">{order.order_number}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{t('shopify.customer')}</p>
                                <p className="font-medium">{order.customer_name || '-'}</p>
                                {order.customer_email && (
                                    <p className="text-xs text-gray-500">{order.customer_email}</p>
                                )}
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{t('shopify.date')}</p>
                                <p className="font-medium">{new Date(order.ordered_at).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{t('shopify.financial_status')}</p>
                                <div className="mt-1">{statusBadge(order.financial_status)}</div>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{t('shopify.fulfillment_status')}</p>
                                <p className="font-medium">{order.fulfillment_status || '-'}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Line Items */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>{t('shopify.line_items')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('shopify.product')}</TableHead>
                                    <TableHead>{t('shopify.local_article')}</TableHead>
                                    <TableHead className="text-right">{t('shopify.qty')}</TableHead>
                                    <TableHead className="text-right">{t('shopify.price')}</TableHead>
                                    <TableHead className="text-right">{t('shopify.discount')}</TableHead>
                                    <TableHead className="text-right">{t('shopify.line_total')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {order.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">{item.title}</TableCell>
                                        <TableCell>
                                            {item.article ? (
                                                <span className="text-blue-600">{item.article.name}</span>
                                            ) : (
                                                <span className="text-gray-400">{t('shopify.unmapped')}</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">{item.quantity}</TableCell>
                                        <TableCell className="text-right">{formatNumber(item.price, 2)}</TableCell>
                                        <TableCell className="text-right">
                                            {item.total_discount > 0 ? formatNumber(item.total_discount, 2) : '-'}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatNumber(item.price * item.quantity - item.total_discount, 2)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Totals */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="space-y-2 max-w-xs ml-auto">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('shopify.subtotal')}</span>
                                <span>{formatNumber(order.subtotal_price, 2)} {order.currency}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('shopify.tax')}</span>
                                <span>{formatNumber(order.total_tax, 2)} {order.currency}</span>
                            </div>
                            <div className="flex justify-between font-medium text-lg border-t pt-2">
                                <span>{t('shopify.total')}</span>
                                <span>{formatNumber(order.total_price, 2)} {order.currency}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
