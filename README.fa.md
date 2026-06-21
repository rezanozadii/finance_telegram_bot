<div dir="rtl">

# ربات تلگرام مدیریت مالی شخصی

یک سیستم ردیابی مالی شخصی کامل، ساخته‌شده به‌صورت ربات تلگرام + Mini App. درآمد و هزینه‌ها را ردیابی کنید، چندین حساب مدیریت کنید، هزینه‌ها را با دوستان تقسیم کنید، از تجزیه‌ی تراکنش با هوش مصنوعی بهره‌مند شوید و گزارش‌های مخارج را — همه درون تلگرام — مشاهده کنید.

## امکانات

### ربات
- **حساب‌ها** — نقدی، کارت، بانک، کیف پول الکترونیکی، اعتباری با ردیابی لحظه‌ای موجودی
- **دسته‌بندی‌ها** — ۱۴ دسته‌بندی پیش‌فرض + امکان ایجاد دسته‌بندی سفارشی
- **ثبت دستی تراکنش** — درآمد، هزینه، انتقال با همگام‌سازی موجودی حساب
- **تجزیه‌ی هوش مصنوعی** — یک پیام به‌زبان طبیعی بفرستید («۵۰ هزار تومان برای ناهار پرداختم») و DeepSeek R1 به‌صورت خودکار تراکنش را استخراج می‌کند
- **یادگیری دسته‌بندی** — اصلاح دسته‌بندی پیشنهادی هوش مصنوعی، قوانین مرتبط با همان فروشنده را برای دفعات بعد ذخیره می‌کند
- **تراکنش‌های تکرارشونده** — قبوض هفتگی/ماهانه/سالانه با یادآوری روزانه و تأیید با یک ضربه
- **دوستان و تقسیم هزینه** — ارسال درخواست دوستی، ثبت هزینه‌های مشترک، ردیابی بدهی به تفکیک ارز، تسویه‌حساب
- **گزارش‌ها** — خلاصه‌ی ماهانه / فصلی / سالانه با تفکیک دسته‌بندی و مقایسه با دوره‌ی قبل

### Mini App (وب‌اپ درون تلگرام)
- **داشبورد** — موجودی حساب‌ها + آخرین تراکنش‌ها در یک نگاه
- **تراکنش‌ها** — لیست صفحه‌بندی‌شده با فیلتر، حذف و فرم افزودن درون‌برنامه‌ای
- **گزارش‌ها** — انتخابگر بازه‌ی زمانی با نمایش بصری تفکیک دسته‌بندی
- **دوستان** — مانده‌های باز و دوستان تسویه‌شده

## پشته‌ی فناوری

| لایه | فناوری |
|---|---|
| بک‌اند | PHP 8.3 / Laravel 11 |
| SDK ربات | `irazasyed/telegram-bot-sdk` (مبتنی بر Webhook) |
| هوش مصنوعی | DeepSeek `deepseek-reasoner` از طریق `openai-php/client` |
| پایگاه داده | MySQL + Eloquent ORM |
| کش / مدیریت وضعیت | Laravel Cache (درایور پایگاه داده) |
| فرانت‌اند | React 18 + TypeScript + Vite |
| کامپوننت‌های UI | `@telegram-apps/telegram-ui` |
| SDK تلگرام | `@telegram-apps/sdk-react` |

## ساختار پروژه

```
app/
├── Console/Commands/       # زمان‌بند CheckRecurringDue
├── Http/
│   ├── Controllers/Api/    # REST API (۱۰ endpoint)
│   └── Middleware/         # TelegramInitDataAuth (اعتبارسنجی HMAC)
├── Models/                 # User, Account, Category, Transaction, ...
├── Services/               # منطق کسب‌وکار (AccountService, AiParsingService, ...)
└── Telegram/
    ├── Commands/           # /start /accounts /report /friends ...
    ├── Handlers/           # مدیریت مکالمه (پیام + کال‌بک)
    ├── Keyboards/          # سازنده‌های کیبورد درون‌خطی
    └── WebhookController.php

mini-app/                   # React Mini App (خروجی در public/mini-app/)
├── src/
│   ├── api/client.ts       # کلاینت API تایپ‌دار با احراز هویت initData
│   ├── pages/              # Dashboard, Transactions, Report, Friends
│   └── components/         # نوار تب BottomNav
└── vite.config.ts

routes/
├── web.php                 # Webhook + مسیر SPA برای Mini App
├── api.php                 # مسیرهای REST API (همه پشت telegram.auth)
└── console.php             # زمان‌بند تکرارشونده

database/migrations/        # ۹ مایگریشن (users تا shared_expenses)
```

## راه‌اندازی

### پیش‌نیازها
- PHP 8.3 به بالا
- MySQL
- Composer
- Node.js 18 به بالا
- توکن ربات تلگرام (از [@BotFather](https://t.me/BotFather))
- کلید API دیپ‌سیک ([platform.deepseek.com](https://platform.deepseek.com))
- آدرس HTTPS عمومی (برای توسعه‌ی محلی از ngrok استفاده کنید)

### ۱. نصب وابستگی‌ها

```bash
composer install
```

### ۲. پیکربندی محیط

```bash
cp .env.example .env
php artisan key:generate
```

فایل `.env` را ویرایش کنید:

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

### ۳. پایگاه داده

```bash
php artisan migrate
php artisan db:seed --class=DefaultCategoriesSeeder
```

### ۴. ثبت Webhook

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook/telegram"
```

برای توسعه‌ی محلی با ngrok:

```bash
ngrok http 8000
# آدرس https://xxxx.ngrok.io را به‌عنوان TELEGRAM_WEBHOOK_URL استفاده کنید
```

### ۵. اجرای سرور

```bash
php artisan serve
```

### ۶. Mini App

```bash
cd mini-app
npm install
npm run build       # خروجی در public/mini-app/
```

آدرس Mini App را در BotFather تنظیم کنید:
Edit Bot ← Edit Menu Button ← `https://your-domain.com/mini-app`

### ۷. زمان‌بند (یادآوری تراکنش‌های تکرارشونده)

به crontab اضافه کنید:
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

یا برای تست دستی:
```bash
php artisan recurring:check
```

### ۸. صف‌کار (Queue Worker)

```bash
php artisan queue:work
```

## API

تمام endpoint‌ها زیر `/api/*` قرار دارند و نیاز به هدر `Authorization: tma <initDataRaw>` دارند (اعتبارسنجی از طریق HMAC-SHA256 با توکن ربات).

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/me` | پروفایل کاربر + موجودی کل |
| GET | `/api/accounts` | لیست حساب‌ها |
| POST | `/api/accounts` | ایجاد حساب |
| GET | `/api/categories` | لیست دسته‌بندی‌ها (`?type=expense\|income`) |
| GET | `/api/transactions` | لیست صفحه‌بندی‌شده (`?limit&offset&type&from&to`) |
| POST | `/api/transactions` | ایجاد تراکنش |
| DELETE | `/api/transactions/{id}` | حذف تراکنش |
| GET | `/api/report` | گزارش (`?period=month\|quarter\|year`) |
| GET | `/api/friends` | دوستان با مانده‌ها |
| GET | `/api/friends/{id}/expenses` | هزینه‌های مشترک باز با یک دوست |

## مجوز

MIT

</div>
