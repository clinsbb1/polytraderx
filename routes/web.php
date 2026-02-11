<?php

use App\Http\Controllers\AiCostController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

// Public pages (guests)
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : app(PublicController::class)->landing();
});
Route::get('/pricing', [PublicController::class, 'pricing']);
Route::get('/terms', [PublicController::class, 'terms']);
Route::get('/privacy', [PublicController::class, 'privacy']);
Route::get('/contact', [PublicController::class, 'contact']);

// Webhooks (no auth, no CSRF)
Route::post('/api/webhooks/nowpayments', [WebhookController::class, 'nowpayments']);

// Onboarding (auth only, NOT onboarded middleware)
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'welcome']);
    Route::post('/onboarding', [OnboardingController::class, 'saveWelcome']);
    Route::get('/onboarding/polymarket', [OnboardingController::class, 'polymarket']);
    Route::post('/onboarding/polymarket', [OnboardingController::class, 'savePolymarket']);
    Route::get('/onboarding/telegram', [OnboardingController::class, 'telegram']);
    Route::post('/onboarding/telegram', [OnboardingController::class, 'saveTelegram']);
    Route::get('/onboarding/anthropic', [OnboardingController::class, 'anthropic']);
    Route::post('/onboarding/anthropic', [OnboardingController::class, 'saveAnthropic']);
    Route::get('/onboarding/activate', [OnboardingController::class, 'activate']);
    Route::post('/onboarding/activate', [OnboardingController::class, 'complete']);
});

// Subscription (auth + onboarded)
Route::middleware(['auth', 'onboarded'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
    Route::get('/subscription/success', [SubscriptionController::class, 'success']);
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel']);
});

// Main app (auth + onboarded + subscribed)
Route::middleware(['auth', 'onboarded', 'subscribed'])->group(function () {
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

    // User settings
    Route::get('/settings/credentials', [CredentialController::class, 'edit']);
    Route::post('/settings/credentials', [CredentialController::class, 'update']);
    Route::get('/settings/profile', [ProfileSettingsController::class, 'edit']);
    Route::post('/settings/profile', [ProfileSettingsController::class, 'update']);
    Route::get('/settings/notifications', [NotificationSettingsController::class, 'edit']);
    Route::post('/settings/notifications', [NotificationSettingsController::class, 'update']);

    // Breeze profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Super Admin panel
Route::middleware(['auth', 'superadmin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::post('/users/{user}/change-plan', [AdminUserController::class, 'changePlan']);

    Route::get('/payments', [AdminPaymentController::class, 'index']);

    Route::get('/plans', [AdminPlanController::class, 'index']);
    Route::get('/plans/create', [AdminPlanController::class, 'create']);
    Route::post('/plans', [AdminPlanController::class, 'store']);
    Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit']);
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update']);
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy']);

    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::post('/settings', [AdminSettingController::class, 'update']);

    Route::get('/logs', [AdminLogController::class, 'index']);

    Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
    Route::get('/announcements/create', [AdminAnnouncementController::class, 'create']);
    Route::post('/announcements', [AdminAnnouncementController::class, 'store']);
    Route::get('/announcements/{announcement}/edit', [AdminAnnouncementController::class, 'edit']);
    Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy']);
});

require __DIR__ . '/auth.php';
