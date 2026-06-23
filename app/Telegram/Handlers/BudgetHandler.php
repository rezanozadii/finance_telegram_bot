<?php

namespace App\Telegram\Handlers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Services\AI\BudgetAnalysisService;
use App\Services\ConversationStateService;
use App\Telegram\Keyboards\BudgetKeyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class BudgetHandler
{
    public function __construct(
        private ConversationStateService $state,
        private BudgetAnalysisService $budgetAnalysis,
    ) {}

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'budget.name'     => $this->stepName($telegramId, $chatId, $text),
            'budget.amount'   => $this->stepAmount($telegramId, $chatId, $text),
            'budget.currency' => $this->stepCurrency($telegramId, $chatId, $text),
            default           => null,
        };
    }

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'budget:add'                     => $this->startCreation($telegramId, $chatId),
            $action === 'budget:list'                    => $this->showList($telegramId, $chatId, $messageId),
            str_starts_with($action, 'budget_view:')    => $this->showDetail($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'budget_period:')  => $this->stepPeriod($telegramId, $chatId, substr($action, 14)),
            str_starts_with($action, 'budget_delete:')  => $this->delete($telegramId, $chatId, $messageId, (int) substr($action, 14)),
            default                                      => null,
        };
    }

    public function showList(int|string $telegramId, int|string $chatId, ?int $messageId = null): void
    {
        $user    = User::where('telegram_id', $telegramId)->firstOrFail();
        $budgets = $this->budgetAnalysis->analyze($user);

        $text = $user->language === 'fa' ? "📊 *بودجه‌ها*\n\n" : "📊 *Budgets*\n\n";

        if (empty($budgets)) {
            $text .= $user->language === 'fa' ? 'هنوز هیچ بودجه‌ای ندارید.' : 'You have no budgets yet.';
        } else {
            foreach ($budgets as $b) {
                $icon  = match ($b['status']) {
                    'exceeded' => '🔴',
                    'critical' => '🟠',
                    'warning'  => '🟡',
                    default    => '🟢',
                };
                $bar   = $this->progressBar($b['pct_used']);
                $text .= "{$icon} *{$b['name']}*\n";
                $text .= "{$bar} {$b['pct_used']}%\n";
                $text .= "{$b['currency']} " . number_format($b['spent'], 2) . ' / ' . number_format($b['amount'], 2) . "\n\n";
            }
        }

        $keyboard = BudgetKeyboard::list($user->budgets()->get());
        $payload  = [
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode($keyboard),
        ];

        if ($messageId) {
            Telegram::editMessageText(array_merge(['chat_id' => $chatId, 'message_id' => $messageId], $payload));
        } else {
            Telegram::sendMessage(array_merge(['chat_id' => $chatId], $payload));
        }
    }

    public function startCreation(int|string $telegramId, int|string $chatId): void
    {
        $this->state->set($telegramId, 'budget.name');
        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? '📊 نام بودجه را وارد کنید (مثلاً: غذا، تفریح، حمل‌ونقل):' : '📊 Enter the budget name (e.g., Food, Entertainment, Transport):',
        ]);
    }

    private function stepName(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a budget name.']);
            return;
        }

        $this->state->set($telegramId, 'budget.amount', ['name' => $text]);
        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? "💰 سقف بودجه \"{$text}\" را وارد کنید:" : "💰 Enter the budget limit for \"{$text}\":",
        ]);
    }

    private function stepAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a valid positive amount.']);
            return;
        }

        $this->state->set($telegramId, 'budget.currency', array_merge(
            $this->state->data($telegramId),
            ['amount' => (float) $text]
        ));

        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? '💱 ارز را وارد کنید (مثلاً: USD, EUR):' : 'Enter currency (e.g., USD, EUR):',
        ]);
    }

    private function stepCurrency(int|string $telegramId, int|string $chatId, string $text): void
    {
        $currency = strtoupper(trim($text));
        if (strlen($currency) < 2 || strlen($currency) > 10) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Invalid currency.']);
            return;
        }

        $this->state->set($telegramId, 'budget.period_select', array_merge(
            $this->state->data($telegramId),
            ['currency' => $currency]
        ));

        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $user?->language === 'fa' ? '📅 دوره بودجه را انتخاب کنید:' : '📅 Select budget period:',
            'reply_markup' => json_encode(BudgetKeyboard::periodSelector()),
        ]);
    }

    private function stepPeriod(int|string $telegramId, int|string $chatId, string $period): void
    {
        $data = $this->state->data($telegramId);
        $user = User::where('telegram_id', $telegramId)->firstOrFail();

        Budget::create([
            'user_id'  => $user->id,
            'name'     => $data['name'],
            'amount'   => $data['amount'],
            'currency' => $data['currency'],
            'period'   => $period,
        ]);

        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => $user->language === 'fa'
                ? "✅ بودجه *{$data['name']}* ایجاد شد!"
                : "✅ Budget *{$data['name']}* created!",
            'parse_mode' => 'Markdown',
        ]);
    }

    private function showDetail(int|string $telegramId, int|string $chatId, int $messageId, int $budgetId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $budget = $user->budgets()->find($budgetId);

        if (!$budget) {
            return;
        }

        $analysis = $this->budgetAnalysis->analyze($user);
        $data     = collect($analysis)->firstWhere('id', $budget->id);

        if (!$data) {
            $data = [
                'name'     => $budget->name,
                'amount'   => (float) $budget->amount,
                'spent'    => 0.0,
                'pct_used' => 0,
                'currency' => $budget->currency,
                'status'   => 'ok',
            ];
        }

        $icon = match ($data['status']) {
            'exceeded' => '🔴',
            'critical' => '🟠',
            'warning'  => '🟡',
            default    => '🟢',
        };

        $bar  = $this->progressBar($data['pct_used']);
        $text = "*{$data['name']}*\n\n";
        $text .= "{$icon} {$bar} {$data['pct_used']}%\n";
        $text .= "{$data['currency']} " . number_format($data['spent'], 2) . ' / ' . number_format($data['amount'], 2);

        $periodLabel = match ($budget->period) {
            'weekly' => $user->language === 'fa' ? 'هفتگی' : 'Weekly',
            'yearly' => $user->language === 'fa' ? 'سالانه' : 'Yearly',
            default  => $user->language === 'fa' ? 'ماهانه' : 'Monthly',
        };
        $text .= "\n📅 " . $periodLabel;

        $btnDelete = $user->language === 'fa' ? '🗑 حذف بودجه' : '🗑 Delete Budget';
        $btnBack   = $user->language === 'fa' ? '⬅️ بازگشت' : '⬅️ Back';

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => $btnDelete, 'callback_data' => "budget_delete:{$budgetId}"]],
                    [['text' => $btnBack,   'callback_data' => 'budget:list']],
                ],
            ]),
        ]);
    }

    private function delete(int|string $telegramId, int|string $chatId, int $messageId, int $budgetId): void
    {
        $user   = User::where('telegram_id', $telegramId)->firstOrFail();
        $budget = $user->budgets()->find($budgetId);
        if ($budget) {
            $budget->delete();
        }
        $this->showList($telegramId, $chatId, $messageId);
    }

    private function progressBar(float $pct): string
    {
        $filled = (int) min(10, round($pct / 10));
        $empty  = 10 - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }
}
