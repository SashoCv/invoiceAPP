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
    X,
    ChevronDown,
    Eye,
} from 'lucide-react';
import type { BankTransactionBatch, BankTransaction, BankAccount, Invoice, Client, PaginatedData } from '@/types';

interface FormItem {
    type: 'income' | 'expense';
    amount: string;
    currency: string;
    invoice_id: string;
    client_id: string;
    description: string;
    reference: string;
}

const emptyItem = (): FormItem => ({
    type: 'income',
    amount: '',
    currency: 'MKD',
    invoice_id: '',
    client_id: '',
    description: '',
    reference: '',
});

interface BankTransactionsIndexProps {
    batches: PaginatedData<BankTransactionBatch>;
    bankAccounts: BankAccount[];
    unpaidInvoices: Invoice[];
    clients: Client[];
    totalIncome: number;
    totalExpense: number;
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
    batches,
    bankAccounts,
    unpaidInvoices,
    clients,
    totalIncome,
    totalExpense,
    filters,
}: BankTransactionsIndexProps) {
    const { t } = useTranslation();
    const { isActive } = useSubscription();

    // Filter state
    const [filterDateFrom, setFilterDateFrom] = useState(filters.date_from);
    const [filterDateTo, setFilterDateTo] = useState(filters.date_to);
    const [filterBankAccount, setFilterBankAccount] = useState(filters.bank_account);
    const [filterType, setFilterType] = useState(filters.type);

    // Expand state
    const [expandedBatches, setExpandedBatches] = useState<Set<string>>(new Set());

    const toggleExpand = (batchId: string) => {
        setExpandedBatches(prev => {
            const next = new Set(prev);
            if (next.has(batchId)) {
                next.delete(batchId);
            } else {
                next.add(batchId);
            }
            return next;
        });
    };

    // Dialog state
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingBatch, setEditingBatch] = useState<BankTransactionBatch | null>(null);
    const [formHeader, setFormHeader] = useState({
        date: new Date().toISOString().split('T')[0],
        bank_account_id: '',
    });
    const [formItems, setFormItems] = useState<FormItem[]>([emptyItem()]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);

    // View state
    const [viewBatch, setViewBatch] = useState<BankTransactionBatch | null>(null);

    // Delete state
    const [deleteBatch, setDeleteBatch] = useState<BankTransactionBatch | null>(null);

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

    const openDialog = (batch?: BankTransactionBatch) => {
        if (batch) {
            setEditingBatch(batch);
            setFormHeader({
                date: batch.date,
                bank_account_id: batch.bank_account?.id ? String(batch.bank_account.id) : '',
            });
            setFormItems(batch.items.map(item => ({
                type: item.type,
                amount: String(item.amount),
                currency: item.currency,
                invoice_id: item.invoice_id ? String(item.invoice_id) : '',
                client_id: item.client_id ? String(item.client_id) : '',
                description: item.description || '',
                reference: item.reference || '',
            })));
        } else {
            setEditingBatch(null);
            setFormHeader({
                date: new Date().toISOString().split('T')[0],
                bank_account_id: '',
            });
            setFormItems([emptyItem()]);
        }
        setErrors({});
        setDialogOpen(true);
    };

    const updateItem = (index: number, field: keyof FormItem, value: string) => {
        setFormItems(prev => prev.map((item, i) =>
            i === index ? { ...item, [field]: value } : item
        ));
    };

    const addItem = () => {
        setFormItems(prev => [...prev, emptyItem()]);
    };

    const removeItem = (index: number) => {
        if (formItems.length > 1) {
            setFormItems(prev => prev.filter((_, i) => i !== index));
        }
    };

    const handleItemInvoiceSelect = (index: number, invoiceId: string) => {
        const id = invoiceId === 'none' ? '' : invoiceId;
        setFormItems(prev => prev.map((item, i) => {
            if (i !== index) return item;
            const updated = { ...item, invoice_id: id };
            if (id) {
                const invoice = unpaidInvoices.find(inv => inv.id === Number(id));
                if (invoice) {
                    updated.amount = String(invoice.total);
                    updated.currency = invoice.currency;
                    updated.client_id = String(invoice.client_id);
                }
            }
            return updated;
        }));
    };

    const getItemError = (index: number, field: string) => {
        return errors[`items.${index}.${field}`];
    };

    const submitForm = () => {
        setLoading(true);
        const clean = (v: string) => (v && v !== 'none') ? v : null;
        const data = {
            date: formHeader.date,
            bank_account_id: clean(formHeader.bank_account_id),
            items: formItems.map(item => ({
                type: item.type,
                amount: item.amount,
                currency: item.currency,
                invoice_id: clean(item.invoice_id),
                client_id: clean(item.client_id),
                description: item.description || null,
                reference: item.reference || null,
            })),
        };

        const url = editingBatch
            ? `/bank-transactions/${editingBatch.items[0].id}`
            : '/bank-transactions';
        const method = editingBatch ? 'put' : 'post';

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

    const getBatchTotals = (batch: BankTransactionBatch) => {
        let income = 0;
        let expense = 0;
        for (const item of batch.items) {
            if (item.type === 'income') income += Number(item.amount);
            else expense += Number(item.amount);
        }
        return { income, expense, net: income - expense };
    };

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
                        {(totalIncome > 0 || totalExpense > 0) && (
                            <div className="flex items-center gap-2">
                                {totalIncome > 0 && (
                                    <Badge variant="outline" className="text-green-700 border-green-200 bg-green-50 px-3 py-1.5">
                                        +{formatNumber(totalIncome)}
                                    </Badge>
                                )}
                                {totalExpense > 0 && (
                                    <Badge variant="outline" className="text-red-600 border-red-200 bg-red-50 px-3 py-1.5">
                                        -{formatNumber(totalExpense)}
                                    </Badge>
                                )}
                                <Badge variant="outline" className={`px-3 py-1.5 ${(totalIncome - totalExpense) >= 0 ? 'text-green-700 border-green-200 bg-green-50' : 'text-red-600 border-red-200 bg-red-50'}`}>
                                    {t('bank_transactions.total')}: {(totalIncome - totalExpense) >= 0 ? '+' : ''}{formatNumber(totalIncome - totalExpense)}
                                </Badge>
                            </div>
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

                {/* Batch list */}
                {batches.data.length === 0 ? (
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
                        <div className="space-y-3">
                            {batches.data.map((batch) => {
                                const isExpanded = expandedBatches.has(batch.batch_id);
                                const totals = getBatchTotals(batch);

                                return (
                                    <div key={batch.batch_id} className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                        {/* Batch header */}
                                        <div
                                            className="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                                            onClick={() => toggleExpand(batch.batch_id)}
                                        >
                                            <div className="flex items-center gap-4 min-w-0">
                                                <ChevronDown
                                                    className={`w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`}
                                                />
                                                <span className="font-semibold text-gray-900 whitespace-nowrap">
                                                    {t('bank_transactions.batch_label', { number: String(batch.batch_number) })}
                                                </span>
                                                <span className="text-sm text-gray-500 whitespace-nowrap">
                                                    {formatDate(batch.date)}
                                                </span>
                                                <span className="text-sm text-gray-500 truncate hidden sm:inline">
                                                    {batch.bank_account?.bank_name || ''}
                                                </span>
                                                <Badge variant="outline" className="text-xs shrink-0">
                                                    {(() => {
                                                        const parts = t('bank_transactions.items_count', { count: String(batch.items.length) }).split('|');
                                                        const text = batch.items.length === 1 ? parts[0] : (parts[1] || parts[0]);
                                                        return text.replace(':count', String(batch.items.length));
                                                    })()}
                                                </Badge>
                                            </div>
                                            <div className="flex items-center gap-3 shrink-0 ml-4">
                                                {/* Amount summary */}
                                                <div className="text-sm font-medium tabular-nums text-right hidden sm:block">
                                                    <span className={totals.net >= 0 ? 'text-green-700' : 'text-red-600'}>
                                                        {totals.net >= 0 ? '+' : ''}{formatNumber(totals.net)}
                                                    </span>
                                                </div>
                                                {/* Actions */}
                                                <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() => setViewBatch(batch)}
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() => openDialog(batch)}
                                                        disabled={!isActive}
                                                    >
                                                        <Pencil className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-500 hover:text-red-700"
                                                        onClick={() => setDeleteBatch(batch)}
                                                        disabled={!isActive}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Expanded items */}
                                        {isExpanded && (
                                            <div className="border-t border-gray-200">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow className="bg-gray-50">
                                                            <TableHead className="text-xs">{t('bank_transactions.type')}</TableHead>
                                                            <TableHead className="text-xs">{t('bank_transactions.client')}</TableHead>
                                                            <TableHead className="text-xs">{t('bank_transactions.invoice')}</TableHead>
                                                            <TableHead className="text-xs text-right">{t('bank_transactions.amount')}</TableHead>
                                                            <TableHead className="text-xs">{t('bank_transactions.description')}</TableHead>
                                                            <TableHead className="text-xs">{t('bank_transactions.reference')}</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {batch.items.map((transaction) => (
                                                            <TableRow key={transaction.id}>
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
                                                                <TableCell className="text-sm text-right font-medium tabular-nums">
                                                                    <span className={transaction.type === 'income' ? 'text-green-700' : 'text-red-600'}>
                                                                        {transaction.type === 'income' ? '+' : '-'}{formatNumber(transaction.amount)}
                                                                    </span>
                                                                    <span className="text-gray-400 ml-1 text-xs">{transaction.currency}</span>
                                                                </TableCell>
                                                                <TableCell className="text-sm text-gray-500">
                                                                    {transaction.description || <span className="text-gray-400">&mdash;</span>}
                                                                </TableCell>
                                                                <TableCell className="text-sm text-gray-500">
                                                                    {transaction.reference || <span className="text-gray-400">&mdash;</span>}
                                                                </TableCell>
                                                            </TableRow>
                                                        ))}
                                                    </TableBody>
                                                </Table>
                                                {totals.income > 0 && totals.expense > 0 && (
                                                    <div className="flex justify-end px-4 py-2 text-sm font-medium border-t bg-gray-50">
                                                        <span className={totals.net >= 0 ? 'text-green-700' : 'text-red-600'}>
                                                            {t('bank_transactions.total')}: {totals.net >= 0 ? '+' : ''}{formatNumber(totals.net)}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <Pagination
                            links={batches.links}
                            from={batches.from}
                            to={batches.to}
                            total={batches.total}
                        />
                    </>
                )}
            </div>

            {/* Add/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingBatch
                                ? t('bank_transactions.edit_transaction')
                                : t('bank_transactions.add_transaction')
                            }
                        </DialogTitle>
                        <DialogDescription>
                            {editingBatch
                                ? t('bank_transactions.edit_transaction_desc')
                                : t('bank_transactions.add_transaction_desc')
                            }
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* Shared fields: Date + Bank Account */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>{t('bank_transactions.date')}</Label>
                                <Input
                                    type="date"
                                    value={formHeader.date}
                                    onChange={(e) => setFormHeader(prev => ({ ...prev, date: e.target.value }))}
                                />
                                {errors.date && <p className="text-xs text-red-500">{errors.date}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <Label>{t('bank_transactions.bank_account')}</Label>
                                <Select value={formHeader.bank_account_id} onValueChange={(v) => setFormHeader(prev => ({ ...prev, bank_account_id: v }))}>
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
                        </div>

                        {/* Items */}
                        <div className="space-y-3">
                            {formItems.map((item, index) => (
                                <div key={index} className="border border-gray-200 rounded-lg p-3 space-y-3 relative">
                                    {/* Item header with remove button */}
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-medium text-gray-500">
                                            {t('bank_transactions.item_number', { number: String(index + 1) })}
                                        </span>
                                        {formItems.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-6 w-6 text-gray-400 hover:text-red-500"
                                                onClick={() => removeItem(index)}
                                            >
                                                <X className="w-3.5 h-3.5" />
                                            </Button>
                                        )}
                                    </div>

                                    {/* Row 1: Type + Amount + Currency */}
                                    <div className="grid grid-cols-[1fr_1fr_100px] gap-2">
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.type')}</Label>
                                            <Select value={item.type} onValueChange={(v) => updateItem(index, 'type', v)}>
                                                <SelectTrigger className="h-9">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="income">{t('bank_transactions.type_income')}</SelectItem>
                                                    <SelectItem value="expense">{t('bank_transactions.type_expense')}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {getItemError(index, 'type') && <p className="text-xs text-red-500">{getItemError(index, 'type')}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.amount')}</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                className="h-9"
                                                value={item.amount}
                                                onChange={(e) => updateItem(index, 'amount', e.target.value)}
                                                placeholder="0.00"
                                            />
                                            {getItemError(index, 'amount') && <p className="text-xs text-red-500">{getItemError(index, 'amount')}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.currency')}</Label>
                                            <Select value={item.currency} onValueChange={(v) => updateItem(index, 'currency', v)}>
                                                <SelectTrigger className="h-9">
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

                                    {/* Row 2: Invoice + Client */}
                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.invoice')}</Label>
                                            <Select value={item.invoice_id} onValueChange={(v) => handleItemInvoiceSelect(index, v)}>
                                                <SelectTrigger className="h-9">
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
                                            {item.invoice_id && item.invoice_id !== 'none' && item.invoice_id !== '' && (
                                                <p className="text-xs text-blue-600 flex items-center gap-1">
                                                    <Info className="w-3 h-3" />
                                                    {t('bank_transactions.invoice_will_be_marked_paid')}
                                                </p>
                                            )}
                                            {getItemError(index, 'invoice_id') && <p className="text-xs text-red-500">{getItemError(index, 'invoice_id')}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.client')}</Label>
                                            <Select value={item.client_id} onValueChange={(v) => updateItem(index, 'client_id', v)}>
                                                <SelectTrigger className="h-9">
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
                                            {getItemError(index, 'client_id') && <p className="text-xs text-red-500">{getItemError(index, 'client_id')}</p>}
                                        </div>
                                    </div>

                                    {/* Row 3: Description + Reference */}
                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.description')}</Label>
                                            <Input
                                                className="h-9"
                                                value={item.description}
                                                onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                placeholder={t('bank_transactions.description_placeholder')}
                                            />
                                            {getItemError(index, 'description') && <p className="text-xs text-red-500">{getItemError(index, 'description')}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs">{t('bank_transactions.reference')}</Label>
                                            <Input
                                                className="h-9"
                                                value={item.reference}
                                                onChange={(e) => updateItem(index, 'reference', e.target.value)}
                                                placeholder={t('bank_transactions.reference_placeholder')}
                                            />
                                            {getItemError(index, 'reference') && <p className="text-xs text-red-500">{getItemError(index, 'reference')}</p>}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Add item button */}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="w-full"
                                onClick={addItem}
                            >
                                <Plus className="w-4 h-4 mr-1" />
                                {t('bank_transactions.add_item')}
                            </Button>
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

            {/* View Dialog */}
            <Dialog open={!!viewBatch} onOpenChange={(open) => !open && setViewBatch(null)}>
                <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {viewBatch && t('bank_transactions.batch_label', { number: String(viewBatch.batch_number) })}
                        </DialogTitle>
                        <DialogDescription>
                            {t('bank_transactions.view_items')}
                        </DialogDescription>
                    </DialogHeader>

                    {viewBatch && (
                        <div className="space-y-4 py-2">
                            {/* Shared info */}
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-gray-500">{t('bank_transactions.date')}</span>
                                    <p className="font-medium">{formatDate(viewBatch.date)}</p>
                                </div>
                                <div>
                                    <span className="text-gray-500">{t('bank_transactions.bank_account')}</span>
                                    <p className="font-medium">
                                        {viewBatch.bank_account
                                            ? `${viewBatch.bank_account.bank_name} - ${viewBatch.bank_account.account_number} (${viewBatch.bank_account.currency})`
                                            : <span className="text-gray-400">&mdash;</span>
                                        }
                                    </p>
                                </div>
                            </div>

                            {/* Items table */}
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-50">
                                        <TableHead className="text-xs">{t('bank_transactions.type')}</TableHead>
                                        <TableHead className="text-xs">{t('bank_transactions.client')}</TableHead>
                                        <TableHead className="text-xs">{t('bank_transactions.invoice')}</TableHead>
                                        <TableHead className="text-xs text-right">{t('bank_transactions.amount')}</TableHead>
                                        <TableHead className="text-xs">{t('bank_transactions.description')}</TableHead>
                                        <TableHead className="text-xs">{t('bank_transactions.reference')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {viewBatch.items.map((transaction) => (
                                        <TableRow key={transaction.id}>
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
                                            <TableCell className="text-sm text-right font-medium tabular-nums">
                                                <span className={transaction.type === 'income' ? 'text-green-700' : 'text-red-600'}>
                                                    {transaction.type === 'income' ? '+' : '-'}{formatNumber(transaction.amount)}
                                                </span>
                                                <span className="text-gray-400 ml-1 text-xs">{transaction.currency}</span>
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {transaction.description || <span className="text-gray-400">&mdash;</span>}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {transaction.reference || <span className="text-gray-400">&mdash;</span>}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {/* Totals */}
                            {(() => {
                                const totals = getBatchTotals(viewBatch);
                                return (
                                    <div className="flex justify-end gap-4 text-sm font-medium pt-2 border-t">
                                        {totals.income > 0 && (
                                            <span className="text-green-700">
                                                {t('bank_transactions.type_income')}: +{formatNumber(totals.income)}
                                            </span>
                                        )}
                                        {totals.expense > 0 && (
                                            <span className="text-red-600">
                                                {t('bank_transactions.type_expense')}: -{formatNumber(totals.expense)}
                                            </span>
                                        )}
                                        {totals.income > 0 && totals.expense > 0 && (
                                            <span className={`border-l pl-4 ${totals.net >= 0 ? 'text-green-700' : 'text-red-600'}`}>
                                                {t('bank_transactions.total')}: {totals.net >= 0 ? '+' : ''}{formatNumber(totals.net)}
                                            </span>
                                        )}
                                    </div>
                                );
                            })()}
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setViewBatch(null)}>
                            {t('general.cancel')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirm */}
            <DeleteConfirmDialog
                open={!!deleteBatch}
                onOpenChange={(open) => !open && setDeleteBatch(null)}
                title={t('bank_transactions.delete_transaction')}
                description={t('bank_transactions.delete_transaction_confirm')}
                deleteUrl={deleteBatch ? `/bank-transactions/${deleteBatch.items[0].id}` : ''}
            />
        </AppLayout>
    );
}
