<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class TelegramInitDataAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'tma ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $initData = trim(substr($authHeader, 4));

        if ($initData === '') {
            return response()->json(['error' => 'Missing initData'], 401);
        }

        if (!$this->validateHash($initData)) {
            return response()->json(['error' => 'Invalid initData signature'], 401);
        }

        $params = [];
        parse_str($initData, $params);

        $authDate = (int) ($params['auth_date'] ?? 0);
        // 7 days — mini apps can stay open a long time
        if ($authDate > 0 && time() - $authDate > 604800) {
            return response()->json(['error' => 'Session expired — please reopen the app'], 401);
        }

        $telegramUser = json_decode($params['user'] ?? '{}', true);
        $telegramId   = $telegramUser['id'] ?? null;

        if (!$telegramId) {
            return response()->json(['error' => 'User data missing in initData'], 401);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['error' => 'User not registered — send /start to the bot first'], 404);
        }

        $request->attributes->set('telegram_user', $user);

        App::setLocale($user->language ?? 'en');

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

        // Use the configured token (works even when config is cached)
        $botToken  = config('telegram.bots.mybot.token', '');
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $expected  = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($expected, $receivedHash);
    }
}
