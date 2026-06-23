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

        $initData = trim(substr($authHeader, 4));

        if ($initData === '') {
            return response()->json(['error' => 'Missing initData'], 401);
        }

        if (!$this->validateHash($initData)) {
            return response()->json(['error' => 'Invalid initData signature'], 401);
        }

        $params = $this->parseInitData($initData);

        $authDate = (int) ($params['auth_date'] ?? 0);
        // Allow up to 7 days — Telegram mini apps can stay open a long time
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

        return $next($request);
    }

    /**
     * Parse Telegram initData query string manually to avoid parse_str quirks.
     * Uses rawurldecode for values so + is not treated as space.
     */
    private function parseInitData(string $initData): array
    {
        $result = [];
        foreach (explode('&', $initData) as $part) {
            $eq = strpos($part, '=');
            if ($eq === false) {
                continue;
            }
            $key   = rawurldecode(substr($part, 0, $eq));
            $value = rawurldecode(substr($part, $eq + 1));
            $result[$key] = $value;
        }
        return $result;
    }

    private function validateHash(string $initData): bool
    {
        $params = $this->parseInitData($initData);

        $receivedHash = $params['hash'] ?? '';
        if ($receivedHash === '') {
            return false;
        }

        unset($params['hash']);
        ksort($params);

        // Rebuild data-check-string from decoded values
        $lines = [];
        foreach ($params as $k => $v) {
            $lines[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $lines);

        $botToken  = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', ''));
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $expected  = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($expected, strtolower($receivedHash));
    }
}
