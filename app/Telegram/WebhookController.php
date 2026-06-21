<?php

namespace App\Telegram;

use App\Models\User;
use App\Telegram\Handlers\CallbackHandler;
use App\Telegram\Handlers\MessageHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function __invoke(Request $request, MessageHandler $messages, CallbackHandler $callbacks): Response
    {
        // Set locale from user preference before processing anything
        $telegramId = $request->input('message.from.id')
            ?? $request->input('callback_query.from.id');
        if ($telegramId) {
            $lang = User::where('telegram_id', $telegramId)->value('language') ?? 'en';
            App::setLocale($lang);
        }

        $update = Telegram::commandsHandler(true);

        if (!$update) {
            return response('', 200);
        }

        if ($update->getCallbackQuery()) {
            $callbacks->handle($update->getCallbackQuery());
        } elseif ($update->getMessage()) {
            $message = $update->getMessage();
            $text    = $message->getText() ?? '';

            // Commands are already dispatched by commandsHandler — skip them here
            if (!str_starts_with($text, '/')) {
                $messages->handle($message);
            }
        }

        return response('', 200);
    }
}
