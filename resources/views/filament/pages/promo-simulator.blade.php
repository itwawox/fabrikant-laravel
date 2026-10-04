<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
            <label style="display:grid;gap:.25rem;font-size:.875rem;font-weight:500">
                Дата и время (Симферополь)
                <x-filament::input.wrapper>
                    <x-filament::input type="datetime-local" wire:model.live="at" />
                </x-filament::input.wrapper>
            </label>
            <x-filament::button color="gray" wire:click="now">Сейчас</x-filament::button>
        </div>
        <p style="margin:.75rem 0 0;color:var(--gray-500);font-size:.875rem">
            {{ $this->moment()->translatedFormat('l, j F Y, H:i') }}. Учитываются включённые акции с расписанием, праздники и дни без акций.
        </p>
    </x-filament::section>

    <x-filament::section heading="Плашка">
        @include('filament.promos.items', ['items' => $this->items()])
    </x-filament::section>
</x-filament-panels::page>
