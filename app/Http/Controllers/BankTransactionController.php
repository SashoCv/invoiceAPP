<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
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
        $buildFilteredQuery = function () use ($request) {
            $query = $request->user()->bankTransactions();

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

            return $query;
        };

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['date', 'amount'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'date';
        }

        // Count total batches
        $totalBatches = $buildFilteredQuery()->distinct()->count('batch_id');

        // Get paginated batch_ids
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $sortColumn = $sortField === 'amount' ? 'total_amount' : 'batch_date';
        $paginatedBatchRows = $buildFilteredQuery()
            ->selectRaw('batch_id, MAX(date) as batch_date, SUM(amount) as total_amount')
            ->groupBy('batch_id')
            ->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $paginatedBatchIds = $paginatedBatchRows->pluck('batch_id');

        // Fetch all transactions for these batches (unfiltered within batch)
        $batchTransactions = $request->user()->bankTransactions()
            ->with(['bankAccount', 'invoice.client', 'client'])
            ->whereIn('batch_id', $paginatedBatchIds)
            ->orderBy('id')
            ->get()
            ->groupBy('batch_id');

        // Build batch data preserving sort order
        $batchData = $paginatedBatchIds->map(function ($batchId) use ($batchTransactions) {
            $items = $batchTransactions->get($batchId, collect());
            $first = $items->first();
            return [
                'batch_id' => $batchId,
                'batch_number' => $first?->batch_number,
                'batch_year' => $first?->batch_year,
                'date' => $first?->date?->format('Y-m-d'),
                'bank_account' => $first?->bankAccount,
                'items' => $items->values(),
            ];
        })->values()->all();

        // Create paginator
        $batches = new LengthAwarePaginator(
            $batchData,
            $totalBatches,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $bankAccounts = $request->user()->bankAccounts()->orderBy('bank_name')->get();

        $unpaidInvoices = $request->user()->invoices()
            ->with('client')
            ->whereIn('status', ['draft', 'sent', 'unpaid', 'overdue'])
            ->orderBy('invoice_number', 'desc')
            ->get();

        $clients = $request->user()->clients()->orderBy('name')->get();

        // Calculate totals with same filters
        $applyTotalFilters = function ($query) use ($request) {
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }
            if ($request->filled('bank_account')) {
                $query->where('bank_account_id', $request->bank_account);
            }
            return $query;
        };

        $totalIncome = (float) $applyTotalFilters($request->user()->bankTransactions()->where('type', 'income'))->sum('amount');
        $totalExpense = (float) $applyTotalFilters($request->user()->bankTransactions()->where('type', 'expense'))->sum('amount');

        return Inertia::render('BankTransactions/Index', [
            'batches' => $batches,
            'bankAccounts' => $bankAccounts,
            'unpaidInvoices' => $unpaidInvoices,
            'clients' => $clients,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
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
            'date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'batch_number' => ['nullable', 'integer', 'min:1'],
            'batch_year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:income,expense'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.currency' => ['required', 'in:MKD,EUR,USD'],
            'items.*.invoice_id' => ['nullable', 'exists:invoices,id'],
            'items.*.client_id' => ['nullable', 'exists:clients,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.reference' => ['nullable', 'string', 'max:255'],
        ]);

        $batchId = (string) Str::uuid();
        $batchYear = $validated['batch_year'] ?? (int) date('Y');
        $batchNumber = $validated['batch_number']
            ?? ($request->user()->bankTransactions()->where('batch_year', $batchYear)->max('batch_number') ?? 0) + 1;

        foreach ($validated['items'] as $item) {
            $transaction = $request->user()->bankTransactions()->create([
                'date' => $validated['date'],
                'bank_account_id' => $validated['bank_account_id'],
                'batch_id' => $batchId,
                'batch_number' => $batchNumber,
                'batch_year' => $batchYear,
                ...$item,
            ]);

            $this->handleInvoiceLink($transaction);
            $this->handleExpenseSync($request->user(), $transaction);
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_created'));
    }

    public function update(Request $request, BankTransaction $bankTransaction): RedirectResponse
    {
        $this->authorize('update', $bankTransaction);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'batch_number' => ['nullable', 'integer', 'min:1'],
            'batch_year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:income,expense'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.currency' => ['required', 'in:MKD,EUR,USD'],
            'items.*.invoice_id' => ['nullable', 'exists:invoices,id'],
            'items.*.client_id' => ['nullable', 'exists:clients,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.reference' => ['nullable', 'string', 'max:255'],
        ]);

        $batchId = $bankTransaction->batch_id;

        // Update batch_number and batch_year on all transactions in this batch
        if (isset($validated['batch_number']) || isset($validated['batch_year'])) {
            $batchUpdate = [];
            if (isset($validated['batch_number'])) {
                $batchUpdate['batch_number'] = $validated['batch_number'];
            }
            if (isset($validated['batch_year'])) {
                $batchUpdate['batch_year'] = $validated['batch_year'];
            }
            BankTransaction::where('batch_id', $batchId)
                ->where('user_id', $request->user()->id)
                ->update($batchUpdate);
        }

        $existingTransactions = BankTransaction::where('batch_id', $batchId)
            ->where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get();

        $items = $validated['items'];

        // Update existing transactions or delete extras
        foreach ($existingTransactions as $i => $existing) {
            if (isset($items[$i])) {
                $oldInvoiceId = $existing->invoice_id;
                $newInvoiceId = $items[$i]['invoice_id'] ?? null;

                $existing->update([
                    'date' => $validated['date'],
                    'bank_account_id' => $validated['bank_account_id'],
                    ...$items[$i],
                ]);

                // Handle invoice unlink
                if ($oldInvoiceId && $oldInvoiceId != $newInvoiceId) {
                    $otherTransactions = BankTransaction::where('invoice_id', $oldInvoiceId)
                        ->where('id', '!=', $existing->id)
                        ->exists();
                    if (!$otherTransactions) {
                        $oldInvoice = Invoice::find($oldInvoiceId);
                        if ($oldInvoice && $oldInvoice->status === 'paid') {
                            $oldInvoice->update(['status' => 'unpaid', 'paid_date' => null]);
                        }
                    }
                }

                // Handle invoice link
                if ($newInvoiceId && $newInvoiceId != $oldInvoiceId) {
                    $this->handleInvoiceLink($existing);
                }

                // Sync expense
                $linkedExpense = Expense::where('bank_transaction_id', $existing->id)->first();
                if ($existing->type === 'expense') {
                    $expenseName = $existing->description ?: $existing->reference ?: __('bank_transactions.type_expense');
                    if ($linkedExpense) {
                        $linkedExpense->update([
                            'name' => $expenseName,
                            'description' => $existing->reference ?: null,
                            'amount' => $existing->amount,
                            'date' => $existing->date,
                        ]);
                    } else {
                        $request->user()->expenses()->create([
                            'bank_transaction_id' => $existing->id,
                            'name' => $expenseName,
                            'description' => $existing->reference ?: null,
                            'amount' => $existing->amount,
                            'date' => $existing->date,
                        ]);
                    }
                } elseif ($linkedExpense) {
                    $linkedExpense->delete();
                }
            } else {
                // Item was removed — delete this transaction
                $this->revertInvoiceIfNeeded($existing);
                $existing->delete();
            }
        }

        // Create new items beyond existing count
        $batchNumber = $validated['batch_number'] ?? $bankTransaction->batch_number;
        $batchYear = $validated['batch_year'] ?? $bankTransaction->batch_year;
        for ($i = count($existingTransactions); $i < count($items); $i++) {
            $transaction = $request->user()->bankTransactions()->create([
                'date' => $validated['date'],
                'bank_account_id' => $validated['bank_account_id'],
                'batch_id' => $batchId,
                'batch_number' => $batchNumber,
                'batch_year' => $batchYear,
                ...$items[$i],
            ]);

            $this->handleInvoiceLink($transaction);
            $this->handleExpenseSync($request->user(), $transaction);
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_updated'));
    }

    public function destroy(BankTransaction $bankTransaction): RedirectResponse
    {
        $this->authorize('delete', $bankTransaction);

        // Delete all transactions in the batch
        $batchTransactions = BankTransaction::where('batch_id', $bankTransaction->batch_id)
            ->where('user_id', auth()->id())
            ->get();

        foreach ($batchTransactions as $transaction) {
            $this->revertInvoiceIfNeeded($transaction);
            $transaction->delete();
        }

        return redirect()->route('bank-transactions.index')
            ->with('success', __('toast.bank_transaction_deleted'));
    }

    private function handleInvoiceLink(BankTransaction $transaction): void
    {
        if ($transaction->invoice_id) {
            $invoice = Invoice::find($transaction->invoice_id);
            if ($invoice && in_array($invoice->status, ['draft', 'sent', 'unpaid', 'overdue'])) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_date' => $transaction->date,
                ]);
            }
        }
    }

    private function handleExpenseSync($user, BankTransaction $transaction): void
    {
        if ($transaction->type === 'expense') {
            $expenseName = $transaction->description ?: $transaction->reference ?: __('bank_transactions.type_expense');
            $user->expenses()->create([
                'bank_transaction_id' => $transaction->id,
                'name' => $expenseName,
                'description' => $transaction->reference ?: null,
                'amount' => $transaction->amount,
                'date' => $transaction->date,
            ]);
        }
    }

    private function revertInvoiceIfNeeded(BankTransaction $transaction): void
    {
        if ($transaction->invoice_id) {
            $otherTransactions = BankTransaction::where('invoice_id', $transaction->invoice_id)
                ->where('id', '!=', $transaction->id)
                ->exists();

            if (!$otherTransactions) {
                $invoice = Invoice::find($transaction->invoice_id);
                if ($invoice && $invoice->status === 'paid') {
                    $invoice->update(['status' => 'unpaid', 'paid_date' => null]);
                }
            }
        }
    }
}
