# PolyTraderX

PolyTraderX is a simulation-only strategy lab for crypto prediction markets.

It uses live public market and spot-price data, but does not place live trades and does not require user wallet/API connection for core simulation.

## Current Feature Set

- Simulation-only trading engine (paper trades)
- Per-user strategy controls:
  - risk limits, confidence thresholds, configurable entry-window range (`ENTRY_WINDOW_MIN_SECONDS` / `ENTRY_WINDOW_MAX_SECONDS`)
  - monitored assets and market durations (5m/15m)
  - simulator toggle and notification preferences
  - selectable spot-price source (`binance`, `coingecko`, `coinbase`, `kraken`)
  - threshold and confidence inputs normalized automatically (accepts ratio 0.92 or percent 92)
- Market scans and strategy evaluation with user-visible scan logs
- Simulator auto-disables when balance reaches zero
- 5m and 15m market resolution handled with correct per-duration timing
- AI tiers:
  - Reflexes (rule-based, free)
  - Muscles (market pre-analysis, Haiku)
  - Brain (loss audits + daily/weekly reviews, Sonnet)
- Hard backend AI guardrails:
  - per-plan limits, monthly token caps, daily call caps
  - global budget guardrail (`AI_MONTHLY_BUDGET`)
  - anti-duplicate Muscles caching/locking/cooldown
  - low-burn defaults for Muscles prompt/completion caps
  - `AI_AUDIT_RECHARGED_AT` admin gate — loss audits only run after this marker is set, preventing unexpected spend after a top-up
- Subscription and billing:
  - NOWPayments checkout/callback flow
  - plan lifecycle, pending-expiry handling, subscription expiration handling
  - admin email notification on successful payment
  - no free plan; paid subscriptions only
- Telegram:
  - account linking via `/start ACCOUNT_ID`
  - display name sourced from `telegram_first_name` captured at link time
  - user alerts and summaries (queued)
  - admin single/broadcast messaging with history, optional image, and per-user targeting
- Announcements:
  - dashboard announcements with dismiss tracking and expiry
  - optional Telegram and email broadcast, with per-user/per-plan targeting
- Security:
  - Cloudflare Turnstile on login/register/forgot-password
  - TOTP 2FA for users and admins
  - Sentry-backed error reporting

## Simulation and AI Model

- Scanner runs every minute and polls Binance multiple times per minute (3 to 5 polls, with 10 to 15 second spacing).
- Markets come from Polymarket public endpoints and are filtered to supported crypto 5m/15m markets.
- 5m and 15m markets resolve at different times; the resolution monitor uses per-duration timing.
- AI suggestions are stored as `pending_review`; there is no automatic strategy mutation.
- Failed Brain audits are retryable (trades are not marked audited unless an audit record is created).
- Loss audits require `AI_AUDIT_RECHARGED_AT` to be set in admin settings; audits on trades resolved before this marker are skipped. Set it to the current date after each API credit top-up.

## Tech Stack

- PHP 8.2+, Laravel 12
- MySQL 8
- Bootstrap 5, Chart.js, Vite
- Laravel Breeze + Socialite (Google auth)
- NOWPayments
- Telegram Bot API
- Anthropic API (optional, for Muscles/Brain)
- Sentry Laravel SDK

## Local Setup

```bash
git clone <repo-url>
cd polytraderx
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then open `http://localhost:8000`.

## Default Superadmin

- Email: `SUPERADMIN_EMAIL` in `.env` (default `admin@polytraderx.xyz`)
- Password: `SUPERADMIN_PASSWORD` in `.env` (default `password`)

## Configuration Model

Use `.env` for infrastructure/runtime:

- app environment and URL
- database/session/cache/queue/mail transport
- Google OAuth, Turnstile
- provider base URLs
- queue names (`MAIL_QUEUE`, `TELEGRAM_QUEUE`)

Use Admin `Settings` (database `platform_settings`) for operational runtime secrets/toggles:

- `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`
- `ANTHROPIC_API_KEY`, AI model keys, AI budget and low-burn controls
- NOWPayments keys
- platform-level feature toggles

## Scheduler and Queues

Production cron:

```cron
* * * * * cd /path/to/polytraderx && php artisan schedule:run >> /dev/null 2>&1
```

Recommended queue workers:

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3
php artisan queue:work --queue=emails --sleep=3 --tries=3
php artisan queue:work --queue=telegram --sleep=3 --tries=3
```

### Scheduled Commands

| Command | Frequency | Purpose |
| --- | --- | --- |
| `subscriptions:check-expired` | Hourly | Subscription expiry handling and downgrade flow |
| `payments:expire-pending` | Hourly | Marks pending payments as expired after 5 hours |
| `sim:scan-markets` | Every minute | Scans active 5m/15m markets for active users |
| `sim:evaluate-signals` | Every minute | Strategy evaluation and simulated trade placement |
| `sim:monitor-positions` | Every minute | Monitors/resolves open simulated positions |
| `sim:analyze-markets` | Every 5 minutes | Muscles pre-analysis (respects low-burn controls) |
| `sim:audit-losses` | Every 5 minutes | Brain loss audits on unaudited losing trades |
| `sim:snapshot-balance` | Every 15 minutes | Balance snapshots and alert checks |
| `sim:daily-summary` | Every 15 minutes | Timezone-aware daily summary generation and delivery |
| `sim:daily-review` | Daily at 23:55 | Brain daily performance review |
| `sim:weekly-report` | Weekly Sunday at 23:55 | Brain weekly analysis/report |
| `sim:cleanup-logs` | Hourly | Prunes stale logs (market scans retained for 24h) |

If you use admin Telegram broadcasts, also schedule:

```bash
php artisan telegram:process-admin-messages --limit=200
```

Run it every minute.

## Telegram Setup

1. Add `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_SECRET` in Admin `Settings`.
2. Register webhook URL: `https://your-domain/api/webhooks/telegram` using the same secret.
3. User links account by sending `/start ACCOUNT_ID` to the bot.
4. Core bot commands include `/status`, `/today`, `/balance`, `/unlink`, `/help`.

## Payments Setup

- Configure NOWPayments API key and IPN secret.
- Webhook endpoint: `POST /api/webhooks/nowpayments`.
- IPN signature validation is enforced before activating subscriptions.

## Security and Observability

- Turnstile validation on login/register/forgot-password
- Login throttling and auth protections
- TOTP 2FA challenge on login when enabled
- Simulation acknowledgment gate before app access
- Sentry-enabled error capture and centralized reporting

## Admin Operations

- Service diagnostics: `/admin/settings/diagnostics`
- Telegram diagnostics: `/admin/settings/telegram-diagnostics`
- Health endpoint: `/health`

Temporary token-protected maintenance routes (delete after use):

- `/admin/run-migration?token=...`
- `/admin/run-telegram-enforcement?token=...`
- `/admin/run-ai-low-burn-profile?token=...`
- `/admin/telegram-diagnostics?token=...`

All use `MAINTENANCE_ROUTE_TOKEN`.

## Seeder Behavior (Important)

- `SubscriptionPlansSeeder` deletes existing plan rows, then recreates canonical plan records.
- `PlatformSettingsSeeder` creates missing keys and refreshes metadata, but does not overwrite existing configured values.
- New users get default per-user strategy settings with simulator disabled by default.

## Testing

```bash
php artisan test
```

Tests run with SQLite in-memory (`phpunit.xml`).

## Deployment

See `DEPLOYMENT.md`.

## License

Proprietary. All rights reserved.
