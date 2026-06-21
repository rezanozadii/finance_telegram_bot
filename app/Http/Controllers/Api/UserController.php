<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request, AccountService $accountService): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $accounts = $accountService->listActive($user);

        return response()->json([
            'id'               => $user->id,
            'telegram_id'      => $user->telegram_id,
            'username'         => $user->username,
            'display_name'     => $user->display_name,
            'default_currency' => $user->default_currency,
            'account_count'    => $accounts->count(),
            'total_balance'    => $accountService->totalBalance($user, $user->default_currency ?? 'USD'),
        ]);
    }
}
