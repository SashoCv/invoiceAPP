<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: [
                'store', 'update', 'destroy',
                'storeCategory', 'updateCategory', 'destroyCategory',
                'storeRecurring', 'updateRecurring', 'destroyRecurring', 'toggleRecurring',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        $month = $request->get('month', now()->format('Y-m'));
        $tab = $request->get('tab', 'monthly');
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $expenses = $request->user()->expenses()
            ->with('category')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->get();

        $recurringExpenses = $request->user()->recurringExpenses()
            ->with('category')
            ->orderBy('name')
            ->get();

        $categories = $request->user()->expenseCategories()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $monthlyTotal = $expenses->sum('amount');

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'recurringExpenses' => $recurringExpenses,
            'categories' => $categories,
            'month' => $month,
            'tab' => $tab,
            'monthlyTotal' => (float) $monthlyTotal,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
        ]);

        $request->user()->expenses()->create($validated);

        $month = Carbon::parse($validated['date'])->format('Y-m');

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'monthly'])
            ->with('success', __('toast.expense_created'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
        ]);

        $expense->update($validated);

        $month = Carbon::parse($validated['date'])->format('Y-m');

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'monthly'])
            ->with('success', __('toast.expense_updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $month = $expense->date->format('Y-m');
        $expense->delete();

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'monthly'])
            ->with('success', __('toast.expense_deleted'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $request->user()->expenseCategories()->create($validated);

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', __('toast.expense_category_created'));
    }

    public function updateCategory(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('update', $expenseCategory);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $expenseCategory->update($validated);

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', __('toast.expense_category_updated'));
    }

    public function destroyCategory(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('delete', $expenseCategory);

        $expenseCategory->delete();

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', __('toast.expense_category_deleted'));
    }

    public function storeRecurring(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
        ]);

        $recurring = $request->user()->recurringExpenses()->create($validated);

        // Generate expense for current month immediately
        $now = Carbon::now();
        $day = min($recurring->day_of_month, $now->daysInMonth);
        $date = Carbon::createFromDate($now->year, $now->month, $day);

        $shouldGenerate = true;
        if ($recurring->start_date && $now->startOfMonth()->lt($recurring->start_date->startOfMonth())) {
            $shouldGenerate = false;
        }
        if ($recurring->end_date && $now->startOfMonth()->gt($recurring->end_date->startOfMonth())) {
            $shouldGenerate = false;
        }

        if ($shouldGenerate) {
            $recurring->expenses()->create([
                'user_id' => $recurring->user_id,
                'category_id' => $recurring->category_id,
                'name' => $recurring->name,
                'description' => $recurring->description,
                'amount' => $recurring->amount,
                'date' => $date,
            ]);
        }

        return redirect()->route('expenses.index', ['tab' => 'recurring'])
            ->with('success', __('toast.recurring_expense_created'));
    }

    public function updateRecurring(Request $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorize('update', $recurringExpense);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
        ]);

        $recurringExpense->update($validated);

        return redirect()->route('expenses.index', ['tab' => 'recurring'])
            ->with('success', __('toast.recurring_expense_updated'));
    }

    public function destroyRecurring(RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorize('delete', $recurringExpense);

        $recurringExpense->delete();

        return redirect()->route('expenses.index', ['tab' => 'recurring'])
            ->with('success', __('toast.recurring_expense_deleted'));
    }

    public function toggleRecurring(RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorize('update', $recurringExpense);

        $recurringExpense->update(['is_active' => !$recurringExpense->is_active]);

        return redirect()->route('expenses.index', ['tab' => 'recurring'])
            ->with('success', __('toast.recurring_expense_updated'));
    }
}
