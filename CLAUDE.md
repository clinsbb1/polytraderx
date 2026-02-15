# PolyTraderX — Multi-Tenant SaaS Polymarket Strategy Simulator

## Project Overview
PolyTraderX is a **multi-tenant SaaS** Laravel application that provides strategy simulation and backtesting for Polymarket's 5-minute and 15-minute crypto prediction markets (BTC, ETH, SOL). The platform operates in **simulation-only mode**, allowing users to test and optimize trading strategies without risking real capital. The core edge is **late-minute certainty trading**: signals are generated only in the final 30–60 seconds when outcome is near-certain (>92% confidence). Uses a 3-tier AI cost architecture (Reflexes/Muscles/Brain), logs everything with full forensics, and self-audits after every loss. Users can choose to trade one or both market durations via strategy parameters.

**Product positioning**: This is a strategy lab, not a live trading bot. All "trades" are simulated using real market data.

**Multi-tenant architecture**: Each user has their own strategy_params, trades, API credentials (encrypted), and subscription. The `BelongsToUser` trait provides user scoping on all data models. No global scopes — commands use `UserBotRunner` to iterate users explicitly.

## Tech Stack
- **PHP 8.2+** / **Laravel 12**
- **Bootstrap 5** + Blade templates (admin UI + public website)
- **Chart.js** for charts
- **MySQL 8** database
- **Laravel Breeze** for auth + **Laravel Socialite** for Google OAuth
- **Anthropic Claude API** — Haiku (Muscles tier) + Sonnet (Brain tier)
- **NOWPayments** for crypto subscription billing
- **Binance Public API** for real-time BTC/ETH/SOL spot prices
- **Telegram Bot API** for notifications and bot commands
- **Database queue driver** (no Redis)
- **Timezone**: Africa/Lagos (per-user configurable)

## Common Commands
```bash
php artisan serve                    # Dev server
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Reset + seed (plans, settings, superadmin)
php artisan db:seed                  # Seed all
php artisan test                     # Run tests (161 tests)
php artisan schedule:run             # Run scheduler manually
php artisan queue:work               # Process queued jobs

# Simulation commands (run for all active users via UserBotRunner)
php artisan sim:scan-markets         # Find active 15-min markets in entry window
php artisan sim:execute-trades       # Generate signals + simulate orders
php artisan sim:monitor-positions    # Track open positions, resolve completed markets
php artisan sim:ai-analyze-markets   # Haiku: pre-score markets near close
php artisan sim:ai-audit-losses      # Sonnet: post-loss forensics on unaudited trades
php artisan sim:daily-review         # Sonnet: daily performance review
php artisan sim:weekly-report        # Sonnet: weekly deep analysis
php artisan sim:snapshot-balance     # Record balance/equity + alert on drawdown
php artisan sim:daily-summary        # Compile yesterday's stats + Telegram notification
php artisan sim:cleanup-logs         # Prune trade_logs >90d, snapshots >180d, decisions >90d
php artisan subscriptions:check-expired  # Warn/deactivate expired subscriptions
php artisan payments:expire-pending  # Auto-expire pending payments after 5 hours
```

## Scheduled Commands
```
HOURLY:
  subscriptions:check-expired     — Warn 3/1 days before expiry, deactivate expired
  payments:expire-pending         — Auto-expire pending payments after 5 hours

EVERY MINUTE (Tier 1 — Reflexes):
  sim:scan-markets                — Find entry windows
  sim:execute-trades              — Signal generation + simulated trade placement
  sim:monitor-positions           — Resolve completed markets

EVERY 5 MINUTES (Tier 2 — Muscles):
  sim:ai-analyze-markets          — Pre-score with Haiku
  sim:ai-audit-losses             — Audit losing trades with Sonnet

EVERY 15 MINUTES:
  sim:snapshot-balance            — Record equity, check low balance + drawdown alerts

DAILY AT 23:55:
  sim:daily-review                — Daily AI review (Sonnet)

DAILY AT 00:05:
  sim:daily-summary               — Compile yesterday's stats + send Telegram

DAILY:
  sim:cleanup-logs                — Prune old records

WEEKLY (Sunday 23:55):
  sim:weekly-report               — Weekly deep analysis (Sonnet)
```

All scheduled commands use `->withoutOverlapping()->runInBackground()`.

**Note**: Live trading infrastructure is preserved behind the `FEATURE_LIVE_TRADING` platform setting (default: false). The platform currently operates in simulation-only mode for strategy testing and optimization.

## Architecture Rules

### Multi-Tenancy
- **`BelongsToUser` trait** on all user-owned models: auto-sets user_id on create, provides `user()` relationship and `scopeForUser()` scope
- **NO global scopes** — commands run without auth context, so explicit `forUser($userId)` calls are required
- **`UserBotRunner` service** iterates active users with Polymarket credentials, try/catch per user, Telegram error notifications
- **`SettingsService`** is user-aware: all methods accept optional `?int $userId` (defaults to `auth()->id()`)
- **`PlatformSettingsService`** handles global admin config (no user_id)

### Service-Oriented
- All business logic lives in `app/Services/`, NOT in controllers or commands
- Controllers are thin: validate input, call service, return view
- Commands are thin: call service, handle output
- Models define relationships and scopes only

### 3-Tier AI Pattern
1. **Reflexes** (`ReflexesService`) — Free PHP rule-based logic. Runs every minute. Checks price thresholds, desync, volatility, monitored assets.
2. **Muscles** (`MusclesService`) — Cheap Claude Haiku. Runs every 5 min. Pre-scores markets near close with confidence scoring.
3. **Brain** (`BrainService`) — Expensive Claude Sonnet. On-demand: loss audits, daily/weekly reviews with forensic analysis and suggested parameter fixes.

### Trading Pipeline
`StrategyEngine` orchestrates the full simulation pipeline per user:
1. `MarketService` fetches active Polymarket crypto markets
2. `MarketTimingService` identifies markets in entry window (last 60s)
3. `PriceAggregator` combines Binance + Polymarket prices, detects desync
4. `VolatilityCalculator` estimates reversal probability
5. `ReflexesService` applies rule-based filters
6. `SignalGenerator` combines Reflexes + Muscles, enforces confidence threshold
7. `RiskManager` enforces daily loss, trade count, concurrent position limits, calculates bet size
8. `TradeExecutor` creates simulated trade record, simulates order execution, logs forensics

### Admin-Editable Parameters
**CRITICAL**: ALL trading parameters live in the `strategy_params` DB table (per-user), read via `SettingsService`. NEVER hardcode trading rules. Platform-wide settings (API keys, AI models, budgets) live in `platform_settings` table, read via `PlatformSettingsService`.

### Middleware Layers
- `subscribed` — Redirects to `/subscription` if trial expired and no active subscription
- `superadmin` — 403 if not super admin
- Route groups: public (no auth) → auth-only (settings, subscription) → main app (auth+subscribed) → admin (auth+superadmin)

## Code Style
- PSR-12 coding standard
- Strict types: `declare(strict_types=1);` in every PHP file
- Type hints on all method parameters and return types
- Use Laravel's `Http` facade for external API calls
- Use `Log::channel('bot')` for all bot-related logging
- JSON responses from AI must be parsed with try/catch
- All monetary values as `decimal` in DB, never float in PHP — use `bcmath` or string casting

## Database Conventions
- Always use migrations, never raw SQL
- Foreign keys with cascading deletes where appropriate
- All user-owned tables have `user_id` FK with compound indexes
- JSON columns for flexible data (trade logs, AI responses, IPN data)
- Enum columns for fixed sets (status, side, tier, subscription_plan)
- Timestamps on every table
- Soft deletes on `trades` table only
- Encrypted casts on `user_credentials` for API keys

## Security Rules
- NEVER log or display: private keys, API keys, secrets
- All env secrets in `.env` only, never in code or DB (except platform_settings for API keys stored encrypted)
- API keys stored encrypted via Laravel's `encrypted` cast in UserCredential model
- Validate all input server-side
- Use Laravel's CSRF protection on all forms (webhook excluded via `bootstrap/app.php`)
- Rate-limit all external API calls via `ApiRateLimiter` service
- NOWPayments IPN verified via HMAC-SHA512 signature
- Polymarket API uses HMAC-SHA256 signed requests with exponential backoff retry

## File Structure
```
app/
├── Console/Commands/              # 11 Artisan commands (thin, call services)
│   ├── SimAiAnalyzeMarkets        # Haiku market pre-scoring
│   ├── SimAiAuditLosses           # Sonnet loss forensics
│   ├── CheckExpiredSubscriptions  # Hourly subscription check
│   ├── SimCleanupLogs            # Prune old records
│   ├── SimDailyReview            # Sonnet daily review
│   ├── SimDailySummaryCommand    # Compile daily stats
│   ├── SimExecuteTrades          # Signal generation + simulated trade placement
│   ├── SimMonitorPositions       # Resolve completed markets
│   ├── SimScanMarkets            # Find entry windows
│   ├── SimSnapshotBalance        # Record equity + alerts
│   └── SimWeeklyReport           # Sonnet weekly analysis
├── Exceptions/
│   └── Handler                    # Global exception handler
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                 # 8 super admin controllers
│   │   │   ├── AdminAiCostController
│   │   │   ├── AdminAnnouncementController
│   │   │   ├── AdminDashboardController
│   │   │   ├── AdminLogController
│   │   │   ├── AdminPaymentController
│   │   │   ├── AdminPlanController
│   │   │   ├── AdminSettingController
│   │   │   └── AdminUserController
│   │   ├── Auth/                  # Breeze (10) + GoogleAuthController
│   │   ├── PublicController       # Landing, pricing, terms, privacy, contact
│   │   ├── SubscriptionController # Plan management + crypto checkout
│   │   ├── WebhookController      # NOWPayments IPN
│   │   ├── TelegramWebhookController # Telegram bot updates
│   │   ├── DashboardController    # User dashboard (scoped)
│   │   ├── TradeController        # User trades + CSV export (scoped)
│   │   ├── AuditController        # AI audits + approve/reject fixes (scoped)
│   │   ├── BalanceController      # Equity curve (scoped)
│   │   ├── StrategyController     # Per-user strategy params (scoped)
│   │   ├── LogController          # Bot log viewer (scoped)
│   │   ├── AiCostController       # AI spend tracking (scoped)
│   │   ├── CredentialController   # API key management
│   │   ├── HealthCheckController  # /health endpoint
│   │   ├── ProfileSettingsController
│   │   ├── NotificationSettingsController
│   │   └── TelegramSettingsController
│   └── Middleware/
│       ├── EnsureActiveSubscription
│       └── EnsureSuperAdmin
├── Models/                        # 13 Eloquent models
│   ├── User                       # SaaS fields, subscription helpers, Telegram
│   ├── UserCredential             # Encrypted API keys per user
│   ├── SubscriptionPlan           # Admin-editable plans with limits
│   ├── Payment                    # NOWPayments records
│   ├── PlatformSetting            # Global admin config
│   ├── Announcement               # Admin announcements
│   ├── Trade                      # Trade records (soft deletes)
│   ├── TradeLog                   # Forensic event logs
│   ├── AiDecision                 # Claude API call records + cost
│   ├── AiAudit                    # Loss/daily/weekly audits with fixes
│   ├── BalanceSnapshot            # Time-series equity tracking
│   ├── StrategyParam              # Per-user trading parameters
│   └── DailySummary               # Compiled daily stats
├── Services/
│   ├── AI/
│   │   ├── AIRouter               # Routes to Muscles/Brain based on plan limits
│   │   ├── AnthropicClient        # Claude API HTTP client with retry
│   │   ├── BrainService           # Sonnet: loss audits, daily/weekly reviews
│   │   ├── CostTracker            # Token/cost tracking, budget enforcement
│   │   ├── MusclesService         # Haiku: quick market confidence scoring
│   │   └── PromptBuilder          # System/user prompts for all AI tiers
│   ├── Audit/
│   │   ├── ForensicsBuilder       # Reconstructs complete trade forensics
│   │   └── StrategyUpdater        # Validates + applies AI parameter fixes
│   ├── Payment/
│   │   └── NOWPaymentsService     # Invoice creation, IPN verification
│   ├── Polymarket/
│   │   ├── BalanceService         # Fetch USDC balance + open positions
│   │   ├── MarketService          # Normalize markets, filter crypto, parse prices
│   │   ├── OrderService           # Place/cancel orders, DRY_RUN simulation
│   │   └── PolymarketClient       # HMAC-SHA256 signed HTTP client
│   ├── PriceFeed/
│   │   ├── BinanceService         # Spot prices, klines, price changes
│   │   ├── PriceAggregator        # Binance vs Polymarket desync detection
│   │   └── VolatilityCalculator   # 1-min volatility, reversal probability
│   ├── RateLimiter/
│   │   └── ApiRateLimiter         # Generic cache-based rate limiter
│   ├── Settings/
│   │   ├── SettingsService        # Per-user strategy params (cached 1h)
│   │   └── PlatformSettingsService # Global platform config (cached 1h)
│   ├── Subscription/
│   │   └── SubscriptionService    # Plan limits, activation, cancellation
│   ├── Telegram/
│   │   ├── NotificationFormatter  # Format messages for Telegram HTML
│   │   ├── NotificationService    # Send notifications (respects preferences + throttle)
│   │   └── TelegramBotService     # Bot API client, /start /status /today /balance commands
│   ├── Trading/
│   │   ├── MarketTimingService    # Entry window detection (last 60s)
│   │   ├── ReflexesService        # Rule-based filters (price, desync, volatility)
│   │   ├── RiskManager            # Daily loss/trade/position limits, bet sizing
│   │   ├── SignalGenerator        # Combines Reflexes + Muscles signals
│   │   ├── StrategyEngine         # Main trading orchestrator
│   │   └── TradeExecutor          # Creates trade, places order, logs forensics
│   └── UserBotRunner              # Multi-tenant bot execution with per-user error handling
├── Traits/
│   └── BelongsToUser              # Auto user_id, user() relation, scopeForUser()
resources/views/
├── layouts/
│   ├── admin.blade.php            # Bootstrap 5 dark sidebar for user dashboard
│   ├── super-admin.blade.php      # Indigo sidebar for admin panel
│   └── public.blade.php           # Marketing website layout
├── public/                        # Landing, pricing, terms, privacy, contact
├── subscription/                  # Plan selection, success, cancel
├── admin/                         # Super admin views (dashboard, users, payments, plans, settings, logs, ai-costs, announcements)
├── settings/                      # User settings (credentials, profile, notifications, telegram)
├── dashboard.blade.php            # User dashboard with announcements + subscription status
├── trades/                        # index + show (scoped by user) + CSV export
├── audits/                        # index + show (scoped by user) + approve/reject fixes
├── strategy/                      # Grouped param editor (scoped by user)
├── balance/                       # Equity curve (scoped by user)
├── logs/                          # Filtered log viewer (scoped by user)
└── ai-costs/                      # Spend tracking (scoped by user)
```

## Route Structure
```
Public (no auth):           /, /pricing, /terms, /privacy, /contact, /health
Auth (Breeze + Google):     /login, /register, /auth/google, /auth/google/callback
Settings (auth only):       /settings/credentials, /settings/profile, /settings/notifications, /settings/telegram
Subscription (auth only):   /subscription, /subscription/checkout, /subscription/success, /subscription/cancel
Main App (auth+subscribed): /dashboard, /trades, /trades/export, /audits, /strategy, /balance, /logs, /ai-costs
Admin (auth+superadmin):    /admin, /admin/users, /admin/payments, /admin/plans, /admin/settings, /admin/logs, /admin/ai-costs, /admin/announcements
Webhook (no auth/CSRF):     POST /api/webhooks/nowpayments, POST /api/webhooks/telegram
```

## Key Gotchas
- **SIMULATION-ONLY MODE**: All trades are simulated. No real orders are placed on Polymarket.
- **Live trading infrastructure** is preserved behind `FEATURE_LIVE_TRADING=false` for potential future use
- 15-minute markets are TIME-CRITICAL: server clock must use NTP for accurate simulation
- Every cron command uses `->withoutOverlapping()` to prevent duplicate simulations
- Polymarket API can change without notice — all calls wrapped in try/catch with exponential backoff retry
- The simulator only generates signals in the LAST 30-60 seconds of each 15-min cycle
- API desync between Binance and Polymarket is the #1 historical failure mode — `PriceAggregator` detects this
- Always scope queries by user_id — use `forUser()` scope, never query without user context
- UserCredential uses encrypted casts — never log decrypted values
- Webhook routes are excluded from CSRF in `bootstrap/app.php`
- All users operate in simulation mode by default — no real capital at risk
- Telegram and Anthropic API keys are stored in `platform_settings` (global)
- Polymarket API keys (if stored) are per-user in `user_credentials` but NOT used for live trading
- `SubscriptionService` enforces plan limits on max simulated trades, max positions, and available AI tiers

## Reference
The full detailed specification is in `SPEC.md` in the project root.
