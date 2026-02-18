# PolyTraderX

PolyTraderX is a simulation-first strategy lab for crypto prediction markets.

It is designed to help users test strategy logic against live market data with no live order execution and no wallet connection.

## Core Capabilities

- Simulation-only trading workflow (no live trades, no user Polymarket API keys required)
- Per-user strategy controls for risk, entry windows, monitored assets, market durations, notifications, and simulator on/off
- Public market-data scanning for 5-minute and 15-minute crypto markets
- Multi-source spot context for strategy calculations
- AI tiers (Reflexes, Muscles, Brain) with hard backend caps and budget guardrails
- Plan-based subscriptions with NOWPayments, trial handling, and expiration automation
- Telegram account linking and command-based user updates
- Admin Telegram messaging (single user or broadcast) with send history and optional image attachment
- Dashboard announcements with expiry, dismissal tracking, and optional Telegram/email broadcast
- Branded lifecycle emails for account, payment, subscription, and support events
- Cloudflare Turnstile on login, registration, and forgot-password flows
- TOTP 2FA for both users and admins
- Sentry-first logging/error reporting

## Data and Execution Model

- The scanner runs every minute and polls Binance multiple times per cycle (3 to 5 polls, 10 to 15 seconds apart).
- Markets are fetched from Polymarket public endpoints, normalized, and filtered per user settings.
- The simulator evaluates signals and tracks positions in the background.
- AI usage is limited by plan caps and global monthly budget to prevent runaway costs.
- AI suggestions are saved for review; strategy parameters are not auto-mutated by AI.

## Tech Stack

- PHP 8.2+, Laravel 12
- MySQL 8
- Bootstrap 5, Chart.js, Vite
- Laravel Breeze auth + Socialite (Google login)
- NOWPayments (crypto subscription checkout)
- Telegram Bot API
- Anthropic API (optional for AI tiers)
- Sentry Laravel SDK

## Quick Start

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

- Email: value of `SUPERADMIN_EMAIL` in `.env` (default `admin@polytraderx.xyz`)
- Password: value of `SUPERADMIN_PASSWORD` in `.env` (default `password`)

## Configuration Model

PolyTraderX uses both `.env` and database-backed platform settings.

Use `.env` for infrastructure/runtime:

- app URL/env
- database, queue, cache, session, mail transport
- Google OAuth
- Turnstile
- base URLs for public market/price providers
- queue names (`MAIL_QUEUE`, `TELEGRAM_QUEUE`)

Use Admin -> Settings (platform_settings table) for operational secrets and runtime controls:

- `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`
- `ANTHROPIC_API_KEY`, AI model keys, AI monthly budget
- NOWPayments platform keys
- general toggles and limits

## Scheduler and Queues

Add this cron on production:

```cron
* * * * * cd /path/to/polytraderx && php artisan schedule:run >> /dev/null 2>&1
```

Run queue workers. Recommended separate workers per queue:

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3
php artisan queue:work --queue=emails --sleep=3 --tries=3
php artisan queue:work --queue=telegram --sleep=3 --tries=3
```

### Scheduled Commands

| Command | Frequency | Purpose |
| --- | --- | --- |
| `subscriptions:check-expired` | Hourly | Expiry warnings, downgrades, simulator disable on expiry |
| `payments:expire-pending` | Hourly | Marks pending payments expired after 5 hours |
| `sim:scan-markets` | Every minute | Scans active markets for all active users |
| `sim:evaluate-signals` | Every minute | Runs strategy evaluation and simulated trade placement |
| `sim:monitor-positions` | Every minute | Resolves open simulated positions |
| `sim:analyze-markets` | Every 5 minutes | Muscles-tier AI pre-analysis |
| `sim:audit-losses` | Every 5 minutes | Brain-tier AI audit on losses |
| `sim:snapshot-balance` | Every 15 minutes | Balance/equity snapshots and alerts |
| `sim:daily-summary` | Daily at 00:05 | Daily summary processing |
| `sim:daily-review` | Daily at 23:55 | AI daily review |
| `sim:weekly-report` | Sunday at 23:55 | AI weekly report |
| `sim:cleanup-logs` | Hourly | Prunes logs and removes market-scan records older than 24h |

Admin Telegram queue processing command:

- `telegram:process-admin-messages --limit=200`
- If you use admin Telegram broadcasts, schedule this command every minute as well.

## Telegram Setup

1. Add `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_SECRET` in Admin -> Settings.
2. Set webhook to `https://your-domain/api/webhooks/telegram` with the same secret token.
3. In Telegram, users link by sending `/start YOUR-ACCOUNT-ID`.
4. Available bot commands include `/status`, `/today`, `/balance`, `/unlink`, `/help`.

## Payments Setup

- Configure NOWPayments credentials.
- Webhook endpoint: `POST /api/webhooks/nowpayments`
- IPN signature verification is enforced before subscription activation.

## Security Features

- Turnstile validation on login/register/forgot-password
- Login throttling
- TOTP 2FA challenge on login when enabled
- Simulation acknowledgment gate before app access
- Sentry exception handling and logging channels

## Admin Operations

- Service diagnostics UI: `/admin/settings/diagnostics`
- Telegram diagnostics UI: `/admin/settings/telegram-diagnostics`
- Health endpoint: `/health` (enhanced output for superadmin)
- Temporary migration route (token-protected): `/admin/run-migration?token=...`
  - Uses `MAINTENANCE_ROUTE_TOKEN`
  - Intended for constrained hosting (for example, no shell access)

## Seeders and Caution Notes

- `SubscriptionPlansSeeder` replaces the existing plan set (deletes current plan records before recreate).
- `PlatformSettingsSeeder` creates missing keys and updates metadata, but does not overwrite existing configured values.
- New users are seeded with default per-user settings and simulator disabled by default.

## Testing

```bash
php artisan test
```

Tests run with in-memory SQLite as configured in `phpunit.xml`.

## Deployment

See `DEPLOYMENT.md` for server deployment details.

## License

Proprietary. All rights reserved.
