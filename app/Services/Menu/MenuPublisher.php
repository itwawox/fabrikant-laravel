<?php

namespace App\Services\Menu;

use App\Enums\MenuStatus;
use App\Models\Menu;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Публикация меню. Опубликовано всегда ровно одно меню: новое публикуется — прежнее уходит в архив.
 * Кэш страниц сайта сбрасывается сам при сохранении меню (AppServiceProvider).
 */
class MenuPublisher
{
    public function publish(Menu $menu): void
    {
        if (! in_array($menu->status, [MenuStatus::Ready, MenuStatus::Archived], true)) {
            throw new MenuPdfException('Опубликовать можно только обработанное меню.');
        }

        DB::transaction(function () use ($menu) {
            Menu::published()->whereKeyNot($menu->getKey())->get()
                ->each(fn (Menu $old) => $old->update(['status' => MenuStatus::Archived]));
            $menu->update(['status' => MenuStatus::Published, 'published_at' => now(), 'scheduled_at' => null]);
        });
    }

    public function schedule(Menu $menu, CarbonInterface $at): void
    {
        if ($menu->status !== MenuStatus::Ready) {
            throw new MenuPdfException('Запланировать можно только обработанное, ещё не опубликованное меню.');
        }
        $menu->update(['scheduled_at' => $at]);
    }

    public function unschedule(Menu $menu): void
    {
        $menu->update(['scheduled_at' => null]);
    }

    /** Меню, которое было опубликовано до текущего, — для кнопки «Откатить». */
    public function previous(): ?Menu
    {
        return Menu::query()->where('status', MenuStatus::Archived)->whereNotNull('published_at')
            ->latest('published_at')->first();
    }

    /** Вернуть прошлое меню. Отдельного «снять с публикации» нет: без меню страница сайта пустая. */
    public function rollback(): Menu
    {
        $previous = $this->previous() ?? throw new MenuPdfException('Нет прошлого меню, на которое можно откатиться.');
        $this->publish($previous);

        return $previous;
    }

    /** Публикация запланированных меню; вызывает планировщик каждую минуту. Возвращает опубликованное. */
    public function publishDue(): ?Menu
    {
        $due = Menu::query()->where('status', MenuStatus::Ready)->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())->latest('scheduled_at')->first();

        if ($due !== null) {
            $this->publish($due);
        }

        return $due;
    }

    /** Удаление меню вместе с файлами. Опубликованное и обрабатываемое удалять нельзя. */
    public function delete(Menu $menu): void
    {
        if (in_array($menu->status, [MenuStatus::Published, MenuStatus::Processing], true)) {
            throw new MenuPdfException('Опубликованное меню и меню в обработке удалить нельзя.');
        }
        (new MenuFiles($menu))->deleteAll();
        $menu->delete();
    }
}
