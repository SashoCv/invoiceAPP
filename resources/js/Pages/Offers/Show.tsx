import { useState } from 'react';
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
import {
    Dialog,
    DialogContent,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { ArrowLeft, Pencil, Copy, FileText, Printer, Check, X, ArrowRightLeft, Eye, Download } from 'lucide-react';
import InvoicePreview from '@/Components/InvoicePreview';
import type { Offer, Agency, BankAccount } from '@/types';

interface ShowOfferProps {
    offer: Offer & {
        user?: {
            agency?: Agency;
            bank_accounts?: BankAccount[];
            offer_template?: 'classic' | 'modern' | 'minimal';
        };
    };
}

const statusVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    draft: 'gray',
    sent: 'info',
    accepted: 'success',
    rejected: 'destructive',
};

export default function ShowOffer({ offer }: ShowOfferProps) {
    const { t } = useTranslation();
    const [previewOpen, setPreviewOpen] = useState(false);

    const agency = offer.user?.agency;
    const bankAccount = offer.user?.bank_accounts?.find(
        (acc) => acc.currency === offer.currency && acc.is_default
    ) || offer.user?.bank_accounts?.find(
        (acc) => acc.currency === offer.currency
    ) || offer.user?.bank_accounts?.find(
        (acc) => acc.is_default
    );

    const handleAccept = () => {
        router.post(`/offers/${offer.id}/accept`);
    };

    const handleReject = () => {
        router.post(`/offers/${offer.id}/reject`);
    };

    const handleConvertToInvoice = () => {
        router.post(`/offers/${offer.id}/convert`);
    };

    return (
        <AppLayout>
            <Head title={`${t('offers.offer')} ${offer.offer_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/offers" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('offers.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <FileText className="w-6 h-6 text-orange-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{offer.offer_number}</h1>
                                <p className="text-sm text-gray-500">{offer.client?.name}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Badge variant={statusVariants[offer.status] || 'gray'} className="text-sm px-3 py-1">
                                {t(`offers.status_${offer.status}`)}
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2 mb-6 flex-wrap">
                    <Button variant="outline" asChild>
                        <Link href={`/offers/${offer.id}/edit`} className="flex items-center gap-2">
                            <Pencil className="w-4 h-4" />
                            {t('offers.edit')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/offers/${offer.id}/duplicate`} className="flex items-center gap-2">
                            <Copy className="w-4 h-4" />
                            {t('offers.duplicate')}
                        </Link>
                    </Button>
                    <Button variant="outline" onClick={() => setPreviewOpen(true)} className="flex items-center gap-2">
                        <Eye className="w-4 h-4" />
                        {t('offers.preview')}
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/offers/${offer.id}/pdf`} className="flex items-center gap-2">
                            <Download className="w-4 h-4" />
                            {t('offers.download_pdf')}
                        </a>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/offers/${offer.id}/pdf/preview`} target="_blank" className="flex items-center gap-2">
                            <Printer className="w-4 h-4" />
                            {t('offers.print_pdf')}
                        </a>
                    </Button>
                    {offer.status === 'sent' && (
                        <>
                            <Button onClick={handleAccept} variant="outline" className="flex items-center gap-2 text-green-600 hover:text-green-700">
                                <Check className="w-4 h-4" />
                                {t('offers.accept')}
                            </Button>
                            <Button onClick={handleReject} variant="outline" className="flex items-center gap-2 text-red-600 hover:text-red-700">
                                <X className="w-4 h-4" />
                                {t('offers.reject')}
                            </Button>
                        </>
                    )}
                    {offer.status === 'accepted' && !offer.converted_invoice_id && offer.has_items && (
                        <Button onClick={handleConvertToInvoice} className="flex items-center gap-2">
                            <ArrowRightLeft className="w-4 h-4" />
                            {t('offers.convert_to_invoice')}
                        </Button>
                    )}
                </div>

                {/* Preview Dialog */}
                <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
                    <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto p-0">
                        <InvoicePreview
                            document={offer}
                            type="offer"
                            agency={agency}
                            bankAccount={bankAccount}
                            template={offer.user?.offer_template || 'classic'}
                        />
                    </DialogContent>
                </Dialog>

                {/* Converted Invoice Link */}
                {offer.convertedInvoice && (
                    <Card className="mb-6 bg-green-50 border-green-200">
                        <CardContent className="py-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <ArrowRightLeft className="w-5 h-5 text-green-600" />
                                    <span className="text-sm text-green-800">
                                        {t('offers.converted_to')}:
                                    </span>
                                    <Link
                                        href={`/invoices/${offer.convertedInvoice.id}`}
                                        className="text-sm font-medium text-green-700 hover:text-green-900 underline"
                                    >
                                        {offer.convertedInvoice.invoice_number}
                                    </Link>
                                </div>
                                {offer.converted_at && (
                                    <span className="text-xs text-green-600">
                                        {formatDate(offer.converted_at)}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Offer Details */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('offers.offer_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('offers.offer_number')}:</span>
                                <span className="font-medium">{offer.offer_number}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('offers.title')}:</span>
                                <span className="font-medium">{offer.title}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('offers.issue_date')}:</span>
                                <span className="font-medium">{formatDate(offer.issue_date)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('offers.valid_until')}:</span>
                                <span className="font-medium">{formatDate(offer.valid_until)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('offers.currency')}:</span>
                                <span className="font-medium">{offer.currency}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('offers.client_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="text-sm">
                                <span className="font-medium">{offer.client?.name}</span>
                            </div>
                            {offer.client?.address && (
                                <div className="text-sm text-gray-600">
                                    {offer.client.address}
                                    {offer.client.city && `, ${offer.client.city}`}
                                </div>
                            )}
                            {offer.client?.email && (
                                <div className="text-sm text-gray-600">{offer.client.email}</div>
                            )}
                            {offer.client?.tax_number && (
                                <div className="text-sm text-gray-600">
                                    {t('offers.tax_number')}: {offer.client.tax_number}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Content */}
                {offer.content && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-base">{t('offers.content')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                className="text-sm text-gray-600 prose prose-sm max-w-none"
                                dangerouslySetInnerHTML={{ __html: offer.content }}
                            />
                        </CardContent>
                    </Card>
                )}

                {/* Items */}
                {offer.has_items && offer.items && offer.items.length > 0 && (
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="text-base">{t('offers.items')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-1/2">{t('offers.description')}</TableHead>
                                        <TableHead className="text-right">{t('offers.quantity')}</TableHead>
                                        <TableHead className="text-right">{t('offers.unit_price')}</TableHead>
                                        <TableHead className="text-right">{t('offers.tax_rate')}</TableHead>
                                        <TableHead className="text-right">{t('offers.total')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {offer.items.map((item, index) => {
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
                                            {t('offers.subtotal')}:
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatNumber(offer.subtotal, 2)} {offer.currency}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-right font-medium">
                                            {t('offers.tax')}:
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatNumber(offer.tax_amount, 2)} {offer.currency}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-right text-lg font-bold">
                                            {t('offers.total')}:
                                        </TableCell>
                                        <TableCell className="text-right text-lg font-bold">
                                            {formatNumber(offer.total, 2)} {offer.currency}
                                        </TableCell>
                                    </TableRow>
                                </TableFooter>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Notes */}
                {offer.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('offers.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{offer.notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
