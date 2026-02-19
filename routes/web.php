<?php

use App\Http\Controllers\AiCostController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TelegramSettingsController;
use App\Http\Controllers\TwoFactorSettingsController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Admin\AdminAiCostController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminTelegramMessageController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\SimulationAcknowledgmentController;
use App\Models\PlatformSetting;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


// Temporary migrations maintenance helper for cPanel deployments (token only).
Route::middleware(['throttle:3,1'])->get('/admin/run-migration', function (Request $request) {
    $expectedToken = (string) env('MAINTENANCE_ROUTE_TOKEN', '');
    $providedToken = (string) $request->query('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $output = [];

    try {
        Artisan::call('migrate', ['--force' => true]);
        $output[] = '[migrate] OK';
        $artisanOutput = trim(Artisan::output());
        if ($artisanOutput !== '') {
            $output[] = $artisanOutput;
        }
    } catch (\Throwable $e) {
        $output[] = '[migrate] ERROR: ' . $e->getMessage();
    }

    return response('<pre>' . e(implode("\n\n", $output)) . '</pre>');
}); // delete after one-time deployment

// Temporary maintenance helper: disable simulator + cancel open/pending trades for users without Telegram (token only).
Route::middleware(['throttle:3,1'])->get('/admin/run-telegram-enforcement', function (Request $request) {
    $expectedToken = (string) env('MAINTENANCE_ROUTE_TOKEN', '');
    $providedToken = (string) $request->query('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $dryRun = in_array(strtolower((string) $request->query('dry', '1')), ['1', 'true', 'yes', 'on'], true);
    $output = [];
    $output[] = '[sim:disable-without-telegram] mode=' . ($dryRun ? 'DRY-RUN' : 'LIVE');

    try {
        $params = [];
        if ($dryRun) {
            $params['--dry-run'] = true;
        }

        Artisan::call('sim:disable-without-telegram', $params);
        $output[] = '[sim:disable-without-telegram] OK';

        $artisanOutput = trim(Artisan::output());
        if ($artisanOutput !== '') {
            $output[] = $artisanOutput;
        }
    } catch (\Throwable $e) {
        $output[] = '[sim:disable-without-telegram] ERROR: ' . $e->getMessage();
    }

    $output[] = '';
    $output[] = 'Tip: use ?dry=0 to run live changes.';

    return response('<pre>' . e(implode("\n\n", $output)) . '</pre>');
}); // delete after one-time maintenance

// Temporary maintenance helper: apply strict low-burn AI profile (token only).
Route::middleware(['throttle:3,1'])->get('/admin/run-ai-low-burn-profile', function (Request $request) {
    $expectedToken = (string) env('MAINTENANCE_ROUTE_TOKEN', '');
    $providedToken = (string) $request->query('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $settings = app(\App\Services\Settings\PlatformSettingsService::class);
    $profile = [
        'AI_PRE_ANALYSIS_ENABLED' => ['value' => 'false', 'type' => 'boolean', 'description' => 'Enable background AI pre-analysis (costly at scale)'],
        'AI_PRE_ANALYSIS_MAX_CANDIDATES' => ['value' => '3', 'type' => 'number', 'description' => 'Max markets per cycle/user for AI pre-analysis'],
        'AI_MUSCLES_CACHE_TTL_SECONDS' => ['value' => '900', 'type' => 'number', 'description' => 'Cache lifetime for Muscles results per user/market'],
        'AI_MUSCLES_FAILURE_COOLDOWN_SECONDS' => ['value' => '300', 'type' => 'number', 'description' => 'Cooldown after failed Muscles response before retry'],
        'AI_MUSCLES_MAX_PROMPT_TOKENS_HARD_CAP' => ['value' => '1500', 'type' => 'number', 'description' => 'Hard cap on Muscles prompt tokens per request'],
        'AI_MUSCLES_MAX_COMPLETION_TOKENS' => ['value' => '256', 'type' => 'number', 'description' => 'Hard cap on Muscles completion tokens per request'],
        'AI_MUSCLES_ENFORCE_CHEAP_MODEL' => ['value' => 'true', 'type' => 'boolean', 'description' => 'Force Muscles tier to Haiku-like cheap model to control cost'],
    ];

    $output = [];
    $output[] = '[ai:low-burn] Applying strict low-burn profile...';

    foreach ($profile as $key => $meta) {
        PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $meta['value'],
                'type' => $meta['type'],
                'group' => 'ai',
                'description' => $meta['description'],
            ]
        );

        // Ensure settings cache is refreshed for runtime reads.
        $settings->set($key, $meta['value']);
        $output[] = "{$key}={$meta['value']}";
    }

    $output[] = '';
    $output[] = 'Profile applied successfully.';
    $output[] = 'Note: AI_MONTHLY_BUDGET was not changed.';
    $output[] = 'Delete this route after use.';

    return response('<pre>' . e(implode("\n", $output)) . '</pre>');
}); // delete after one-time maintenance

// Temporary Telegram diagnostics helper (token only).
Route::middleware(['throttle:5,1'])->get('/admin/telegram-diagnostics', function (Request $request) {
    $expectedToken = (string) env('MAINTENANCE_ROUTE_TOKEN', '');
    $providedToken = (string) $request->query('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $settings = app(\App\Services\Settings\PlatformSettingsService::class);
    $botToken = trim((string) $settings->get('TELEGRAM_BOT_TOKEN', ''));
    $botUsername = trim((string) $settings->get('TELEGRAM_BOT_USERNAME', ''));
    $secret = trim((string) $settings->get('TELEGRAM_WEBHOOK_SECRET', ''));
    $webhookUrl = url('/api/webhooks/telegram');

    $out = [];
    $out[] = 'Telegram Diagnostics';
    $out[] = '--------------------';
    $out[] = 'bot_token_configured: ' . ($botToken !== '' ? 'yes' : 'no');
    $out[] = 'bot_username: ' . ($botUsername !== '' ? '@' . $botUsername : '(empty)');
    $out[] = 'webhook_secret_configured: ' . ($secret !== '' ? 'yes' : 'no');
    $out[] = 'expected_webhook_url: ' . $webhookUrl;

    if ($botToken === '') {
        $out[] = '';
        $out[] = 'ERROR: TELEGRAM_BOT_TOKEN is empty in Admin Settings.';
        return response('<pre>' . e(implode("\n", $out)) . '</pre>');
    }

    try {
        $base = 'https://api.telegram.org/bot' . $botToken;

        $me = Http::timeout(10)->get($base . '/getMe')->json();
        $out[] = '';
        $out[] = 'getMe.ok: ' . (($me['ok'] ?? false) ? 'true' : 'false');
        if (($me['ok'] ?? false) && isset($me['result']['username'])) {
            $out[] = 'getMe.username: @' . $me['result']['username'];
        } elseif (isset($me['description'])) {
            $out[] = 'getMe.error: ' . $me['description'];
        }

        $info = Http::timeout(10)->get($base . '/getWebhookInfo')->json();
        $out[] = '';
        $out[] = 'getWebhookInfo.ok: ' . (($info['ok'] ?? false) ? 'true' : 'false');

        if (($info['ok'] ?? false) && isset($info['result'])) {
            $result = $info['result'];
            $out[] = 'webhook.url: ' . ($result['url'] ?? '(none)');
            $out[] = 'webhook.pending_update_count: ' . (string) ($result['pending_update_count'] ?? 0);
            $out[] = 'webhook.last_error_date: ' . (string) ($result['last_error_date'] ?? '(none)');
            $out[] = 'webhook.last_error_message: ' . (string) ($result['last_error_message'] ?? '(none)');
            $out[] = 'webhook.ip_address: ' . (string) ($result['ip_address'] ?? '(none)');
            $out[] = 'webhook.max_connections: ' . (string) ($result['max_connections'] ?? '(none)');
        } elseif (isset($info['description'])) {
            $out[] = 'getWebhookInfo.error: ' . $info['description'];
        }
    } catch (\Throwable $e) {
        $out[] = '';
        $out[] = 'HTTP ERROR: ' . $e->getMessage();
    }

    $out[] = '';
    $out[] = 'If /start still gives no reply, check:';
    $out[] = '1) TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET are set in Admin Settings (DB).';
    $out[] = '2) Telegram webhook URL exactly matches expected_webhook_url.';
    $out[] = '3) Telegram webhook was registered with the same secret.';

    return response('<pre>' . e(implode("\n", $out)) . '</pre>');
}); // delete after debugging

// Temporary maintenance helper: run only pricing plans seeder from browser (superadmin only).
/*
Route::middleware(['auth', 'superadmin', 'throttle:5,1'])->get('/admin/run-pricing-seeder', function () {
    $output = [];

    try {
        Artisan::call('db:seed', [
            '--class' => SubscriptionPlansSeeder::class,
            '--force' => true,
        ]);
        $output[] = Artisan::output();
    } catch (\Throwable $e) {
        $output[] = 'Error: ' . $e->getMessage();
    }

    return nl2br(implode("\n", $output));
});

// Temporary maintenance helper: refresh platform settings keys from seeder (superadmin only).
Route::middleware(['auth', 'superadmin', 'throttle:5,1'])->get('/admin/run-platform-settings-seeder', function () {
    $output = [];

    try {
        Artisan::call('db:seed', [
            '--class' => PlatformSettingsSeeder::class,
            '--force' => true,
        ]);
        $output[] = Artisan::output();
    } catch (\Throwable $e) {
        $output[] = 'Error: ' . $e->getMessage();
    }

    return nl2br(implode("\n", $output));
});
*/

Route::get('/health', \App\Http\Controllers\HealthCheckController::class);

// Public pages (guests)
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : app(PublicController::class)->landing();
});
Route::get('/pricing', [PublicController::class, 'pricing']);
Route::get('/terms', [PublicController::class, 'terms']);
Route::get('/privacy', [PublicController::class, 'privacy']);
Route::get('/refund-policy', [PublicController::class, 'refundPolicy']);
Route::get('/contact', [PublicController::class, 'contact']);
Route::post('/contact', [PublicController::class, 'submitContact'])->middleware('auth')->name('contact.submit');

// Webhooks (no auth, no CSRF)
Route::post('/api/webhooks/nowpayments', [WebhookController::class, 'nowpayments'])
    ->middleware('throttle:120,1');
Route::post('/api/webhooks/telegram', TelegramWebhookController::class)
    ->middleware('throttle:120,1');

// Simulation acknowledgment (auth only, no subscription or acknowledgment required)
Route::middleware(['auth'])->group(function () {
    Route::get('/acknowledge-simulation', [SimulationAcknowledgmentController::class, 'show'])->name('simulation.acknowledge');
    Route::post('/acknowledge-simulation', [SimulationAcknowledgmentController::class, 'accept'])->name('simulation.accept');
    Route::post('/admin/stop-impersonating', [AdminUserController::class, 'stopImpersonating'])->name('admin.stop-impersonating');
});

// User settings (auth only, accessible even with expired subscription)
Route::middleware(['auth', 'simulation_acknowledged'])->group(function () {
    // Commented out - Users don't need Polymarket credentials for simulation
    // Route::get('/settings/credentials', [CredentialController::class, 'edit']);
    // Route::post('/settings/credentials', [CredentialController::class, 'update']);
    // Route::post('/settings/credentials/test-polymarket', [CredentialController::class, 'testPolymarket']);
    // Route::get('/settings/credentials/test-binance', [CredentialController::class, 'testBinance']);
    Route::get('/settings/profile', [ProfileSettingsController::class, 'edit']);
    Route::post('/settings/profile', [ProfileSettingsController::class, 'update']);
    Route::get('/settings/notifications', [NotificationSettingsController::class, 'edit']);
    Route::post('/settings/notifications', [NotificationSettingsController::class, 'update']);
    Route::get('/settings/telegram', [TelegramSettingsController::class, 'edit']);
    Route::post('/settings/telegram/unlink', [TelegramSettingsController::class, 'unlink']);
    Route::get('/settings/security', [TwoFactorSettingsController::class, 'edit'])->name('settings.security');
    Route::post('/settings/security/2fa/generate', [TwoFactorSettingsController::class, 'generate'])->name('settings.security.2fa.generate');
    Route::post('/settings/security/2fa/enable', [TwoFactorSettingsController::class, 'enable'])->name('settings.security.2fa.enable');
    Route::post('/settings/security/2fa/disable', [TwoFactorSettingsController::class, 'disable'])->name('settings.security.2fa.disable');
    Route::post('/settings/simulator-toggle', [StrategyController::class, 'toggleSimulator'])->name('settings.simulator-toggle');

    // Breeze profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

// Subscription (auth only, accessible even with expired subscription)
Route::middleware(['auth', 'simulation_acknowledged'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
    Route::get('/subscription/success', [SubscriptionController::class, 'success']);
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel']);
});

// Main app (auth + active subscription required)
Route::middleware(['auth', 'simulation_acknowledged', 'subscribed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/announcements/{announcement}/dismiss', [DashboardController::class, 'dismissAnnouncement'])
        ->name('announcements.dismiss');

    Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
    Route::get('/trades/export', [TradeController::class, 'export'])->name('trades.export');
    Route::get('/trades/{trade}', [TradeController::class, 'show'])->name('trades.show');

    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
    Route::post('/audits/{audit}/approve-fix', [AuditController::class, 'approveFix'])->name('audits.approve-fix');
    Route::post('/audits/{audit}/reject-fix', [AuditController::class, 'rejectFix'])->name('audits.reject-fix');
    Route::post('/audits/manual-trigger', [AuditController::class, 'manualTrigger'])
        ->middleware('throttle:2,10')
        ->name('audits.manual-trigger');

    Route::get('/strategy', [StrategyController::class, 'index'])->name('strategy.index');
    Route::post('/strategy/{group}', [StrategyController::class, 'update'])->name('strategy.update');

    Route::get('/balance', [BalanceController::class, 'index'])->name('balance.index');
    Route::post('/balance/reset', [BalanceController::class, 'reset'])->name('balance.reset');
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/bot-activity', function () {
        return redirect()->route('logs.market-scans');
    });
    Route::get('/logs/market-scans', [LogController::class, 'botActivity'])->name('logs.market-scans');
    // Commented out - AI costs are included in subscription, only admin needs to see them
    // Route::get('/ai-costs', [AiCostController::class, 'index'])->name('ai-costs.index');
});

// Super Admin panel
Route::middleware(['auth', 'superadmin', 'simulation_acknowledged'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::post('/users/{user}/change-plan', [AdminUserController::class, 'changePlan']);
    Route::post('/users/{user}/grant-free-subscription', [AdminUserController::class, 'grantFreeSubscription']);
    Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate']);

    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::put('/payments/{payment}', [AdminPaymentController::class, 'update']);

    Route::get('/plans', [AdminPlanController::class, 'index']);
    Route::get('/plans/create', [AdminPlanController::class, 'create']);
    Route::post('/plans', [AdminPlanController::class, 'store']);
    Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit']);
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update']);
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy']);

    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::post('/settings', [AdminSettingController::class, 'update']);
    Route::post('/settings/ai-recharged-now', [AdminSettingController::class, 'markAiRechargedNow']);
    Route::get('/settings/telegram-diagnostics', [AdminSettingController::class, 'telegramDiagnostics']);
    Route::get('/settings/diagnostics', [AdminSettingController::class, 'serviceDiagnostics']);
    Route::get('/telegram/messages', [AdminTelegramMessageController::class, 'index']);
    Route::post('/telegram/messages/send', [AdminTelegramMessageController::class, 'send'])->name('admin.telegram.messages.send');

    Route::get('/logs', [AdminLogController::class, 'index']);

    Route::get('/ai-costs', [AdminAiCostController::class, 'index']);

    Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
    Route::get('/announcements/create', [AdminAnnouncementController::class, 'create']);
    Route::post('/announcements', [AdminAnnouncementController::class, 'store']);
    Route::get('/announcements/{announcement}/edit', [AdminAnnouncementController::class, 'edit']);
    Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy']);
});

require __DIR__ . '/auth.php';
