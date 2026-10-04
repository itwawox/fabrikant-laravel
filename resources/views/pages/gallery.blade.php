@use('App\Support\Html')
@use('App\Support\Press')
@php
    $phone = [$site->tel(), $site->phone()];
    $texts = \App\Support\PageTexts::for('gallery');
    // Маршрут в Яндекс Картах до ресторана — тот же, что на странице «Контакты»
    $route = $site->routeUrl();
@endphp
{{-- Галерея «Фотохроника» — иллюстрированное приложение к газете-меню. Фото, рубрики и подписи — из базы.
     Кадр зала стоит прямо в разметке, фоновая шапка не нужна --}}
<x-layouts.site
    :title="$texts->title()"
    :description="$texts->description()"
    :image="$texts->image()"
    :hero="false"
    :css="['/assets/css/gazette.css', '/assets/css/press.css']"
>
    <main>
    <!-- Первая полоса: тёмная плашка под прозрачной шапкой сайта. Скрипт печатает кадр растром и проявляет его в цвет -->
    <section class="press-hero">
        <div class="press-hero__plate" data-press="hero">
            <picture>
                <source media="(max-width: 767px)" srcset="/assets/img/hero/stage-m.webp" type="image/webp" width="900" height="600">
                <img src="/assets/img/hero/stage.webp" width="1920" height="683" alt="Большой зал ресторана ФабрикантЪ: столы под жёлтыми абажурами, кирпичные стены" fetchpriority="high">
            </picture>
        </div>
        <h1 class="press-hero__title">Галерея</h1>
        <div class="press-hero__deck">
            <nav aria-label="Вы здесь">
                <ol class="press-hero__crumbs">
                    <li><a href="/">Главная</a></li>
                    <li aria-current="page">Галерея</li>
                </ol>
            </nav>
            <p class="press-hero__lede">Островок уюта и комфорта в самом центре шумного города. Здесь все настоящее: пиво из собственной пивоварни, вкуснейшее угощение, стильный интерьер.</p>
        </div>
        <p class="press-hero__running">Фотохроника &bull; Ресторан с собственной пивоварней &bull; Симферополь</p>
    </section>

    <section class="gazette press">
        <div class="gazette__sheet">
            <!-- Колонтитул с рубриками прилипает под шапкой сайта: отмечает рубрику на экране (gallery/contents.js),
                 а линейка под ним показывает, сколько номера уже пролистано -->
            <nav class="gazette__running press__contents" aria-label="Рубрики">
                <div class="press__contents-list">
                    <span>В номере:</span>
@foreach ($rubrics as $rubricId => $rubric)
                    {!! $loop->first ? '' : '<span aria-hidden="true">&bull;</span> ' !!}<a href="#rubric-{{ $rubricId }}">{{ $rubric['title'] }}</a>
@endforeach
                </div>
            </nav>

@php $first = true; @endphp
@foreach ($rubrics as $rubricId => $rubric)
            <section class="rubric" id="rubric-{{ $rubricId }}" aria-labelledby="rubric-{{ $rubricId }}-title">
                <div class="rubric__head">
                    <span class="gz-rule" aria-hidden="true"></span>
                    <h2 class="rubric__title" id="rubric-{{ $rubricId }}-title">{{ $rubric['title'] }}</h2>
                    <span class="gz-rule" aria-hidden="true"></span>
                </div>
                <p class="rubric__lede">{{ $rubric['lede'] }}</p>
                <div class="rubric__grid">
@foreach ($rubric['columns'] as $column)
                    <div class="rubric__col rubric__col--{{ $column['kind'] }}">
@foreach ($column['plates'] as $plate)
                        {!! Press::plate($plate, ! $first) !!}

@php $first = false; @endphp
@endforeach
                    </div>
@endforeach
                </div>
            </section>

@endforeach
            <!-- Чем заканчивается номер: посмотрели фото — приходите -->
            <aside class="press-cta" aria-labelledby="press-cta-title">
                <h2 class="press-cta__title" id="press-cta-title">Приходите посмотреть вживую</h2>
                <p class="press-cta__text">{{ $site->city() }}, {{ $site->settings->street }},&nbsp;{{ $site->settings->house }}. Открыто каждый день с&nbsp;{{ $site->opensAt() }} до&nbsp;{{ $site->closesAt() }}.</p>
                <div class="press-cta__actions">
                    <a class="press-cta__action press-cta__action--main" href="{{ $phone[0] }}" data-goal="gallery_call">
                        <span>Забронировать стол</span>
                        <span class="press-cta__note">{{ $phone[1] }}</span>
                    </a>
                    <a class="press-cta__action" href="/menu" data-goal="gallery_menu">
                        <span>Открыть меню</span>
                        <span class="press-cta__note">пиво, кухня, вино</span>
                    </a>
                </div>
                <p class="press-cta__route"><a href="{!! $route !!}" target="_blank" rel="noopener" data-goal="gallery_route">Проложить маршрут в Яндекс Картах</a></p>
            </aside>

            <p class="gazette__strip">
                <span>{{ $site->city() }}, {{ $site->streetAddress() }}.</span>
                <span>тел. <a href="{{ $phone[0] }}">{{ $phone[1] }}</a></span>
                <span><a href="/menu">Меню ресторана</a></span>
            </p>
        </div>
    </section>
    </main>

    <!-- Окно просмотра. Слайды уже в разметке: скрипт только открывает окно и листает ленту -->
    <!-- Окно просмотра. Слайды уже в разметке: скрипт только открывает окно и листает ленту.
         Сверху счётчик и кнопки, снизу лента миниатюр — по ней видно, сколько фото и где ты сейчас -->
    <dialog class="viewer" aria-label="Просмотр фотографий">
        <div class="viewer__top">
            <p class="viewer__count" aria-live="polite"><span class="viewer__current">1</span> / {{ count($shots) }}</p>
            <button type="button" class="viewer__btn viewer__btn--share" aria-label="Поделиться фотографией" title="Поделиться фотографией" hidden>
                <svg class="viewer__glyph" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 15V4M7.5 8.5 12 4l4.5 4.5M6 12H5v8h14v-8h-1"/></svg>
            </button>
            <button type="button" class="viewer__btn viewer__btn--close" aria-label="Закрыть" title="Закрыть" autofocus>{!! Html::icon('close') !!}</button>
        </div>
        <div class="viewer__track">
@foreach ($shots as $plate)
            {!! Press::shot($plate) !!}

@endforeach
        </div>
        <button type="button" class="viewer__btn viewer__btn--prev" aria-label="Предыдущее фото" title="Предыдущее фото">{!! Html::icon('chevron-left') !!}</button>
        <button type="button" class="viewer__btn viewer__btn--next" aria-label="Следующее фото" title="Следующее фото">{!! Html::icon('chevron-right') !!}</button>
        <div class="viewer__strip" role="group" aria-label="Все фотографии">
@foreach ($shots as $i => $plate)
            {!! Press::thumb($plate, $i + 1) !!}

@endforeach
        </div>
        <p class="viewer__toast" role="status"></p>
    </dialog>

    <!-- Со страницы галереи чаще всего идут в меню — подгружаем его заранее, когда курсор задержался на ссылке -->
    <script type="speculationrules">{"prefetch":[{"urls":["/menu"],"eagerness":"moderate"}]}</script>
{!! Html::moduleTags('/assets/js/gallery/main.js', array('/assets/js/gallery', '/assets/js/lib'), array('preload' => array('/assets/js/lib/env.js', '/assets/js/gallery/viewer.js', '/assets/js/gallery/route.js', '/assets/js/gallery/contents.js'))) !!}

</x-layouts.site>
