<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class BankTransactionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('subscribed', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->bankTransactions()
            ->with(['bankAccount', 'invoice.client', 'client']);

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('bank_account')) {
            $query->where('bank_account_id', $request->bank_account);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['date', 'amount'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'date';
        }
        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        $transactions = $query->paginate(20)->withQueryString();

        $bankAccounts = $request->user()->bankAccounts()->orderBy('bank_name')->get();

        $unpaidInvoices = $request->user()->invoices()
            ->with('client')
            ->whereIn('status', ['draft', 'sent', 'unpaid', 'overdue'])
            ->orderBy('invoice_number', 'desc')
            ->get();

        $clients = $request->user()->clients()->orderBy('name')->get();

        // Calculate total income with same filters
        $incomeQuery = $request->user()->bankTransactions()->where('type', 'income');
        if ($request->filled('date_from')) {
            $incomeQuery->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $incomeQuery->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('bank_account')) {
            $incomeQuery->where('bank_account_id', $request->bank_account);
        }
        $totalIncome = (float) $incomeQuery->sum('amount');

        return Inertia::render('BankTransactions/Index', [
            'transactions' => $transactions,
            'bankAccounts' => $bankAccounts,
            'unpaidInvoices' => $unpaidInvoices,
            'clients' => $clients,
            'totalIncome' => $totalIncome,
            'filters' => [
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'bank_account' => $request->get('bank_account', ''),
                'type' => $request->get('type', ''),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $transaction = $request->user()->bankTransactions()->create($validated);

        // Auto-mark invoice as paid
        if ($transaction->invoice_id) {
            $invoice = Invoice::find($transaction->invoice_id);
            if ($invoice && in_array($invoice->status, ['draft', 'sent', 'unpaid', 'overdue'])) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_date' => $transaction->date,
                ]);
            }
        }

        // Auto-create expense for expense-type transactions
        if ($transaction->type === 'expense') {
            $expenseName = $transaction->description ?: $transaction->reference ?: __('bank_transactions.type_expense');
            $request->user()->expenses()->create([
                'bank_transaction_id' => $transaction->id,
                'name' => $expenseName,
                'description' => $transaction->reference ? $transaction->reference : null,
                'amount' => $transaction->amount,
                'date' => $transaction->date,
            ]);
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_created'));
    }

    public function update(Request $request, BankTransaction $bankTransaction): RedirectResponse
    {
        $this->authorize('update', $bankTransaction);

        $oldInvoiceId = $bankTransaction->invoice_id;

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'in:MKD,EUR,USD'],
            'date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $bankTransaction->update($validated);

        // Handle invoice link changes
        $newInvoiceId = $validated['invoice_id'] ?? null;

        // Revert old invoice if unlinked and no other transactions reference it
        if ($oldInvoiceId && $oldInvoiceId !== $newInvoiceId) {
            $otherTransactions = BankTransaction::where('invoice_id', $oldInvoiceId)
                ->where('id', '!=', $bankTransaction->id)
                ->exists();

            if (!$otherTransactions) {
                $oldInvoice = Invoice::find($oldInvoiceId);
                if ($oldInvoice && $oldInvoice->status === 'paid') {
                    $oldInvoice->update([
                        'status' => 'unpaid',
                        'paid_date' => null,
                    ]);
                }
            }
        }

        // Mark new invoice as paid
        if ($newInvoiceId && $newInvoiceId !== $oldInvoiceId) {
            $newInvoice = Invoice::find($newInvoiceId);
            if ($newInvoice && in_array($newInvoice->status, ['draft', 'sent', 'unpaid', 'overdue'])) {
                $newInvoice->update([
                    'status' => 'paid',
                    'paid_date' => $bankTransaction->date,
                ]);
            }
        }

        // Sync linked expense
        $linkedExpense = Expense::where('bank_transaction_id', $bankTransaction->id)->first();

        if ($bankTransaction->type === 'expense') {
            $expenseName = $bankTransaction->description ?: $bankTransaction->reference ?: __('bank_transactions.type_expense');
            if ($linkedExpense) {
                $linkedExpense->update([
                    'name' => $expenseName,
                    'description' => $bankTransaction->reference ?: null,
                    'amount' => $bankTransaction->amount,
                    'date' => $bankTransaction->date,
                ]);
            } else {
                $request->user()->expenses()->create([
                    'bank_transaction_id' => $bankTransaction->id,
                    'name' => $expenseName,
                    'description' => $bankTransaction->reference ?: null,
                    'amount' => $bankTransaction->amount,
                    'date' => $bankTransaction->date,
                ]);
            }
        } elseif ($linkedExpense) {
            // Type changed from expense to income — remove linked expense
            $linkedExpense->delete();
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_updated'));
    }

    public function destroy(BankTransaction $bankTransaction): RedirectResponse
    {
        $this->authorize('delete', $bankTransaction);

        $invoiceId = $bankTransaction->invoice_id;
        $bankTransaction->delete();

        // Revert invoice if no other transactions reference it
        if ($invoiceId) {
            $otherTransactions = BankTransaction::where('invoice_id', $invoiceId)->exists();

            if (!$otherTransactions) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice && $invoice->status === 'paid') {
                    $invoice->update([
                        'status' => 'unpaid',
                        'paid_date' => null,
                    ]);
                }
            }
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_deleted'));
    }
}
