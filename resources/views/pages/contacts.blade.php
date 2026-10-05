@php
    // Контакты ресторана — из настроек сайта в админке. Часы работы — как в карточке ресторана в Яндекс Картах.
    $rows = array_filter([
        'Открыто' => ['', 'ежедневно '.$site->opensAt().'–'.$site->closesAt()],
        'Телефон' => [$site->tel(), $site->phone()],
        'Почта' => ['mailto:'.$site->email(), $site->email()],
        'PR-служба' => $site->prEmail() ? ['mailto:'.$site->prEmail(), $site->prEmail()] : null,
        'ВКонтакте' => $site->vkUrl() ? [$site->vkUrl(), 'наша страница'] : null,
        'Одноклассники' => $site->okUrl() ? [$site->okUrl(), 'наша группа'] : null,
        'Яндекс Карты' => $site->yandexMapsUrl() ? [$site->yandexMapsUrl(), 'отзывы и фото'] : null,
    ]);
    // Маршрут в Яндекс Картах до ресторана (широта, долгота точки из настроек)
    $route = $site->routeUrl();
    $texts = \App\Support\PageTexts::for('contacts');
@endphp
{{-- Главное фото стоит прямо в разметке ниже, предзагрузка фона шапки не нужна --}}
<x-layouts.site
    :title="$texts->title()"
    :description="$texts->description()"
    :image="$texts->image()"
    :hero="false"
    :css="['/assets/css/gazette.css']"
>
    <div class="contact-hero">
        <!-- На телефоне — свой кадр: вход и зонты террасы. JPEG — запасной вариант для старых браузеров -->
        <picture>
            <source type="image/webp" media="(max-width: 767px)" srcset="/assets/img/hero/terrace-m.webp">
            <source type="image/webp" srcset="/assets/img/hero/terrace.webp">
            <img src="/assets/img/hero/terrace.jpg" width="1920" height="960" alt="Вход в ресторан ФабрикантЪ и летняя терраса" fetchpriority="high">
        </picture>
    </div>

    <section class="gazette contact">
        <div class="gazette__sheet">
            <div class="contact__card orn-corners">
                <ol class="contact__crumbs">
                    <li><a href="/">Главная</a></li>
                    <li>Контакты</li>
                </ol>
                <h1 class="contact__title">{{ $texts->get('title') }}</h1>
                <address class="contact__address">{{ $site->city() }}, {{ $site->streetAddressFull() }}</address>
                <hr class="gz-rule">

                <dl class="gz-rows">
@foreach ($rows as $label => $row)
@if ($row[0] === '')
                    <div class="gz-row"><dt>{{ $label }}</dt><dd>{{ $row[1] }}</dd></div>
@else
                    <div class="gz-row"><dt>{{ $label }}</dt><dd><a href="{{ $row[0] }}"{!! str_starts_with($row[0], 'http') ? ' target="_blank" rel="noopener"' : '' !!}>{{ $row[1] }}</a></dd></div>
@endif
@endforeach
                </dl>

                <div class="contact__actions">
                    <a class="gz-plaque contact__action" href="{{ $rows['Телефон'][0] }}">Позвонить</a>
                    <a class="gz-plaque contact__action" href="{!! $route !!}" target="_blank" rel="noopener">Проложить маршрут</a>
@if (\App\Http\Controllers\BookingController::available($site->settings))
                    <a class="gz-plaque contact__action" href="{{ route('booking') }}">Заявка онлайн</a>
@endif
                </div>
                <p class="contact__note">{{ $texts->get('note') }}</p>
            </div>

            <h2 class="contact__map-title">Как нас найти</h2>
            <hr class="gz-rule">
            <div class="contact__map">
@if ($site->mapWidgetUrl())
                <iframe src="{!! $site->mapWidgetUrl() !!}" loading="lazy" title="Карта проезда к ресторану ФабрикантЪ"></iframe>
@endif
            </div>
        </div>
    </section>

</x-layouts.site>
