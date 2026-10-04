<x-filament-widgets::widget>
    <x-filament::section heading="Акции: что гость видит сейчас">
        <x-slot name="afterHeader">
            <x-filament::link :href="\App\Filament\Pages\PromoSimulator::getUrl()">Проверить другое время</x-filament::link>
        </x-slot>
        @include('filament.promos.items', ['items' => $this->items()])
    </x-filament::section>
</x-filament-widgets::widget>
