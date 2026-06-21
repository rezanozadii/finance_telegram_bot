# Telegram Personal Finance Tracker

[🇮🇷 نسخه فارسی](README.fa.md)

A full-stack personal finance tracker built as a Telegram bot + Mini App. Track income and expenses, manage multiple accounts, split costs with friends, get AI-powered transaction parsing, and view spending reports — all inside Telegram.

## Features

### Bot
- **Accounts** — cash, card, bank, e-wallet, credit with live balance tracking
- **Categories** — seeded defaults (14 categories) + create your own
- **Manual transactions** — income, expense, transfer with account balance sync
- **AI parsing** — send a natural-language message ("paid $45 for groceries at Walmart") and DeepSeek R1 extracts the transaction automatically
- **Categorization learning** — correcting the AI's category teaches it for next time (merchant rules)
- **Recurring templates** — weekly/monthly/yearly bills with daily reminders and one-tap confirmation
- **Friends & split expenses** — send friend requests, log shared costs, track who owes whom per currency, settle up
- **Reports** — month / quarter / year summaries with category breakdown and period-over-period comparison

### Mini App (in-Telegram web app)
- **Dashboard** — account balances + recent transactions at a glance
- **Transactions** — paginated list with filters, delete, and inline add form
- **Reports** — interactive period switcher with visual category breakdown
- **Friends** — open balances and settled friends

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 / Laravel 11 |
| Bot SDK | `irazasyed/telegram-bot-sdk` (webhook-based) |
| AI | DeepSeek `deepseek-reasoner` via `openai-php/client` |
| Database | MySQL + Eloquent ORM |
| Cache / State | Laravel cache (database driver) |
| Frontend | React 18 + TypeScript + Vite |
| UI Components | `@telegram-apps/telegram-ui` |
| Telegram SDK | `@telegram-apps/sdk-react` |

## Project Structure

```
app/
├── Console/Commands/       # CheckRecurringDue scheduler
├── Http/
│   ├── Controllers/Api/    # REST API (10 endpoints)
│   └── Middleware/         # TelegramInitDataAuth (HMAC validation)
├── Models/                 # User, Account, Category, Transaction, ...
├── Services/               # Business logic (AccountService, AiParsingService, ...)
└── Telegram/
    ├── Commands/           # /start /accounts /report /friends ...
    ├── Handlers/           # Message + callback conversation handlers
    ├── Keyboards/          # Inline keyboard builders
    └── WebhookController.php

mini-app/                   # React Mini App (builds to public/mini-app/)
├── src/
│   ├── api/client.ts       # Typed API client with initData auth
│   ├── pages/              # Dashboard, Transactions, Report, Friends
│   └── components/         # BottomNav tab bar
└── vite.config.ts

routes/
├── web.php                 # Webhook + Mini App SPA route
├── api.php                 # REST API routes (all behind telegram.auth)
└── console.php             # Recurring scheduler

database/migrations/        # 9 migrations (users → shared_expenses)
```

## Setup

### Requirements
- PHP 8.3+
- MySQL
- Composer
- Node.js 18+
- A Telegram bot token (from [@BotFather](https://t.me/BotFather))
- A DeepSeek API key ([platform.deepseek.com](https://platform.deepseek.com))
- A public HTTPS URL (ngrok for local dev)

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

For local development with ngrok:

```bash
ngrok http 8000
# use the https://xxxx.ngrok.io URL as your TELEGRAM_WEBHOOK_URL
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

### 7. Scheduler (recurring reminders)

Add to crontab:
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Or test manually:
```bash
php artisan recurring:check
```

### 8. Queue worker

```bash
php artisan queue:work
```

## API

All endpoints are under `/api/*` and require an `Authorization: tma <initDataRaw>` header (validated via HMAC-SHA256 against the bot token).

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/me` | User profile + total balance |
| GET | `/api/accounts` | List accounts |
| POST | `/api/accounts` | Create account |
| GET | `/api/categories` | List categories (`?type=expense\|income`) |
| GET | `/api/transactions` | Paginated list (`?limit&offset&type&from&to`) |
| POST | `/api/transactions` | Create transaction |
| DELETE | `/api/transactions/{id}` | Delete transaction |
| GET | `/api/report` | Report (`?period=month\|quarter\|year`) |
| GET | `/api/friends` | Friends with balances |
| GET | `/api/friends/{id}/expenses` | Open shared expenses with a friend |

## License

MIT
