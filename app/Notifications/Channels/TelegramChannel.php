<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сообщение в Telegram от бота ресторана. Токен — только в .env (TELEGRAM_BOT_TOKEN); куда писать —
 * чат из route(TelegramChannel::class, …). Не настроено — молча пропускаем: заявка всё равно в админке и на почте.
 */
class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $token = config('services.telegram.bot_token');
        $chat = method_exists($notifiable, 'routeNotificationFor') ? $notifiable->routeNotificationFor(self::class, $notification) : null;
        if (! $token || ! $chat || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        $response = Http::timeout(5)->asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chat,
            'text' => $notification->toTelegram($notifiable),
            'disable_web_page_preview' => true,
        ]);
        if (! $response->successful()) {
            Log::warning('Telegram: сообщение не отправлено', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
        }
    }
}
