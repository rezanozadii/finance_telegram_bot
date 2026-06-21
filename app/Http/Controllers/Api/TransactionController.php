<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->attributes->get('telegram_user');
        $limit = min((int) $request->query('limit', 20), 100);
        $offset = (int) $request->query('offset', 0);

        $query = Transaction::where('user_id', $user->id)
            ->with(['account', 'category', 'toAccount'])
            ->orderByDesc('occurred_at');

        if ($request->query('account_id')) {
            $query->where('account_id', $request->query('account_id'));
        }
        if ($request->query('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->query('from')) {
            $query->where('occurred_at', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->where('occurred_at', '<=', $request->query('to') . ' 23:59:59');
        }
        if ($request->query('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $total = $query->count();
        $items = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'data' => $items->map(fn ($t) => $this->formatTransaction($t)),
            'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'account_id'  => 'required|integer',
            'type'        => 'required|in:income,expense,transfer',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'required|string|max:10',
            'category_id' => 'nullable|integer',
            'merchant'    => 'nullable|string|max:200',
            'description' => 'nullable|string|max:500',
            'occurred_at' => 'nullable|date',
            'to_account_id' => 'nullable|integer',
        ]);

        $user = $request->attributes->get('telegram_user');

        // Guard: account must belong to this user
        $ownedAccountIds = $user->accounts()->pluck('id')->all();
        if (!in_array((int) $request->input('account_id'), $ownedAccountIds, true)) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $txn = $this->transactionService->createTransaction($user, [
            'account_id'    => $request->input('account_id'),
            'type'          => $request->input('type'),
            'amount'        => (float) $request->input('amount'),
            'currency'      => $request->input('currency'),
            'category_id'   => $request->input('category_id'),
            'merchant'      => $request->input('merchant'),
            'description'   => $request->input('description'),
            'occurred_at'   => $request->input('occurred_at', now()->toDateTimeString()),
            'to_account_id' => $request->input('to_account_id'),
            'source'        => 'manual',
        ]);

        $txn->load(['account', 'category', 'toAccount']);

        return response()->json($this->formatTransaction($txn), 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('telegram_user');
        $txn  = Transaction::where('id', $id)->where('user_id', $user->id)->first();

        if (!$txn) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $this->transactionService->deleteTransaction($txn);

        return response()->json(['success' => true]);
    }

    private function formatTransaction(Transaction $t): array
    {
        return [
            'id'          => $t->id,
            'type'        => $t->type,
            'amount'      => (float) $t->amount,
            'currency'    => $t->currency,
            'merchant'    => $t->merchant,
            'description' => $t->description,
            'occurred_at' => $t->occurred_at?->toIso8601String(),
            'source'      => $t->source,
            'account'     => $t->account ? ['id' => $t->account->id, 'name' => $t->account->name, 'type' => $t->account->type] : null,
            'category'    => $t->category ? ['id' => $t->category->id, 'name' => $t->category->name, 'icon' => $t->category->icon, 'type' => $t->category->type] : null,
            'to_account'  => $t->toAccount ? ['id' => $t->toAccount->id, 'name' => $t->toAccount->name] : null,
        ];
    }
}
