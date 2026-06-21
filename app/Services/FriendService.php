<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\SharedExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FriendService
{
    // ── Friend requests ──────────────────────────────────────────────────────

    public function findByUsername(string $username): ?User
    {
        return User::where('username', ltrim($username, '@'))->first();
    }

    /** Returns the Friendship or null if one already exists in any state. */
    public function sendRequest(User $from, User $to): ?Friendship
    {
        if ($this->relationshipExists($from, $to)) {
            return null;
        }

        return Friendship::create([
            'user_id'        => $from->id,
            'friend_user_id' => $to->id,
            'status'         => 'pending',
        ]);
    }

    public function acceptRequest(Friendship $friendship): void
    {
        $friendship->update(['status' => 'accepted']);

        Friendship::firstOrCreate(
            [
                'user_id'        => $friendship->friend_user_id,
                'friend_user_id' => $friendship->user_id,
            ],
            ['status' => 'accepted']
        );
    }

    public function declineRequest(Friendship $friendship): void
    {
        $friendship->delete();
    }

    public function areFriends(User $a, User $b): bool
    {
        return Friendship::where('user_id', $a->id)
            ->where('friend_user_id', $b->id)
            ->where('status', 'accepted')
            ->exists();
    }

    public function getFriends(User $user): Collection
    {
        $friendIds = Friendship::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->pluck('friend_user_id');

        return User::whereIn('id', $friendIds)->orderBy('display_name')->get();
    }

    public function getIncomingRequests(User $user): Collection
    {
        return Friendship::where('friend_user_id', $user->id)
            ->where('status', 'pending')
            ->with('user')
            ->get();
    }

    // ── Shared expenses ──────────────────────────────────────────────────────

    /**
     * Log that $from paid and $to owes them $amount.
     */
    public function logSharedExpense(
        User $from,
        User $to,
        float $amount,
        string $currency,
        string $description,
        ?int $transactionId = null,
    ): SharedExpense {
        return SharedExpense::create([
            'from_user_id'           => $from->id,
            'to_user_id'             => $to->id,
            'amount'                 => $amount,
            'currency'               => $currency,
            'description'            => $description,
            'status'                 => 'open',
            'related_transaction_id' => $transactionId,
        ]);
    }

    /**
     * Returns per-currency net balances between two users.
     * Positive = $friend owes $user. Negative = $user owes $friend.
     */
    public function getBalance(User $user, User $friend): array
    {
        $open = SharedExpense::where('status', 'open')
            ->where(function ($q) use ($user, $friend) {
                $q->where(fn ($q) => $q->where('from_user_id', $user->id)->where('to_user_id', $friend->id))
                  ->orWhere(fn ($q) => $q->where('from_user_id', $friend->id)->where('to_user_id', $user->id));
            })
            ->get();

        $byCurrency = [];

        foreach ($open as $expense) {
            $cur = $expense->currency;
            $byCurrency[$cur] ??= 0.0;

            if ($expense->from_user_id === $user->id) {
                $byCurrency[$cur] += (float) $expense->amount;  // friend owes me
            } else {
                $byCurrency[$cur] -= (float) $expense->amount;  // I owe friend
            }
        }

        return $byCurrency;
    }

    /**
     * Mark all open shared expenses between two users as settled.
     * Returns the count of settled records.
     */
    public function settleUp(User $user, User $friend): int
    {
        return SharedExpense::where('status', 'open')
            ->where(function ($q) use ($user, $friend) {
                $q->where(fn ($q) => $q->where('from_user_id', $user->id)->where('to_user_id', $friend->id))
                  ->orWhere(fn ($q) => $q->where('from_user_id', $friend->id)->where('to_user_id', $user->id));
            })
            ->update(['status' => 'settled']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function relationshipExists(User $a, User $b): bool
    {
        return Friendship::where(function ($q) use ($a, $b) {
            $q->where('user_id', $a->id)->where('friend_user_id', $b->id);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('user_id', $b->id)->where('friend_user_id', $a->id);
        })->exists();
    }
}
