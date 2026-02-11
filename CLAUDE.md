# PolyTraderX — Multi-Tenant SaaS Polymarket Trading Bot

## Project Overview
PolyTraderX is a **multi-tenant SaaS** Laravel app that auto-trades Polymarket's 15-minute crypto prediction markets (BTC, ETH, SOL). The core edge is **late-minute certainty trading**: enter only in the final 30–60 seconds when outcome is near-certain (>92% confidence). Uses a 3-tier AI cost architecture (Reflexes/Muscles/Brain), logs everything with full forensics, self-audits after every loss.

**Multi-tenant architecture**: Each user has their own strategy_params, trades, API credentials (encrypted), and subscription. The `BelongsToUser` trait provides user scoping on all data models. No global scopes — commands use `UserBotRunner` to iterate users explicitly.

## Tech Stack
- **PHP 8.2+** / **Laravel 12**
- **Bootstrap 5** + Blade templates (admin UI + public website)
- **Chart.js** for charts
- **MySQL 8** database
- **Laravel Breeze** for auth + **Laravel Socialite** for Google OAuth
- **NOWPayments** for crypto subscription billing
- **Database queue driver** (no Redis)
- **Timezone**: Africa/Lagos (per-user configurable)

## Common Commands
```bash
php artisan serve                    # Dev server
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Reset + seed (plans, settings, superadmin, demo user)
php artisan db:seed                  # Seed all
php artisan test                     # Run tests
php artisan schedule:run             # Run scheduler manually
php artisan queue:work               # Process queued jobs
php artisan subscriptions:check-expired  # Deactivate expired subscriptions (runs hourly via scheduler)

# Bot commands (run for all active users via UserBotRunner)
php artisan bot:scan-markets         # Find active 15-min markets
php artisan bot:execute-trades       # Place orders
php artisan bot:monitor-positions    # Track open positions
php artisan bot:ai-analyze-markets   # Haiku: score markets
php artisan bot:ai-audit-losses      # Sonnet: post-loss forensics
php artisan bot:daily-review         # Sonnet: daily review
php artisan bot:weekly-report        # Sonnet: weekly deep analysis
php artisan bot:snapshot-balance     # Record balance
php artisan bot:daily-summary        # Compile stats + Telegram
```

## Architecture Rules

### Multi-Tenancy
- **`BelongsToUser` trait** on all user-owned models: auto-sets user_id on create, provides `user()` relationship and `scopeForUser()` scope
- **NO global scopes** — commands run without auth context, so explicit `forUser($userId)` calls are required
- **`UserBotRunner` service** iterates active+subscribed+onboarded users with try/catch per user
- **`SettingsService`** is user-aware: all methods accept optional `?int $userId` (defaults to `auth()->id()`)
- **`PlatformSettingsService`** handles global admin config (no user_id)

### Service-Oriented
- All business logic lives in `app/Services/`, NOT in controllers or commands
- Controllers are thin: validate input, call service, return view
- Commands are thin: call service, handle output
- Models define relationships and scopes only

### 3-Tier AI Pattern
1. **Reflexes** (`ReflexesService`) — Free PHP rule-based logic. Runs every minute.
2. **Muscles** (`MusclesService`) — Cheap Claude Haiku. Runs every 5 min.
3. **Brain** (`BrainService`) — Expensive Claude Sonnet. On-demand only.

### Admin-Editable Parameters
**CRITICAL**: ALL trading parameters live in the `strategy_params` DB table (per-user), read via `SettingsService`. NEVER hardcode trading rules. Platform-wide settings live in `platform_settings` table, read via `PlatformSettingsService`.

### Middleware Layers
- `onboarded` — Redirects to `/onboarding` if not completed
- `subscribed` — Redirects to `/subscription` if trial expired and no active subscription
- `superadmin` — 403 if not super admin
- Route groups: public (no auth) → onboarding (auth) → subscription (auth+onboarded) → main app (auth+onboarded+subscribed) → admin (auth+superadmin)

### External Price Feeds (Required)
- Binance API for real-time BTC/ETH/SOL spot prices
- Cross-check against Polymarket implied price
- Flag API desyncs → auto-SKIP trade

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
- All env secrets in `.env` only, never in code or DB
- API keys stored encrypted via Laravel's `encrypted` cast in UserCredential model
- Validate all input server-side
- Use Laravel's CSRF protection on all forms (webhook excluded via `bootstrap/app.php`)
- Rate-limit all external API calls
- NOWPayments IPN verified via HMAC-SHA512 signature

## File Structure
```
app/
├── Console/Commands/              # Artisan commands (thin, call services)
│   └── CheckExpiredSubscriptions  # Hourly subscription check
├── Http/
│   ├── Controllers/               # User-facing controllers
│   │   ├── Admin/                 # Super admin controllers (7 controllers)
│   │   ├── Auth/                  # Breeze + GoogleAuthController
│   │   ├── PublicController       # Landing, pricing, terms, privacy, contact
│   │   ├── OnboardingController   # 5-step onboarding wizard
│   │   ├── SubscriptionController # Plan management + crypto checkout
│   │   ├── WebhookController      # NOWPayments IPN
│   │   ├── DashboardController    # User dashboard (scoped)
│   │   ├── TradeController        # User trades (scoped)
│   │   ├── CredentialController   # API key management
│   │   ├── ProfileSettingsController
│   │   └── NotificationSettingsController
│   └── Middleware/
│       ├── EnsureOnboarded
│       ├── EnsureActiveSubscription
│       └── EnsureSuperAdmin
├── Models/                        # 13 Eloquent models
│   ├── User                       # Extended with SaaS fields, relationships, helpers
│   ├── UserCredential             # Encrypted API keys per user
│   ├── SubscriptionPlan           # Admin-editable plans
│   ├── Payment                    # NOWPayments records
│   ├── PlatformSetting            # Global admin config
│   ├── Announcement               # Admin announcements
│   ├── Trade, TradeLog, AiDecision, AiAudit, BalanceSnapshot, StrategyParam, DailySummary
├── Services/
│   ├── Settings/
│   │   ├── SettingsService        # Per-user strategy params (cached)
│   │   └── PlatformSettingsService # Global platform config (cached)
│   ├── Subscription/
│   │   └── SubscriptionService    # Plan management, limits, activation
│   ├── Payment/
│   │   └── NOWPaymentsService     # Invoice creation, IPN verification
│   ├── UserBotRunner              # Multi-tenant bot execution
│   ├── Polymarket/                # CLOB API (TODO)
│   ├── PriceFeed/                 # Binance (TODO)
│   ├── AI/                        # Brain/Muscles/Reflexes (TODO)
│   ├── Trading/                   # Strategy, risk (TODO)
│   ├── Audit/                     # Loss forensics (TODO)
│   └── Telegram/                  # Notifications (TODO)
├── Traits/
│   └── BelongsToUser              # Auto user_id, user() relation, scopeForUser()
resources/views/
├── layouts/
│   ├── admin.blade.php            # Bootstrap 5 dark sidebar for user dashboard
│   ├── super-admin.blade.php      # Indigo sidebar for admin panel
│   └── public.blade.php           # Marketing website layout
├── public/                        # Landing, pricing, terms, privacy, contact
├── onboarding/                    # 5-step wizard views
├── subscription/                  # Plan selection, success, cancel
├── admin/                         # Super admin views (dashboard, users, payments, plans, settings, logs, announcements)
├── settings/                      # User settings (credentials, profile, notifications)
├── dashboard.blade.php            # User dashboard with announcements + subscription status
├── trades/                        # index + show (scoped by user)
├── audits/                        # index + show (scoped by user)
├── strategy/                      # Grouped param editor (scoped by user)
├── balance/                       # Equity curve (scoped by user)
├── logs/                          # Filtered log viewer (scoped by user)
└── ai-costs/                      # Spend tracking (scoped by user)
```

## Route Structure
```
Public (no auth):           /, /pricing, /terms, /privacy, /contact
Auth (Breeze + Google):     /login, /register, /auth/google, /auth/google/callback
Onboarding (auth):          /onboarding, /onboarding/polymarket, /onboarding/telegram, /onboarding/anthropic, /onboarding/activate
Subscription (auth+onboarded): /subscription, /subscription/checkout, /subscription/success, /subscription/cancel
Main App (auth+onboarded+subscribed): /dashboard, /trades, /audits, /strategy, /balance, /logs, /ai-costs, /settings/*
Admin (auth+superadmin):    /admin, /admin/users, /admin/payments, /admin/plans, /admin/settings, /admin/logs, /admin/announcements
Webhook (no auth/CSRF):     POST /api/webhooks/nowpayments
```

## Key Gotchas
- Polymarket uses EIP-712 signatures on Polygon — may need `kornrunner/keccak` + `simplito/elliptic-php`, or a small Node helper script
- 15-minute markets are TIME-CRITICAL: server clock must use NTP
- Every cron command must use `->withoutOverlapping()` to prevent duplicate trades
- Polymarket API can change without notice — wrap all calls in try/catch with retry
- The bot only enters in the LAST 30-60 seconds of each 15-min cycle
- API desync between Binance and Polymarket is the #1 historical failure mode
- Always scope queries by user_id — use `forUser()` scope, never query without user context
- UserCredential uses encrypted casts — never log decrypted values
- Webhook route is excluded from CSRF in `bootstrap/app.php`

## Build Phases
1. **Foundation**: Laravel + Breeze + migrations + models + SettingsService + admin layout ✅
2. **SaaS Conversion**: Multi-tenancy, public website, onboarding, subscriptions, admin panel ✅
3. **Polymarket + Price Feeds**: API clients, market fetching, Binance integration
4. **Trading Engine**: Reflexes, risk management, trade execution, cron commands
5. **AI Layer**: Muscles + Brain services, prompts, cost tracking, audit commands
6. **Notifications**: Telegram integration, all notification types
7. **Polish**: DRY_RUN testing, error handling, rate limiting, deployment

## Reference
The full detailed specification is in `SPEC.md` in the project root.
