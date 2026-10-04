<?php

namespace App\Filament\Widgets;

use App\Services\PromoSchedule;
use Filament\Widgets\Widget;

/** Дашборд: что гость видит в плашке акций прямо сейчас. */
class PromosNow extends Widget
{
    protected string $view = 'filament.widgets.promos-now';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    // Считается мгновенно — грузить отдельным запросом незачем
    protected static bool $isLazy = false;

    /** @return list<array{id: string, title: string, short: ?string, kind: string, label: string}> */
    public function items(): array
    {
        $schedule = app(PromoSchedule::class);

        return $schedule->items($schedule->rules());
    }
}
