<?php

use App\Http\Controllers\ImpersonationController;
use App\Livewire\Banking\BankReconciliation;
use App\Livewire\Sales\InteractiveInvoiceBuilder;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

// Livewire routes (protected by Filament auth)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/banking/reconcile/{bankAccountId?}/{reconciliationId?}', BankReconciliation::class)
        ->name('banking.reconcile');

    Route::get('/sales/invoice-builder/{invoiceId?}', InteractiveInvoiceBuilder::class)
        ->name('sales.invoice-builder');
});

// Impersonation routes
Route::middleware(['auth'])->group(function () {
    Route::get('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])
        ->name('impersonate');

    Route::get('/stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])
        ->name('stop-impersonating');
});
