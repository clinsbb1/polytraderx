# PolyTraderX — Claude Code Build Guide

## Prerequisites

1. **Claude Code installed**: `npm install -g @anthropic-ai/claude-code`
2. **PHP 8.2+** and **Composer** installed
3. **Node.js 18+** (for Laravel asset compilation)
4. **MySQL 8** running locally
5. **A Claude subscription** (Pro, Max, Teams, or Enterprise)

---

## Initial Setup (Do This Once)

### Step 1: Create the Laravel project

```bash
# Create project
composer create-project laravel/laravel polytraderx
cd polytraderx

# Install Breeze with Blade
composer require laravel/breeze --dev
php artisan breeze:install blade

# Install frontend deps and build
npm install
npm run build
```

### Step 2: Copy the Claude Code files into your project

Copy these files into your project root:
- `CLAUDE.md` → `polytraderx/CLAUDE.md`
- `SPEC.md` → `polytraderx/SPEC.md`

### Step 3: Set up your .env

Edit `polytraderx/.env` with your database credentials and API keys.
**Remember to rotate all keys first** (old ones were exposed).

### Step 4: Create the database

```bash
mysql -u root -p -e "CREATE DATABASE polytraderx;"
```

### Step 5: Launch Claude Code

```bash
cd polytraderx
claude
```

Claude Code will automatically read `CLAUDE.md` and understand the full project context.

---

## Build Sessions

### How to Use Each Session

1. Open Claude Code in the project directory: `claude`
2. Claude reads CLAUDE.md automatically
3. Give it the phase prompt below
4. Let it work — review changes, ask questions, iterate
5. Test the output before moving to next phase
6. Use `/clear` between major phases to reset context (CLAUDE.md persists)

### Tips for Best Results
- **Use Plan Mode first** (Shift+Tab twice): Ask Claude to plan each phase before coding
- **One phase per session**: Don't try to cram multiple phases
- **Review migrations before running**: Ask Claude to show you the migration code first
- **Test as you go**: Run `php artisan test` after each phase
- **Commit after each phase**: `git add -A && git commit -m "Phase N: description"`

---

## Phase 1: Foundation

**Prompt to Claude Code:**

```
Read SPEC.md for full details. Build Phase 1 — Foundation:

1. Create all database migrations (trades, trade_logs, ai_decisions, ai_audits, balance_snapshots, strategy_params, daily_summaries). Follow the exact schema in SPEC.md section 3.

2. Create all Eloquent models with:
   - Proper relationships (Trade hasMany TradeLogs, hasMany AiDecisions, etc.)
   - Casts for JSON columns, enums, and decimals
   - Scopes: Trade::won(), Trade::lost(), Trade::open(), Trade::today()

3. Create SettingsService in app/Services/Settings/SettingsService.php:
   - get(string $key, $default = null) — reads from strategy_params with Laravel cache
   - set(string $key, $value, string $updatedBy = 'system') — updates DB, logs old value, clears cache
   - getGroup(string $group) — returns all params in a group
   - All trading params come from here, NEVER from config/env

4. Create DatabaseSeeder that populates strategy_params with all default values from SPEC.md section 2.

5. Set up the admin layout:
   - Bootstrap 5 sidebar layout in resources/views/layouts/admin.blade.php
   - Sidebar links: Dashboard, Trades, AI Audits, Strategy, Balance, Logs, AI Costs
   - Top bar with bot status indicator and user dropdown
   - All routes behind Breeze auth middleware

6. Create route structure in routes/web.php for all admin pages (controllers can return empty views for now).

7. Set APP_TIMEZONE=Africa/Lagos in config/app.php.

Run migrations and seeders when done. Verify everything works.
```

**Verify:** `php artisan migrate:fresh --seed` should complete without errors.

---

## Phase 2: Polymarket + Price Feeds

**Prompt to Claude Code:**

```
Read SPEC.md sections 1 and 8. Build Phase 2 — Polymarket + Price Feed integrations:

1. PolymarketClient (app/Services/Polymarket/PolymarketClient.php):
   - HTTP wrapper using Laravel Http facade
   - Base URL: https://clob.polymarket.com
   - Auth header handling (API key + secret)
   - Retry with exponential backoff (3 attempts)
   - All methods return typed arrays/objects, not raw responses
   - Methods: getMarkets(), getOrderBook($marketId), getPositions(), getTrades(), getBalance()

2. MarketService (app/Services/Polymarket/MarketService.php):
   - getActiveCryptoMarkets() — filter for BTC/ETH/SOL 15-min markets
   - getMarketsEndingSoon($seconds = 180) — markets ending within N seconds
   - getMarketDetails($marketId)
   - Parse market slugs/questions to identify asset and cycle timing

3. OrderService (app/Services/Polymarket/OrderService.php):
   - placeOrder($marketId, $side, $price, $amount) — returns order ID
   - cancelOrder($orderId)
   - getOrderStatus($orderId)
   - Must check DRY_RUN setting — if true, log the order but don't actually call API

4. BalanceService (app/Services/Polymarket/BalanceService.php):
   - getBalance() — USDC balance
   - getOpenPositions() — list with market IDs and values

5. SignatureService (app/Services/Polymarket/SignatureService.php):
   - EIP-712 order signing for Polygon
   - Try kornrunner/keccak + simplito/elliptic-php first
   - If PHP crypto is too complex, create scripts/sign-order.js Node helper and shell_exec it
   - Add composer requires for any crypto packages

6. BinanceService (app/Services/PriceFeed/BinanceService.php):
   - getCurrentPrice($symbol) — e.g., 'BTCUSDT' returns float
   - getKlines($symbol, $interval = '1m', $limit = 15) — candle data
   - getPriceChange($symbol, $minutes = 15) — % change

7. PriceAggregator (app/Services/PriceFeed/PriceAggregator.php):
   - comparePrices($asset, $polymarketYesPrice) — returns desync detection
   - calculateTrueProbability($asset, $secondsRemaining) — Binance-based probability estimate

8. VolatilityCalculator (app/Services/PriceFeed/VolatilityCalculator.php):
   - estimate1MinVolatility($asset) — std dev of recent 1-min price changes
   - estimateReversalProbability($asset, $currentChangePct, $secondsRemaining)

Add proper config entries in config/services.php for Polymarket and Binance base URLs.
Write unit tests for PriceAggregator and VolatilityCalculator with mocked data.
```

**Verify:** Unit tests pass. Try calling BinanceService methods manually via tinker.

---

## Phase 3: Trading Engine

**Prompt to Claude Code:**

```
Read SPEC.md sections 1 and 4. Build Phase 3 — Trading Engine:

1. MarketTimingService (app/Services/Trading/MarketTimingService.php):
   - isInEntryWindow($market) — is market within ENTRY_WINDOW_SECONDS of close?
   - getSecondsRemaining($market) — seconds until market resolves
   - getCurrentCycleMarkets() — which 15-min cycles are active right now?
   - All timing uses strategy_params via SettingsService

2. ReflexesService (app/Services/AI/ReflexesService.php):
   - evaluateMarket($market, $spotData) — rule-based go/no-go
   - Rules: price threshold check, entry window check, desync check, volatility check
   - Returns: {action: BUY_YES|BUY_NO|SKIP, rule_triggered: string, details: array}
   - Reads ALL thresholds from SettingsService (never hardcoded)

3. RiskManager (app/Services/Trading/RiskManager.php):
   - canTrade() — checks BOT_ENABLED, DRY_RUN awareness, daily loss limit, daily trade count
   - calculateBetSize($confidence, $currentBankroll) — between $1 and MAX_BET_AMOUNT
   - checkConcurrentPositions() — current open count vs MAX_CONCURRENT_POSITIONS
   - checkDailyLoss() — sum today's losses vs MAX_DAILY_LOSS
   - All reads from SettingsService

4. SignalGenerator (app/Services/Trading/SignalGenerator.php):
   - generateSignal($market, $spotData, $musclesResult = null)
   - Combines reflexes output + muscles output (if available) into final signal
   - Final signal must pass: reflexes=go AND confidence >= MIN_CONFIDENCE_SCORE AND risk=ok

5. TradeExecutor (app/Services/Trading/TradeExecutor.php):
   - execute($signal, $market) — places trade (or logs in DRY_RUN mode)
   - Creates Trade record with all fields populated
   - Creates TradeLog entry with full forensic JSON (see SPEC.md section 10)
   - Returns the created Trade model

6. StrategyEngine (app/Services/Trading/StrategyEngine.php):
   - Orchestrates the full flow: timing → spot data → reflexes → risk → signal → execute
   - Called by the cron commands

7. Artisan Commands:
   - bot:scan-markets — calls MarketService, logs active markets
   - bot:execute-trades — calls StrategyEngine for each market in entry window
   - bot:monitor-positions — checks open trades, detects resolutions, updates status/pnl

All commands use withoutOverlapping(). All use SettingsService for params.
Write tests for RiskManager and ReflexesService.
```

**Verify:** Run commands manually. DRY_RUN mode should log trades without calling Polymarket API.

---

## Phase 4: AI Layer

**Prompt to Claude Code:**

```
Read SPEC.md sections 5 and 6. Build Phase 4 — AI Layer:

1. PromptBuilder (app/Services/AI/PromptBuilder.php):
   - buildMusclesPrompt($market, $spotData, $historicalContext) — uses template from SPEC.md section 5
   - buildBrainAuditPrompt($trade, $forensics, $strategyParams, $performanceContext) — uses template from SPEC.md section 5
   - buildDailyReviewPrompt($dailySummary, $strategyParams)
   - buildWeeklyReviewPrompt($weeklySummaries, $strategyParams)

2. MusclesService (app/Services/AI/MusclesService.php):
   - analyze($market, $spotData) — calls Haiku, parses JSON response
   - Returns: {side, confidence, reasoning, reversal_risk, suggested_bet_size_pct}
   - Saves to ai_decisions table with full prompt/response/token counts/cost
   - Handles malformed JSON gracefully (try/catch, log error, return SKIP)

3. BrainService (app/Services/AI/BrainService.php):
   - auditLoss($trade) — builds forensics, calls Sonnet, parses response
   - dailyReview() — end-of-day analysis
   - weeklyReport() — weekly deep analysis
   - All save to ai_decisions table

4. CostTracker (app/Services/AI/CostTracker.php):
   - recordUsage($model, $inputTokens, $outputTokens) — calculate and store cost
   - getMonthlySpend() — total this month
   - isOverBudget() — compare vs AI_MONTHLY_BUDGET from SettingsService
   - Token pricing: Haiku input $0.25/MTok, output $1.25/MTok; Sonnet input $3/MTok, output $15/MTok

5. AIRouter (app/Services/AI/AIRouter.php):
   - Decides which tier handles a request
   - Checks budget before making expensive calls
   - Falls back to reflexes-only if AI budget exhausted

6. LossAnalyzer (app/Services/Audit/LossAnalyzer.php):
   - getUnauditedLosses() — trades where status=lost AND audited=false
   - Marks trades as audited after processing

7. ForensicsBuilder (app/Services/Audit/ForensicsBuilder.php):
   - buildForensics($trade) — assembles the full context package (trade logs, market data, spot prices, other positions, recent pattern)

8. StrategyUpdater (app/Services/Audit/StrategyUpdater.php):
   - applyFix($auditId, $fixIndex) — applies a specific fix from an audit to strategy_params
   - rejectFix($auditId, $fixIndex, $reason)

9. Commands:
   - bot:ai-analyze-markets — runs MusclesService on markets in entry window
   - bot:ai-audit-losses — finds unaudited losses, runs BrainService per loss
   - bot:daily-review — runs BrainService daily review
   - bot:weekly-report — runs BrainService weekly report

The actual Anthropic API call should use Laravel Http facade:
Http::withHeaders(['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01'])
    ->post('https://api.anthropic.com/v1/messages', [...])
```

**Verify:** Test in tinker with a mock trade. Check ai_decisions table has entries.

---

## Phase 5: Notifications

**Prompt to Claude Code:**

```
Read SPEC.md. Build Phase 5 — Telegram Notifications:

1. TelegramService (app/Services/Telegram/TelegramService.php):
   - sendMessage($text, $parseMode = 'HTML') — send to configured chat
   - sendDocument($filePath, $caption) — for CSV exports
   - Uses TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID from .env
   - Handles API errors gracefully (log, don't crash the bot)

2. NotificationFormatter (app/Services/Telegram/NotificationFormatter.php):
   - formatTradeNotification($trade) — "🟢 BUY YES BTC @ $0.96..."
   - formatLossAudit($audit) — "🔴 Loss audited: root cause, pending fixes"
   - formatDailySummary($summary) — daily P&L, win rate, trade count
   - formatWeeklyReport($data) — weekly stats
   - formatBalanceAlert($balance, $threshold) — low balance warning
   - formatErrorAlert($error) — API failure details
   - All check SettingsService for NOTIFY_* preferences before sending

3. Integrate notifications into existing services:
   - TradeExecutor → send trade notification (if NOTIFY_EACH_TRADE)
   - AiAuditLosses command → send audit notification
   - DailySummary command → send daily P&L
   - WeeklyReport command → send weekly report
   - MonitorPositions → send balance alert if below threshold
   - All commands → send error alert on exception (if NOTIFY_ERRORS)

4. Commands:
   - bot:daily-summary — compile DailySummary record + send Telegram
   - bot:snapshot-balance — record BalanceSnapshot, check for alerts
   - bot:cleanup-logs — delete TradeLog entries older than 90 days
```

**Verify:** Send a test message via tinker: `app(TelegramService::class)->sendMessage('Test')`

---

## Phase 6: Admin Dashboard (2 sessions recommended)

**Prompt to Claude Code (Part A — Dashboard + Trades + Strategy):**

```
Read SPEC.md section 7. Build Phase 6A — Admin Dashboard core pages:

Use Bootstrap 5 with the admin layout already created. Use Chart.js for all charts.
All pages are behind Breeze auth middleware.

1. Dashboard Home (/dashboard):
   - Cards: Today's P&L (big, green/red), current balance, open positions, bot status
   - Row: win rate today / 7d / 30d
   - Chart.js: small equity curve (last 7 days)
   - Table: last 10 trades
   - Badge: pending AI recommendations count
   - Shows if DRY_RUN is on (yellow banner)

2. Trades (/trades):
   - Bootstrap table with sorting + pagination
   - Filters: date range, asset dropdown, side, status
   - Columns: time, asset, side, amount, entry/exit, P&L, status, tier
   - Row click → /trades/{id}
   - CSV export button

3. Trade Detail (/trades/{id}):
   - Trade info card (all fields)
   - Timeline of TradeLog entries (Bootstrap list-group, chronological)
   - Collapsible: AI decision prompt + response
   - External spot comparison (entry vs resolution)
   - If loss + audited: link to audit

4. Strategy (/strategy):
   - Params grouped by: Risk Management, Trading Rules, AI Settings, Notifications
   - Each param: label (description), current value, input field
   - Input types: number input for decimals/numbers, checkbox for booleans, text for strings
   - Save button per group (POST, updates via SettingsService)
   - Below: change history table (strategy_params updated_at, updated_by, previous_value)
   - Highlight AI-pending suggestions in yellow

Create all routes, controllers, and views.
```

**Prompt to Claude Code (Part B — Remaining pages):**

```
Build Phase 6B — remaining admin dashboard pages:

5. AI Audits (/audits):
   - List: date, trigger, trades analyzed count, status badge (color-coded)
   - Each expandable (Bootstrap accordion): full analysis text, fixes list
   - Each fix has Approve/Reject buttons (POST to AuditController)
   - "Run Manual Audit" button (triggers bot:ai-audit-losses)
   - Filter dropdown by status

6. Balance (/balance):
   - Cards: current balance, open positions value, total equity
   - Chart.js line chart: equity curve (balance_snapshots over last 30 days)
   - Chart.js bar chart: daily P&L (from daily_summaries)

7. Logs (/logs):
   - Search input (searches across trade_id, event, data JSON)
   - Filter dropdowns: event type, date range
   - Paginated table: timestamp, trade_id, event, data (truncated)
   - Click row: modal with full JSON pretty-printed

8. AI Costs (/ai-costs):
   - Card: monthly spend / budget with progress bar
   - Chart.js doughnut: spend by tier (reflexes=$0, muscles, brain)
   - Chart.js bar: spend by day (last 30 days)
   - Table: recent AI calls (model, tokens, cost, type)
   - Projected monthly cost (current daily average × 30)
```

**Verify:** Navigate all pages. Check forms submit correctly. Charts render with sample data.

---

## Phase 7: Polish + Deploy

**Prompt to Claude Code:**

```
Read SPEC.md section 11. Build Phase 7 — Polish:

1. DRY_RUN mode verification:
   - When DRY_RUN=true: bot scans, analyzes, generates signals, logs everything, but OrderService.placeOrder() returns a fake order ID without calling Polymarket API
   - Add yellow "DRY RUN MODE" banner to all admin pages
   - Telegram notifications should prefix with "[DRY RUN]"

2. Error handling:
   - Wrap all external API calls (Polymarket, Binance, Anthropic, Telegram) in try/catch
   - Log errors to 'bot' channel
   - Send Telegram error alert (if NOTIFY_ERRORS is true)
   - Never let one failed API call crash the entire cron cycle

3. Rate limiting:
   - Polymarket: max 10 requests/second
   - Binance: max 1200 requests/minute
   - Anthropic: respect rate limit headers, back off on 429
   - Use Laravel's RateLimiter or simple sleep-based throttling

4. Idempotency:
   - ExecuteTrades: check if trade already exists for this market before placing
   - MonitorPositions: only update trade status once per resolution
   - AiAuditLosses: only audit trades where audited=false

5. Health check:
   - GET /health — returns JSON with: bot_enabled, dry_run, last_trade_at, last_scan_at, balance, queue_size
   - No auth required (for uptime monitoring)

6. CSV export:
   - Add export button to /trades that downloads all filtered trades as CSV

7. Create DEPLOYMENT.md with:
   - Hostinger VPS setup steps
   - nginx config for Laravel
   - MySQL setup
   - Crontab entry
   - Queue worker setup (supervisor or systemd)
   - SSL note (hosting provides it)
   - First-run checklist

8. Add to .gitignore: .env, node_modules, vendor, storage/logs/*

Commit everything and verify the full app works end-to-end in DRY_RUN mode.
```

---

## After All Phases

### Go-Live Checklist
1. [ ] All API keys rotated and set in .env
2. [ ] Database migrated and seeded on production
3. [ ] Crontab installed: `* * * * * cd /path && php artisan schedule:run`
4. [ ] Queue worker running (supervisor or systemd)
5. [ ] DRY_RUN=true for first week
6. [ ] Telegram test message received
7. [ ] Dashboard accessible at bot.polytraderx.sbs
8. [ ] Monitor for 1 week in DRY_RUN mode
9. [ ] Review trade logs — are signals sensible?
10. [ ] Set DRY_RUN=false via admin dashboard
11. [ ] Start with MIN_CONFIDENCE_SCORE=0.95 (extra conservative)
12. [ ] Monitor first 10 real trades closely
13. [ ] Lower confidence threshold gradually based on results
