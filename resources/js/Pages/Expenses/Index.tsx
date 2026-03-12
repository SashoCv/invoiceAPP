import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { useSubscription } from '@/hooks/use-subscription';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import EmptyState from '@/Components/EmptyState';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import {
    Plus,
    Pencil,
    Trash2,
    Receipt,
    ChevronLeft,
    ChevronRight,
    Repeat,
    Tag,
    Power,
    PowerOff,
    CalendarRange,
    Download,
    FileText,
    Check,
    X,
} from 'lucide-react';
import { Textarea } from '@/Components/ui/textarea';
import type { Expense, ExpenseCategory, RecurringExpense, IncomingInvoice, SpendingAnalysisItem, Client } from '@/types';

interface ExpensesIndexProps {
    expenses: Expense[];
    recurringExpenses: RecurringExpense[];
    categories: ExpenseCategory[];
    incomingInvoices: IncomingInvoice[];
    clients: Pick<Client, 'id' | 'name' | 'company'>[];
    unpaidIncomingTotal: number;
    paidIncomingTotal: number;
    spendingAnalysis: SpendingAnalysisItem[];
    month: string;
    tab: string;
    monthlyTotal: number;
}

const CATEGORY_COLORS = [
    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
    '#ec4899', '#06b6d4', '#f97316', '#6366f1', '#14b8a6',
];

function MonthPicker({ month, onChange }: { month: string; onChange: (month: string) => void }) {
    const date = new Date(month + '-01');

    const monthNames: Record<string, string[]> = {
        mk: ['Јануари', 'Февруари', 'Март', 'Април', 'Мај', 'Јуни',
             'Јули', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'],
        en: ['January', 'February', 'March', 'April', 'May', 'June',
             'July', 'August', 'September', 'October', 'November', 'December'],
    };

    const { locale } = useTranslation();
    const names = monthNames[locale] || monthNames.en;
    const label = `${names[date.getMonth()]} ${date.getFullYear()}`;

    const navigate = (direction: -1 | 1) => {
        const d = new Date(date);
        d.setMonth(d.getMonth() + direction);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        onChange(`${y}-${m}`);
    };

    return (
        <div className="flex items-center gap-2">
            <Button variant="outline" size="icon" onClick={() => navigate(-1)} className="h-8 w-8">
                <ChevronLeft className="w-4 h-4" />
            </Button>
            <span className="text-sm font-medium min-w-[160px] text-center">{label}</span>
            <Button variant="outline" size="icon" onClick={() => navigate(1)} className="h-8 w-8">
                <ChevronRight className="w-4 h-4" />
            </Button>
        </div>
    );
}

export default function ExpensesIndex({
    expenses,
    recurringExpenses,
    categories,
    incomingInvoices,
    clients,
    unpaidIncomingTotal,
    paidIncomingTotal,
    spendingAnalysis,
    month,
    tab,
    monthlyTotal,
}: ExpensesIndexProps) {
    const { t } = useTranslation();
    const { isActive } = useSubscription();
    const [activeTab, setActiveTab] = useState(tab || 'monthly');

    useEffect(() => {
        setActiveTab(tab || 'monthly');
    }, [tab]);

    // Expense dialog state
    const [expenseDialogOpen, setExpenseDialogOpen] = useState(false);
    const [editingExpense, setEditingExpense] = useState<Expense | null>(null);
    const [expenseForm, setExpenseForm] = useState({
        name: '',
        description: '',
        amount: '',
        date: '',
        category_id: '',
    });
    const [expenseErrors, setExpenseErrors] = useState<Record<string, string>>({});
    const [expenseLoading, setExpenseLoading] = useState(false);

    // Recurring dialog state
    const [recurringDialogOpen, setRecurringDialogOpen] = useState(false);
    const [editingRecurring, setEditingRecurring] = useState<RecurringExpense | null>(null);
    const [recurringForm, setRecurringForm] = useState({
        name: '',
        description: '',
        amount: '',
        day_of_month: '',
        start_date: '',
        end_date: '',
        category_id: '',
    });
    const [recurringErrors, setRecurringErrors] = useState<Record<string, string>>({});
    const [recurringLoading, setRecurringLoading] = useState(false);

    // Category state
    const [categoryForm, setCategoryForm] = useState({ name: '', color: '#3b82f6' });
    const [editingCategory, setEditingCategory] = useState<ExpenseCategory | null>(null);
    const [categoryLoading, setCategoryLoading] = useState(false);

    // Incoming invoice dialog state
    const [incomingDialogOpen, setIncomingDialogOpen] = useState(false);
    const [editingIncoming, setEditingIncoming] = useState<IncomingInvoice | null>(null);
    const [incomingForm, setIncomingForm] = useState({
        supplier_name: '',
        client_id: '',
        invoice_number: '',
        amount: '',
        currency: 'MKD',
        date: '',
        due_date: '',
        status: 'unpaid' as 'unpaid' | 'paid',
        paid_date: '',
        notes: '',
        items: [] as Array<{ description: string; quantity: string; unit_price: string; tax_rate: string }>,
    });
    const [incomingErrors, setIncomingErrors] = useState<Record<string, string>>({});
    const [incomingLoading, setIncomingLoading] = useState(false);

    // Delete state
    const [deleteExpense, setDeleteExpense] = useState<Expense | null>(null);
    const [deleteRecurring, setDeleteRecurring] = useState<RecurringExpense | null>(null);
    const [deleteCategory, setDeleteCategory] = useState<ExpenseCategory | null>(null);
    const [deleteIncoming, setDeleteIncoming] = useState<IncomingInvoice | null>(null);

    const handleMonthChange = (newMonth: string) => {
        router.get('/expenses', { month: newMonth, tab: activeTab }, { preserveState: true });
    };

    const handleTabChange = (value: string) => {
        setActiveTab(value);
    };

    // --- Expense CRUD ---
    const openExpenseDialog = (expense?: Expense) => {
        if (expense) {
            setEditingExpense(expense);
            setExpenseForm({
                name: expense.name,
                description: expense.description || '',
                amount: String(expense.amount),
                date: expense.date,
                category_id: expense.category_id ? String(expense.category_id) : '',
            });
        } else {
            setEditingExpense(null);
            const today = new Date();
            const [y, m] = month.split('-');
            const defaultDate = `${y}-${m}-${String(today.getDate()).padStart(2, '0')}`;
            setExpenseForm({
                name: '',
                description: '',
                amount: '',
                date: defaultDate,
                category_id: '',
            });
        }
        setExpenseErrors({});
        setExpenseDialogOpen(true);
    };

    const submitExpense = () => {
        setExpenseLoading(true);
        const data = {
            ...expenseForm,
            category_id: expenseForm.category_id || null,
        };

        const url = editingExpense ? `/expenses/${editingExpense.id}` : '/expenses';
        const method = editingExpense ? 'put' : 'post';

        router[method](url, data, {
            preserveScroll: true,
            onSuccess: () => {
                setExpenseDialogOpen(false);
                setExpenseLoading(false);
            },
            onError: (errors) => {
                setExpenseErrors(errors);
                setExpenseLoading(false);
            },
        });
    };

    // --- Recurring CRUD ---
    const openRecurringDialog = (recurring?: RecurringExpense) => {
        if (recurring) {
            setEditingRecurring(recurring);
            setRecurringForm({
                name: recurring.name,
                description: recurring.description || '',
                amount: String(recurring.amount),
                day_of_month: String(recurring.day_of_month),
                start_date: recurring.start_date || '',
                end_date: recurring.end_date || '',
                category_id: recurring.category_id ? String(recurring.category_id) : '',
            });
        } else {
            setEditingRecurring(null);
            setRecurringForm({
                name: '',
                description: '',
                amount: '',
                day_of_month: '1',
                start_date: '',
                end_date: '',
                category_id: '',
            });
        }
        setRecurringErrors({});
        setRecurringDialogOpen(true);
    };

    const submitRecurring = () => {
        setRecurringLoading(true);
        const data = {
            ...recurringForm,
            category_id: recurringForm.category_id || null,
            start_date: recurringForm.start_date || null,
            end_date: recurringForm.end_date || null,
        };

        const url = editingRecurring
            ? `/expenses/recurring/${editingRecurring.id}`
            : '/expenses/recurring';
        const method = editingRecurring ? 'put' : 'post';

        router[method](url, data, {
            preserveScroll: true,
            onSuccess: () => {
                setRecurringDialogOpen(false);
                setRecurringLoading(false);
            },
            onError: (errors) => {
                setRecurringErrors(errors);
                setRecurringLoading(false);
            },
        });
    };

    const toggleRecurring = (recurring: RecurringExpense) => {
        router.post(`/expenses/recurring/${recurring.id}/toggle`, {}, {
            preserveScroll: true,
        });
    };

    // --- Category CRUD ---
    const startEditCategory = (category: ExpenseCategory) => {
        setEditingCategory(category);
        setCategoryForm({ name: category.name, color: category.color || '#3b82f6' });
    };

    const cancelEditCategory = () => {
        setEditingCategory(null);
        setCategoryForm({ name: '', color: '#3b82f6' });
    };

    const submitCategory = () => {
        setCategoryLoading(true);
        const url = editingCategory
            ? `/expenses/categories/${editingCategory.id}`
            : '/expenses/categories';
        const method = editingCategory ? 'put' : 'post';

        router[method](url, categoryForm, {
            preserveScroll: true,
            onSuccess: () => {
                setCategoryLoading(false);
                cancelEditCategory();
            },
            onError: () => {
                setCategoryLoading(false);
            },
        });
    };

    // --- Incoming Invoice CRUD ---
    const openIncomingDialog = (incoming?: IncomingInvoice) => {
        if (incoming) {
            setEditingIncoming(incoming);
            setIncomingForm({
                supplier_name: incoming.supplier_name,
                client_id: incoming.client_id ? String(incoming.client_id) : '',
                invoice_number: incoming.invoice_number || '',
                amount: String(incoming.amount),
                currency: incoming.currency,
                date: incoming.date,
                due_date: incoming.due_date || '',
                status: incoming.status,
                paid_date: incoming.paid_date || '',
                notes: incoming.notes || '',
                items: incoming.items?.map(item => ({
                    description: item.description,
                    quantity: String(item.quantity),
                    unit_price: String(item.unit_price),
                    tax_rate: String(item.tax_rate),
                })) || [],
            });
        } else {
            setEditingIncoming(null);
            const today = new Date();
            const [y, m] = month.split('-');
            const defaultDate = `${y}-${m}-${String(today.getDate()).padStart(2, '0')}`;
            setIncomingForm({
                supplier_name: '',
                client_id: '',
                invoice_number: '',
                amount: '',
                currency: 'MKD',
                date: defaultDate,
                due_date: '',
                status: 'unpaid',
                paid_date: '',
                notes: '',
                items: [] as Array<{ description: string; quantity: string; unit_price: string; tax_rate: string }>,
            });
        }
        setIncomingErrors({});
        setIncomingDialogOpen(true);
    };

    const incomingItemsTotal = incomingForm.items.reduce((sum, item) => {
        const subtotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
        const tax = subtotal * ((parseFloat(item.tax_rate) || 0) / 100);
        return sum + subtotal + tax;
    }, 0);

    const addIncomingItem = () => {
        setIncomingForm(prev => ({
            ...prev,
            items: [...prev.items, { description: '', quantity: '1', unit_price: '', tax_rate: '18' }],
        }));
    };

    const removeIncomingItem = (index: number) => {
        setIncomingForm(prev => ({
            ...prev,
            items: prev.items.filter((_, i) => i !== index),
        }));
    };

    const updateIncomingItem = (index: number, field: string, value: string) => {
        setIncomingForm(prev => {
            const newItems = [...prev.items];
            newItems[index] = { ...newItems[index], [field]: value };
            return { ...prev, items: newItems };
        });
    };

    const submitIncoming = () => {
        setIncomingLoading(true);
        const hasItems = incomingForm.items.length > 0;
        const data = {
            ...incomingForm,
            amount: hasItems ? incomingItemsTotal.toFixed(2) : incomingForm.amount,
            client_id: incomingForm.client_id || null,
            due_date: incomingForm.due_date || null,
            paid_date: incomingForm.paid_date || null,
            notes: incomingForm.notes || null,
            invoice_number: incomingForm.invoice_number || null,
            items: hasItems ? incomingForm.items : null,
        };

        const url = editingIncoming
            ? `/expenses/incoming/${editingIncoming.id}`
            : '/expenses/incoming';
        const method = editingIncoming ? 'put' : 'post';

        router[method](url, data, {
            preserveScroll: true,
            onSuccess: () => {
                setIncomingDialogOpen(false);
                setIncomingLoading(false);
            },
            onError: (errors) => {
                setIncomingErrors(errors);
                setIncomingLoading(false);
            },
        });
    };

    const toggleIncomingStatus = (incoming: IncomingInvoice) => {
        router.post(`/expenses/incoming/${incoming.id}/toggle-status`, {}, {
            preserveScroll: true,
        });
    };

    const getCategoryBadge = (category?: ExpenseCategory | null) => {
        if (!category) return null;
        return (
            <Badge variant="outline" className="gap-1.5">
                <span
                    className="w-2 h-2 rounded-full inline-block"
                    style={{ backgroundColor: category.color || '#6b7280' }}
                />
                {category.name}
            </Badge>
        );
    };

    return (
        <AppLayout>
            <Head title={t('expenses.title')} />

            <div>
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{t('expenses.title')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('expenses.subtitle')}</p>
                    </div>
                    <div className="flex items-center gap-4">
                        {activeTab !== 'categories' && activeTab !== 'recurring' && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={
                                        activeTab === 'incoming'
                                            ? `/expenses/export/incoming/csv?month=${month}`
                                            : `/expenses/export/csv?month=${month}`
                                    }
                                    className="flex items-center gap-2"
                                >
                                    <Download className="w-4 h-4" />
                                    {t('general.export_csv')}
                                </a>
                            </Button>
                        )}
                        {activeTab !== 'categories' && (
                            <MonthPicker month={month} onChange={handleMonthChange} />
                        )}
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList>
                        <TabsTrigger value="monthly">{t('expenses.tab_monthly')}</TabsTrigger>
                        <TabsTrigger value="incoming">{t('expenses.tab_incoming')}</TabsTrigger>
                        <TabsTrigger value="recurring">{t('expenses.tab_recurring')}</TabsTrigger>
                        <TabsTrigger value="categories">{t('expenses.tab_categories')}</TabsTrigger>
                    </TabsList>

                    {/* Tab 1: Monthly overview */}
                    <TabsContent value="monthly">
                        {/* Summary */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.total_amount')}</div>
                                    <div className="text-2xl font-bold text-gray-900">
                                        {formatNumber(monthlyTotal, 2)} ден.
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.expense_count')}</div>
                                    <div className="text-2xl font-bold text-gray-900">{expenses.length}</div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <div className="flex items-center justify-between p-4 border-b">
                                <h3 className="font-semibold text-gray-900">{t('expenses.tab_monthly')}</h3>
                                <Button size="sm" onClick={() => openExpenseDialog()} disabled={!isActive} className="gap-1.5">
                                    <Plus className="w-4 h-4" />
                                    {t('expenses.add_expense')}
                                </Button>
                            </div>
                            {expenses.length === 0 ? (
                                <CardContent className="p-0">
                                    <EmptyState
                                        icon={Receipt}
                                        title={t('expenses.no_expenses')}
                                        description={t('expenses.no_expenses_description')}
                                        action={{
                                            label: t('expenses.add_expense'),
                                            onClick: () => openExpenseDialog(),
                                        }}
                                    />
                                </CardContent>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('expenses.name')}</TableHead>
                                            <TableHead>{t('expenses.category')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.amount')}</TableHead>
                                            <TableHead>{t('expenses.date')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {expenses.map((expense) => (
                                            <TableRow key={expense.id}>
                                                <TableCell>
                                                    <div>
                                                        <span className="font-medium text-gray-900">
                                                            {expense.name}
                                                        </span>
                                                        {expense.description && (
                                                            <p className="text-xs text-gray-500 mt-0.5">
                                                                {expense.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{getCategoryBadge(expense.category)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(expense.amount, 2)}
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-600">
                                                    {formatDate(expense.date)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() => openExpenseDialog(expense)}
                                                            disabled={!isActive}
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700"
                                                            onClick={() => setDeleteExpense(expense)}
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
                            )}
                        </Card>
                    </TabsContent>

                    {/* Tab 2: Recurring expenses */}
                    <TabsContent value="recurring">
                        <Card>
                            <div className="flex items-center justify-between p-4 border-b">
                                <h3 className="font-semibold text-gray-900">{t('expenses.tab_recurring')}</h3>
                                <Button size="sm" onClick={() => openRecurringDialog()} disabled={!isActive} className="gap-1.5">
                                    <Plus className="w-4 h-4" />
                                    {t('expenses.add_recurring')}
                                </Button>
                            </div>
                            {recurringExpenses.length === 0 ? (
                                <CardContent className="p-0">
                                    <EmptyState
                                        icon={Repeat}
                                        title={t('expenses.no_recurring')}
                                        description={t('expenses.no_recurring_description')}
                                        action={{
                                            label: t('expenses.add_recurring'),
                                            onClick: () => openRecurringDialog(),
                                        }}
                                    />
                                </CardContent>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('expenses.name')}</TableHead>
                                            <TableHead>{t('expenses.category')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.amount')}</TableHead>
                                            <TableHead className="text-center">{t('expenses.day_of_month')}</TableHead>
                                            <TableHead>{t('expenses.period')}</TableHead>
                                            <TableHead className="text-center">{t('expenses.status')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recurringExpenses.map((recurring) => (
                                            <TableRow key={recurring.id} className={!recurring.is_active ? 'opacity-50' : ''}>
                                                <TableCell>
                                                    <div>
                                                        <span className="font-medium text-gray-900">
                                                            {recurring.name}
                                                        </span>
                                                        {recurring.description && (
                                                            <p className="text-xs text-gray-500 mt-0.5">
                                                                {recurring.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{getCategoryBadge(recurring.category)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(recurring.amount, 2)}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {recurring.day_of_month}
                                                </TableCell>
                                                <TableCell>
                                                    {recurring.start_date || recurring.end_date ? (
                                                        <div className="flex items-center gap-1.5 text-xs text-gray-500">
                                                            <CalendarRange className="w-3.5 h-3.5" />
                                                            <span>
                                                                {recurring.start_date ? formatDate(recurring.start_date) : '...'}
                                                                {' — '}
                                                                {recurring.end_date ? formatDate(recurring.end_date) : '...'}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-gray-400">{t('expenses.no_limit')}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => toggleRecurring(recurring)}
                                                        className={recurring.is_active ? 'text-green-600' : 'text-gray-400'}
                                                    >
                                                        {recurring.is_active ? (
                                                            <><Power className="w-4 h-4 mr-1" /> {t('expenses.active')}</>
                                                        ) : (
                                                            <><PowerOff className="w-4 h-4 mr-1" /> {t('expenses.inactive')}</>
                                                        )}
                                                    </Button>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() => openRecurringDialog(recurring)}
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700"
                                                            onClick={() => setDeleteRecurring(recurring)}
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </Card>
                    </TabsContent>

                    {/* Tab 3: Categories */}
                    <TabsContent value="categories">
                        <Card>
                            <div className="p-4 border-b">
                                <h3 className="font-semibold text-gray-900">{t('expenses.tab_categories')}</h3>
                            </div>
                            <CardContent className="pt-6">
                                {/* Add/Edit category form */}
                                <div className="flex items-end gap-3 mb-6">
                                    <div className="flex-1">
                                        <Label className="mb-2 block">{t('expenses.category_name')}</Label>
                                        <Input
                                            value={categoryForm.name}
                                            onChange={(e) => setCategoryForm({ ...categoryForm, name: e.target.value })}
                                            placeholder={t('expenses.category_name_placeholder')}
                                        />
                                    </div>
                                    <div>
                                        <Label className="mb-2 block">{t('expenses.color')}</Label>
                                        <div className="flex gap-1">
                                            {CATEGORY_COLORS.map((color) => (
                                                <button
                                                    key={color}
                                                    type="button"
                                                    onClick={() => setCategoryForm({ ...categoryForm, color })}
                                                    className="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110"
                                                    style={{
                                                        backgroundColor: color,
                                                        borderColor: categoryForm.color === color ? '#1f2937' : 'transparent',
                                                    }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            onClick={submitCategory}
                                            disabled={!categoryForm.name.trim() || categoryLoading}
                                            size="sm"
                                        >
                                            {editingCategory ? t('general.save') : t('expenses.add_category')}
                                        </Button>
                                        {editingCategory && (
                                            <Button variant="outline" size="sm" onClick={cancelEditCategory}>
                                                {t('general.cancel')}
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {/* Category list */}
                                {categories.length === 0 ? (
                                    <EmptyState
                                        icon={Tag}
                                        title={t('expenses.no_categories')}
                                        description={t('expenses.no_categories_description')}
                                    />
                                ) : (
                                    <div className="space-y-2">
                                        {categories.map((category) => (
                                            <div
                                                key={category.id}
                                                className="flex items-center justify-between p-3 rounded-lg border"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <span
                                                        className="w-4 h-4 rounded-full"
                                                        style={{ backgroundColor: category.color || '#6b7280' }}
                                                    />
                                                    <span className="font-medium text-gray-900">{category.name}</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        onClick={() => startEditCategory(category)}
                                                    >
                                                        <Pencil className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8 text-red-600 hover:text-red-700"
                                                        onClick={() => setDeleteCategory(category)}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab 4: Incoming invoices */}
                    <TabsContent value="incoming">
                        {/* Summary */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.unpaid_total')}</div>
                                    <div className="text-2xl font-bold text-red-600">
                                        {formatNumber(unpaidIncomingTotal, 2)} ден.
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.paid_total')}</div>
                                    <div className="text-2xl font-bold text-green-600">
                                        {formatNumber(paidIncomingTotal, 2)} ден.
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.unpaid_count')}</div>
                                    <div className="text-2xl font-bold text-gray-900">
                                        {incomingInvoices.filter(i => i.status === 'unpaid').length}
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-sm text-gray-500">{t('expenses.paid_count')}</div>
                                    <div className="text-2xl font-bold text-green-600">
                                        {incomingInvoices.filter(i => i.status === 'paid').length}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {spendingAnalysis.length > 0 && (
                            <Card className="mb-6">
                                <div className="p-4 border-b">
                                    <h3 className="font-semibold text-gray-900">{t('expenses.spending_analysis')}</h3>
                                </div>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('expenses.item_description')}</TableHead>
                                            <TableHead className="text-center">{t('expenses.occurrence_count')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.total_spent')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {spendingAnalysis.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium capitalize">{item.description}</TableCell>
                                                <TableCell className="text-center">{item.count}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(item.total_amount, 2)} ден.
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </Card>
                        )}

                        <Card>
                            <div className="flex items-center justify-between p-4 border-b">
                                <h3 className="font-semibold text-gray-900">{t('expenses.tab_incoming')}</h3>
                                <Button size="sm" onClick={() => openIncomingDialog()} disabled={!isActive} className="gap-1.5">
                                    <Plus className="w-4 h-4" />
                                    {t('expenses.add_incoming')}
                                </Button>
                            </div>
                            {incomingInvoices.length === 0 ? (
                                <CardContent className="p-0">
                                    <EmptyState
                                        icon={FileText}
                                        title={t('expenses.no_incoming')}
                                        description={t('expenses.no_incoming_description')}
                                        action={{
                                            label: t('expenses.add_incoming'),
                                            onClick: () => openIncomingDialog(),
                                        }}
                                    />
                                </CardContent>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('expenses.supplier_name')}</TableHead>
                                            <TableHead>{t('expenses.invoice_number')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.amount')}</TableHead>
                                            <TableHead>{t('expenses.date')}</TableHead>
                                            <TableHead>{t('expenses.due_date')}</TableHead>
                                            <TableHead className="text-center">{t('expenses.status')}</TableHead>
                                            <TableHead className="text-right">{t('expenses.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {incomingInvoices.map((incoming) => (
                                            <TableRow key={incoming.id}>
                                                <TableCell>
                                                    <div>
                                                        <span className="font-medium text-gray-900">
                                                            {incoming.supplier_name}
                                                        </span>
                                                        {incoming.client && (
                                                            <p className="text-xs text-gray-500 mt-0.5">
                                                                {incoming.client.company || incoming.client.name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-600">
                                                    {incoming.invoice_number || '—'}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatNumber(incoming.amount, 2)} {incoming.currency === 'MKD' ? 'ден.' : incoming.currency}
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-600">
                                                    {formatDate(incoming.date)}
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-600">
                                                    {incoming.due_date ? formatDate(incoming.due_date) : '—'}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => toggleIncomingStatus(incoming)}
                                                        disabled={!isActive}
                                                        className="gap-1"
                                                    >
                                                        {incoming.status === 'paid' ? (
                                                            <Badge className="bg-green-100 text-green-700 hover:bg-green-200 gap-1">
                                                                <Check className="w-3 h-3" />
                                                                {t('expenses.status_paid')}
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="destructive" className="gap-1">
                                                                <X className="w-3 h-3" />
                                                                {t('expenses.status_unpaid')}
                                                            </Badge>
                                                        )}
                                                    </Button>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() => openIncomingDialog(incoming)}
                                                            disabled={!isActive}
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700"
                                                            onClick={() => setDeleteIncoming(incoming)}
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
                            )}
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Expense Dialog */}
            <Dialog open={expenseDialogOpen} onOpenChange={setExpenseDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingExpense ? t('expenses.edit_expense') : t('expenses.add_expense')}
                        </DialogTitle>
                        <DialogDescription>
                            {editingExpense ? t('expenses.edit_expense_description') : t('expenses.add_expense_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-2 block">{t('expenses.name')}</Label>
                            <Input
                                value={expenseForm.name}
                                onChange={(e) => setExpenseForm({ ...expenseForm, name: e.target.value })}
                            />
                            {expenseErrors.name && (
                                <p className="text-sm text-red-600 mt-1">{expenseErrors.name}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.description')}</Label>
                            <Input
                                value={expenseForm.description}
                                onChange={(e) => setExpenseForm({ ...expenseForm, description: e.target.value })}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.amount')}</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={expenseForm.amount}
                                    onChange={(e) => setExpenseForm({ ...expenseForm, amount: e.target.value })}
                                />
                                {expenseErrors.amount && (
                                    <p className="text-sm text-red-600 mt-1">{expenseErrors.amount}</p>
                                )}
                            </div>
                            <div>
                                <Label className="mb-2 block">{t('expenses.date')}</Label>
                                <Input
                                    type="date"
                                    value={expenseForm.date}
                                    onChange={(e) => setExpenseForm({ ...expenseForm, date: e.target.value })}
                                />
                                {expenseErrors.date && (
                                    <p className="text-sm text-red-600 mt-1">{expenseErrors.date}</p>
                                )}
                            </div>
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.category')}</Label>
                            <Select
                                value={expenseForm.category_id}
                                onValueChange={(val) => setExpenseForm({ ...expenseForm, category_id: val === '__none__' ? '' : val })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('expenses.no_category')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">{t('expenses.no_category')}</SelectItem>
                                    {categories.map((cat) => (
                                        <SelectItem key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setExpenseDialogOpen(false)} disabled={expenseLoading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitExpense} loading={expenseLoading}>
                            {editingExpense ? t('general.save') : t('general.create')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Recurring Expense Dialog */}
            <Dialog open={recurringDialogOpen} onOpenChange={setRecurringDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingRecurring ? t('expenses.edit_recurring') : t('expenses.add_recurring')}
                        </DialogTitle>
                        <DialogDescription>
                            {editingRecurring ? t('expenses.edit_recurring_description') : t('expenses.add_recurring_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-2 block">{t('expenses.name')}</Label>
                            <Input
                                value={recurringForm.name}
                                onChange={(e) => setRecurringForm({ ...recurringForm, name: e.target.value })}
                            />
                            {recurringErrors.name && (
                                <p className="text-sm text-red-600 mt-1">{recurringErrors.name}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.description')}</Label>
                            <Input
                                value={recurringForm.description}
                                onChange={(e) => setRecurringForm({ ...recurringForm, description: e.target.value })}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.amount')}</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={recurringForm.amount}
                                    onChange={(e) => setRecurringForm({ ...recurringForm, amount: e.target.value })}
                                />
                                {recurringErrors.amount && (
                                    <p className="text-sm text-red-600 mt-1">{recurringErrors.amount}</p>
                                )}
                            </div>
                            <div>
                                <Label className="mb-2 block">{t('expenses.day_of_month')}</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="31"
                                    value={recurringForm.day_of_month}
                                    onChange={(e) => setRecurringForm({ ...recurringForm, day_of_month: e.target.value })}
                                />
                                {recurringErrors.day_of_month && (
                                    <p className="text-sm text-red-600 mt-1">{recurringErrors.day_of_month}</p>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.start_date')}</Label>
                                <Input
                                    type="date"
                                    value={recurringForm.start_date}
                                    onChange={(e) => setRecurringForm({ ...recurringForm, start_date: e.target.value })}
                                />
                                {recurringErrors.start_date && (
                                    <p className="text-sm text-red-600 mt-1">{recurringErrors.start_date}</p>
                                )}
                            </div>
                            <div>
                                <Label className="mb-2 block">{t('expenses.end_date')}</Label>
                                <Input
                                    type="date"
                                    value={recurringForm.end_date}
                                    onChange={(e) => setRecurringForm({ ...recurringForm, end_date: e.target.value })}
                                />
                                {recurringErrors.end_date && (
                                    <p className="text-sm text-red-600 mt-1">{recurringErrors.end_date}</p>
                                )}
                            </div>
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.category')}</Label>
                            <Select
                                value={recurringForm.category_id}
                                onValueChange={(val) => setRecurringForm({ ...recurringForm, category_id: val === '__none__' ? '' : val })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('expenses.no_category')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">{t('expenses.no_category')}</SelectItem>
                                    {categories.map((cat) => (
                                        <SelectItem key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setRecurringDialogOpen(false)} disabled={recurringLoading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitRecurring} loading={recurringLoading}>
                            {editingRecurring ? t('general.save') : t('general.create')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Dialogs */}
            <DeleteConfirmDialog
                open={!!deleteExpense}
                onOpenChange={() => setDeleteExpense(null)}
                title={t('expenses.delete_expense')}
                description={t('expenses.delete_expense_confirm')}
                deleteUrl={deleteExpense ? `/expenses/${deleteExpense.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!deleteRecurring}
                onOpenChange={() => setDeleteRecurring(null)}
                title={t('expenses.delete_recurring')}
                description={t('expenses.delete_recurring_confirm')}
                deleteUrl={deleteRecurring ? `/expenses/recurring/${deleteRecurring.id}` : ''}
            />

            <DeleteConfirmDialog
                open={!!deleteCategory}
                onOpenChange={() => setDeleteCategory(null)}
                title={t('expenses.delete_category')}
                description={t('expenses.delete_category_confirm')}
                deleteUrl={deleteCategory ? `/expenses/categories/${deleteCategory.id}` : ''}
            />

            {/* Incoming Invoice Dialog */}
            <Dialog open={incomingDialogOpen} onOpenChange={setIncomingDialogOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingIncoming ? t('expenses.edit_incoming') : t('expenses.add_incoming')}
                        </DialogTitle>
                        <DialogDescription>
                            {editingIncoming ? t('expenses.edit_incoming_description') : t('expenses.add_incoming_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-2 block">{t('expenses.supplier_name')}</Label>
                            <Input
                                value={incomingForm.supplier_name}
                                onChange={(e) => setIncomingForm({ ...incomingForm, supplier_name: e.target.value })}
                            />
                            {incomingErrors.supplier_name && (
                                <p className="text-sm text-red-600 mt-1">{incomingErrors.supplier_name}</p>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.select_client')}</Label>
                            <Select
                                value={incomingForm.client_id}
                                onValueChange={(val) => setIncomingForm({ ...incomingForm, client_id: val === '__none__' ? '' : val })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('expenses.no_client')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">{t('expenses.no_client')}</SelectItem>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.company || client.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.invoice_number')}</Label>
                            <Input
                                value={incomingForm.invoice_number}
                                onChange={(e) => setIncomingForm({ ...incomingForm, invoice_number: e.target.value })}
                            />
                        </div>
                        {/* Line Items */}
                        <div className="border rounded-lg p-3 space-y-3">
                            <div className="flex items-center justify-between">
                                <Label className="text-sm font-medium">{t('expenses.line_items')}</Label>
                                <Button type="button" variant="outline" size="sm" onClick={addIncomingItem} className="gap-1 h-7 text-xs">
                                    <Plus className="w-3 h-3" />
                                    {t('expenses.add_item')}
                                </Button>
                            </div>
                            {incomingForm.items.length > 0 && (
                                <div className="space-y-2">
                                    <div className="grid grid-cols-[2fr_1fr_1fr_80px_1fr_32px] gap-2 text-xs text-gray-500 font-medium px-1">
                                        <span>{t('expenses.item_description')}</span>
                                        <span>{t('expenses.quantity')}</span>
                                        <span>{t('expenses.unit_price')}</span>
                                        <span>{t('expenses.tax_rate')}</span>
                                        <span className="text-right">{t('expenses.item_total')}</span>
                                        <span></span>
                                    </div>
                                    {incomingForm.items.map((item, index) => {
                                        const subtotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                                        const tax = subtotal * ((parseFloat(item.tax_rate) || 0) / 100);
                                        return (
                                        <div key={index} className="grid grid-cols-[2fr_1fr_1fr_80px_1fr_32px] gap-2 items-center">
                                            <Input
                                                value={item.description}
                                                onChange={(e) => updateIncomingItem(index, 'description', e.target.value)}
                                                placeholder={t('expenses.item_description')}
                                                className="h-8 text-sm"
                                            />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                value={item.quantity}
                                                onChange={(e) => updateIncomingItem(index, 'quantity', e.target.value)}
                                                className="h-8 text-sm"
                                            />
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={item.unit_price}
                                                onChange={(e) => updateIncomingItem(index, 'unit_price', e.target.value)}
                                                className="h-8 text-sm"
                                            />
                                            <Input
                                                type="number"
                                                step="1"
                                                min="0"
                                                max="100"
                                                value={item.tax_rate}
                                                onChange={(e) => updateIncomingItem(index, 'tax_rate', e.target.value)}
                                                className="h-8 text-sm"
                                            />
                                            <div className="text-sm text-right font-medium text-gray-700">
                                                {formatNumber(subtotal + tax, 2)}
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-red-500 hover:text-red-700"
                                                onClick={() => removeIncomingItem(index)}
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                        );
                                    })}
                                    <div className="flex justify-end pt-2 border-t text-sm font-semibold text-gray-900 pr-12">
                                        {t('expenses.items_total')}: {formatNumber(incomingItemsTotal, 2)}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.amount')}</Label>
                                {incomingForm.items.length > 0 ? (
                                    <div>
                                        <Input
                                            type="number"
                                            value={incomingItemsTotal.toFixed(2)}
                                            disabled
                                            className="mt-0"
                                        />
                                        <p className="text-xs text-gray-400 mt-1">{t('expenses.auto_calculated')}</p>
                                    </div>
                                ) : (
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={incomingForm.amount}
                                        onChange={(e) => setIncomingForm({ ...incomingForm, amount: e.target.value })}
                                    />
                                )}
                                {incomingErrors.amount && (
                                    <p className="text-sm text-red-600 mt-1">{incomingErrors.amount}</p>
                                )}
                            </div>
                            <div>
                                <Label className="mb-2 block">{t('expenses.currency')}</Label>
                                <Select
                                    value={incomingForm.currency}
                                    onValueChange={(val) => setIncomingForm({ ...incomingForm, currency: val })}
                                >
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
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.date')}</Label>
                                <Input
                                    type="date"
                                    value={incomingForm.date}
                                    onChange={(e) => setIncomingForm({ ...incomingForm, date: e.target.value })}
                                />
                                {incomingErrors.date && (
                                    <p className="text-sm text-red-600 mt-1">{incomingErrors.date}</p>
                                )}
                            </div>
                            <div>
                                <Label className="mb-2 block">{t('expenses.due_date')}</Label>
                                <Input
                                    type="date"
                                    value={incomingForm.due_date}
                                    onChange={(e) => setIncomingForm({ ...incomingForm, due_date: e.target.value })}
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="mb-2 block">{t('expenses.status')}</Label>
                                <Select
                                    value={incomingForm.status}
                                    onValueChange={(val) => setIncomingForm({
                                        ...incomingForm,
                                        status: val as 'unpaid' | 'paid',
                                        paid_date: val === 'paid' && !incomingForm.paid_date
                                            ? new Date().toISOString().split('T')[0]
                                            : val === 'unpaid' ? '' : incomingForm.paid_date,
                                    })}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unpaid">{t('expenses.status_unpaid')}</SelectItem>
                                        <SelectItem value="paid">{t('expenses.status_paid')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {incomingForm.status === 'paid' && (
                                <div>
                                    <Label className="mb-2 block">{t('expenses.paid_date')}</Label>
                                    <Input
                                        type="date"
                                        value={incomingForm.paid_date}
                                        onChange={(e) => setIncomingForm({ ...incomingForm, paid_date: e.target.value })}
                                    />
                                </div>
                            )}
                        </div>
                        <div>
                            <Label className="mb-2 block">{t('expenses.notes')}</Label>
                            <Textarea
                                value={incomingForm.notes}
                                onChange={(e) => setIncomingForm({ ...incomingForm, notes: e.target.value })}
                                rows={3}
                            />
                        </div>
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setIncomingDialogOpen(false)} disabled={incomingLoading}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={submitIncoming} loading={incomingLoading}>
                            {editingIncoming ? t('general.save') : t('general.create')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <DeleteConfirmDialog
                open={!!deleteIncoming}
                onOpenChange={() => setDeleteIncoming(null)}
                title={t('expenses.delete_incoming')}
                description={t('expenses.delete_incoming_confirm')}
                deleteUrl={deleteIncoming ? `/expenses/incoming/${deleteIncoming.id}` : ''}
            />
        </AppLayout>
    );
}
