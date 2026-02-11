import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import { ArrowLeft, Pencil, Copy, FileText, Printer, Eye, Download, Send } from 'lucide-react';
import InvoicePreview from '@/Components/InvoicePreview';
import type { Invoice, Agency, BankAccount } from '@/types';

interface ShowInvoiceProps {
    invoice: Invoice & {
        user?: {
            agency?: Agency;
            bank_accounts?: BankAccount[];
            invoice_template?: 'classic' | 'modern' | 'minimal';
        };
    };
}

const statusVariants: Record<string, 'success' | 'info' | 'gray' | 'destructive' | 'warning'> = {
    draft: 'gray',
    sent: 'info',
    unpaid: 'warning',
    paid: 'success',
    overdue: 'warning',
    cancelled: 'destructive',
};

export default function ShowInvoice({ invoice }: ShowInvoiceProps) {
    const { t } = useTranslation();
    const [previewOpen, setPreviewOpen] = useState(false);
    const [sendDialogOpen, setSendDialogOpen] = useState(false);
    const [sendForm, setSendForm] = useState({
        to: '',
        subject: '',
        body: '',
    });
    const [sendErrors, setSendErrors] = useState<Record<string, string>>({});
    const [sendLoading, setSendLoading] = useState(false);

    const openSendDialog = () => {
        setSendForm({
            to: invoice.client?.email || '',
            subject: `${t('invoices.invoice')} ${invoice.invoice_number}`,
            body: t('invoices.email_default_body').replace(/\\n/g, '\n'),
        });
        setSendErrors({});
        setSendDialogOpen(true);
    };

    const submitSend = () => {
        setSendLoading(true);
        router.post(`/invoices/${invoice.id}/send`, sendForm, {
            preserveScroll: true,
            onSuccess: () => {
                setSendDialogOpen(false);
                setSendLoading(false);
            },
            onError: (errors) => {
                setSendErrors(errors);
                setSendLoading(false);
            },
        });
    };

    const agency = invoice.user?.agency;
    const bankAccount = invoice.user?.bank_accounts?.find(
        (acc) => acc.currency === invoice.currency && acc.is_default
    ) || invoice.user?.bank_accounts?.find(
        (acc) => acc.currency === invoice.currency
    ) || invoice.user?.bank_accounts?.find(
        (acc) => acc.is_default
    );

    return (
        <AppLayout>
            <Head title={`${t('invoices.invoice')} ${invoice.invoice_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/invoices" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('invoices.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <FileText className="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{invoice.invoice_number}</h1>
                                <p className="text-sm text-gray-500">{invoice.client?.name}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Badge variant={statusVariants[invoice.status] || 'gray'} className="text-sm px-3 py-1">
                                {t(`invoices.status_${invoice.status}`)}
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="outline" asChild>
                        <Link href={`/invoices/${invoice.id}/edit`} className="flex items-center gap-2">
                            <Pencil className="w-4 h-4" />
                            {t('invoices.edit_invoice')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/invoices/${invoice.id}/duplicate`} className="flex items-center gap-2">
                            <Copy className="w-4 h-4" />
                            {t('invoices.duplicate')}
                        </Link>
                    </Button>
                    <Button variant="outline" onClick={() => setPreviewOpen(true)} className="flex items-center gap-2">
                        <Eye className="w-4 h-4" />
                        {t('invoices.preview')}
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/invoices/${invoice.id}/pdf`} className="flex items-center gap-2">
                            <Download className="w-4 h-4" />
                            {t('invoices.download_pdf')}
                        </a>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={`/invoices/${invoice.id}/pdf/preview`} target="_blank" className="flex items-center gap-2">
                            <Printer className="w-4 h-4" />
                            {t('invoices.print_pdf')}
                        </a>
                    </Button>
                    <Button onClick={openSendDialog} className="flex items-center gap-2">
                        <Send className="w-4 h-4" />
                        {t('invoices.send_invoice')}
                    </Button>
                </div>

                {/* Preview Dialog */}
                <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
                    <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto p-0">
                        <InvoicePreview
                            document={invoice}
                            type="invoice"
                            agency={agency}
                            bankAccount={bankAccount}
                            template={invoice.user?.invoice_template || 'classic'}
                        />
                    </DialogContent>
                </Dialog>

                {/* Invoice Details */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('invoices.invoice_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('invoices.invoice_number')}:</span>
                                <span className="font-medium">{invoice.invoice_number}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('invoices.issue_date')}:</span>
                                <span className="font-medium">{formatDate(invoice.issue_date)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('invoices.due_date')}:</span>
                                <span className="font-medium">{formatDate(invoice.due_date)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">{t('invoices.currency')}:</span>
                                <span className="font-medium">{invoice.currency}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('invoices.client_details')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="text-sm">
                                <span className="font-medium">{invoice.client?.name}</span>
                            </div>
                            {invoice.client?.address && (
                                <div className="text-sm text-gray-600">
                                    {invoice.client.address}
                                    {invoice.client.city && `, ${invoice.client.city}`}
                                </div>
                            )}
                            {invoice.client?.email && (
                                <div className="text-sm text-gray-600">{invoice.client.email}</div>
                            )}
                            {invoice.client?.tax_number && (
                                <div className="text-sm text-gray-600">
                                    {t('invoices.tax_number')}: {invoice.client.tax_number}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Items */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base">{t('invoices.items')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/2">{t('invoices.description')}</TableHead>
                                    <TableHead className="text-right">{t('invoices.quantity')}</TableHead>
                                    <TableHead className="text-right">{t('invoices.unit_price')}</TableHead>
                                    <TableHead className="text-right">{t('invoices.tax_rate')}</TableHead>
                                    <TableHead className="text-right">{t('invoices.total')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoice.items?.map((item, index) => {
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
                                        {t('invoices.subtotal')}:
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {formatNumber(invoice.subtotal, 2)} {invoice.currency}
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell colSpan={4} className="text-right font-medium">
                                        {t('invoices.tax')}:
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {formatNumber(invoice.tax_amount, 2)} {invoice.currency}
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell colSpan={4} className="text-right text-lg font-bold">
                                        {t('invoices.total')}:
                                    </TableCell>
                                    <TableCell className="text-right text-lg font-bold">
                                        {formatNumber(invoice.total, 2)} {invoice.currency}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>

                {/* Notes */}
                {invoice.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('invoices.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{invoice.notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Send Email Dialog */}
            <Dialog open={sendDialogOpen} onOpenChange={setSendDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('invoices.send_invoice')}</DialogTitle>
                        <DialogDescription>
                            {t('invoices.invoice')} {invoice.invoice_number}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-2 block">{t('invoices.email_to')}</Label>
                            <Input
                                type="email"
                                value={sendForm.to}
                                onChange={(e) => setSendForm({ ...sendForm, to: e.target.value })}
                            />
                            {sendErrors.to && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.to}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('invoices.email_subject')}</Label>
                            <Input
                                value={sendForm.subject}
                                onChange={(e) => setSendForm({ ...sendForm, subject: e.target.value })}
                            />
                            {sendErrors.subject && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.subject}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('invoices.email_body')}</Label>
                            <Textarea
                                rows={6}
                                value={sendForm.body}
                                onChange={(e) => setSendForm({ ...sendForm, body: e.target.value })}
                            />
                            {sendErrors.body && (
                                <p className="text-sm text-red-600 mt-1">{sendErrors.body}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setSendDialogOpen(false)} disabled={sendLoading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitSend} loading={sendLoading}>
                            <Send className="w-4 h-4 mr-2" />
                            {t('invoices.send_email')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
