import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from '@/Components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { ArrowLeft, Pencil, Copy, FileText, Printer, ArrowRightLeft } from 'lucide-react';
import type { ProformaInvoice } from '@/types';

interface ShowProformaProps {
    proforma: ProformaInvoice;
}

const statusVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    draft: 'gray',
    sent: 'info',
    converted_to_invoice: 'success',
};

export default function ShowProforma({ proforma }: ShowProformaProps) {
    const { t } = useTranslation();

    const handleConvertToInvoice = () => {
        router.post(`/proforma-invoices/${proforma.id}/convert`);
    };

    return (
        <AppLayout>
            <Head title={`${t('proforma.proforma')} ${proforma.proforma_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/proforma-invoices" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('proforma.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <FileText className="w-6 h-6 text-purple-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{proforma.proforma_number}</h1>
                                <p className="text-sm text-gray-500">{proforma.client?.name}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Badge variant={statusVariants[proforma.status] || 'gray'} className="text-sm px-3 py-1">
                                {t(`proforma.status_${proforma.status}`)}
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="outline" asChild>
                        <Link href={`/proforma-invoices/${proforma.id}/edit`} className="flex items-center gap-2">
                            <Pencil className="w-4 h-4" />
                            {t('proforma.edit')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/proforma-invoices/${proforma.id}/duplicate`} className="flex items-center gap-2">
                            <Copy className="w-4 h-4" />
                            {t('proforma.duplicate')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/proforma-invoices/${proforma.id}/pdf`} target="_blank" className="flex items-center gap-2">
                            <Printer className="w-4 h-4" />
                            {t('proforma.print_pdf')}
                        </a>
                    </Button>
                    {proforma.status !== 'converted_to_invoice' && (
                        <Button onClick={handleConvertToInvoice} className="flex items-center gap-2">
                            <ArrowRightLeft className="w-4 h-4" />
                            {t('proforma.convert_to_invoice')}
                        </Button>
                    )}
                </div>

                {/* Converted Invoice Link */}
                {proforma.convertedInvoice && (
                    <Card className="mb-6 bg-green-50 border-green-200">
                        <CardContent className="py-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <ArrowRightLeft className="w-5 h-5 text-green-600" />
                                    <span className="text-sm text-green-800">
                                        {t('proforma.converted_to')}:
                                    </span>
                                    <Link
                                        href={`/invoices/${proforma.convertedInvoice.id}`}
                                        className="text-sm font-medium text-green-700 hover:text-green-900 underline"
                                    >
                                        {proforma.convertedInvoice.invoice_number}
                                    </Link>
                                </div>
                                {proforma.converted_at && (
                                    <span className="text-xs text-green-600">
                                        {formatDate(proforma.converted_at)}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Proforma Details */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('proforma.proforma_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('proforma.proforma_number')}:</span>
                                <span className="font-medium">{proforma.proforma_number}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('proforma.issue_date')}:</span>
                                <span className="font-medium">{formatDate(proforma.issue_date)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('proforma.valid_until')}:</span>
                                <span className="font-medium">{formatDate(proforma.valid_until)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('proforma.currency')}:</span>
                                <span className="font-medium">{proforma.currency}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('proforma.client_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="text-sm">
                                <span className="font-medium">{proforma.client?.name}</span>
                            </div>
                            {proforma.client?.address && (
                                <div className="text-sm text-gray-600">
                                    {proforma.client.address}
                                    {proforma.client.city && `, ${proforma.client.city}`}
                                </div>
                            )}
                            {proforma.client?.email && (
                                <div className="text-sm text-gray-600">{proforma.client.email}</div>
                            )}
                            {proforma.client?.tax_number && (
                                <div className="text-sm text-gray-600">
                                    {t('proforma.tax_number')}: {proforma.client.tax_number}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Items */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base">{t('proforma.items')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/2">{t('proforma.description')}</TableHead>
                                    <TableHead className="text-right">{t('proforma.quantity')}</TableHead>
                                    <TableHead className="text-right">{t('proforma.unit_price')}</TableHead>
                                    <TableHead className="text-right">{t('proforma.tax_rate')}</TableHead>
                                    <TableHead className="text-right">{t('proforma.total')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {proforma.items?.map((item, index) => {
                                    const itemSubtotal = item.quantity * item.unit_price;
                                    const itemTax = itemSubtotal * (item.tax_rate / 100);
                                    const itemTotal = itemSubtotal + itemTax;
                                    return (
                                        <TableRow key={index}>
                                            <TableCell>{item.description}</TableCell>
                                            <TableCell className="text-right">{formatNumber(item.quantity, 2)}</TableCell>
                                            <TableCell className="text-right">{formatNumber(item.unit_price, 2)}</TableCell>
                                            <TableCell className="text-right">{item.tax_rate}%</TableCell>
                                            <TableCell className="text-right font-medium">{formatNumber(itemTotal, 2)}</TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell colSpan={4} className="text-right font-medium">
                                        {t('proforma.subtotal')}:
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {formatNumber(proforma.subtotal, 2)} {proforma.currency}
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell colSpan={4} className="text-right font-medium">
                                        {t('proforma.tax')}:
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {formatNumber(proforma.tax_amount, 2)} {proforma.currency}
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell colSpan={4} className="text-right text-lg font-bold">
                                        {t('proforma.total')}:
                                    </TableCell>
                                    <TableCell className="text-right text-lg font-bold">
                                        {formatNumber(proforma.total, 2)} {proforma.currency}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>

                {/* Notes */}
                {proforma.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('proforma.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{proforma.notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
