<?php

namespace App\Telegram\Handlers;

use App\AI\Agents\FinancialCoachAgent;
use App\AI\Agents\FinancialHealthAgent;
use App\Models\AiInsight;
use App\Models\User;
use App\Services\AI\HealthScoreService;
use App\Services\AI\SubscriptionDetectorService;
use App\Services\ConversationStateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;

class CallbackHandler
{
    public function __construct(
        private AccountHandler $accountHandler,
        private CategoryHandler $categoryHandler,
        private TransactionHandler $transactionHandler,
        private AiTransactionHandler $aiTransactionHandler,
        private RecurringHandler $recurringHandler,
        private FriendHandler $friendHandler,
        private ReportHandler $reportHandler,
        private GoalHandler $goalHandler,
        private BudgetHandler $budgetHandler,
        private ConversationStateService $state,
    ) {}

    public function handle(CallbackQuery $query): void
    {
        $action = $query->getData() ?? '';

        match (true) {
            str_starts_with($action, 'goal')       => $this->goalHandler->handleCallback($query, $action),
            str_starts_with($action, 'budget')     => $this->budgetHandler->handleCallback($query, $action),
            str_starts_with($action, 'account')    => $this->accountHandler->handleCallback($query, $action),
            str_starts_with($action, 'category')   => $this->categoryHandler->handleCallback($query, $action),
            str_starts_with($action, 'ai_txn')     => $this->aiTransactionHandler->handleCallback($query, $action),
            str_starts_with($action, 'txn_filter:')=> $this->handleTxnFilter($query, $action),
            str_starts_with($action, 'txn:start')  => $this->handleTxnStart($query),
            str_starts_with($action, 'txn')        => $this->transactionHandler->handleCallback($query, $action),
            str_starts_with($action, 'rec')        => $this->recurringHandler->handleCallback($query, $action),
            str_starts_with($action, 'friend')     => $this->friendHandler->handleCallback($query, $action),
            str_starts_with($action, 'report:')    => $this->reportHandler->handleCallback($query, $action),
            str_starts_with($action, 'lang:')      => $this->handleLanguage($query, $action),
            str_starts_with($action, 'settings:')  => $this->handleSettings($query, $action),
            str_starts_with($action, 'ai:')        => $this->handleAi($query, $action),
            default => null,
        };
    }

    private function handleLanguage(CallbackQuery $query, string $action): void
    {
        $lang       = substr($action, 5);
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        if (!in_array($lang, ['en', 'fa'])) {
            return;
        }

        $user = User::where('telegram_id', $telegramId)->first();
        $user->update(['language' => $lang]);
        App::setLocale($lang);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.language_set'),
            'reply_markup' => json_encode(\App\Telegram\Keyboards\MainKeyboard::main($lang)),
        ]);

        // Send updated main keyboard
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $lang === 'fa' ? '✅ زبان تغییر کرد.' : '✅ Language updated.',
            'reply_markup' => json_encode(\App\Telegram\Keyboards\MainKeyboard::main($lang)),
        ]);
    }

    private function handleSettings(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $user       = User::where('telegram_id', $telegramId)->first();
        $lang       = $user->language ?? 'en';

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        $sub = substr($action, 9); // strip 'settings:'

        match ($sub) {
            'language'   => $this->sendLanguageSelector($chatId, $lang),
            'categories' => $this->categoryHandler->showList($telegramId, $chatId),
            'recurring'  => app(RecurringHandler::class)->showList($telegramId, $chatId),
            'health'     => $this->sendHealthScore($chatId, $user, $lang),
            'insights'   => $this->sendInsights($chatId, $user, $lang),
            'coach'      => $this->sendCoaching($chatId, $user, $lang),
            'menu'       => $this->sendSettingsMenu($chatId, $lang),
            default      => null,
        };
    }

    private function handleAi(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $user       = User::where('telegram_id', $telegramId)->first();
        $lang       = $user->language ?? 'en';

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        $sub = substr($action, 3); // strip 'ai:'

        match ($sub) {
            'exit'          => $this->exitAiChat($telegramId, $chatId, $lang),
            'subscriptions' => $this->sendSubscriptions($chatId, $user, $lang),
            'start_chat'    => $this->startAiChat($telegramId, $chatId, $lang),
            default         => null,
        };
    }

    private function handleTxnFilter(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();
        $type       = substr($action, 11); // strip 'txn_filter:'
        $user       = User::where('telegram_id', $telegramId)->first();
        $lang       = $user->language ?? 'en';

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        $transactions = app(\App\Services\TransactionService::class)
            ->listRecent($user, 10, $type === 'all' ? null : $type);

        $typeLabel = match ($type) {
            'expense'  => $lang === 'fa' ? 'هزینه' : 'Expenses',
            'income'   => $lang === 'fa' ? 'درآمد' : 'Income',
            'transfer' => $lang === 'fa' ? 'انتقال' : 'Transfers',
            default    => $lang === 'fa' ? 'همه' : 'All',
        };

        $header = $lang === 'fa' ? "📋 *{$typeLabel}*\n" : "📋 *{$typeLabel}*\n";

        if ($transactions->isEmpty()) {
            $text = $header . ($lang === 'fa' ? 'تراکنشی یافت نشد.' : 'No transactions found.');
        } else {
            $lines = [$header];
            foreach ($transactions as $txn) {
                $emoji  = match ($txn->type) { 'income' => '💰', 'expense' => '💸', 'transfer' => '🔄' };
                $amount = number_format($txn->amount, 2);
                $date   = $txn->occurred_at->format('M d');
                $label  = $txn->description ?: ($txn->category?->name ?? 'Uncategorized');
                $lines[] = "{$emoji} {$date} · *{$txn->currency} {$amount}* · {$label}";
            }
            $text = implode("\n", $lines);
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $lang === 'fa' ? '💸 هزینه' : '💸 Expense',   'callback_data' => 'txn_filter:expense'],
                        ['text' => $lang === 'fa' ? '💰 درآمد' : '💰 Income',    'callback_data' => 'txn_filter:income'],
                        ['text' => $lang === 'fa' ? '🔄 انتقال' : '🔄 Transfer', 'callback_data' => 'txn_filter:transfer'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '➕ افزودن' : '➕ Add Transaction', 'callback_data' => 'txn:start'],
                    ],
                ],
            ]),
        ]);
    }

    private function handleTxnStart(CallbackQuery $query): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);
        app(TransactionHandler::class)->startManualEntry($telegramId, $chatId);
    }

    // ── Feature senders ──────────────────────────────────────────────────────

    private function sendLanguageSelector(int|string $chatId, string $lang): void
    {
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $lang === 'fa' ? '🌐 زبان مورد نظر را انتخاب کنید:' : '🌐 Choose your language:',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => '🇬🇧 English', 'callback_data' => 'lang:en'],
                    ['text' => '🇮🇷 فارسی',   'callback_data' => 'lang:fa'],
                ]],
            ]),
        ]);
    }

    private function sendHealthScore(int|string $chatId, User $user, string $lang): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $lang === 'fa' ? '⏳ در حال محاسبه امتیاز سلامت مالی...' : '⏳ Calculating your financial health score...',
        ]);

        $currency = $user->default_currency ?? 'USD';
        $score    = app(HealthScoreService::class)->calculate($user, $currency);
        $total    = $score['total'] ?? ($score['score'] ?? 0);
        $filled   = (int) min(10, round($total / 10));
        $bar      = str_repeat('█', $filled) . str_repeat('░', 10 - $filled);

        $lines = [$lang === 'fa' ? "❤️ *امتیاز سلامت مالی*\n" : "❤️ *Financial Health Score*\n"];
        $lines[] = "*{$total}/100*";
        $lines[] = $bar;

        if (!empty($score['personality'])) {
            $lines[] = '';
            $lines[] = ($lang === 'fa' ? '🧠 شخصیت: ' : '🧠 Personality: ') . $score['personality'];
        }

        if (!empty($score['components'])) {
            $lines[] = '';
            foreach ($score['components'] as $c) {
                $label = is_array($c) ? ($c['label'] ?? '') : '';
                $s     = is_array($c) ? ($c['score'] ?? 0) : 0;
                if ($label) {
                    $lines[] = "• *{$label}*: {$s}/100";
                }
            }
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '🏋️ دریافت مشاوره' : '🏋️ Get Coaching', 'callback_data' => 'settings:coach'],
                ]],
            ]),
        ]);
    }

    private function sendInsights(int|string $chatId, User $user, string $lang): void
    {
        $insight = AiInsight::where('user_id', $user->id)
            ->whereDate('insights_date', Carbon::today())
            ->latest()
            ->first();

        if (!$insight) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => $lang === 'fa'
                    ? '💡 امروز هنوز هیچ بینشی تولید نشده. فردا صبح دوباره بررسی کنید.'
                    : '💡 No insights generated yet for today. Check back tomorrow morning.',
            ]);
            return;
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => ($lang === 'fa' ? "💡 *بینش‌های روزانه*\n\n" : "💡 *Daily Insights*\n\n") . $insight->content,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '❤️ سلامت مالی' : '❤️ Health Score', 'callback_data' => 'settings:health'],
                    ['text' => $lang === 'fa' ? '🏋️ مشاوره' : '🏋️ Coaching',        'callback_data' => 'settings:coach'],
                ]],
            ]),
        ]);
    }

    private function sendCoaching(int|string $chatId, User $user, string $lang): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $lang === 'fa' ? '⏳ در حال آماده کردن مشاوره مالی...' : '⏳ Preparing your financial coaching...',
        ]);

        $currency = $user->default_currency ?? 'USD';
        $coaching = app(FinancialCoachAgent::class)->weeklyCoaching($user, $currency);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => ($lang === 'fa' ? "🏋️ *مشاوره مالی*\n\n" : "🏋️ *Financial Coaching*\n\n") . $coaching,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '❤️ سلامت مالی' : '❤️ Health Score',   'callback_data' => 'settings:health'],
                    ['text' => $lang === 'fa' ? '💡 بینش‌ها' : '💡 Daily Insights',     'callback_data' => 'settings:insights'],
                ]],
            ]),
        ]);
    }

    private function sendSubscriptions(int|string $chatId, User $user, string $lang): void
    {
        $subs = app(SubscriptionDetectorService::class)->detect($user);

        if (empty($subs)) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text'    => $lang === 'fa' ? '🔄 هیچ اشتراکی تشخیص داده نشد.' : '🔄 No subscriptions detected yet.',
            ]);
            return;
        }

        $total  = array_sum(array_column($subs, 'monthly_cost'));
        $header = $lang === 'fa' ? "🔄 *اشتراک‌های تشخیص داده شده*\n" : "🔄 *Detected Subscriptions*\n";
        $lines  = [$header];

        foreach ($subs as $s) {
            $lines[] = "• *{$s['merchant']}*: {$s['currency']} " . number_format($s['monthly_cost'], 2) . '/mo';
        }

        $lines[] = '';
        $currency = $subs[0]['currency'] ?? ($user->default_currency ?? 'USD');
        $lines[] = ($lang === 'fa' ? '💰 کل ماهانه: ' : '💰 Total monthly: ') . $currency . ' ' . number_format($total, 2);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function exitAiChat(int|string $telegramId, int|string $chatId, string $lang): void
    {
        $this->state->clear($telegramId);
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $lang === 'fa' ? '✅ از حالت چت خارج شدید.' : '✅ Exited AI chat.',
            'reply_markup' => json_encode(\App\Telegram\Keyboards\MainKeyboard::main($lang)),
        ]);
    }

    private function startAiChat(int|string $telegramId, int|string $chatId, string $lang): void
    {
        $this->state->set($telegramId, 'ai_chat');

        $text = $lang === 'fa'
            ? "🤖 *دستیار مالی هوش مصنوعی*\n\nسوال مالی خود را بنویسید.\nبرای خروج /done را بزنید."
            : "🤖 *AI Financial Assistant*\n\nAsk me anything about your finances.\nType /done to exit.";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => $lang === 'fa' ? '❌ خروج' : '❌ Exit chat', 'callback_data' => 'ai:exit'],
                ]],
            ]),
        ]);
    }

    private function sendSettingsMenu(int|string $chatId, string $lang): void
    {
        $text = $lang === 'fa' ? "⚙️ *تنظیمات*\nیک گزینه انتخاب کنید:" : "⚙️ *Settings*\nChoose an option:";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(\App\Telegram\Keyboards\MainKeyboard::settings($lang)),
        ]);
    }
}
