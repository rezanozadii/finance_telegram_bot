<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AccountService $accountService) {}

    public function index(Request $request): JsonResponse
    {
        $user     = $request->attributes->get('telegram_user');
        $accounts = $this->accountService->listActive($user);

        return response()->json($accounts->map(fn ($a) => [
            'id'        => $a->id,
            'name'      => $a->name,
            'type'      => $a->type,
            'currency'  => $a->currency,
            'balance'   => (float) $a->balance,
            'is_active' => (bool) $a->is_active,
        ])->values());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'type'     => 'required|in:cash,card,bank,e-wallet,credit',
            'currency' => 'required|string|max:10',
            'balance'  => 'sometimes|numeric',
        ]);

        $user    = $request->attributes->get('telegram_user');
        $account = $this->accountService->create(
            $user,
            $request->input('name'),
            $request->input('type'),
            $request->input('currency'),
            (float) $request->input('balance', 0),
        );

        return response()->json([
            'id'       => $account->id,
            'name'     => $account->name,
            'type'     => $account->type,
            'currency' => $account->currency,
            'balance'  => (float) $account->balance,
        ], 201);
    }
}
