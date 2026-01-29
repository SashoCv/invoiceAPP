<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientContractController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\AgencyController;
use App\Http\Controllers\Settings\BankAccountController;
use App\Http\Controllers\Settings\TemplateController;
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
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        // Templates
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates');
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

    // Invoices
    Route::get('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
    Route::delete('invoices/{id}/force-delete', [InvoiceController::class, 'forceDelete'])->name('invoices.force-delete');
    Route::resource('invoices', InvoiceController::class);

    // Proforma Invoices
    Route::get('proforma-invoices/{proformaInvoice}/duplicate', [ProformaInvoiceController::class, 'duplicate'])->name('proforma-invoices.duplicate');
    Route::post('proforma-invoices/{proformaInvoice}/convert', [ProformaInvoiceController::class, 'convertToInvoice'])->name('proforma-invoices.convert');
    Route::post('proforma-invoices/{id}/restore', [ProformaInvoiceController::class, 'restore'])->name('proforma-invoices.restore');
    Route::delete('proforma-invoices/{id}/force-delete', [ProformaInvoiceController::class, 'forceDelete'])->name('proforma-invoices.force-delete');
    Route::resource('proforma-invoices', ProformaInvoiceController::class);

    // Offers
    Route::get('offers/{offer}/duplicate', [OfferController::class, 'duplicate'])->name('offers.duplicate');
    Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
    Route::post('offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');
    Route::post('offers/{offer}/convert', [OfferController::class, 'convertToInvoice'])->name('offers.convert');
    Route::post('offers/{id}/restore', [OfferController::class, 'restore'])->name('offers.restore');
    Route::delete('offers/{id}/force-delete', [OfferController::class, 'forceDelete'])->name('offers.force-delete');
    Route::resource('offers', OfferController::class);

    // Client Contracts
    Route::get('clients/{client}/contracts', [ClientContractController::class, 'index'])->name('clients.contracts.index');
    Route::post('clients/{client}/contracts', [ClientContractController::class, 'store'])->name('clients.contracts.store');
    Route::get('contracts/{contract}/download', [ClientContractController::class, 'download'])->name('contracts.download');
    Route::delete('contracts/{contract}', [ClientContractController::class, 'destroy'])->name('contracts.destroy');
});

require __DIR__.'/auth.php';
