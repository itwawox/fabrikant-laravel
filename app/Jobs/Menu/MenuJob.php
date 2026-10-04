<?php

namespace App\Jobs\Menu;

use App\Models\Menu;
use App\Services\Menu\MenuPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Шаг обработки PDF меню. Шаги идут цепочкой; если шаг упал, цепочка останавливается, а админка показывает
 * ошибку и кнопку «Повторить» — повтор начинает заново, но готовые страницы пропускает.
 */
abstract class MenuJob implements ShouldQueue
{
    use Queueable;

    // Повторяет человек кнопкой: автоматический повтор битого PDF ничего не даст
    public int $tries = 1;

    // Три отрисовки страницы A3 занимают пару секунд, но сервер бывает занят
    public int $timeout = 300;

    public function __construct(public Menu $menu) {}

    public function failed(?Throwable $e): void
    {
        app(MenuPipeline::class)->fail($this->menu, $e);
    }
}
