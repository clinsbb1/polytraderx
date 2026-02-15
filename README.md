# PolyTraderX

AI-powered strategy simulation platform for Polymarket's 15-minute crypto prediction markets.

**Design, test, and refine trading strategies using real market data — without risking real money.**

## What It Does

PolyTraderX simulates trading strategies for Polymarket's 15-minute BTC/ETH/SOL prediction markets using a **late-minute certainty** approach: signals are generated only in the final 30-60 seconds when the outcome is near-certain (>92% confidence). The platform uses a 3-tier AI architecture — free PHP rule-based Reflexes (every minute), cheap Claude Haiku Muscles (every 5 min), and expensive Claude Sonnet Brain (on-demand audits) — to minimize API costs while maximizing decision quality. Every simulated loss is automatically audited by Sonnet with suggested parameter fixes. Delivered as a multi-tenant SaaS with crypto subscription billing via NOWPayments.

**All trading is simulated** — no real orders are placed, no funds are at risk. This is a strategy lab for testing and optimization.

## Tech Stack

- **PHP 8.2+** / **Laravel 12**
- **Bootstrap 5** + **Chart.js**
- **MySQL 8**
- **Anthropic Claude API** (Haiku + Sonnet)
- **NOWPayments** (crypto subscriptions)
- **Telegram Bot API** (notifications + commands)
- **Binance Public API** (real-time spot prices)
- **Laravel Breeze** + **Socialite** (auth + Google OAuth)

## Quick Start (Local Development)

```bash
git clone <repo-url> && cd polytraderx
composer install
npm install && npm run build
cp .env.example .env
# Edit .env with your database credentials
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
# Visit http://localhost:8000
```

## Default Superadmin

- **Email**: value from `SUPERADMIN_EMAIL` in `.env` (default: `admin@polytraderx.xyz`)
- **Password**: value from `SUPERADMIN_PASSWORD` in `.env` (default: `password`)

## Running the Bot

The bot runs via Laravel's scheduler. Add this to your server's crontab:

```
* * * * * cd /path-to-polytraderx && php artisan schedule:run >> /dev/null 2>&1
```

Also start the queue worker for background jobs:

```bash
php artisan queue:work --sleep=3 --tries=3
```

### Simulation Mode

The platform operates exclusively in simulation mode. All "trades" are simulated using real Polymarket market data and Binance price feeds. No real orders are placed.

Live trading infrastructure is preserved behind the `FEATURE_LIVE_TRADING` platform setting (default: false) for potential future use. See `docs/POLYMARKET_SIGNER_SERVICE.md` for details on the live trading requirements.

## Scheduled Commands

| Command | Frequency | Description |
|---------|-----------|-------------|
| `subscriptions:check-expired` | Hourly | Warn users before expiry, deactivate expired subscriptions |
| `sim:scan-markets` | Every minute | Find active Polymarket crypto markets in entry window |
| `sim:execute-trades` | Every minute | Generate signals and simulate orders for each user |
| `sim:monitor-positions` | Every minute | Track open positions, resolve completed markets |
| `sim:ai-analyze-markets` | Every 5 min | Pre-score markets near close with Claude Haiku |
| `sim:ai-audit-losses` | Every 5 min | Post-loss forensic audit with Claude Sonnet |
| `sim:snapshot-balance` | Every 15 min | Record balance/equity, check low balance + drawdown alerts |
| `sim:daily-review` | Daily 23:55 | Daily AI performance review (Sonnet) |
| `sim:daily-summary` | Daily 00:05 | Compile yesterday's stats, send Telegram summary |
| `sim:cleanup-logs` | Daily | Prune trade_logs >90d, snapshots >180d, ai_decisions >90d |
| `sim:weekly-report` | Sunday 23:55 | Weekly deep analysis with Claude Sonnet |

## Deployment

See `DEPLOYMENT.md` for the full Hostinger VPS deployment guide.

## Testing

```bash
php artisan test
# 161 tests, 387 assertions
```

## License

Proprietary — All rights reserved.
