<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadExportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leads
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('/leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update-status');

    // Export
    Route::get('/leads/export/csv', [LeadExportController::class, 'csv'])->name('leads.export.csv');
    Route::get('/leads/export/pdf', [LeadExportController::class, 'pdf'])->name('leads.export.pdf');

    // Profile (Breeze)
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
