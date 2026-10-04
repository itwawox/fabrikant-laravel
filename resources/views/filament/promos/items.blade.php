{{-- Плашка «Сейчас действует» так, как её покажет сайт; $items — из PromoSchedule::items() --}}
@if ($items)
    <ul style="display:grid;gap:.5rem;margin:0;padding:0;list-style:none">
        @foreach ($items as $item)
            <li style="display:flex;gap:.75rem;align-items:baseline;flex-wrap:wrap">
                <x-filament::badge :color="match ($item['kind']) { 'live' => 'success', 'today' => 'warning', default => 'gray' }">
                    {{ $item['label'] }}
                </x-filament::badge>
                <strong>{{ $item['title'] }}</strong>
                <span style="color:var(--gray-500)">{{ $item['short'] }}</span>
            </li>
        @endforeach
    </ul>
@else
    <p style="color:var(--gray-500);margin:0">Плашки нет: в ближайшую неделю нет акций с расписанием.</p>
@endif
