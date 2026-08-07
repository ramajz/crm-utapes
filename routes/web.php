<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\WhatsAppTemplateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leads
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/follow-up', [LeadController::class, 'followUpIndex'])->name('leads.follow-up');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('/leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::post('/leads/{lead}/follow-up', [LeadController::class, 'toggleFollowUp'])->name('leads.toggle-follow-up');
    Route::post('/leads/{lead}/follow-up/complete', [LeadController::class, 'completeFollowUp'])->name('leads.complete-follow-up');
    Route::post('/leads/bulk-reassign', [LeadController::class, 'bulkReassign'])->name('leads.bulk-reassign');

    // Profile (Breeze)
    Route::view('profile', 'profile')->name('profile');

    // WhatsApp Templates (manager/admin only — di cek di controller)
    Route::resource('templates', WhatsAppTemplateController::class);
});

require __DIR__.'/auth.php';
