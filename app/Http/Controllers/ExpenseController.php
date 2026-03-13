<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IncomingInvoice;
use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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
                'storeIncoming', 'updateIncoming', 'destroyIncoming', 'toggleIncomingStatus',
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

        $incomingInvoices = $request->user()->incomingInvoices()
            ->with(['client', 'items'])
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->get();

        $spendingAnalysis = DB::table('incoming_invoice_items')
            ->join('incoming_invoices', 'incoming_invoices.id', '=', 'incoming_invoice_items.incoming_invoice_id')
            ->where('incoming_invoices.user_id', $request->user()->id)
            ->whereBetween('incoming_invoices.date', [$startOfMonth, $endOfMonth])
            ->selectRaw('LOWER(incoming_invoice_items.description) as description, SUM(incoming_invoice_items.total) as total_amount, COUNT(*) as count')
            ->groupByRaw('LOWER(incoming_invoice_items.description)')
            ->orderByDesc('total_amount')
            ->get();

        $unpaidIncomingTotal = $request->user()->incomingInvoices()
            ->where('status', 'unpaid')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $paidIncomingTotal = $request->user()->incomingInvoices()
            ->where('status', 'paid')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $clients = $request->user()->clients()
            ->orderBy('name')
            ->get(['id', 'name', 'company']);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'recurringExpenses' => $recurringExpenses,
            'categories' => $categories,
            'incomingInvoices' => $incomingInvoices,
            'clients' => $clients,
            'unpaidIncomingTotal' => (float) $unpaidIncomingTotal,
            'paidIncomingTotal' => (float) $paidIncomingTotal,
            'spendingAnalysis' => $spendingAnalysis,
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

    // --- Incoming Invoices ---

    private function syncIncomingExpense(IncomingInvoice $invoice): void
    {
        $category = ExpenseCategory::firstOrCreate(
            ['user_id' => $invoice->user_id, 'name' => 'Влезни фактури'],
            ['user_id' => $invoice->user_id, 'color' => '#f59e0b'],
        );

        $name = $invoice->supplier_name;
        if ($invoice->invoice_number) {
            $name .= ' #' . $invoice->invoice_number;
        }
        if ($invoice->client_id) {
            $client = $invoice->client;
            if ($client) {
                $name .= ' - ' . ($client->company ?: $client->name);
            }
        }

        Expense::updateOrCreate(
            ['incoming_invoice_id' => $invoice->id],
            [
                'user_id' => $invoice->user_id,
                'category_id' => $category->id,
                'name' => $name,
                'amount' => $invoice->amount,
                'date' => $invoice->date,
            ],
        );
    }

    public function storeIncoming(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required_without:items', 'nullable', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:3'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:unpaid,paid'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $invoice = $request->user()->incomingInvoices()->create($validated);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $invoice->items()->create($item);
            }
            $invoice->update(['amount' => $invoice->items()->sum('total')]);
        }

        $this->syncIncomingExpense($invoice);

        $month = Carbon::parse($validated['date'])->format('Y-m');

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'incoming'])
            ->with('success', __('toast.incoming_invoice_created'));
    }

    public function updateIncoming(Request $request, IncomingInvoice $incomingInvoice): RedirectResponse
    {
        $this->authorize('update', $incomingInvoice);

        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required_without:items', 'nullable', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:3'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:unpaid,paid'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $incomingInvoice->update($validated);

        $incomingInvoice->items()->delete();
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $incomingInvoice->items()->create($item);
            }
            $incomingInvoice->update(['amount' => $incomingInvoice->items()->sum('total')]);
        }

        $this->syncIncomingExpense($incomingInvoice);

        $month = Carbon::parse($validated['date'])->format('Y-m');

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'incoming'])
            ->with('success', __('toast.incoming_invoice_updated'));
    }

    public function destroyIncoming(IncomingInvoice $incomingInvoice): RedirectResponse
    {
        $this->authorize('delete', $incomingInvoice);

        $month = $incomingInvoice->date->format('Y-m');
        Expense::where('incoming_invoice_id', $incomingInvoice->id)->delete();
        $incomingInvoice->delete();

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'incoming'])
            ->with('success', __('toast.incoming_invoice_deleted'));
    }

    public function toggleIncomingStatus(IncomingInvoice $incomingInvoice): RedirectResponse
    {
        $this->authorize('update', $incomingInvoice);

        $newStatus = $incomingInvoice->status === 'paid' ? 'unpaid' : 'paid';
        $incomingInvoice->update([
            'status' => $newStatus,
            'paid_date' => $newStatus === 'paid' ? now()->toDateString() : null,
        ]);

        $month = $incomingInvoice->date->format('Y-m');

        return redirect()->route('expenses.index', ['month' => $month, 'tab' => 'incoming'])
            ->with('success', __('toast.incoming_invoice_updated'));
    }
}
