import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
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
} from 'lucide-react';
import type { Expense, ExpenseCategory, RecurringExpense } from '@/types';

interface ExpensesIndexProps {
    expenses: Expense[];
    recurringExpenses: RecurringExpense[];
    categories: ExpenseCategory[];
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
    month,
    tab,
    monthlyTotal,
}: ExpensesIndexProps) {
    const { t } = useTranslation();
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

    // Delete state
    const [deleteExpense, setDeleteExpense] = useState<Expense | null>(null);
    const [deleteRecurring, setDeleteRecurring] = useState<RecurringExpense | null>(null);
    const [deleteCategory, setDeleteCategory] = useState<ExpenseCategory | null>(null);

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
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={`/expenses/export/csv?month=${month}`}
                                className="flex items-center gap-2"
                            >
                                <Download className="w-4 h-4" />
                                {t('general.export_csv')}
                            </a>
                        </Button>
                        <MonthPicker month={month} onChange={handleMonthChange} />
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList>
                        <TabsTrigger value="monthly">{t('expenses.tab_monthly')}</TabsTrigger>
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
                                <Button size="sm" onClick={() => openExpenseDialog()} className="gap-1.5">
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
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700"
                                                            onClick={() => setDeleteExpense(expense)}
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
                                <Button size="sm" onClick={() => openRecurringDialog()} className="gap-1.5">
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
        </AppLayout>
    );
}
