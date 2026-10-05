@use('App\Support\Html')
@use('App\Support\Press')
@php
    // «О ресторане» — очерк в стиле газеты-меню. Шапка, рубрики, колонтитул и приглашение в конце —
    // те же детали, что в галерее (press.css); своё у очерка — колонки текста, сорта пива,
    // плашка «Своими руками» и объявление о работе (story.css). Тексты — из админки («Страницы»).
    $texts = \App\Support\PageTexts::for('about');
    $phone = [$site->tel(), $site->phone()];
    $mail = $site->email();
    // Маршрут в Яндекс Картах до ресторана — тот же, что на странице «Контакты»
    $route = $site->routeUrl();
    $rubrics = array_filter([
        'pivovarnya' => 'Пивоварня',
        'kuhnya' => 'Кухня',
        'atmosfera' => 'Атмосфера',
        // Вакансий нет — объявление и пункт «Работа у нас» не показываем
        'rabota' => $texts->get('job.show') ? 'Работа у нас' : null,
    ]);
@endphp
{{-- Кадр зала стоит прямо в разметке ниже --}}
<x-layouts.site
    :title="$texts->title()"
    :description="$texts->description()"
    :image="$texts->image()"
    :hero="false"
    :css="['/assets/css/gazette.css', '/assets/css/press.css', '/assets/css/story.css']"
>
    <main>
    <!-- Первая полоса — как в галерее: кадр зала на тёмной плашке, заголовок «печатается» прокатом валика -->
    <section class="press-hero">
        <div class="press-hero__plate">
            <picture>
                <source media="(max-width: 767px)" srcset="/assets/img/hero/hall-m.webp" type="image/webp" width="900" height="600">
                <img src="/assets/img/hero/hall.webp" width="1920" height="735" alt="Зал ресторана ФабрикантЪ: кирпичные стены, абажуры над столами, медная пивоварня" fetchpriority="high">
            </picture>
        </div>
        <h1 class="press-hero__title">О ресторане</h1>
        <div class="press-hero__deck">
            <nav aria-label="Вы здесь">
                <ol class="press-hero__crumbs">
                    <li><a href="/">Главная</a></li>
                    <li aria-current="page">О ресторане</li>
                </ol>
            </nav>
            <p class="press-hero__lede">{{ $texts->get('lede') }}</p>
        </div>
        <p class="press-hero__running">Ресторан-пивоварня &bull; {{ $site->city() }}, {{ $site->address() }} &bull; Каждый день с {{ $site->opensAt() }} до {{ $site->closesAt() }}</p>
    </section>

    <section class="gazette press story">
        <div class="gazette__sheet">
            <nav class="gazette__running press__contents" aria-label="Рубрики">
                <div class="press__contents-list">
                    <span>В номере:</span>
@foreach ($rubrics as $rubricId => $rubricTitle)
                    {!! $loop->first ? '' : '<span aria-hidden="true">&bull;</span> ' !!}<a href="#rubric-{{ $rubricId }}">{{ $rubricTitle }}</a>
@endforeach
                </div>
            </nav>

            <!-- Пивоварня -->
            <section class="rubric" id="rubric-pivovarnya" aria-labelledby="rubric-pivovarnya-title">
                <div class="rubric__head">
                    <span class="gz-rule" aria-hidden="true"></span>
                    <h2 class="rubric__title" id="rubric-pivovarnya-title">Пивоварня</h2>
                    <span class="gz-rule" aria-hidden="true"></span>
                </div>
                <p class="rubric__lede">{{ $texts->get('brewery.lede') }}</p>
                <div class="story__spread">
                    <div class="story__text story__text--lead">
@foreach ($texts->list('brewery.text') as $paragraph)
                        <p{!! $loop->first ? ' class="story__first"' : '' !!}>{{ $paragraph }}</p>
@endforeach
                    </div>
                    <aside class="story__side" aria-label="Сорта собственной пивоварни">
                        <figure class="story-photo">
                            <span class="story-photo__frame">{!! Html::picture('/assets/img/info_img2.webp', '/assets/img/info_img2.jpg', 'width="640" height="349" alt="Барная стойка с пивными кранами и схемой пивоварения на стене" loading="lazy" decoding="async"') !!}</span>
                        </figure>
                        <div class="story-beers">
                            <p class="story-beers__title">{{ $texts->get('brewery.beers_title') }}</p>
                            <dl class="gz-rows">
@foreach ($texts->list('brewery.beers') as $beer)
                                <div class="gz-row"><dt>{{ $beer['name'] }}</dt><dd>{{ $beer['note'] }}</dd></div>
@endforeach
                            </dl>
                            <p class="story-beers__note">{{ $texts->get('brewery.beers_note') }}</p>
                        </div>
                    </aside>
                </div>
            </section>

            <!-- Кухня -->
            <section class="rubric" id="rubric-kuhnya" aria-labelledby="rubric-kuhnya-title">
                <div class="rubric__head">
                    <span class="gz-rule" aria-hidden="true"></span>
                    <h2 class="rubric__title" id="rubric-kuhnya-title">Кухня</h2>
                    <span class="gz-rule" aria-hidden="true"></span>
                </div>
                <p class="rubric__lede">{{ $texts->get('kitchen.lede') }}</p>
                <div class="story__spread story__spread--flip">
                    <div class="story__text story__text--cols">
@foreach ($texts->list('kitchen.text') as $paragraph)
                        <p{!! $loop->first ? ' class="story__first"' : '' !!}>{{ $paragraph }}</p>
@endforeach
                    </div>
                    <aside class="story__side">
@if ($kare)
                        <figure class="story-photo">
                            <span class="story-photo__frame">{!! Press::picture($kare, 'Каре ягнёнка с картофелем и печёным перцем', '(min-width: 1248px) 482px, (min-width: 700px) 40vw, 100vw') !!}</span>
                            <figcaption class="story-photo__caption">Каре ягнёнка с картофелем и печёным перцем.</figcaption>
                        </figure>
@endif
                        <!-- Плашка, как «Метр пива» в меню: что в ресторане делают сами -->
                        <div class="story-plaque orn-curls">
                            <p class="story-plaque__title">{{ $texts->get('kitchen.plaque_title') }}</p>
                            <ul class="story-plaque__list">
@foreach ($texts->list('kitchen.plaque') as $item)
                                <li>{{ $item }}</li>
@endforeach
                            </ul>
                        </div>
                    </aside>
                </div>
                <p class="story__more"><a href="/menu">Открыть меню ресторана</a></p>
            </section>

            <!-- Атмосфера -->
            <section class="rubric" id="rubric-atmosfera" aria-labelledby="rubric-atmosfera-title">
                <div class="rubric__head">
                    <span class="gz-rule" aria-hidden="true"></span>
                    <h2 class="rubric__title" id="rubric-atmosfera-title">Атмосфера</h2>
                    <span class="gz-rule" aria-hidden="true"></span>
                </div>
                <p class="rubric__lede">{{ $texts->get('atmosphere.lede') }}</p>
                <div class="story__pair">
                    <figure class="story-photo">
                        <span class="story-photo__frame">{!! Html::picture('/assets/img/info_img.webp', '/assets/img/info_img.jpg', 'width="640" height="349" alt="Фасад ресторана вечером: светящаяся вывеска ФабрикантЪ над входом" loading="lazy" decoding="async"') !!}</span>
                        <figcaption class="story-photo__caption">Вход с улицы Киевской.</figcaption>
                    </figure>
                    <figure class="story-photo">
                        <span class="story-photo__frame">{!! Html::picture('/assets/img/info_img3.webp', '/assets/img/info_img3.jpg', 'width="640" height="349" alt="Отдельный зал: сервированный стол, обои с узором и старые фотографии на стенах" loading="lazy" decoding="async"') !!}</span>
                        <figcaption class="story-photo__caption">Малый зал для компании.</figcaption>
                    </figure>
                </div>
                <div class="story__text story__text--narrow">
@foreach ($texts->list('atmosphere.text') as $paragraph)
                    <p{!! $loop->first ? ' class="story__first"' : '' !!}>{{ $paragraph }}</p>
@endforeach
                </div>
                <div class="story__welcome">
                    <p class="story__welcome-text">{{ $texts->get('atmosphere.welcome') }}</p>
                    {!! Html::svg('logo-full', 'story__logo', 'ФабрикантЪ — ресторан с собственной пивоварней') !!}
                </div>
            </section>

@if ($texts->get('job.show'))
            <!-- Работа у нас: газетное объявление -->
            <section class="rubric" id="rubric-rabota" aria-labelledby="rubric-rabota-title">
                <div class="story-ad orn-scroll">
                    <h2 class="story-ad__title orn-crown" id="rubric-rabota-title">{{ $texts->get('job.title') }}</h2>
                    <p class="story-ad__lede">{{ $texts->get('job.lede') }}</p>
                    <ul class="story-ad__list">
@foreach ($texts->list('job.items') as $item)
                        <li>{{ $item }}</li>
@endforeach
                    </ul>
                    <p class="story-ad__contact">Резюме присылайте на <a href="mailto:{{ $mail }}?subject={{ rawurlencode('Резюме') }}" data-goal="about_job_mail">{{ $mail }}</a> или звоните: <a href="{{ $phone[0] }}" data-goal="about_job_call">{{ $phone[1] }}</a>.</p>
                </div>
            </section>
@endif

            <!-- Чем заканчивается очерк: приходите -->
            <aside class="press-cta orn-corners" aria-labelledby="press-cta-title">
                <h2 class="press-cta__title" id="press-cta-title">Ждём вас в гости</h2>
                <p class="press-cta__text">{{ $site->city() }}, {{ $site->settings->street }},&nbsp;{{ $site->settings->house }}. Открыто каждый день с&nbsp;{{ $site->opensAt() }} до&nbsp;{{ $site->closesAt() }}.</p>
                <div class="press-cta__actions">
                    <a class="press-cta__action press-cta__action--main" href="{{ $phone[0] }}" data-goal="about_call">
                        <span>Забронировать стол</span>
                        <span class="press-cta__note">{{ $phone[1] }}</span>
                    </a>
                    <a class="press-cta__action" href="/gallery" data-goal="about_gallery">
                        <span>Смотреть галерею</span>
                        <span class="press-cta__note">зал, сад, пивоварня</span>
                    </a>
                </div>
                <p class="press-cta__route"><a href="{!! $route !!}" target="_blank" rel="noopener" data-goal="about_route">Проложить маршрут в Яндекс Картах</a></p>
            </aside>

        </div>
    </section>
    </main>

{!! Html::moduleTags('/assets/js/story/main.js', ['/assets/js/story', '/assets/js/gallery']) !!}

</x-layouts.site>
