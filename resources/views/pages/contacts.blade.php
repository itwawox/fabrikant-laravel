@php
    // Контакты ресторана. Часы работы — как в карточке ресторана в Яндекс Картах.
    // На этапе 6 эти данные переедут в настройки сайта в админке.
    $yandex = 'https://yandex.ru/maps/org/fabrikant/1324964934/';
    $rows = [
        'Открыто' => ['', 'ежедневно 11:00–23:00'],
        'Телефон' => ['tel:+79788072001', '+7 978 807 20 01'],
        'Почта' => ['mailto:info@fabrikant-simf.ru', 'info@fabrikant-simf.ru'],
        'PR-служба' => ['mailto:pr@fabrikant-simf.ru', 'pr@fabrikant-simf.ru'],
        'ВКонтакте' => ['https://vk.com/fabricantsimferopol', 'наша страница'],
        'Одноклассники' => ['https://ok.ru/group/54607657435147', 'наша группа'],
        'Яндекс Карты' => [$yandex, 'отзывы и фото'],
    ];
    // Маршрут в Яндекс Картах до ресторана (широта, долгота точки из карточки)
    $route = 'https://yandex.ru/maps/?rtext=~44.957436%2C34.109319&amp;rtt=auto';
@endphp
{{-- Главное фото стоит прямо в разметке ниже, предзагрузка фона шапки не нужна --}}
<x-layouts.site
    title="Контакты | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь"
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
            <div class="contact__card">
                <ol class="contact__crumbs">
                    <li><a href="/">Главная</a></li>
                    <li>Контакты</li>
                </ol>
                <h1 class="contact__title">Ждём вас в гости</h1>
                <address class="contact__address">Симферополь, улица Киевская, 54</address>
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
                </div>
                <p class="contact__note">Вопрос управляющему, отзыв или пожелание — напишите нам на почту.</p>
            </div>

            <h2 class="contact__map-title">Как нас найти</h2>
            <hr class="gz-rule">
            <div class="contact__map">
                <iframe src="https://yandex.ru/map-widget/v1/org/fabrikant/1324964934/?ll=34.109319%2C44.957436&amp;z=16" loading="lazy" title="Карта проезда к ресторану ФабрикантЪ"></iframe>
            </div>
        </div>
    </section>

</x-layouts.site>
