<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\ConversationStateService;
use App\Telegram\Keyboards\MainKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

class MessageHandler
{
    // Maps button text (both languages) to a handler method on this class.
    private const MENU_BUTTONS = [
        '➕ Add Transaction'   => 'menuAdd',
        '➕ افزودن تراکنش'    => 'menuAdd',
        '💰 Balance'           => 'menuBalance',
        '💰 موجودی'            => 'menuBalance',
        '📊 Report'            => 'menuReport',
        '📊 گزارش'             => 'menuReport',
        '📋 Transactions'      => 'menuTransactions',
        '📋 تراکنش‌ها'         => 'menuTransactions',
        '🎯 Goals'             => 'menuGoals',
        '🎯 اهداف'             => 'menuGoals',
        '💼 Budgets'           => 'menuBudgets',
        '💼 بودجه‌ها'          => 'menuBudgets',
        '👥 Friends'           => 'menuFriends',
        '👥 دوستان'            => 'menuFriends',
        '🤖 AI Coach'          => 'menuAi',
        '🤖 هوش مصنوعی'       => 'menuAi',
        '🏦 Accounts'          => 'menuAccounts',
        '🏦 حساب‌ها'           => 'menuAccounts',
        '⚙️ Settings'          => 'menuSettings',
        '⚙️ تنظیمات'           => 'menuSettings',
    ];

    public function __construct(
        private ConversationStateService $state,
        private AccountHandler $accountHandler,
        private CategoryHandler $categoryHandler,
        private TransactionHandler $transactionHandler,
        private AiTransactionHandler $aiTransactionHandler,
        private RecurringHandler $recurringHandler,
        private FriendHandler $friendHandler,
        private AiChatHandler $aiChatHandler,
        private GoalHandler $goalHandler,
        private BudgetHandler $budgetHandler,
        private ReportHandler $reportHandler,
    ) {}

    public function handle(Message $message): void
    {
        $telegramId = $message->getFrom()?->getId();
        if (!$telegramId) {
            return;
        }

        $text = trim($message->getText() ?? '');
        $step = $this->state->step($telegramId);

        // Menu button taps take priority (only when no active conversation step).
        if ($step === null && isset(self::MENU_BUTTONS[$text])) {
            $method = self::MENU_BUTTONS[$text];
            $this->$method($telegramId, $message->getChat()->getId());
            return;
        }

        if ($step === null) {
            $this->aiTransactionHandler->handle($message);
            return;
        }

        match (true) {
            $step === 'ai_chat'                  => $this->aiChatHandler->handle($message),
            str_starts_with($step, 'goal.')      => $this->goalHandler->handleMessage($message, $step),
            str_starts_with($step, 'budget.')    => $this->budgetHandler->handleMessage($message, $step),
            str_starts_with($step, 'account.')   => $this->accountHandler->handleMessage($message, $step),
            str_starts_with($step, 'category.')  => $this->categoryHandler->handleMessage($message, $step),
            str_starts_with($step, 'txn.')       => $this->transactionHandler->handleMessage($message, $step),
            str_starts_with($step, 'recurring.') => $this->recurringHandler->handleMessage($message, $step),
            str_starts_with($step, 'friend.')    => $this->friendHandler->handleMessage($message, $step),
            default => null,
        };
    }

    // ── Menu button handlers ─────────────────────────────────────────────────

    private function menuAdd(int|string $telegramId, int|string $chatId): void
    {
        $this->transactionHandler->startManualEntry($telegramId, $chatId);
    }

    private function menuBalance(int|string $telegramId, int|string $chatId): void
    {
        $user    = User::where('telegram_id', $telegramId)->first();
        $service = app(\App\Services\FriendService::class);
        $friends = $service->getFriends($user);

        if ($friends->isEmpty()) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.balance_no_friends')]);
            return;
        }

        $lines = ["💰 *" . __('bot.report_net') . "*\n"];
        foreach ($friends as $friend) {
            $balances = $service->getBalance($user, $friend);
            $name     = $friend->username ? '@' . $friend->username : $friend->display_name;
            if (empty($balances)) {
                $lines[] = "{$name}: " . __('bot.friend_settled');
                continue;
            }
            foreach ($balances as $currency => $amount) {
                if (abs($amount) < 0.01) {
                    $lines[] = "{$name}: " . __('bot.friend_settled');
                } elseif ($amount > 0) {
                    $lines[] = "{$name}: *" . __('bot.friend_they_owe', ['currency' => $currency, 'amount' => number_format($amount, 2)]) . "*";
                } else {
                    $lines[] = "{$name}: *" . __('bot.friend_you_owe', ['currency' => $currency, 'amount' => number_format(abs($amount), 2)]) . "*";
                }
            }
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => __('bot.btn_add_friend'),  'callback_data' => 'friend:add'],
                    ['text' => __('bot.btn_view_friends'), 'callback_data' => 'friend:list'],
                ]],
            ]),
        ]);
    }

    private function menuReport(int|string $telegramId, int|string $chatId): void
    {
        $this->reportHandler->show($telegramId, $chatId, 'month');
    }

    private function menuTransactions(int|string $telegramId, int|string $chatId): void
    {
        $user         = User::where('telegram_id', $telegramId)->first();
        $transactions = app(\App\Services\TransactionService::class)->listRecent($user, 10);
        $lang         = $user->language ?? 'en';

        if ($transactions->isEmpty()) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.txn_none')]);
            return;
        }

        $lines = [$lang === 'fa' ? "📋 *تراکنش‌های اخیر*\n" : "📋 *Recent Transactions*\n"];
        foreach ($transactions as $txn) {
            $typeEmoji = match ($txn->type) {
                'income'   => '💰',
                'expense'  => '💸',
                'transfer' => '🔄',
            };
            $amount = number_format($txn->amount, 2);
            $date   = $txn->occurred_at->format('M d');
            $label  = $txn->description
                ?: ($txn->category?->name ?? ($txn->type === 'transfer' ? "→ {$txn->toAccount?->name}" : __('bot.txn_uncategorized')));
            $lines[] = "{$typeEmoji} {$date} · *{$txn->currency} {$amount}* · {$label}";
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => __('bot.btn_expense'),  'callback_data' => 'txn_filter:expense'],
                        ['text' => __('bot.btn_income'),   'callback_data' => 'txn_filter:income'],
                        ['text' => __('bot.btn_transfer'), 'callback_data' => 'txn_filter:transfer'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '➕ افزودن تراکنش' : '➕ Add Transaction', 'callback_data' => 'txn:start'],
                    ],
                ],
            ]),
        ]);
    }

    private function menuGoals(int|string $telegramId, int|string $chatId): void
    {
        $this->goalHandler->showList($telegramId, $chatId);
    }

    private function menuBudgets(int|string $telegramId, int|string $chatId): void
    {
        $this->budgetHandler->showList($telegramId, $chatId);
    }

    private function menuFriends(int|string $telegramId, int|string $chatId): void
    {
        $this->friendHandler->showList($telegramId, $chatId);
    }

    private function menuAi(int|string $telegramId, int|string $chatId): void
    {
        $user = User::where('telegram_id', $telegramId)->first();
        $lang = $user->language ?? 'en';

        $this->state->set($telegramId, 'ai_chat');

        $text = $lang === 'fa'
            ? "🤖 *دستیار مالی هوش مصنوعی*\n\nسلام! سوال مالی خود را بپرسید یا یک گزینه سریع انتخاب کنید."
            : "🤖 *AI Financial Assistant*\n\nAsk me anything or pick a quick option below.";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $lang === 'fa' ? '❤️ امتیاز سلامت' : '❤️ Health Score',   'callback_data' => 'settings:health'],
                        ['text' => $lang === 'fa' ? '💡 بینش‌ها' : '💡 Insights',            'callback_data' => 'settings:insights'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '🏋️ مشاور مالی' : '🏋️ Financial Coach', 'callback_data' => 'settings:coach'],
                        ['text' => $lang === 'fa' ? '📋 اشتراک‌ها' : '📋 Subscriptions',    'callback_data' => 'ai:subscriptions'],
                    ],
                    [
                        ['text' => $lang === 'fa' ? '❌ خروج' : '❌ Exit chat', 'callback_data' => 'ai:exit'],
                    ],
                ],
            ]),
        ]);
    }

    private function menuAccounts(int|string $telegramId, int|string $chatId): void
    {
        $user     = User::where('telegram_id', $telegramId)->first();
        $accounts = app(\App\Services\AccountService::class)->listActive($user);
        $handler  = app(\App\Telegram\Handlers\AccountHandler::class);

        if ($accounts->isEmpty()) {
            Telegram::sendMessage([
                'chat_id'      => $chatId,
                'text'         => __('bot.account_none'),
                'reply_markup' => json_encode(['inline_keyboard' => [[
                    ['text' => __('bot.btn_add_account'), 'callback_data' => 'account:add'],
                ]]]),
            ]);
            return;
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $handler->formatAccountList($accounts),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(\App\Telegram\Keyboards\AccountKeyboard::accountList($accounts)),
        ]);
    }

    private function menuSettings(int|string $telegramId, int|string $chatId): void
    {
        $user = User::where('telegram_id', $telegramId)->first();
        $lang = $user->language ?? 'en';

        $text = $lang === 'fa' ? "⚙️ *تنظیمات*\nیک گزینه انتخاب کنید:" : "⚙️ *Settings*\nChoose an option:";

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(MainKeyboard::settings($lang)),
        ]);
    }
}
