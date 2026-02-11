<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientContractController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\AgencyController;
use App\Http\Controllers\Settings\BankAccountController;
use App\Http\Controllers\Settings\TemplateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Old profile routes (keep for compatibility)
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // Profile
        Route::get('/profile', function () {
            return \Inertia\Inertia::render('Settings/Profile', [
                'user' => auth()->user(),
            ]);
        })->name('profile');

        // Agency
        Route::get('/agency', [AgencyController::class, 'edit'])->name('agency');
        Route::put('/agency', [AgencyController::class, 'update'])->name('agency.update');

        // Bank Accounts
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        // Templates
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates');
        Route::get('/templates/preview', [TemplateController::class, 'preview'])->name('templates.preview');
        Route::put('/templates', [TemplateController::class, 'update'])->name('templates.update');
    });

    // Redirect old profile.edit to new settings
    Route::get('/profile', function () {
        return redirect()->route('settings.profile');
    })->name('profile.edit');

    // Clients
    Route::get('clients/archived', [ClientController::class, 'archived'])->name('clients.archived');
    Route::post('clients/{id}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::delete('clients/{id}/force-delete', [ClientController::class, 'forceDelete'])->name('clients.force-delete');
    Route::resource('clients', ClientController::class);

    // Articles
    Route::resource('articles', ArticleController::class)->except(['show']);

    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('expenses/categories', [ExpenseController::class, 'storeCategory'])->name('expenses.categories.store');
    Route::put('expenses/categories/{expenseCategory}', [ExpenseController::class, 'updateCategory'])->name('expenses.categories.update');
    Route::delete('expenses/categories/{expenseCategory}', [ExpenseController::class, 'destroyCategory'])->name('expenses.categories.destroy');
    Route::post('expenses/recurring', [ExpenseController::class, 'storeRecurring'])->name('expenses.recurring.store');
    Route::put('expenses/recurring/{recurringExpense}', [ExpenseController::class, 'updateRecurring'])->name('expenses.recurring.update');
    Route::delete('expenses/recurring/{recurringExpense}', [ExpenseController::class, 'destroyRecurring'])->name('expenses.recurring.destroy');
    Route::post('expenses/recurring/{recurringExpense}/toggle', [ExpenseController::class, 'toggleRecurring'])->name('expenses.recurring.toggle');

    // CSV Exports
    Route::get('invoices/export/csv', [ExportController::class, 'exportInvoices'])->name('invoices.export.csv');
    Route::get('expenses/export/csv', [ExportController::class, 'exportExpenses'])->name('expenses.export.csv');
    Route::get('clients/export/csv', [ExportController::class, 'exportClients'])->name('clients.export.csv');

    // Invoices
    Route::get('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('invoices/{invoice}/pdf', [PdfController::class, 'invoice'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/pdf/preview', [PdfController::class, 'invoicePreview'])->name('invoices.pdf.preview');
    Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
    Route::delete('invoices/{id}/force-delete', [InvoiceController::class, 'forceDelete'])->name('invoices.force-delete');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::resource('invoices', InvoiceController::class);

    // Proforma Invoices
    Route::get('proforma-invoices/{proformaInvoice}/duplicate', [ProformaInvoiceController::class, 'duplicate'])->name('proforma-invoices.duplicate');
    Route::get('proforma-invoices/{proformaInvoice}/pdf', [PdfController::class, 'proforma'])->name('proforma-invoices.pdf');
    Route::get('proforma-invoices/{proformaInvoice}/pdf/preview', [PdfController::class, 'proformaPreview'])->name('proforma-invoices.pdf.preview');
    Route::post('proforma-invoices/{proformaInvoice}/convert', [ProformaInvoiceController::class, 'convertToInvoice'])->name('proforma-invoices.convert');
    Route::post('proforma-invoices/{id}/restore', [ProformaInvoiceController::class, 'restore'])->name('proforma-invoices.restore');
    Route::delete('proforma-invoices/{id}/force-delete', [ProformaInvoiceController::class, 'forceDelete'])->name('proforma-invoices.force-delete');
    Route::patch('proforma-invoices/{proformaInvoice}/status', [ProformaInvoiceController::class, 'updateStatus'])->name('proforma-invoices.update-status');
    Route::post('proforma-invoices/{proformaInvoice}/send', [ProformaInvoiceController::class, 'send'])->name('proforma-invoices.send');
    Route::resource('proforma-invoices', ProformaInvoiceController::class);

    // Offers
    Route::get('offers/{offer}/duplicate', [OfferController::class, 'duplicate'])->name('offers.duplicate');
    Route::get('offers/{offer}/pdf', [PdfController::class, 'offer'])->name('offers.pdf');
    Route::get('offers/{offer}/pdf/preview', [PdfController::class, 'offerPreview'])->name('offers.pdf.preview');
    Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
    Route::post('offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');
    Route::post('offers/{offer}/convert', [OfferController::class, 'convertToInvoice'])->name('offers.convert');
    Route::post('offers/{id}/restore', [OfferController::class, 'restore'])->name('offers.restore');
    Route::delete('offers/{id}/force-delete', [OfferController::class, 'forceDelete'])->name('offers.force-delete');
    Route::patch('offers/{offer}/status', [OfferController::class, 'updateStatus'])->name('offers.update-status');
    Route::post('offers/{offer}/send', [OfferController::class, 'send'])->name('offers.send');
    Route::resource('offers', OfferController::class);

    // Currency converter
    Route::post('currency/convert', [\App\Http\Controllers\CurrencyController::class, 'convert'])->name('currency.convert');

    // Client Contracts
    Route::get('clients/{client}/contracts', [ClientContractController::class, 'index'])->name('clients.contracts.index');
    Route::post('clients/{client}/contracts', [ClientContractController::class, 'store'])->name('clients.contracts.store');
    Route::get('contracts/{contract}/download', [ClientContractController::class, 'download'])->name('contracts.download');
    Route::delete('contracts/{contract}', [ClientContractController::class, 'destroy'])->name('contracts.destroy');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/calendar', [AdminUserController::class, 'calendar'])->name('users.calendar');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/extend', [AdminUserController::class, 'extend'])->name('users.extend');
    Route::post('/users/{user}/revoke', [AdminUserController::class, 'revoke'])->name('users.revoke');
    Route::post('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/create', [AdminNotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
});

require __DIR__.'/auth.php';
