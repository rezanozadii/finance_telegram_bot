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
            'language'         => $user->language ?? 'en',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');

        $data = [];

        if ($request->has('language') && in_array($request->input('language'), ['en', 'fa'])) {
            $data['language'] = $request->input('language');
            // Auto-set currency default when language changes, unless currency is also explicitly provided
            if (!$request->has('default_currency')) {
                $data['default_currency'] = $data['language'] === 'fa' ? 'IRR' : 'USD';
            }
        }

        if ($request->has('default_currency')) {
            $currency = strtoupper(trim($request->input('default_currency', '')));
            if (strlen($currency) >= 2 && strlen($currency) <= 10) {
                $data['default_currency'] = $currency;
            }
        }

        if (!empty($data)) {
            $user->update($data);
        }

        return response()->json([
            'language'         => $user->fresh()->language ?? 'en',
            'default_currency' => $user->fresh()->default_currency,
        ]);
    }
}
