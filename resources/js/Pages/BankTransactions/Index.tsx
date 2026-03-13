import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { useSubscription } from '@/hooks/use-subscription';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import {
    Plus,
    Pencil,
    Trash2,
    Landmark,
    Download,
    Search,
    Info,
} from 'lucide-react';
import type { BankTransaction, BankAccount, Invoice, Client, PaginatedData } from '@/types';

interface BankTransactionsIndexProps {
    transactions: PaginatedData<BankTransaction>;
    bankAccounts: BankAccount[];
    unpaidInvoices: Invoice[];
    clients: Client[];
    totalIncome: number;
    filters: {
        date_from: string;
        date_to: string;
        bank_account: string;
        type: string;
        sort: string;
        direction: string;
    };
}

export default function BankTransactionsIndex({
    transactions,
    bankAccounts,
    unpaidInvoices,
    clients,
    totalIncome,
    filters,
}: BankTransactionsIndexProps) {
    const { t } = useTranslation();
    const { isActive } = useSubscription();

    // Filter state
    const [filterDateFrom, setFilterDateFrom] = useState(filters.date_from);
    const [filterDateTo, setFilterDateTo] = useState(filters.date_to);
    const [filterBankAccount, setFilterBankAccount] = useState(filters.bank_account);
    const [filterType, setFilterType] = useState(filters.type);

    // Dialog state
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<BankTransaction | null>(null);
    const [form, setForm] = useState({
        type: 'income' as 'income' | 'expense',
        invoice_id: '',
        client_id: '',
        bank_account_id: '',
        amount: '',
        currency: 'MKD',
        date: new Date().toISOString().split('T')[0],
        reference: '',
        description: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);

    // Delete state
    const [deleteTransaction, setDeleteTransaction] = useState<BankTransaction | null>(null);

    const applyFilters = () => {
        const cleanFilter = (v: string) => (v && v !== 'all') ? v : undefined;
        router.get('/bank-transactions', {
            date_from: filterDateFrom || undefined,
            date_to: filterDateTo || undefined,
            bank_account: cleanFilter(filterBankAccount),
            type: cleanFilter(filterType),
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setFilterDateFrom('');
        setFilterDateTo('');
        setFilterBankAccount('');
        setFilterType('');
        router.get('/bank-transactions', {}, { preserveState: true });
    };

    const openDialog = (transaction?: BankTransaction) => {
        if (transaction) {
            setEditing(transaction);
            setForm({
                type: transaction.type,
                invoice_id: transaction.invoice_id ? String(transaction.invoice_id) : '',
                client_id: transaction.client_id ? String(transaction.client_id) : '',
                bank_account_id: transaction.bank_account_id ? String(transaction.bank_account_id) : '',
                amount: String(transaction.amount),
                currency: transaction.currency,
                date: transaction.date,
                reference: transaction.reference || '',
                description: transaction.description || '',
            });
        } else {
            setEditing(null);
            setForm({
                type: 'income',
                invoice_id: '',
                client_id: '',
                bank_account_id: '',
                amount: '',
                currency: 'MKD',
                date: new Date().toISOString().split('T')[0],
                reference: '',
                description: '',
            });
        }
        setErrors({});
        setDialogOpen(true);
    };

    const handleInvoiceSelect = (invoiceId: string) => {
        const id = invoiceId === 'none' ? '' : invoiceId;
        setForm(prev => {
            const updated = { ...prev, invoice_id: id };
            if (id) {
                const invoice = unpaidInvoices.find(i => i.id === Number(id));
                if (invoice) {
                    updated.amount = String(invoice.total);
                    updated.currency = invoice.currency;
                    updated.client_id = String(invoice.client_id);
                }
            }
            return updated;
        });
    };

    const submitForm = () => {
        setLoading(true);
        const clean = (v: string) => (v && v !== 'none') ? v : null;
        const data = {
            ...form,
            invoice_id: clean(form.invoice_id),
            client_id: clean(form.client_id),
            bank_account_id: clean(form.bank_account_id),
        };

        const url = editing ? `/bank-transactions/${editing.id}` : '/bank-transactions';
        const method = editing ? 'put' : 'post';

        router[method](url, data, {
            preserveScroll: true,
            onSuccess: () => {
                setDialogOpen(false);
                setLoading(false);
            },
            onError: (errs) => {
                setErrors(errs);
                setLoading(false);
            },
        });
    };

    const getClientName = (transaction: BankTransaction) => {
        return transaction.client?.name
            || transaction.invoice?.client?.name
            || '';
    };

    const buildExportUrl = () => {
        const params = new URLSearchParams();
        if (filterDateFrom) params.set('date_from', filterDateFrom);
        if (filterDateTo) params.set('date_to', filterDateTo);
        if (filterBankAccount && filterBankAccount !== 'all') params.set('bank_account', filterBankAccount);
        if (filterType && filterType !== 'all') params.set('type', filterType);
        const qs = params.toString();
        return `/bank-transactions/export/csv${qs ? '?' + qs : ''}`;
    };

    const hasFilters = filterDateFrom || filterDateTo || (filterBankAccount && filterBankAccount !== 'all') || (filterType && filterType !== 'all');

    return (
        <AppLayout>
            <Head title={t('bank_transactions.title')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            {t('bank_transactions.title')}
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            {t('bank_transactions.subtitle')}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {totalIncome > 0 && (
                            <Badge variant="outline" className="text-green-700 border-green-200 bg-green-50 px-3 py-1.5">
                                {t('bank_transactions.total_income')}: {formatNumber(totalIncome)} MKD
                            </Badge>
                        )}
                        <a href={buildExportUrl()}>
                            <Button variant="outline" size="sm">
                                <Download className="w-4 h-4 mr-2" />
                                CSV
                            </Button>
                        </a>
                        <Button
                            onClick={() => openDialog()}
                            disabled={!isActive}
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            {t('bank_transactions.add_transaction')}
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white border border-gray-200 rounded-lg p-4">
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('bank_transactions.filter_date_from')}</Label>
                            <Input
                                type="date"
                                value={filterDateFrom}
                                onChange={(e) => setFilterDateFrom(e.target.value)}
                                className="w-40 h-9"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('bank_transactions.filter_date_to')}</Label>
                            <Input
                                type="date"
                                value={filterDateTo}
                                onChange={(e) => setFilterDateTo(e.target.value)}
                                className="w-40 h-9"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('bank_transactions.bank_account')}</Label>
                            <Select value={filterBankAccount} onValueChange={setFilterBankAccount}>
                                <SelectTrigger className="w-48 h-9">
                                    <SelectValue placeholder={t('bank_transactions.filter_all_accounts')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('bank_transactions.filter_all_accounts')}</SelectItem>
                                    {bankAccounts.map((account) => (
                                        <SelectItem key={account.id} value={String(account.id)}>
                                            {account.bank_name} ({account.currency})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs text-gray-500">{t('bank_transactions.type')}</Label>
                            <Select value={filterType} onValueChange={setFilterType}>
                                <SelectTrigger className="w-36 h-9">
                                    <SelectValue placeholder={t('bank_transactions.filter_all_types')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('bank_transactions.filter_all_types')}</SelectItem>
                                    <SelectItem value="income">{t('bank_transactions.type_income')}</SelectItem>
                                    <SelectItem value="expense">{t('bank_transactions.type_expense')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button variant="default" size="sm" className="h-9" onClick={applyFilters}>
                            <Search className="w-4 h-4 mr-1" />
                            {t('bank_transactions.filter_apply')}
                        </Button>
                        {hasFilters && (
                            <Button variant="ghost" size="sm" className="h-9" onClick={clearFilters}>
                                {t('bank_transactions.filter_clear')}
                            </Button>
                        )}
                    </div>
                </div>

                {/* Table */}
                {transactions.data.length === 0 ? (
                    <EmptyState
                        icon={Landmark}
                        title={t('bank_transactions.no_transactions')}
                        description={t('bank_transactions.no_transactions_description')}
                        action={isActive ? {
                            label: t('bank_transactions.add_transaction'),
                            onClick: () => openDialog(),
                        } : undefined}
                    />
                ) : (
                    <>
                        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('bank_transactions.date')}</TableHead>
                                        <TableHead>{t('bank_transactions.type')}</TableHead>
                                        <TableHead>{t('bank_transactions.client')}</TableHead>
                                        <TableHead>{t('bank_transactions.invoice')}</TableHead>
                                        <TableHead>{t('bank_transactions.bank_account')}</TableHead>
                                        <TableHead className="text-right">{t('bank_transactions.amount')}</TableHead>
                                        <TableHead>{t('bank_transactions.reference')}</TableHead>
                                        <TableHead className="w-20"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.data.map((transaction) => (
                                        <TableRow key={transaction.id}>
                                            <TableCell className="text-sm">
                                                {formatDate(transaction.date)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={transaction.type === 'income' ? 'default' : 'destructive'}
                                                    className={transaction.type === 'income'
                                                        ? 'bg-green-100 text-green-700 hover:bg-green-100'
                                                        : 'bg-red-100 text-red-700 hover:bg-red-100'
                                                    }
                                                >
                                                    {transaction.type === 'income'
                                                        ? t('bank_transactions.type_income')
                                                        : t('bank_transactions.type_expense')
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {getClientName(transaction) || <span className="text-gray-400">&mdash;</span>}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {transaction.invoice ? (
                                                    <Link
                                                        href={`/invoices/${transaction.invoice.id}`}
                                                        className="text-blue-600 hover:underline"
                                                    >
                                                        {transaction.invoice.invoice_number}
                                                    </Link>
                                                ) : (
                                                    <span className="text-gray-400">&mdash;</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-600">
                                                {transaction.bank_account?.bank_name || <span className="text-gray-400">&mdash;</span>}
                                            </TableCell>
                                            <TableCell className="text-sm text-right font-medium tabular-nums">
                                                <span className={transaction.type === 'income' ? 'text-green-700' : 'text-red-600'}>
                                                    {transaction.type === 'income' ? '+' : '-'}{formatNumber(transaction.amount)}
                                                </span>
                                                <span className="text-gray-400 ml-1 text-xs">{transaction.currency}</span>
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {transaction.reference || <span className="text-gray-400">&mdash;</span>}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1 justify-end">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() => openDialog(transaction)}
                                                        disabled={!isActive}
                                                    >
                                                        <Pencil className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-500 hover:text-red-700"
                                                        onClick={() => setDeleteTransaction(transaction)}
                                                        disabled={!isActive}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <Pagination
                            links={transactions.links}
                            from={transactions.from}
                            to={transactions.to}
                            total={transactions.total}
                        />
                    </>
                )}
            </div>

            {/* Add/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? t('bank_transactions.edit_transaction')
                                : t('bank_transactions.add_transaction')
                            }
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? t('bank_transactions.edit_transaction_desc')
                                : t('bank_transactions.add_transaction_desc')
                            }
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* Type */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.type')}</Label>
                            <Select value={form.type} onValueChange={(v) => setForm(prev => ({ ...prev, type: v as 'income' | 'expense' }))}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="income">{t('bank_transactions.type_income')}</SelectItem>
                                    <SelectItem value="expense">{t('bank_transactions.type_expense')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Invoice */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.invoice')}</Label>
                            <Select value={form.invoice_id} onValueChange={handleInvoiceSelect}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('bank_transactions.select_invoice_placeholder')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">{t('bank_transactions.no_invoice')}</SelectItem>
                                    {unpaidInvoices.map((invoice) => (
                                        <SelectItem key={invoice.id} value={String(invoice.id)}>
                                            {invoice.invoice_number} - {invoice.client?.name} ({formatNumber(invoice.total, invoice.currency === 'MKD' ? 0 : 2)} {invoice.currency})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.invoice_id && form.invoice_id !== 'none' && form.invoice_id !== '' && (
                                <p className="text-xs text-blue-600 flex items-center gap-1 mt-1">
                                    <Info className="w-3 h-3" />
                                    {t('bank_transactions.invoice_will_be_marked_paid')}
                                </p>
                            )}
                            {errors.invoice_id && <p className="text-xs text-red-500">{errors.invoice_id}</p>}
                        </div>

                        {/* Client */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.client')}</Label>
                            <Select value={form.client_id} onValueChange={(v) => setForm(prev => ({ ...prev, client_id: v }))}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('bank_transactions.select_client')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">{t('bank_transactions.no_client')}</SelectItem>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.name}{client.company ? ` (${client.company})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.client_id && <p className="text-xs text-red-500">{errors.client_id}</p>}
                        </div>

                        {/* Bank Account */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.bank_account')}</Label>
                            <Select value={form.bank_account_id} onValueChange={(v) => setForm(prev => ({ ...prev, bank_account_id: v }))}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('bank_transactions.filter_all_accounts')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">{t('bank_transactions.no_bank_account')}</SelectItem>
                                    {bankAccounts.map((account) => (
                                        <SelectItem key={account.id} value={String(account.id)}>
                                            {account.bank_name} - {account.account_number} ({account.currency})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.bank_account_id && <p className="text-xs text-red-500">{errors.bank_account_id}</p>}
                        </div>

                        {/* Amount + Currency row */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>{t('bank_transactions.amount')}</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={form.amount}
                                    onChange={(e) => setForm(prev => ({ ...prev, amount: e.target.value }))}
                                    placeholder="0.00"
                                />
                                {errors.amount && <p className="text-xs text-red-500">{errors.amount}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>{t('bank_transactions.currency')}</Label>
                                <Select value={form.currency} onValueChange={(v) => setForm(prev => ({ ...prev, currency: v }))}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="MKD">MKD</SelectItem>
                                        <SelectItem value="EUR">EUR</SelectItem>
                                        <SelectItem value="USD">USD</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Date */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.date')}</Label>
                            <Input
                                type="date"
                                value={form.date}
                                onChange={(e) => setForm(prev => ({ ...prev, date: e.target.value }))}
                            />
                            {errors.date && <p className="text-xs text-red-500">{errors.date}</p>}
                        </div>

                        {/* Reference */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.reference')}</Label>
                            <Input
                                value={form.reference}
                                onChange={(e) => setForm(prev => ({ ...prev, reference: e.target.value }))}
                                placeholder={t('bank_transactions.reference_placeholder')}
                            />
                            {errors.reference && <p className="text-xs text-red-500">{errors.reference}</p>}
                        </div>

                        {/* Description */}
                        <div className="space-y-1.5">
                            <Label>{t('bank_transactions.description')}</Label>
                            <Input
                                value={form.description}
                                onChange={(e) => setForm(prev => ({ ...prev, description: e.target.value }))}
                                placeholder={t('bank_transactions.description_placeholder')}
                            />
                            {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setDialogOpen(false)} disabled={loading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitForm} loading={loading}>
                            {t('general.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <DeleteConfirmDialog
                open={!!deleteTransaction}
                onOpenChange={(open) => !open && setDeleteTransaction(null)}
                title={t('bank_transactions.delete_transaction')}
                description={t('bank_transactions.delete_transaction_confirm')}
                deleteUrl={deleteTransaction ? `/bank-transactions/${deleteTransaction.id}` : ''}
            />
        </AppLayout>
    );
}
