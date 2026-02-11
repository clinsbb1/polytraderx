# PolyTraderX — Laravel Polymarket Trading Bot

## Project Overview
PolyTraderX is a Laravel 11 app that auto-trades Polymarket's 15-minute crypto prediction markets (BTC, ETH, SOL). The core edge is **late-minute certainty trading**: enter only in the final 30–60 seconds when outcome is near-certain (>92% confidence). Uses a 3-tier AI cost architecture (Reflexes/Muscles/Brain), logs everything with full forensics, self-audits after every loss, and has an admin dashboard.

## Tech Stack
- **PHP 8.2+** / **Laravel 11**
- **Bootstrap 5** + Blade templates (admin UI)
- **Chart.js** for charts
- **MySQL 8** database
- **Laravel Breeze** for auth
- **Database queue driver** (no Redis)
- **Timezone**: Africa/Lagos

## Common Commands
```bash
php artisan serve                    # Dev server
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Reset + seed
php artisan db:seed                  # Seed strategy_params
php artisan test                     # Run tests
php artisan schedule:run             # Run scheduler manually
php artisan queue:work               # Process queued jobs

# Bot commands
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

### Service-Oriented
- All business logic lives in `app/Services/`, NOT in controllers or commands
- Controllers are thin: validate input, call service, return view
- Commands are thin: call service, handle output
- Models define relationships and scopes only

### 3-Tier AI Pattern
1. **Reflexes** (`ReflexesService`) — Free PHP rule-based logic. Runs every minute. Market scanning, price checks, rule execution.
2. **Muscles** (`MusclesService`) — Cheap Claude Haiku. Runs every 5 min. Confidence scoring, pattern recognition.
3. **Brain** (`BrainService`) — Expensive Claude Sonnet. On-demand only. Post-loss forensics, strategy review.

### Admin-Editable Parameters
**CRITICAL**: ALL trading parameters live in the `strategy_params` DB table, read via `SettingsService`. NEVER hardcode trading rules in config files or .env. The .env only has SEED_ values for initial migration. Every param change is logged with: old value, new value, who changed it (admin/ai/system), timestamp.

### External Price Feeds (Required)
- Binance API for real-time BTC/ETH/SOL spot prices
- Cross-check against Polymarket implied price
- Flag API desyncs (known failure mode) → auto-SKIP trade

## Code Style
- PSR-12 coding standard
- Strict types: `declare(strict_types=1);` in every PHP file
- Type hints on all method parameters and return types
- Use Laravel's `Http` facade for external API calls
- Use `Log::channel('bot')` for all bot-related logging
- JSON responses from AI must be parsed with try/catch (AI can return malformed JSON)
- All monetary values as `decimal` in DB, never float in PHP — use `bcmath` or string casting

## Database Conventions
- Always use migrations, never raw SQL
- Foreign keys with cascading deletes where appropriate
- JSON columns for flexible data (trade logs, AI responses)
- Enum columns for fixed sets (status, side, tier)
- Timestamps on every table
- Soft deletes on `trades` table only

## Security Rules
- NEVER log or display: private keys, API keys, secrets
- All env secrets in `.env` only, never in code or DB
- Validate all admin input server-side
- Use Laravel's CSRF protection on all forms
- Rate-limit all external API calls

## Testing
- Feature tests for all admin dashboard routes
- Unit tests for: RiskManager, ReflexesService, SettingsService
- Use factories for Trade, TradeLog, AiAudit models
- Test DRY_RUN mode thoroughly — it must log but never place real orders

## File Structure
```
app/
├── Console/Commands/          # Artisan commands (thin, call services)
├── Http/Controllers/          # Admin controllers (thin, call services)
├── Models/                    # Eloquent models
├── Services/
│   ├── Polymarket/            # CLOB API, orders, balance, signing
│   ├── PriceFeed/             # Binance, price aggregation, volatility
│   ├── AI/                    # Router, Brain, Muscles, Reflexes, prompts, costs
│   ├── Trading/               # Strategy, risk, signals, execution, timing
│   ├── Audit/                 # Loss analysis, forensics, strategy updates
│   ├── Settings/              # SettingsService (DB params with cache)
│   └── Telegram/              # Notifications
resources/views/
├── layouts/admin.blade.php    # Bootstrap 5 admin layout with sidebar
├── dashboard.blade.php
├── trades/                    # index + show
├── audits/                    # index + show (with approve/reject)
├── strategy/                  # Grouped param editor
├── balance/                   # Equity curve
├── logs/                      # Filtered log viewer
└── ai-costs/                  # Spend tracking
```

## Key Gotchas
- Polymarket uses EIP-712 signatures on Polygon — may need `kornrunner/keccak` + `simplito/elliptic-php`, or a small Node helper script
- 15-minute markets are TIME-CRITICAL: server clock must use NTP
- Every cron command must use `->withoutOverlapping()` to prevent duplicate trades
- Polymarket API can change without notice — wrap all calls in try/catch with retry
- The bot only enters in the LAST 30-60 seconds of each 15-min cycle — the `MarketTimingService` must track cycle boundaries precisely
- API desync between Binance and Polymarket is the #1 historical failure mode

## Build Phases
Build in this order. Each phase should result in working, testable code:

1. **Foundation**: Laravel + Breeze + migrations + models + SettingsService + admin layout
2. **Polymarket + Price Feeds**: API clients, market fetching, Binance integration
3. **Trading Engine**: Reflexes, risk management, trade execution, cron commands
4. **AI Layer**: Muscles + Brain services, prompts, cost tracking, audit commands
5. **Notifications**: Telegram integration, all notification types
6. **Admin Dashboard**: All 8 dashboard pages with charts
7. **Polish**: DRY_RUN testing, error handling, rate limiting, deployment

## Reference
The full detailed specification is in `SPEC.md` in the project root. Read it for: detailed trading strategy, prompt templates, database schema details, API endpoints, dashboard page layouts, and the AI self-audit flow.
