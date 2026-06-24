<?php

namespace App\Telegram\Handlers;

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ConversationStateService;
use App\Telegram\Keyboards\CategoryKeyboard;
use Illuminate\Database\Eloquent\Collection;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;

class CategoryHandler
{
    public function __construct(
        private CategoryService $categoryService,
        private ConversationStateService $state,
    ) {}

    public function handleMessage(Message $message, string $step): void
    {
        $telegramId = $message->getFrom()->getId();
        $chatId     = $message->getChat()->getId();
        $text       = trim($message->getText() ?? '');

        match ($step) {
            'category.name'      => $this->stepName($telegramId, $chatId, $text),
            'category.icon'      => $this->stepIcon($telegramId, $chatId, $text),
            'category.rename'    => $this->stepRename($telegramId, $chatId, $text),
            'category.icon_edit' => $this->stepIconEdit($telegramId, $chatId, $text),
            default              => null,
        };
    }

    public function handleCallback(CallbackQuery $query, string $action): void
    {
        $telegramId = $query->getFrom()->getId();
        $chatId     = $query->getMessage()->getChat()->getId();
        $messageId  = $query->getMessage()->getMessageId();

        Telegram::answerCallbackQuery(['callback_query_id' => $query->getId()]);

        match (true) {
            $action === 'category:add'                            => $this->startCreation($telegramId, $chatId),
            $action === 'category:list'                           => $this->showList($telegramId, $chatId, $messageId),
            $action === 'category:manage'                         => $this->showManage($telegramId, $chatId, $messageId),
            str_starts_with($action, 'category_type:')           => $this->stepType($telegramId, $chatId, substr($action, 14)),
            $action === 'category_icon:skip'                      => $this->stepIconSkip($telegramId, $chatId),
            str_starts_with($action, 'category_parent:')         => $this->stepParent($telegramId, $chatId, substr($action, 16)),
            str_starts_with($action, 'category_edit:')           => $this->showActions($telegramId, $chatId, $messageId, (int) substr($action, 14)),
            str_starts_with($action, 'category_rename:')         => $this->beginRename($telegramId, $chatId, (int) substr($action, 16)),
            str_starts_with($action, 'category_icon_edit:')      => $this->beginIconEdit($telegramId, $chatId, (int) substr($action, 19)),
            str_starts_with($action, 'category_delete:') && !str_contains($action, '_confirm') => $this->confirmDelete($telegramId, $chatId, $messageId, (int) substr($action, 16)),
            str_starts_with($action, 'category_delete_confirm:') => $this->doDelete($telegramId, $chatId, $messageId, (int) substr($action, 24)),
            default => null,
        };
    }

    public function startCreation(int|string $telegramId, int|string $chatId): void
    {
        $this->state->set($telegramId, 'category.type');

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.category_ask_type'),
            'reply_markup' => json_encode(CategoryKeyboard::typeSelector()),
        ]);
    }

    private function stepType(int|string $telegramId, int|string $chatId, string $type): void
    {
        $this->state->set($telegramId, 'category.name', ['type' => $type]);

        $label = $type === 'income' ? '💰 ' . __('bot.report_income') : '💸 ' . __('bot.report_expenses');

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_ask_name', ['type' => $label]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepName(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name_short')]);
            return;
        }

        $this->state->set($telegramId, 'category.icon', array_merge(
            $this->state->data($telegramId),
            ['name' => $text]
        ));

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.category_ask_icon', ['name' => $text]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(CategoryKeyboard::iconStep()),
        ]);
    }

    private function stepIcon(int|string $telegramId, int|string $chatId, string $text): void
    {
        $this->proceedToParent($telegramId, $chatId, $text ?: null);
    }

    private function stepIconSkip(int|string $telegramId, int|string $chatId): void
    {
        $this->proceedToParent($telegramId, $chatId, null);
    }

    private function proceedToParent(int|string $telegramId, int|string $chatId, ?string $icon): void
    {
        $data     = array_merge($this->state->data($telegramId), ['icon' => $icon]);
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $topLevel = $this->categoryService->topLevel($user, $data['type']);

        if ($topLevel->isEmpty()) {
            $this->createCategory($telegramId, $chatId, $data, null);
            return;
        }

        $this->state->set($telegramId, 'category.parent', $data);

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => __('bot.category_ask_parent'),
            'reply_markup' => json_encode(CategoryKeyboard::parentSelector($topLevel)),
        ]);
    }

    private function stepParent(int|string $telegramId, int|string $chatId, string $value): void
    {
        $data     = $this->state->data($telegramId);
        $parentId = $value === 'none' ? null : (int) $value;
        $this->createCategory($telegramId, $chatId, $data, $parentId);
    }

    private function createCategory(int|string $telegramId, int|string $chatId, array $data, ?int $parentId): void
    {
        $user     = User::where('telegram_id', $telegramId)->firstOrFail();
        $category = $this->categoryService->create(
            $user,
            $data['name'],
            $data['type'],
            $data['icon'] ?? null,
            $parentId,
        );

        $this->state->clear($telegramId);

        $icon = $category->icon ? $category->icon . ' ' : '';

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_created', ['icon' => $icon, 'name' => $category->name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    public function showList(int|string $telegramId, int|string $chatId, ?int $messageId = null): void
    {
        $user       = User::where('telegram_id', $telegramId)->firstOrFail();
        $categories = $this->categoryService->listForUser($user);
        $text       = $this->formatCategoryList($categories);

        $payload = [
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(CategoryKeyboard::mainMenu()),
        ];

        $messageId
            ? Telegram::editMessageText(array_merge($payload, ['message_id' => $messageId]))
            : Telegram::sendMessage($payload);
    }

    private function showManage(int|string $telegramId, int|string $chatId, int $messageId): void
    {
        $user       = User::where('telegram_id', $telegramId)->firstOrFail();
        $categories = $this->categoryService->listForUser($user);

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.category_tap_to_edit'),
            'reply_markup' => json_encode(CategoryKeyboard::manageGrid($categories)),
        ]);
    }

    private function showActions(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        $icon = $category->icon ? $category->icon . ' ' : '';

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.category_detail', ['icon' => $icon, 'name' => $category->localizedName(), 'type' => $category->type]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(CategoryKeyboard::categoryActions($category, $this->categoryService->canDelete($category))),
        ]);
    }

    private function beginRename(int|string $telegramId, int|string $chatId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        $this->state->set($telegramId, 'category.rename', ['category_id' => $categoryId]);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_ask_rename', ['name' => $category->name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepRename(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name_short')]);
            return;
        }

        $data     = $this->state->data($telegramId);
        $category = $this->ownedCategory($telegramId, $data['category_id'] ?? 0);

        if (!$category) {
            $this->state->clear($telegramId);
            return;
        }

        $this->categoryService->rename($category, $text);
        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_renamed', ['name' => $text]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function beginIconEdit(int|string $telegramId, int|string $chatId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        $this->state->set($telegramId, 'category.icon_edit', ['category_id' => $categoryId]);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_ask_icon_edit', ['name' => $category->name]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function stepIconEdit(int|string $telegramId, int|string $chatId, string $text): void
    {
        if ($text === '') {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => __('bot.account_enter_name_short')]);
            return;
        }

        $data     = $this->state->data($telegramId);
        $category = $this->ownedCategory($telegramId, $data['category_id'] ?? 0);

        if (!$category) {
            $this->state->clear($telegramId);
            return;
        }

        $this->categoryService->changeIcon($category, $text);
        $this->state->clear($telegramId);

        Telegram::sendMessage([
            'chat_id'    => $chatId,
            'text'       => __('bot.category_icon_updated', ['icon' => $text]),
            'parse_mode' => 'Markdown',
        ]);
    }

    private function confirmDelete(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        if (!$this->categoryService->canDelete($category)) {
            Telegram::editMessageText([
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'text'         => __('bot.category_has_transactions'),
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(CategoryKeyboard::categoryActions($category, false)),
            ]);
            return;
        }

        Telegram::editMessageText([
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'text'         => __('bot.category_confirm_delete', ['name' => $category->name]),
            'parse_mode'   => 'Markdown',
            'reply_markup' => json_encode(CategoryKeyboard::confirmDelete($category)),
        ]);
    }

    private function doDelete(int|string $telegramId, int|string $chatId, int $messageId, int $categoryId): void
    {
        $category = $this->ownedCategory($telegramId, $categoryId);
        if (!$category) {
            return;
        }

        $deleted = $this->categoryService->delete($category);

        if (!$deleted) {
            Telegram::editMessageText([
                'chat_id'    => $chatId,
                'message_id' => $messageId,
                'text'       => __('bot.category_has_transactions'),
                'parse_mode' => 'Markdown',
            ]);
            return;
        }

        Telegram::editMessageText([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => __('bot.category_deleted'),
        ]);
    }

    private function ownedCategory(int|string $telegramId, int $categoryId): ?Category
    {
        $user = User::where('telegram_id', $telegramId)->first();
        return $user?->categories()->find($categoryId);
    }

    public function formatCategoryList(Collection $categories): string
    {
        $expense = $categories->where('type', 'expense')->where('parent_id', null);
        $income  = $categories->where('type', 'income')->where('parent_id', null);

        $lines = ["📋 *" . __('bot.rec_list_title') . "*\n"];

        if ($expense->isNotEmpty()) {
            $lines[] = "💸 *" . __('bot.report_expenses') . "* ({$expense->count()})";
            foreach ($expense as $cat) {
                $icon    = $cat->icon ? $cat->icon . ' ' : '• ';
                $lines[] = "  {$icon}{$cat->localizedName()}";
                foreach ($categories->where('parent_id', $cat->id) as $child) {
                    $ci      = $child->icon ? $child->icon . ' ' : '◦ ';
                    $lines[] = "    ↳ {$ci}{$child->localizedName()}";
                }
            }
        }

        if ($income->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "💰 *" . __('bot.report_income') . "* ({$income->count()})";
            foreach ($income as $cat) {
                $icon    = $cat->icon ? $cat->icon . ' ' : '• ';
                $lines[] = "  {$icon}{$cat->localizedName()}";
                foreach ($categories->where('parent_id', $cat->id) as $child) {
                    $ci      = $child->icon ? $child->icon . ' ' : '◦ ';
                    $lines[] = "    ↳ {$ci}{$child->localizedName()}";
                }
            }
        }

        return implode("\n", $lines);
    }
}
