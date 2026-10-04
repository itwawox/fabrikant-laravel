<?php

namespace App\Filament\Pages;

use App\Services\PromoSchedule;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * Симулятор плашки «Сейчас действует»: выбрать дату и время — увидеть, что покажет сайт.
 * Считает тот же PromoSchedule, что и сайт, по текущим акциям, праздникам и дням без акций.
 */
class PromoSimulator extends Page
{
    protected string $view = 'filament.pages.promo-simulator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Акции';

    protected static ?string $navigationLabel = 'Плашка: проверить';

    protected static ?string $title = 'Что гость видит в плашке «Сейчас действует»';

    protected static ?int $navigationSort = 4;

    // Дата и время в поле datetime-local: 2026-10-05T14:00
    public string $at = '';

    public function mount(): void
    {
        $this->at = CarbonImmutable::now(PromoSchedule::TZ)->format('Y-m-d\TH:i');
    }

    public function now(): void
    {
        $this->mount();
    }

    /** @return list<array{id: string, title: string, short: ?string, kind: string, label: string}> */
    public function items(): array
    {
        $schedule = app(PromoSchedule::class);

        return $schedule->items($schedule->rules(), $this->moment());
    }

    public function moment(): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->at, PromoSchedule::TZ) ?: CarbonImmutable::now(PromoSchedule::TZ);
        } catch (Throwable) {
            return CarbonImmutable::now(PromoSchedule::TZ);
        }
    }
}
