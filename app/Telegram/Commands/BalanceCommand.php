<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Services\FriendService;
use Telegram\Bot\Commands\Command;

class BalanceCommand extends Command
{
    protected string $name = 'balance';
    protected string $description = 'Show balances with friends';

    public function handle(): void
    {
        $from = $this->getUpdate()->getMessage()->getFrom();
        $user = User::where('telegram_id', $from->getId())->first();

        if (!$user) {
            $this->replyWithMessage(['text' => __('bot.please_start_first')]);
            return;
        }

        $friendService = app(FriendService::class);
        $friends       = $friendService->getFriends($user);

        if ($friends->isEmpty()) {
            $this->replyWithMessage(['text' => "You have no friends yet. Use /addfriend to add one."]);
            return;
        }

        // Optional: filter to a specific friend if username provided
        $text     = $this->getUpdate()->getMessage()->getText() ?? '';
        $parts    = explode(' ', trim($text), 2);
        $username = ltrim(trim($parts[1] ?? ''), '@');

        if ($username !== '') {
            $friends = $friends->filter(fn (User $f) => strtolower($f->username ?? '') === strtolower($username));
            if ($friends->isEmpty()) {
                $this->replyWithMessage(['text' => "You're not friends with @{$username}."]);
                return;
            }
        }

        $lines = ["💰 *Balances*\n"];

        foreach ($friends as $friend) {
            $balances = $friendService->getBalance($user, $friend);
            $name     = $friend->username ? '@' . $friend->username : $friend->display_name;

            if (empty($balances)) {
                $lines[] = "{$name}: ✅ settled";
                continue;
            }

            foreach ($balances as $currency => $amount) {
                if (abs($amount) < 0.01) {
                    $lines[] = "{$name}: ✅ settled";
                } elseif ($amount > 0) {
                    $lines[] = "{$name}: they owe you *{$currency} " . number_format($amount, 2) . "*";
                } else {
                    $lines[] = "{$name}: you owe them *{$currency} " . number_format(abs($amount), 2) . "*";
                }
            }
        }

        $this->replyWithMessage([
            'text'       => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ]);
    }
}
