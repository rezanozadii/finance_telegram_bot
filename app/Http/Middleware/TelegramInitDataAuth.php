<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramInitDataAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'tma ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $initData = substr($authHeader, 4);

        if (!$this->validateHash($initData)) {
            return response()->json(['error' => 'Invalid initData signature'], 401);
        }

        $params = [];
        parse_str($initData, $params);

        $authDate = (int) ($params['auth_date'] ?? 0);
        if (time() - $authDate > 86400) {
            return response()->json(['error' => 'initData expired'], 401);
        }

        $telegramUser = json_decode($params['user'] ?? '{}', true);
        $telegramId   = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            return response()->json(['error' => 'User missing in initData'], 401);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['error' => 'User not registered — start the bot first'], 404);
        }

        $request->attributes->set('telegram_user', $user);

        return $next($request);
    }

    private function validateHash(string $initData): bool
    {
        $params = [];
        parse_str($initData, $params);

        $receivedHash = $params['hash'] ?? '';
        if ($receivedHash === '') {
            return false;
        }

        unset($params['hash']);
        ksort($params);

        $dataCheckString = implode("\n", array_map(
            fn (string $k, string $v) => "{$k}={$v}",
            array_keys($params),
            array_values($params),
        ));

        $secretKey    = hash_hmac('sha256', env('TELEGRAM_BOT_TOKEN', ''), 'WebAppData', true);
        $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($expectedHash, $receivedHash);
    }
}
