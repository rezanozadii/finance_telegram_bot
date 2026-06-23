# Telegram Personal Finance Tracker

[🇮🇷 نسخه فارسی](README.fa.md)

A full-stack **AI-powered personal finance assistant** built as a Telegram bot + Mini App. Track income and expenses, manage accounts, split costs with friends, and get intelligent coaching from a built-in AI Financial Advisor — all inside Telegram.

> **Latest:** v1.1.3 — Full bot UI with persistent menus, inline keyboards for every command, and mini-app pages for all features.

---

## Features

### Bot

#### Main Menu (UI)
Every user sees a complete UI from the moment they type `/start`:
- **Persistent bottom keyboard** — 10 tap buttons always visible (no need to type commands)
- **Full inline menu** — all 16 features in one message, one tap away

```
➕ Add Transaction  |  📋 Transactions
🏦 Accounts         |  📂 Categories
📊 Monthly Report   |  📈 Yearly Report
🎯 Goals            |  💼 Budgets
👥 Friends          |  🔄 Recurring
❤️ Health Score     |  💡 Daily Insights
🏋️ Financial Coach |  🤖 AI Chat
🔄 Subscriptions    |  🌐 Language
```

#### Bot Commands
| Command | Description |
|---|---|
| `/start` | Register, show full menu with all buttons |
| `/add` | Log a new transaction (guided steps) |
| `/accounts` | View and manage accounts |
| `/balance` | Friend balances with Add Friend button |
| `/transactions` | Recent transactions with Expense/Income/Transfer filter |
| `/categories` | Browse income and expense categories |
| `/report` | Monthly/quarterly/yearly report |
| `/budget` | Manage budgets with progress bars |
| `/goals` | Track financial goals |
| `/friends` | Friends and shared expenses |
| `/addfriend` | Add a friend by username |
| `/recurring` | Manage recurring transactions |
| `/health` | Financial health score (0–100) |
| `/insights` | Today's AI-generated insights |
| `/coach` | Weekly AI financial coaching |
| `/ai` | AI chat with quick-action buttons |
| `/language` | Switch language (EN ↔ FA) + auto-updates currency |

#### Core Finance
- **Accounts** — cash, card, bank, e-wallet, credit with live balance tracking
- **Categories** — 14 seeded defaults + create your own
- **Manual transactions** — income, expense, transfer with account balance sync
- **AI parsing** — send a natural-language message ("paid $45 for groceries") and DeepSeek extracts the transaction automatically
- **Categorization learning** — correcting the AI's category saves merchant rules for next time
- **Recurring templates** — weekly/monthly/yearly bills with daily reminders and one-tap confirmation
- **Friends & split expenses** — send friend requests, log shared costs, track balances per currency, settle up
- **Reports** — month / quarter / year summaries with category breakdown and period-over-period comparison

#### AI Financial Coach
- **`/ai`** — natural language chat with quick-action buttons (Health, Insights, Coach, Subscriptions, Exit)
- **`/health`** — Financial Health Score (0–100) across 8 weighted components + "Get Coaching" button
- **`/goals`** — define financial goals and track progress with AI-estimated completion dates
- **`/budget`** — spending limits with 80%/90%/100% alerts and color-coded progress bars
- **`/insights`** — 3 proactive daily insights generated at 08:00 with cross-links to coaching
- **`/coach`** — weekly personalized coaching with Health Score and Insights cross-links
- **Spending Personality** — Budget Master, Balanced Saver, Lifestyle Spender, Debt Heavy, etc.
- **Subscription detection** — auto-detects recurring payments (Netflix, Spotify…) with monthly/yearly cost
- **Habit detection** — identifies frequent spending patterns with frequency, average, and yearly cost
- **Forecasting** — predicted end-of-month balance, savings potential, overspending risk, goal timelines
- **What-if simulator** — "If I reduce restaurant spending by 15%, how much do I save?"
- **Monthly review** — AI-generated executive summary every 1st of the month
- **Weekly coaching** — personalized coaching every Monday at 09:00
- **Multi-language** — full English and Persian (Farsi) with auto-currency default (USD / IRR)

---

### Mini App (in-Telegram web app)

#### Core Pages
- **Dashboard** — account balances + recent transactions + ⚙️ Settings shortcut + Manage Accounts link
- **Transactions** — paginated list with type filter, delete, and inline add form
- **Reports** — period switcher with visual category breakdown
- **Friends** — open balances and settled friends

#### Account & Category Management
- **Accounts** — full account management: list all accounts, create new (name, type, currency, balance)
- **Categories** — browse income and expense categories with subcategories

#### Settings
- **Settings page** — language switcher (EN/FA), profile info, default currency display
- Switching language automatically updates the default currency (Persian → IRR, English → USD)

#### AI Tab (🤖)
- **AI Chat** — conversational interface to your finance AI
- **Health Score** — visual score breakdown with per-component progress bars
- **Goals** — create and track goals with progress bars and estimated completion
- **Budgets** — color-coded budget cards (green/yellow/red), add/delete with category assignment
- **Forecast** — projected income, expenses, month-end balance, and per-goal timelines
- **Subscriptions** — detected recurring payments with total monthly/yearly cost
- **Daily Insights** — today's AI-generated proactive tips
- **Spending Habits** — merchant-level habit analysis with monthly/yearly cost (`/ai/habits`)
- **What-If Simulator** — 4 scenarios: reduce category, salary increase, fixed savings, cancel subscription (`/ai/whatif`)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 / Laravel 11 |
| Bot SDK | `irazasyed/telegram-bot-sdk` (webhook-based) |
| AI | DeepSeek `deepseek-reasoner` via `openai-php/client` |
| AI Architecture | Agent-based (9 specialized agents + orchestrator) |
| Database | MySQL + Eloquent ORM |
| Cache / State | Laravel cache (database driver) |
| Frontend | React 18 + TypeScript + Vite |
| UI Components | `@telegram-apps/telegram-ui` |
| Telegram SDK | `@telegram-apps/sdk-react` |

---

## Project Structure

```
app/
├── AI/
│   ├── Agents/             # 9 specialized AI agents + AgentOrchestrator
│   └── Tools/              # 10 data-gathering tools (PHP math, no LLM calculations)
├── Console/Commands/       # Schedulers: recurring, daily insights, weekly coach, monthly review
├── Http/Controllers/Api/   # REST API (25 endpoints)
├── Jobs/                   # GenerateDailyInsights, GenerateWeeklyCoaching, GenerateMonthlyReview
├── Models/                 # User, Account, Category, Transaction, Budget, UserGoal, AiUserMemory…
├── Services/
│   ├── AI/                 # Calculation services (all PHP math — LLM never calculates)
│   └── ...                 # AccountService, TransactionService, ReportService…
└── Telegram/
    ├── Commands/           # All 17 bot commands (/start, /add, /accounts, /ai, /health…)
    ├── Handlers/           # Message, callback, AI chat, goal, budget, account handlers
    ├── Keyboards/
    │   ├── MainKeyboard.php        # Persistent Reply Keyboard + Settings menu (EN/FA)
    │   ├── AccountKeyboard.php
    │   ├── TransactionKeyboard.php
    │   ├── BudgetKeyboard.php
    │   ├── GoalKeyboard.php
    │   ├── CategoryKeyboard.php
    │   ├── FriendKeyboard.php
    │   ├── RecurringKeyboard.php
    │   └── ReportKeyboard.php
    └── WebhookController.php

mini-app/src/
├── api/client.ts           # Typed API client (includes updateMe, habits, whatIf, createAccount)
├── pages/
│   ├── Dashboard.tsx       # + settings button + manage accounts + categories shortcut
│   ├── Transactions.tsx
│   ├── Report.tsx
│   ├── Friends.tsx
│   ├── Accounts.tsx        # NEW — account list + create form
│   ├── Categories.tsx      # NEW — income/expense category browser
│   ├── Settings.tsx        # NEW — language switcher + currency display
│   ├── AiHub.tsx           # + Habits and What-If cards
│   ├── AiChat.tsx
│   ├── HealthScore.tsx
│   ├── Goals.tsx
│   ├── Budgets.tsx
│   ├── Forecast.tsx
│   ├── Subscriptions.tsx
│   ├── DailyInsights.tsx
│   ├── Habits.tsx          # NEW — spending habit analysis
│   └── WhatIf.tsx          # NEW — 4-scenario financial simulator
└── components/BottomNav.tsx

routes/
├── web.php                 # Webhook + Mini App SPA
├── api.php                 # REST API (all behind telegram.auth)
└── console.php             # Schedulers

database/migrations/        # 14 migrations
```

---

## AI Architecture

All financial calculations are performed in PHP. The LLM only receives structured JSON and produces explanations, insights, and recommendations.

```
User message
     │
     ▼
AgentOrchestrator  ──── keyword routing ────►  Specialized Agent
                                                      │
                                               ┌──────┴──────┐
                                               │   Tools      │
                                               │ (PHP math)   │
                                               └──────┬───────┘
                                                      │ structured JSON
                                                      ▼
                                                 DeepSeek LLM
                                                      │ natural language
                                                      ▼
                                                  Response
```

| Agent | Responsibility |
|---|---|
| `ChatAgent` | General Q&A about user's finances |
| `FinancialCoachAgent` | Weekly personalized coaching |
| `SpendingAnalyzerAgent` | Spending patterns and habit analysis |
| `BudgetAdvisorAgent` | Budget monitoring and recommendations |
| `ForecastAgent` | End-of-month and goal completion forecasting |
| `GoalPlannerAgent` | Goal strategies and timelines |
| `SubscriptionAnalyzerAgent` | Recurring payment detection and review |
| `FinancialHealthAgent` | Health score explanation and improvement tips |
| `ReportWriterAgent` | Monthly executive summary |

---

## Setup

### Requirements
- PHP 8.3+
- MySQL
- Composer
- Node.js 18+
- Telegram bot token (from [@BotFather](https://t.me/BotFather))
- DeepSeek API key ([platform.deepseek.com](https://platform.deepseek.com))
- Public HTTPS URL (ngrok for local dev)

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=telegram_finance
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

TELEGRAM_BOT_TOKEN=123456:ABC-your-token
TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook/telegram

DEEPSEEK_API_KEY=sk-your-deepseek-key
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-reasoner
```

### 3. Database

```bash
php artisan migrate
php artisan db:seed --class=DefaultCategoriesSeeder
```

### 4. Register the webhook

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook/telegram"
```

For local dev with ngrok:

```bash
ngrok http 8000
# use the https://xxxx.ngrok.io URL as TELEGRAM_WEBHOOK_URL
```

### 5. Start the server

```bash
php artisan serve
```

### 6. Mini App

```bash
cd mini-app
npm install
npm run build       # outputs to public/mini-app/
```

Set the Mini App URL in BotFather → Edit Bot → Edit Menu Button:
`https://your-domain.com/mini-app`

### 7. Scheduler

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Schedule | Purpose |
|---|---|---|
| `ai:daily-insights` | Daily 08:00 | Generate and send proactive insights |
| `ai:weekly-coaching` | Mondays 09:00 | Personalized weekly coaching |
| `ai:monthly-review` | 1st of month 10:00 | Monthly executive report |
| `recurring:check` | Every minute | Process recurring transaction reminders |

### 8. Queue worker

```bash
php artisan queue:work
```

---

## API

All endpoints are under `/api/*` and require `Authorization: tma <initDataRaw>` (HMAC-SHA256 validated against bot token).

### User

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/me` | User profile + total balance |
| PATCH | `/api/me` | Update language and/or default currency |

### Core

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/accounts` | List accounts |
| POST | `/api/accounts` | Create account |
| GET | `/api/categories` | List categories (`?type=expense\|income`) |
| GET | `/api/transactions` | Paginated list (`?limit&offset&type&from&to`) |
| POST | `/api/transactions` | Create transaction |
| DELETE | `/api/transactions/{id}` | Delete transaction |
| GET | `/api/report` | Report (`?period=month\|quarter\|year`) |
| GET | `/api/friends` | Friends with balances |
| GET | `/api/friends/{id}/expenses` | Shared expenses with a friend |

### AI Financial Coach

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/ai/chat` | Chat with AI (`{message, currency?}`) |
| GET | `/api/ai/insights` | Today's proactive insights |
| GET | `/api/ai/health-score` | Financial health score + breakdown |
| GET | `/api/ai/subscriptions` | Auto-detected recurring payments |
| GET | `/api/ai/habits` | Detected spending habits (`?currency=`) |
| POST | `/api/ai/whatif` | What-if simulation (`{scenario, params, currency?}`) |

**What-if scenarios:** `reduce_category`, `salary_increase`, `save_fixed`, `cancel_subscription`

### Goals & Budgets

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/goals` | List financial goals |
| POST | `/api/goals` | Create goal |
| PATCH | `/api/goals/{id}` | Update goal progress or status |
| DELETE | `/api/goals/{id}` | Delete goal |
| GET | `/api/budgets` | List budgets with usage |
| POST | `/api/budgets` | Create budget |
| DELETE | `/api/budgets/{id}` | Delete budget |
| GET | `/api/forecast` | Financial forecast (`?currency=USD`) |

---

## Changelog

### v1.1.3
- Fixed language keyboard not switching to Persian (Reply Keyboard cannot be set via `editMessageText`)
- Switching language now auto-sets default currency: Persian → IRR, English → USD
- Added `PATCH /api/me` endpoint for language and currency updates
- Mini-app Settings page now calls the API on language change and reflects updated currency

### v1.1.2
- Fixed `/start` showing no buttons for new users (buttons now appear for everyone)
- New users get a "🏦 Create Account" prompt button

### v1.1.1
- `/start` sends full 8-row inline menu covering all 16 features + persistent bottom keyboard

### v1.1.0
- **Bot:** Persistent main menu Reply Keyboard (10 buttons, EN + FA)
- **Bot:** Inline filter buttons for `/transactions` (Expense/Income/Transfer)
- **Bot:** Action buttons for `/health`, `/insights`, `/coach`, `/ai`, `/balance`
- **Bot:** Settings menu with Language, Categories, Recurring, Health, Insights, Coach
- **Mini-App:** Accounts, Categories, Settings, Habits, What-If pages added
- **Mini-App:** AI Hub updated with Habits and What-If cards
- **Mini-App:** Dashboard with settings gear, Manage Accounts, Categories shortcut

---

## License

MIT
