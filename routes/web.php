<?php

use App\Http\Controllers\AiCostController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\TradeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
    Route::get('/trades/{trade}', [TradeController::class, 'show'])->name('trades.show');

    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
    Route::post('/audits/{audit}/approve-fix', [AuditController::class, 'approveFix'])->name('audits.approve-fix');
    Route::post('/audits/{audit}/reject-fix', [AuditController::class, 'rejectFix'])->name('audits.reject-fix');

    Route::get('/strategy', [StrategyController::class, 'index'])->name('strategy.index');
    Route::post('/strategy/{group}', [StrategyController::class, 'update'])->name('strategy.update');

    Route::get('/balance', [BalanceController::class, 'index'])->name('balance.index');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    Route::get('/ai-costs', [AiCostController::class, 'index'])->name('ai-costs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
