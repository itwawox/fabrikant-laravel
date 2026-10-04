@php
    use App\Enums\MenuStatus;
    use App\Services\Menu\MenuPipeline;

    /** @var \App\Models\Menu $menu */
    $menu = $getRecord();
    $processing = $menu->status === MenuStatus::Processing;
    $percent = $menu->progress_total ? intdiv(100 * $menu->progress_done, $menu->progress_total) : 0;
@endphp

{{-- Пока меню обрабатывается, карточка спрашивает сервер раз в 2 секунды --}}
<div @if ($processing) wire:poll.2s="refreshStatus" @endif>
    <x-filament::section>
        @if ($menu->error)
            <div class="fi-menu-status" style="color: var(--danger-600)">
                <strong>Не получилось обработать меню.</strong> {{ $menu->error }}
            </div>
        @elseif ($processing)
            <div>
                <strong>Обработка…</strong> {{ MenuPipeline::progressLabel($menu) }}
                <div style="margin-top:.5rem;height:.5rem;border-radius:9999px;background:var(--gray-200);overflow:hidden">
                    <div style="height:100%;width:{{ $menu->progress_step === 'pages' ? $percent : ($menu->progress_step === 'inspect' ? 2 : 100) }}%;background:var(--primary-500);transition:width .5s"></div>
                </div>
                <p style="margin-top:.5rem;color:var(--gray-500)">Можно уйти со страницы — обработка идёт на сервере, несколько секунд на страницу.</p>
            </div>
        @elseif ($menu->status === MenuStatus::Published)
            <strong>Опубликовано</strong> {{ $menu->published_at?->translatedFormat('j F Y в H:i') }} — гости видят это меню.
        @elseif ($menu->status === MenuStatus::Ready)
            <strong>Готово к публикации.</strong>
            @if ($menu->scheduled_at)
                Опубликуется автоматически {{ $menu->scheduled_at->translatedFormat('j F Y в H:i') }}.
            @else
                Проверьте подписи страниц и посмотрите меню в предпросмотре, затем опубликуйте.
            @endif
        @elseif ($menu->status === MenuStatus::Archived)
            <strong>В архиве.</strong> Гости его не видят; можно опубликовать снова.
        @else
            <strong>Черновик.</strong>
        @endif
    </x-filament::section>
</div>
