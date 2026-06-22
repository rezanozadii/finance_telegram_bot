<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Models\UserGoal;
use App\Services\ConversationStateService;
use App\Telegram\Keyboards\GoalKeyboard;
use Carbon\Carbon;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class GoalHandler
{
    public function __construct(private ConversationStateService $state) {}

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'goal.name'     => $this->stepName($telegramId, $chatId, $text),
            'goal.amount'   => $this->stepAmount($telegramId, $chatId, $text),
            'goal.currency' => $this->stepCurrency($telegramId, $chatId, $text),
            'goal.deadline' => $this->stepDeadline($telegramId, $chatId, $text),
            default         => null,
        };
    }

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'goal:add'                        => $this->startCreation($telegramId, $chatId),
            $action === 'goal:list'                       => $this->showList($telegramId, $chatId, $messageId),
            str_starts_with($action, 'goal_delete:')     => $this->delete($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'goal_complete:')   => $this->markComplete($telegramId, $chatId, $messageId, (int) substr($action, 14)),
            default                                       => null,
        };
    }

    public function showList(int|string $telegramId, int|string $chatId, ?int $messageId = null): void
    {
        $user  = User::where('telegram_id', $telegramId)->firstOrFail();
        $goals = $user->goals()->where('status', 'active')->get();

        $text = $user->language === 'fa' ? "🎯 *اهداف مالی*\n\n" : "🎯 *Financial Goals*\n\n";

        if ($goals->isEmpty()) {
            $text .= $user->language === 'fa' ? 'هنوز هیچ هدفی ندارید.' : 'You have no active goals yet.';
        } else {
            foreach ($goals as $goal) {
                $bar   = $this->progressBar($goal->progressPct());
                $text .= "*{$goal->name}*\n";
                $text .= "{$bar} {$goal->progressPct()}%\n";
                $text .= "{$goal->currency} " . number_format((float) $goal->current_amount, 2) . ' / ' . number_format((float) $goal->target_amount, 2) . "\n\n";
            }
        }

        $keyboard = GoalKeyboard::list($goals);
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
        $this->state->set($telegramId, 'goal.name');
        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? '🎯 نام هدف را بنویسید (مثلاً: لپ‌تاپ، سفر، صندوق اضطراری):' : '🎯 Enter the goal name (e.g., Laptop, Vacation, Emergency Fund):',
        ]);
    }

    private function stepName(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a goal name.']);
            return;
        }

        $this->state->set($telegramId, 'goal.amount', ['name' => $text]);
        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? "💰 مبلغ هدف برای \"{$text}\" را وارد کنید:" : "💰 Enter the target amount for \"{$text}\":",
        ]);
    }

    private function stepAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Please enter a valid positive amount.']);
            return;
        }

        $this->state->set($telegramId, 'goal.currency', array_merge(
            $this->state->data($telegramId),
            ['target_amount' => (float) $text]
        ));

        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa' ? '💱 ارز را وارد کنید (مثلاً: USD, EUR, IRR):' : 'Enter currency (e.g., USD, EUR):',
        ]);
    }

    private function stepCurrency(int|string $telegramId, int|string $chatId, string $text): void
    {
        $currency = strtoupper(trim($text));
        if (strlen($currency) < 2 || strlen($currency) > 10) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Invalid currency code.']);
            return;
        }

        $this->state->set($telegramId, 'goal.deadline', array_merge(
            $this->state->data($telegramId),
            ['currency' => $currency]
        ));

        $user = User::where('telegram_id', $telegramId)->first();
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => $user?->language === 'fa'
                ? '📅 تاریخ ضرب‌الاجل را وارد کنید (YYYY-MM-DD) یا "skip" برای بدون تاریخ:'
                : '📅 Enter a deadline date (YYYY-MM-DD) or type "skip" for no deadline:',
        ]);
    }

    private function stepDeadline(int|string $telegramId, int|string $chatId, string $text): void
    {
        $data     = $this->state->data($telegramId);
        $deadline = null;

        if (strtolower($text) !== 'skip') {
            try {
                $deadline = Carbon::parse($text)->toDateString();
            } catch (\Throwable) {
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => 'Invalid date format. Use YYYY-MM-DD or type "skip".']);
                return;
            }
        }

        $user = User::where('telegram_id', $telegramId)->firstOrFail();
        UserGoal::create([
            'user_id'       => $user->id,
            'name'          => $data['name'],
            'target_amount' => $data['target_amount'],
            'current_amount'=> 0,
            'currency'      => $data['currency'],
            'deadline'      => $deadline,
            'status'        => 'active',
        ]);

        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => $user->language === 'fa'
                ? "✅ هدف *{$data['name']}* با موفقیت ایجاد شد!"
                : "✅ Goal *{$data['name']}* created successfully!",
            'parse_mode' => 'Markdown',
        ]);
    }

    private function delete(int|string $telegramId, int|string $chatId, int $messageId, int $goalId): void
    {
        $user = User::where('telegram_id', $telegramId)->firstOrFail();
        $goal = $user->goals()->find($goalId);

        if ($goal) {
            $name = $goal->name;
            $goal->delete();
            $this->showList($telegramId, $chatId, $messageId);
        }
    }

    private function markComplete(int|string $telegramId, int|string $chatId, int $messageId, int $goalId): void
    {
        $user = User::where('telegram_id', $telegramId)->firstOrFail();
        $goal = $user->goals()->find($goalId);

        if ($goal) {
            $goal->update(['status' => 'completed', 'current_amount' => $goal->target_amount]);
            $this->showList($telegramId, $chatId, $messageId);
        }
    }

    private function progressBar(float $pct): string
    {
        $filled = (int) round($pct / 10);
        $empty  = 10 - $filled;
        return str_repeat('█', $filled) . str_repeat('░', $empty);
    }
}
