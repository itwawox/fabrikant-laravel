<?php

namespace App\Services\Menu;

use App\Enums\MenuStatus;
use App\Jobs\Menu\InspectMenuPdf;
use App\Models\Menu;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Обработка PDF типографии: проверка → страницы картинками → лёгкий PDF → перенос разметки → «готово».
 * Шаги — задачи очереди (app/Jobs/Menu), прогресс пишется в меню и виден в админке.
 */
class MenuPipeline
{
    public const STEPS = [
        'inspect' => 'Проверка PDF',
        'pages' => 'Страницы',
        'web_pdf' => 'PDF для скачивания',
        'carry_over' => 'Перенос подписей и разделов',
    ];

    /**
     * Новое меню из PDF, загруженного в админке на закрытый диск ($upload — путь на диске local).
     */
    public function create(string $season, string $upload, bool $carryOver = true, ?CarbonInterface $scheduledAt = null, ?int $userId = null): Menu
    {
        $menu = Menu::create([
            'season' => trim($season),
            // Служебное имя (в старом сайте — основа имён файлов); в новом файлы лежат в папке меню
            'name' => 'menu_'.now()->format('Ymd_His').'_'.Str::lower(Str::random(4)),
            'status' => MenuStatus::Draft,
            'carry_over' => $carryOver,
            'scheduled_at' => $scheduledAt,
            'created_by' => $userId,
        ]);
        $dir = MenuFiles::ensureDir($menu);
        Storage::disk('local')->move($upload, $dir.'/source.pdf');
        $menu->update(['source_pdf_path' => $dir.'/source.pdf']);

        $this->start($menu);

        return $menu;
    }

    /** Запуск и повтор после ошибки (готовые страницы пропускаются). */
    public function start(Menu $menu): void
    {
        $menu->update([
            'status' => MenuStatus::Processing,
            'error' => null,
            'progress_step' => 'inspect',
            'progress_done' => 0,
            'progress_total' => 0,
        ]);

        InspectMenuPdf::dispatch($menu);
    }

    public function fail(Menu $menu, ?Throwable $e): void
    {
        // Понятные ошибки показываем как есть, остальные — общим текстом, подробности в журнале
        if (! $e instanceof MenuPdfException) {
            Log::error('Menu processing failed', ['menu' => $menu->getKey(), 'exception' => $e]);
        }

        $menu->refresh()->update([
            'status' => MenuStatus::Draft,
            'progress_step' => null,
            'error' => $e instanceof MenuPdfException
                ? $e->getMessage()
                : 'Не получилось обработать меню из-за ошибки на сервере. Нажмите «Повторить»; если не поможет — сообщите разработчику.',
        ]);
    }

    /** «Страницы: 3 из 8» */
    public static function progressLabel(Menu $menu): ?string
    {
        if ($menu->status !== MenuStatus::Processing) {
            return null;
        }
        $step = self::STEPS[$menu->progress_step ?? 'inspect'] ?? 'Обработка';

        return $menu->progress_step === 'pages' && $menu->progress_total
            ? "{$step}: {$menu->progress_done} из {$menu->progress_total}"
            : $step;
    }
}
