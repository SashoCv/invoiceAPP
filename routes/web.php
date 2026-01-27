<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
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
            return view('settings.profile', ['user' => auth()->user()]);
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
});

require __DIR__.'/auth.php';
