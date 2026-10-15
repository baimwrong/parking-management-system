<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Enterprise Parking Management System
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Operations
Route::middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gate Operations & Cashier Terminals
    Route::prefix('gate')->name('gate.')->group(function () {
        Route::get('/entry', [GateController::class, 'entryTerminal'])->name('entry');
        Route::post('/check-in', [GateController::class, 'checkIn'])->name('checkin');
        Route::get('/exit', [GateController::class, 'exitTerminal'])->name('exit');
        Route::post('/process-payment', [GateController::class, 'processExitPayment'])->name('payment');
        Route::get('/lost-ticket', [GateController::class, 'lostTicket'])->name('lost_ticket');
        Route::post('/process-lost-ticket', [GateController::class, 'processLostTicket'])->name('process_lost_ticket');
        Route::post('/barrier-override', [GateController::class, 'barrierOverride'])->name('barrier_override');
    });

    // Parking Slots & Zones
    Route::prefix('slots')->name('slots.')->group(function () {
        Route::get('/', [SlotController::class, 'index'])->name('index');
        Route::post('/', [SlotController::class, 'store'])->name('store')->middleware('role:admin,manager');
        Route::post('/{id}/status', [SlotController::class, 'updateStatus'])->name('status');
    });

    // Parking Tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/{id}', [TicketController::class, 'show'])->name('show');
        Route::get('/{id}/print', [TicketController::class, 'printTicket'])->name('print');
        Route::post('/{id}/discount', [TicketController::class, 'applyDiscount'])->name('discount')->middleware('role:admin,manager');
    });

    // Member Passes
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::post('/', [MemberController::class, 'store'])->name('store')->middleware('role:admin,manager');
        Route::post('/{id}/renew', [MemberController::class, 'renew'])->name('renew')->middleware('role:admin,manager,cashier');
        Route::post('/{id}/toggle-status', [MemberController::class, 'toggleStatus'])->name('toggle_status')->middleware('role:admin,manager');
    });

    // Parking Violations & Enforcement
    Route::prefix('violations')->name('violations.')->group(function () {
        Route::get('/', [ViolationController::class, 'index'])->name('index');
        Route::post('/', [ViolationController::class, 'store'])->name('store');
        Route::post('/{id}/status', [ViolationController::class, 'updateStatus'])->name('status')->middleware('role:admin,manager,patrol');
    });

    // Tariffs & Pricing (Admin & Manager)
    Route::prefix('tariffs')->name('tariffs.')->middleware('role:admin,manager')->group(function () {
        Route::get('/', [TariffController::class, 'index'])->name('index');
        Route::post('/', [TariffController::class, 'store'])->name('store');
        Route::put('/{id}', [TariffController::class, 'update'])->name('update');
    });

    // Reports & Financial Analytics (Admin & Manager)
    Route::prefix('reports')->name('reports.')->middleware('role:admin,manager')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-csv', [ReportController::class, 'exportCsv'])->name('export');
    });

    // Bulk Import Data Center (Admin Only)
    Route::prefix('admin/bulk-import')->name('admin.bulk_import.')->middleware('role:admin')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::post('/', [BulkImportController::class, 'process'])->name('process');
    });
});
