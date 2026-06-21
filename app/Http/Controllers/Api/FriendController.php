<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FriendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function __construct(private FriendService $friendService) {}

    public function index(Request $request): JsonResponse
    {
        $user    = $request->attributes->get('telegram_user');
        $friends = $this->friendService->getFriends($user);

        return response()->json($friends->map(function ($f) use ($user) {
            $balances = $this->friendService->getBalance($user, $f);
            return [
                'id'           => $f->id,
                'username'     => $f->username,
                'display_name' => $f->display_name,
                'balances'     => $balances,
            ];
        })->values());
    }

    public function expenses(Request $request, int $friendId): JsonResponse
    {
        $user   = $request->attributes->get('telegram_user');
        $friend = \App\Models\User::find($friendId);

        if (!$friend || !$this->friendService->areFriends($user, $friend)) {
            return response()->json(['error' => 'Friend not found'], 404);
        }

        $expenses = \App\Models\SharedExpense::where(function ($q) use ($user, $friend) {
            $q->where('paid_by_user_id', $user->id)->where('owed_by_user_id', $friend->id);
        })->orWhere(function ($q) use ($user, $friend) {
            $q->where('paid_by_user_id', $friend->id)->where('owed_by_user_id', $user->id);
        })->where('status', 'open')
          ->orderByDesc('created_at')
          ->get();

        return response()->json($expenses->map(fn ($e) => [
            'id'          => $e->id,
            'amount'      => (float) $e->amount,
            'currency'    => $e->currency,
            'description' => $e->description,
            'paid_by_me'  => $e->paid_by_user_id === $user->id,
            'created_at'  => $e->created_at?->toIso8601String(),
        ])->values());
    }
}
