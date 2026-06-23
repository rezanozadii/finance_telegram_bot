<?php

namespace App\Telegram\Handlers;

use App\Models\RecurringOccurrence;
use App\Models\RecurringTemplate;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\ConversationStateService;
use App\Services\RecurringService;
use App\Telegram\Keyboards\RecurringKeyboard;
use Carbon\Carbon;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class RecurringHandler
{
    public function __construct(
        private RecurringService $recurringService,
        private AccountService $accountService,
        private CategoryService $categoryService,
        private ConversationStateService $state,
    ) {}

    // ── Message (text input) steps ──────────────────────────────────────────

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'recurring.description'   => $this->stepDescription($telegramId, $chatId, $text),
            'recurring.amount'        => $this->stepAmount($telegramId, $chatId, $text),
            'recurring.start_date'    => $this->stepStartDate($telegramId, $chatId, $text),
            'recurring.edit_amount'   => $this->stepEditAmount($telegramId, $chatId, $text),
            default => null,
        };
    }

    // ── Callback query steps ────────────────────────────────────────────────

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'rec:add'                             => $this->startCreation($telegramId, $chatId),
            $action === 'rec:list'                            => $this->showList($telegramId, $chatId, $messageId),
            $action === 'rec:cancel'                          => $this->doCancel($telegramId, $chatId, $messageId),
            str_starts_with($action, 'rec_type:')            => $this->stepType($telegramId, $chatId, $messageId, substr($action, 9)),
            str_starts_with($action, 'rec_category:')        => $this->stepCategory($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            str_starts_with($action, 'rec_account:')         => $this->stepAccount($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'rec_freq:')            => $this->stepFrequency($telegramId, $chatId, $messageId, substr($action, 9)),
            str_starts_with($action, 'rec_reminder:')        => $this->stepReminder($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            str_starts_with($action, 'rec_template:')        => $this->showTemplateActions($telegramId, $chatId, $messageId, (int) substr($action, 13)),
            str_starts_with($action, 'rec_deactivate:') && !str_contains($action, '_confirm') => $this->confirmDeactivate($telegramId, $chatId, $messageId, (int) substr($action, 15)),
            str_starts_with($action, 'rec_deactivate_confirm:') => $this->doDeactivate($telegramId, $chatId, $messageId, (int) substr($action, 23)),
            str_starts_with($action, 'rec_confirm:')         => $this->doConfirm($telegramId, $chatId, $messageId, (int) substr($action, 12)),
            str_starts_with($action, 'rec_skip:')            => $this->doSkip($telegramId, $chatId, $messageId, (int) substr($action, 9)),
            str_starts_with($action, 'rec_edit_amount:')     => $this->beginEditAmount($telegramId, $chatId, (int) substr($action, 16)),
            default => null,
        };
    }

    // ── Creation flow ───────────────────────────────────────────────────────

    public function startCreation(int|string $telegramId, int|string $chatId): void
    {
        $this->state->set($telegramId, 'recurring.description');

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => __('bot.rec_ask_name'),
        ]);
    }

    private function stepDescription(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name_short')]);
            return;
        }

        $this->state->set($telegramId, 'recurring.type', ['description' => $text]);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.rec_ask_type', ['name' => $text]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(RecurringKeyboard::typeSelector()),
        ]);
    }

    private function stepType(int|string $telegramId, int|string $chatId, int $messageId, string $type): void
    {
        $user       = User::where('telegram_id', $telegramId)->firstOrFail();
        $categories = $this->categoryService->topLevel($user, $type);

        $this->state->set($telegramId, 'recurring.category', array_merge(
            $this->state->data($telegramId),
            ['type' => $type]
        ));

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.txn_ask_category'),
            'reply_markup' => json_encode(RecurringKeyboard::categorySelector($categories)),
        ]);
    }

    private function stepCategory(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $category = $user->categories()->find($categoryId);
        if (!$category) {
            return;
        }

        $accounts = $this->accountService->listActive($user);

        $this->state->set($telegramId, 'recurring.account', array_merge(
            $this->state->data($telegramId),
            ['category_id' => $categoryId]
        ));

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.rec_ask_account'),
            'reply_markup' => json_encode(RecurringKeyboard::accountSelector($accounts)),
        ]);
    }

    private function stepAccount(int|string $telegramId, int|string $chatId, int $messageId, int $accountId): void
    {
        $user    = User::where('telegram_id', $telegramId)->firstOrFail();
        $account = $user->accounts()->find($accountId);
        if (!$account) {
            return;
        }

        $this->state->set($telegramId, 'recurring.amount', array_merge(
            $this->state->data($telegramId),
            ['account_id' => $accountId, 'currency' => $account->currency]
        ));

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.txn_ask_amount', ['currency' => $account->currency]),
        ]);
    }

    private function stepAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.enter_positive_number')]);
            return;
        }

        $this->state->set($telegramId, 'recurring.frequency', array_merge(
            $this->state->data($telegramId),
            ['amount' => (float) $text]
        ));

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.rec_ask_frequency'),
            'reply_markup' => json_encode(RecurringKeyboard::frequencySelector()),
        ]);
    }

    private function stepFrequency(int|string $telegramId, int|string $chatId, int $messageId, string $frequency): void
    {
        $this->state->set($telegramId, 'recurring.start_date', array_merge(
            $this->state->data($telegramId),
            ['frequency' => $frequency]
        ));

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.rec_ask_start_date'),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepStartDate(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (strtolower($text) === 'today') {
            $date = today()->toDateString();
        } else {
            try {
                $date = Carbon::parse($text)->toDateString();
            } catch (\Throwable) {
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.rec_invalid_date')]);
                return;
            }
        }

        $this->state->set($telegramId, 'recurring.reminder', array_merge(
            $this->state->data($telegramId),
            ['next_due_date' => $date]
        ));

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.rec_ask_reminder'),
            'reply_markup' => json_encode(RecurringKeyboard::reminderSelector()),
        ]);
    }

    private function stepReminder(int|string $telegramId, int|string $chatId, int $messageId, int $days): void
    {
        $data = array_merge($this->state->data($telegramId), [
            'reminder_enabled'     => $days > 0,
            'reminder_days_before' => max(1, $days),
        ]);

        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $template = $this->recurringService->createTemplate($user, $data);

        $this->state->clear($telegramId);

        $freqLabel = ucfirst($template->frequency);
        $dateLabel = $template->next_due_date->format('M d, Y');
        $reminder  = $template->reminder_enabled
            ? "Reminder: {$template->reminder_days_before} day(s) before"
            : "No reminder";

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.rec_created', [
                'name'      => $template->description,
                'currency'  => $template->currency,
                'amount'    => number_format($template->amount, 2),
                'frequency' => $freqLabel,
            ]) . "\n🔁 {$freqLabel} · Next: {$dateLabel}\n🔔 {$reminder}",
            'parse_mode' => 'Markdown',
        ]);
    }

    // ── Due-date confirmation flow (triggered from Artisan command) ──────────

    public function sendDuePrompt(RecurringTemplate $template, RecurringOccurrence $occurrence): void
    {
        $icon     = $template->category?->icon ? $template->category->icon . ' ' : '📅 ';
        $typeLabel = $template->type === 'income' ? '💰 Income' : '💸 Expense';

        Telegram::sendMessage([
            'chat_id'      => $template->user->telegram_id,
            'text'         => __('bot.rec_due_msg', [
                'name'     => "{$icon}{$template->description}",
                'currency' => $template->currency,
                'amount'   => number_format($template->amount, 2),
                'date'     => $template->next_due_date->format('Y-m-d'),
            ]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(RecurringKeyboard::dueConfirmation($occurrence)),
        ]);
    }

    public function sendReminderNotice(RecurringTemplate $template): void
    {
        $daysUntil = now()->diffInDays($template->next_due_date);

        Telegram::sendMessage([
            'chat_id'    => $template->user->telegram_id,
            'text'       => __('bot.rec_reminder_msg', [
                'name'     => $template->description,
                'currency' => $template->currency,
                'amount'   => number_format($template->amount, 2),
                'date'     => $template->next_due_date->format('Y-m-d'),
            ]),
            'parse_mode' => 'Markdown',
        ]);
    }

    // ── Log / Skip / Edit amount ─────────────────────────────────────────────

    private function doConfirm(int|string $telegramId, int|string $chatId, int $messageId, int $occurrenceId): void
    {
        $occurrence = $this->ownedOccurrence($telegramId, $occurrenceId);
        if (!$occurrence || $occurrence->status !== 'pending') {
            return;
        }

        $txn     = $this->recurringService->confirmOccurrence($occurrence);
        $template = $occurrence->template;

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.rec_confirmed', ['name' => $template->description]) . "\n" .
                "{$template->currency} " . number_format($txn->amount, 2) . " · " .
                $template->fresh()->next_due_date->format('Y-m-d'),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function doSkip(int|string $telegramId, int|string $chatId, int $messageId, int $occurrenceId): void
    {
        $occurrence = $this->ownedOccurrence($telegramId, $occurrenceId);
        if (!$occurrence || $occurrence->status !== 'pending') {
            return;
        }

        $template = $occurrence->template;
        $this->recurringService->skipOccurrence($occurrence);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.rec_skipped') . "\n\n{$template->description}\n" .
                $template->fresh()->next_due_date->format('Y-m-d'),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function beginEditAmount(int|string $telegramId, int|string $chatId, int $occurrenceId): void
    {
        $occurrence = $this->ownedOccurrence($telegramId, $occurrenceId);
        if (!$occurrence || $occurrence->status !== 'pending') {
            return;
        }

        $this->state->set($telegramId, 'recurring.edit_amount', ['occurrence_id' => $occurrenceId]);

        $template = $occurrence->template;
        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.rec_ask_confirm_amount', ['name' => $template->description, 'currency' => $template->currency, 'amount' => number_format($template->amount, 2)]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepEditAmount(int|string $telegramId, int|string $chatId, string $text): void
    {
        if (!is_numeric($text) || (float) $text <= 0) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.enter_positive_number')]);
            return;
        }

        $data       = $this->state->data($telegramId);
        $occurrence = $this->ownedOccurrence($telegramId, $data['occurrence_id'] ?? 0);

        if (!$occurrence || $occurrence->status !== 'pending') {
            $this->state->clear($telegramId);
            return;
        }

        $txn      = $this->recurringService->confirmOccurrence($occurrence, (float) $text);
        $template = $occurrence->template;

        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.rec_confirmed', ['name' => $template->description]) . "\n" .
                "{$template->currency} " . number_format($txn->amount, 2) . " · " .
                $template->fresh()->next_due_date->format('Y-m-d'),
            'parse_mode' => 'Markdown',
        ]);
    }

    // ── List / manage ────────────────────────────────────────────────────────

    public function showList(int|string $telegramId, int|string $chatId, ?int $messageId = null): void
    {
        $user      = User::where('telegram_id', $telegramId)->firstOrFail();
        $templates = $this->recurringService->listActive($user);

        $text = $this->formatTemplateList($templates);

        $payload = [
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(RecurringKeyboard::templateList($templates)),
        ];

        if ($messageId) {
            Telegram::editMessageText(array_merge($payload, ['message_id' => $messageId]));
        } else {
            Telegram::sendMessage($payload);
        }
    }

    private function showTemplateActions(int|string $telegramId, int|string $chatId, int $messageId, int $templateId): void
    {
        $template = $this->ownedTemplate($telegramId, $templateId);
        if (!$template) {
            return;
        }

        $freqLabel = ucfirst($template->frequency);
        $dateLabel = $template->next_due_date->format('M d, Y');
        $catLabel  = $template->category
            ? ($template->category->icon ? $template->category->icon . ' ' : '') . $template->category->name
            : '—';

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => "*{$template->description}*\n\n" .
                __('bot.rec_label_amount')    . ": {$template->currency} " . number_format($template->amount, 2) . "\n" .
                __('bot.rec_label_category')  . ": {$catLabel}\n" .
                __('bot.rec_label_account')   . ": {$template->account->name}\n" .
                __('bot.rec_label_frequency') . ": {$freqLabel}\n" .
                __('bot.rec_label_next_due')  . ": {$dateLabel}",
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(RecurringKeyboard::templateActions($template)),
        ]);
    }

    private function confirmDeactivate(int|string $telegramId, int|string $chatId, int $messageId, int $templateId): void
    {
        $template = $this->ownedTemplate($telegramId, $templateId);
        if (!$template) {
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.rec_confirm_deactivate', ['name' => $template->description]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(RecurringKeyboard::confirmDeactivate($template)),
        ]);
    }

    private function doDeactivate(int|string $telegramId, int|string $chatId, int $messageId, int $templateId): void
    {
        $template = $this->ownedTemplate($telegramId, $templateId);
        if (!$template) {
            return;
        }

        $this->recurringService->deactivate($template);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.rec_deactivated', ['name' => $template->description]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function doCancel(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $this->state->clear($telegramId);

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.cancelled'),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function ownedTemplate(int|string $telegramId, int $templateId): ?RecurringTemplate
    {
        $user = User::where('telegram_id', $telegramId)->first();

        return $user?->recurringTemplates()->with(['account', 'category'])->find($templateId);
    }

    private function ownedOccurrence(int|string $telegramId, int $occurrenceId): ?RecurringOccurrence
    {
        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return null;
        }

        $occurrence = RecurringOccurrence::with('template.user')->find($occurrenceId);

        return $occurrence?->template?->user_id === $user->id ? $occurrence : null;
    }

    private function formatTemplateList(\Illuminate\Database\Eloquent\Collection $templates): string
    {
        if ($templates->isEmpty()) {
            return "🔁 *Recurring Transactions*\n\nNo active recurring transactions.";
        }

        $lines = ["🔁 *Recurring Transactions*\n"];

        foreach ($templates as $t) {
            $icon  = $t->category?->icon ? $t->category->icon . ' ' : '';
            $freq  = ucfirst($t->frequency);
            $date  = $t->next_due_date->format('M d');
            $lines[] = "{$icon}*{$t->description}* — {$t->currency} " .
                number_format($t->amount, 2) . " · {$freq} · next: {$date}";
        }

        return implode("\n", $lines);
    }
}
