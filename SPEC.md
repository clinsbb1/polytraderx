# PolyTraderX — Full Specification (Final — Post-Build)

**Status**: ✅ Build Complete
**Test Suite**: 161 tests, 387 assertions (all passing)
**Last Updated**: February 14, 2026

## 1. Trading Strategy

### The Edge: Late-Minute Certainty
- **Assets**: BTC, ETH, SOL — 15-minute binary prediction markets on Polymarket
- **Entry window**: Final 30–60 seconds of each 15-minute cycle ONLY
- **No early entries** (minutes 0–13 are observation-only)
- **Starting bankroll**: $100 USDC
- **Both YES and NO** sides, opportunistically — whichever has extreme mispricing

### Entry Logic
1. Monitor spot price via Binance every 5–10 seconds in the last 3 minutes
2. Calculate if price direction is "locked in" (e.g., BTC already +2.5% with 45s left)
3. Compare Polymarket YES/NO prices against calculated "true probability"
4. Enter when ALL conditions met:
   - Polymarket price lopsided: YES ≥ $0.92 or ≤ $0.08 (symmetrically for NO)
   - AI confidence ≥ 92%
   - External spot feed confirms direction
   - Risk limits not exceeded (daily loss, concurrent positions, etc.)
5. Bet size: $1–$10 range depending on confidence and current bankroll

### Exit Logic
- **No active exits** — markets resolve automatically at 15-minute mark
- Win → shares pay $1.00 | Loss → shares worth $0
- Hold-to-resolution only (window too short for mid-trade selling)
- Typical outcomes: tiny wins ($0.01–$0.08 per share) when buying at $0.92–$0.99; full loss of stake when wrong

### External Data (Required)
- Binance API for real-time BTC/ETH/SOL spot prices
- Poll every 5–10 seconds in final 3 minutes of each cycle
- Cross-check against Polymarket implied price for desyncs
- Calculate volatility (std dev of last 5–10 min price changes) to estimate reversal probability

---

## 2. Admin-Editable Strategy Parameters

All stored in `strategy_params` table. Seeded from .env SEED_ values on first migration.

### Risk Management
| Key | Default | Type | Group | Description |
|-----|---------|------|-------|-------------|
| MAX_BET_AMOUNT | 10 | decimal | risk | Maximum single bet in USDC |
| MAX_BET_PERCENTAGE | 10.0 | decimal | risk | Max bet as % of current bankroll |
| MAX_DAILY_LOSS | 50 | decimal | risk | Stop all trading after this daily loss |
| MAX_DAILY_TRADES | 48 | number | risk | Max trades per day |
| MAX_CONCURRENT_POSITIONS | 3 | number | risk | Max open bets at once |
| MIN_CONFIDENCE_SCORE | 0.92 | decimal | trading | Minimum AI confidence to trade |
| MIN_ENTRY_PRICE_THRESHOLD | 0.92 | decimal | trading | Only buy "locked in" side at this price or above |
| MAX_ENTRY_PRICE_THRESHOLD | 0.08 | decimal | trading | Only buy cheap contrarian side at this price or below |
| ENTRY_WINDOW_SECONDS | 60 | number | trading | Only enter within this many seconds of market close |
| DRY_RUN | true | boolean | trading | Paper trading mode |
| BOT_ENABLED | true | boolean | trading | Master kill switch |
| MONITORED_ASSETS | BTC,ETH,SOL | string | trading | Comma-separated asset list |

### AI Parameters
| Key | Default | Type | Group | Description |
|-----|---------|------|-------|-------------|
| AI_BRAIN_MODEL | claude-sonnet-4-5-20250929 | string | ai | Expensive model |
| AI_MUSCLES_MODEL | claude-haiku-4-5-20251001 | string | ai | Cheap model |
| AI_MONTHLY_BUDGET | 30.00 | decimal | ai | Stop AI calls if exceeded |
| AI_AUTO_APPLY_FIXES | false | boolean | ai | Auto-apply low-risk suggestions |
| MUSCLES_POLL_INTERVAL_MINUTES | 5 | number | ai | How often muscles tier runs |
| SPOT_POLL_INTERVAL_SECONDS | 5 | number | ai | How often to check Binance in final minutes |

### Notification Preferences
| Key | Default | Type | Group | Description |
|-----|---------|------|-------|-------------|
| NOTIFY_DAILY_PNL | true | boolean | notifications | Daily P&L summary |
| NOTIFY_BALANCE_ALERTS | true | boolean | notifications | Low balance / drawdown alerts |
| NOTIFY_ERRORS | true | boolean | notifications | API failures, bot crashes |
| NOTIFY_WEEKLY_REPORT | true | boolean | notifications | Weekly performance report |
| NOTIFY_EACH_TRADE | false | boolean | notifications | Every trade notification |
| NOTIFY_AI_AUDITS | true | boolean | notifications | AI audit completion |
| LOW_BALANCE_THRESHOLD | 20 | decimal | notifications | Alert below this USDC |
| DRAWDOWN_ALERT_PERCENTAGE | 25 | decimal | notifications | Alert above this daily drawdown % |

---

## 3. Database Schema

### trades
```
id                          bigIncrements
market_id                   string          — Polymarket condition/token ID
market_slug                 string
market_question             string          — human-readable
asset                       string          — BTC, ETH, SOL
side                        enum(YES, NO)
entry_price                 decimal(8,4)
exit_price                  decimal(8,4)    nullable
amount                      decimal(10,2)   — USDC wagered
potential_payout             decimal(10,2)
status                      enum(pending, open, won, lost, cancelled)
confidence_score            decimal(5,4)
decision_tier               enum(reflexes, muscles, brain)
decision_reasoning          json
external_spot_at_entry      decimal(12,2)   nullable
external_spot_at_resolution decimal(12,2)   nullable
market_end_time             timestamp
entry_at                    timestamp
resolved_at                 timestamp       nullable
pnl                         decimal(10,2)   nullable
audited                     boolean         default false
soft deletes
timestamps
```

### trade_logs
```
id              bigIncrements
trade_id        FK → trades
event           string          — placed, filled, resolved, error, price_snapshot
data            json            — full context snapshot
created_at      timestamp
```

### ai_decisions
```
id              bigIncrements
trade_id        FK → trades     nullable
tier            enum(muscles, brain)
model_used      string
prompt          longText
response        longText
tokens_input    integer
tokens_output   integer
cost_usd        decimal(8,6)
decision_type   enum(trade_signal, audit, strategy_update, daily_review, weekly_review)
created_at      timestamp
```

### ai_audits
```
id                  bigIncrements
trigger             enum(post_loss, daily_review, weekly_review, manual)
losing_trade_ids    json
analysis            longText
suggested_fixes     json            — [{param, current, suggested, rationale, confidence, action}]
status              enum(pending_review, approved, rejected, auto_applied)
reviewed_at         timestamp       nullable
review_notes        text            nullable
applied_at          timestamp       nullable
created_at          timestamp
```

### balance_snapshots
```
id                      bigIncrements
balance_usdc            decimal(10,2)
open_positions_value    decimal(10,2)
total_equity            decimal(10,2)
snapshot_at             timestamp
created_at              timestamp
```

### strategy_params
```
id              bigIncrements
key             string          unique
value           string
type            enum(number, decimal, boolean, string, json)
description     string          — human-readable label
group           string          — risk, ai, notifications, trading
updated_by      enum(system, ai_audit, admin)
previous_value  string          nullable
timestamps
```

### daily_summaries
```
id              bigIncrements
date            date            unique
total_trades    integer
wins            integer
losses          integer
win_rate        decimal(5,2)
gross_pnl       decimal(10,2)
net_pnl         decimal(10,2)
ai_cost_usd     decimal(8,4)
best_trade_id   FK → trades     nullable
worst_trade_id  FK → trades     nullable
created_at      timestamp
```

---

## 4. Scheduled Commands

```php
// routes/console.php

// TIER 1: REFLEXES — Every minute
Schedule::command('bot:scan-markets')->everyMinute()->withoutOverlapping();
Schedule::command('bot:execute-trades')->everyMinute()->withoutOverlapping();
Schedule::command('bot:monitor-positions')->everyMinute()->withoutOverlapping();

// TIER 2: MUSCLES — Every 5 minutes
Schedule::command('bot:ai-analyze-markets')->everyFiveMinutes()->withoutOverlapping();

// TIER 3: BRAIN — Reactive + scheduled
Schedule::command('bot:ai-audit-losses')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('bot:daily-review')->dailyAt('23:55')->withoutOverlapping();
Schedule::command('bot:weekly-report')->weeklyOn(0, '23:55')->withoutOverlapping();

// HOUSEKEEPING
Schedule::command('bot:snapshot-balance')->everyFifteenMinutes();
Schedule::command('bot:daily-summary')->dailyAt('00:05');
Schedule::command('bot:cleanup-logs')->daily();
```

Crontab: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`

---

## 5. AI Prompt Templates

### Muscles (Haiku) — Late-Minute Confidence Scoring

```
You are a 15-minute crypto prediction market analyst. Your job is to assess whether the outcome is near-certain in the final seconds.

MARKET: {market_question}
ASSET: {asset}
YES price: ${yes_price} | NO price: ${no_price}
Time remaining: {seconds_remaining} seconds
Market volume: {volume} USDC

EXTERNAL SPOT DATA (Binance):
- Current {asset} price: ${spot_price}
- Price at market open (15 min ago): ${open_price}
- Change since open: {change_pct}%
- 1-minute price change: {1m_change_pct}%
- 5-minute price change: {5m_change_pct}%
- Estimated 1-min volatility (std dev): {volatility}

HISTORICAL CONTEXT:
- Bot win rate (last 50 trades): {win_rate}%
- Similar setups (same asset, similar change%): {similar_count} trades, {similar_win_rate}% win rate
- Last 3 losses pattern: {last_loss_patterns}

Assess the probability that the current price direction holds for the remaining {seconds_remaining} seconds.

Respond in JSON ONLY (no markdown, no explanation outside JSON):
{
  "side": "YES" | "NO" | "SKIP",
  "confidence": 0.00-1.00,
  "reasoning": "one sentence",
  "reversal_risk": "low" | "medium" | "high",
  "suggested_bet_size_pct": 1.0-10.0
}

SKIP if confidence < 0.92 or reversal_risk is "high".
```

### Brain (Sonnet) — Per-Loss Forensics

```
You are a senior quantitative trading strategist performing a post-mortem on a losing trade.

## The Losing Trade
{forensics_json}

## Current Strategy Parameters
{strategy_params_json}

## Historical Context
- Total trades: {total_trades} | Wins: {wins} | Losses: {losses}
- Win rate: {win_rate}%
- Today: {today_pnl} USDC ({today_trades} trades)
- 7-day: {week_pnl} USDC
- 30-day: {month_pnl} USDC

## Previous Similar Losses (if any)
{similar_losses_json}

## Your Analysis
This is loss #{loss_number}. Analyze it and provide:

1. ROOT CAUSE: What specifically went wrong? (api_desync, late_entry, volatility_spike, threshold_error, liquidity_issue, unknown)
2. SIGNAL QUALITY: Was the entry signal fundamentally sound or flawed?
3. LUCK vs STRATEGY: Bad variance or systematic flaw?
4. SPECIFIC FIXES: Exact parameter changes if needed.
5. PATTERN: Does this match any previous loss?

Respond in JSON ONLY:
{
  "analysis": "detailed 2-3 paragraph narrative",
  "root_cause": "one-line summary",
  "root_cause_category": "api_desync|late_entry|volatility_spike|threshold_error|liquidity_issue|unknown",
  "luck_vs_strategy": "bad_luck|strategy_flaw|mixed",
  "matches_previous_loss": null | "loss #X - similar pattern",
  "fixes": [
    {
      "param": "PARAM_KEY",
      "current": "current_value",
      "suggested": "new_value",
      "rationale": "why",
      "confidence": 1-10,
      "action": "auto-apply|needs-review|informational"
    }
  ],
  "should_pause_trading": false,
  "pause_reason": null
}
```

---

## 6. AI Self-Audit Flow

```
Trade resolves as LOSS
    │
    ▼
bot:ai-audit-losses (every 5 min)
    │
    ├── Query: trades WHERE status=lost AND audited=false
    ├── For EACH unaudited loss:
    │   ├── Build forensics (ForensicsBuilder):
    │   │   ├── Full trade log entries
    │   │   ├── Market data at entry + resolution
    │   │   ├── AI decision that triggered entry
    │   │   ├── Binance spot at entry + resolution
    │   │   ├── Other open positions at the time
    │   │   ├── Win/loss pattern from last 20 trades
    │   │   └── Similar historical losses
    │   │
    │   ├── Send to Brain (Sonnet)
    │   ├── Parse response → save to ai_audits
    │   ├── Mark trade audited=true
    │   │
    │   ├── For each fix:
    │   │   ├── auto-apply + AI_AUTO_APPLY_FIXES=true → apply + log
    │   │   ├── needs-review → save pending, show in dashboard
    │   │   └── informational → log only
    │   │
    │   └── Telegram: "🔴 Loss audited: [details + pending fix count]"
    │
    └── If should_pause_trading=true → BOT_ENABLED=false + alert
```

### Approval Flow
- AI writes suggestion → saved to ai_audits with status=pending_review
- Telegram notification sent
- Admin reviews in dashboard → approve or reject (with notes)
- Approved fixes applied to strategy_params automatically
- After 100+ profitable trades: enable AI_AUTO_APPLY_FIXES for low-risk auto-apply

---

## 7. Admin Dashboard Pages

### Dashboard Home `/dashboard`
- Today's P&L (large green/red number)
- Win rate: today / 7-day / 30-day
- Current balance + open positions count
- Bot status (enabled/disabled, dry-run on/off, last heartbeat)
- Active markets being monitored
- AI monthly cost vs budget bar
- Last 10 trades feed
- Pending AI recommendations badge

### Trades `/trades`
- DataTable: date, asset, market, side, amount, entry price, exit price, P&L, status, tier
- Filters: date range, asset, side, status
- Click → trade detail
- Export CSV

### Trade Detail `/trades/{id}`
- Full timeline (every TradeLog, chronological)
- AI decision (prompt + response, collapsible)
- Market snapshot at entry
- External spot at entry vs resolution
- Link to AI audit if loss

### AI Audits `/audits`
- List: date, trigger, trades analyzed, status
- Expandable: analysis, fixes with approve/reject per fix
- "Run Manual Audit" button
- Filter by status

### Strategy `/strategy`
- Grouped by: Risk Management, Trading Rules, AI Settings, Notifications
- Each param: label, current value, type-appropriate input, description
- Save per group
- Change history table (who, when, old→new)
- AI pending suggestions highlighted yellow

### Balance `/balance`
- Equity curve (Chart.js line)
- Balance over time
- Current balance / open value / total equity
- Daily P&L bar chart

### Logs `/logs`
- Search + filter: trade ID, event type, date range
- JSON pretty-printed
- Paginated

### AI Costs `/ai-costs`
- Monthly spend vs budget (gauge)
- Spend by tier (pie)
- Spend by day (bar)
- Token breakdown table
- Projected monthly cost

---

## 8. API Integrations

### Polymarket CLOB API
- Base: `https://clob.polymarket.com`
- Docs: `https://docs.polymarket.com`
- Auth: API key + EIP-712 signature
- Endpoints: GET /markets, GET /book, POST /order, DELETE /order/{id}, GET /positions, GET /trades

### Telegram Bot API
- Base: `https://api.telegram.org/bot{token}`
- POST /sendMessage, POST /sendPhoto, POST /sendDocument

### Anthropic API
- Base: `https://api.anthropic.com/v1`
- POST /messages

### Binance API
- `https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT`
- `https://api.binance.com/api/v3/klines?symbol=BTCUSDT&interval=1m&limit=15`

---

## 9. Environment Variables

```env
APP_NAME=PolyTraderX
APP_ENV=production
APP_URL=https://bot.polytraderx.sbs
APP_TIMEZONE=Africa/Lagos

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=polytraderx
DB_USERNAME=
DB_PASSWORD=

POLYMARKET_API_KEY=
POLYMARKET_API_SECRET=
POLYMARKET_API_PASSPHRASE=
POLYMARKET_WALLET_ADDRESS=
POLYMARKET_PRIVATE_KEY=

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

ANTHROPIC_API_KEY=

# Seed values (used only during initial db:seed, then managed via admin UI)
SEED_MAX_BET_AMOUNT=10
SEED_MAX_BET_PERCENTAGE=10.0
SEED_MAX_DAILY_LOSS=50
SEED_MAX_DAILY_TRADES=48
SEED_MAX_CONCURRENT_POSITIONS=3
SEED_MIN_CONFIDENCE_SCORE=0.92
SEED_DRY_RUN=true
SEED_BOT_ENABLED=true
```

---

## 10. Logging — Full Forensics

Every trade captures this JSON in trade_logs:
```json
{
  "trade_id": 1234,
  "timestamp": "2026-02-11T14:30:00Z",
  "event": "trade_placed",
  "market": {
    "id": "0x...",
    "slug": "btc-15min-up-feb11-1430",
    "question": "Will BTC be higher at 2:45 PM UTC?",
    "current_yes_price": 0.96,
    "current_no_price": 0.04,
    "volume_24h": 15000,
    "end_time": "2026-02-11T14:45:00Z"
  },
  "decision": {
    "tier": "muscles",
    "confidence": 0.95,
    "reasoning": "BTC +2.1% with 45s left, volatility low...",
    "signals": {
      "rule_based": {"triggered": true, "rule": "price_above_threshold"},
      "ai_muscles": {"confidence": 0.95, "sentiment": "bullish", "reversal_risk": "low"},
      "ai_brain": null
    }
  },
  "risk": {
    "bankroll_before": 98.50,
    "bet_amount": 5.00,
    "bet_percentage": 5.08,
    "open_positions": 1,
    "daily_pnl_before": 2.30,
    "daily_trades_before": 12
  },
  "order": {
    "side": "YES",
    "price": 0.96,
    "amount": 5.00,
    "potential_payout": 5.21,
    "order_id": "0x..."
  },
  "external_data": {
    "btc_price_binance": 98542.30,
    "btc_price_at_market_open": 96512.10,
    "btc_change_since_open_pct": 2.10,
    "btc_1m_change_pct": 0.02,
    "btc_5m_change_pct": 0.45,
    "estimated_1m_volatility": 0.0012
  }
}
```

---

## 11. Key Technical Notes

1. **DRY_RUN=true is default**. Test for 1+ week before going live.
2. **SettingsService** reads from DB with Laravel cache. Clear cache on any param update.
3. **Never log wallet private key** — not in trade logs, not in AI prompts.
4. **Idempotency**: All commands use `withoutOverlapping()`. ExecuteTrades must check for existing open position on same market before placing.
5. **Server clock**: Must use NTP. Time drift = missed entry windows.
6. **EIP-712 signing**: Investigate `kornrunner/keccak` + `simplito/elliptic-php`. If PHP crypto libs fail, use a Node.js helper script in `scripts/sign-order.js`.
7. **API desync detection**: When Binance and Polymarket disagree on direction → auto-SKIP, log the desync.
8. **Kill switch**: `BOT_ENABLED=false` stops all trading. AI can trigger via `should_pause_trading`.
9. **Approval flow**: AI proposes fix → Telegram notification → admin approves/rejects in `/audits` → approved fixes auto-applied to strategy_params.
10. **Rate limits**: Implement exponential backoff for Polymarket, Binance, and Anthropic APIs.
