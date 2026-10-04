<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Работает ли очередь задач (обработка меню, копии фото). На хостинге её запускает cron раз в минуту, локально —
 * php artisan schedule:work. Если задача ждёт дольше пары минут — очередь не запущена, и админке надо сказать
 * об этом прямо, а не показывать вечную «Обработку…».
 */
class QueueHealth
{
    // Cron запускает очередь раз в минуту: две минуты ожидания — уже точно не «ещё не дошла»
    public const STALL_SECONDS = 120;

    /** Задача ждёт в очереди дольше STALL_SECONDS и её никто не взял */
    public static function stalled(): bool
    {
        return DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<', now()->subSeconds(self::STALL_SECONDS)->getTimestamp())
            ->exists();
    }

    /** Что делать — одной фразой для админки; команду (если есть) показывают отдельно — её копируют целиком */
    public static function advice(): string
    {
        return app()->isLocal()
            ? 'Очередь задач не запущена. Откройте Терминал на этом компьютере, вставьте команду ниже и не закрывайте окно — обработка продолжится сама.'
            : 'Очередь задач на сервере не запущена (не работает cron). Сообщите разработчику — загруженное не потеряется, обработка продолжится, когда очередь заработает.';
    }

    /** Команда, которая запускает очередь на этом компьютере (только локально) */
    public static function command(): ?string
    {
        return app()->isLocal() ? 'cd '.base_path().' && php artisan schedule:work' : null;
    }
}
