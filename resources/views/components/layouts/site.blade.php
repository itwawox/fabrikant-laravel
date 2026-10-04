{{--
    Каркас страницы сайта: <head>, стили, навигация, шапка с заголовком (если задан heading), подвал, скрипты, счётчик.
    Перенесено из старого сайта (inc/_head.php, _navbar.php, _footer.php) с тем же HTML.
      title       — заголовок вкладки
      heading     — заголовок в шапке (на главной не задаётся); crumb — последняя «крошка», по умолчанию heading
      hero        — фон шапки: hall или stage (файлы в public/assets/img/hero), он же предзагружается;
                    false — если шапки с фоном на странице нет
      css, js     — дополнительные стили и скрипты страницы (массивы путей)
      description — описание для поисковиков и превью ссылки; image — картинка превью (путь от корня сайта)
      head        — слот: готовая разметка в конец <head> (JSON-LD, robots)
      canonical   — false, чтобы не ставить канонический адрес (страница 404)
--}}
@props([
    'title',
    'heading' => null,
    'crumb' => null,
    'hero' => 'home-poster',
    'css' => [],
    'js' => [],
    'description' => null,
    'image' => null,
    'canonical' => true,
    'head' => null,
])
@use('App\Support\Html')
@php
    $heroMobile = $hero === 'home-poster' ? $hero : $hero.'-m';
    // Шапка сайта. Каждая страница — один пункт, без вложенных списков: так все разделы видны сразу
    // и ни один не встречается дважды. Пункт текущей страницы отмечен (aria-current), стили подчёркивают его.
    $navItems = [
        'about' => 'О ресторане',
        'menu' => 'Меню',
        'gallery' => 'Галерея',
        'promos' => 'Акции',
        'contacts' => 'Контакты',
    ];
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ФабрикантЪ">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
@endif
@if ($image)
    <meta property="og:image" content="{{ url($image) }}">
@endif
@if ($canonical)
    <link rel="canonical" href="{{ url()->current() }}">
@endif
    <link rel="icon" href="/assets/favicon/favicon.png">
    <link rel="preload" href="/assets/fonts/arkhive.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/afisha.woff2" as="font" type="font/woff2" crossorigin>
@if ($hero)
    <link rel="preload" href="/assets/img/hero/{{ $heroMobile }}.webp" as="image" type="image/webp" media="(max-width: 767px)">
    <link rel="preload" href="/assets/img/hero/{{ $hero }}.webp" as="image" type="image/webp" media="(min-width: 768px)">
@endif
    <link rel="stylesheet" href="{{ Html::asset('/assets/css/site.css') }}">
@foreach ($css as $file)
    <link rel="stylesheet" href="{{ Html::asset($file) }}">
@endforeach
{{ $head }}
</head>
<body>
    <nav class="navbar navbar-default navbar-fixed-top" aria-label="Разделы сайта">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" aria-controls="navbar__collapse" aria-expanded="false">
                    <span class="sr-only">Меню</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">Фабрикантъ</a>
            </div>
            <div class="collapse navbar-collapse" id="navbar__collapse">
                <ul class="nav navbar-nav navbar-right">
@foreach ($navItems as $navRoute => $navTitle)
                    <li><a href="{{ route($navRoute, absolute: false) }}"@if (request()->routeIs($navRoute)) aria-current="page"@endif>{{ $navTitle }}</a></li>
@endforeach
                </ul>
            </div>
        </div>
    </nav>
@if ($heading)
    <section class="section section_header">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <h1 class="section__heading section_header__heading text-center">{{ $heading }}</h1>
                    <ol class="breadcrumb">
                        <li><a href="/">Главная</a></li>
                        <li class="active">{{ $crumb ?? $heading }}</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="section_header__bg hero_{{ $hero }}"></div>
    </section>
@endif
{{ $slot }}
    <footer class="section_footer">
        <div class="container">
            <div class="row">
                <div class="col-sm-8">
                    <p class="footer_info">&#169; {{ date('Y') }} Ресторан &quot;ФабрикантЪ&quot; Симферополь.</p>
                </div>
                <div class="col-sm-4">
                    <div class="soc-box">
                        <a target="_blank" rel="noopener" href="https://vk.com/fabricantsimferopol" title="Ресторан ФабрикантЪ Вконтакте">{!! Html::icon('vk') !!}</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <a href="#" id="back-to-top" aria-label="Наверх">{!! Html::icon('chevron-up') !!}</a>

    <script src="{{ Html::asset('/assets/js/site.js') }}" defer></script>
@foreach ($js as $file)
    <script src="{{ Html::asset($file) }}" defer></script>
@endforeach

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" >
        (function (d, w, c) {
            (w[c] = w[c] || []).push(function() {
                try {
                    w.yaCounter26918373 = new Ya.Metrika({
                        id:26918373,
                        clickmap:true,
                        trackLinks:true,
                        accurateTrackBounce:true,
                        webvisor:true
                    });
                } catch(e) { }
            });

            var n = d.getElementsByTagName("script")[0],
                s = d.createElement("script"),
                f = function () { n.parentNode.insertBefore(s, n); };
            s.type = "text/javascript";
            s.async = true;
            s.src = "https://mc.yandex.ru/metrika/watch.js";

            if (w.opera == "[object Opera]") {
                d.addEventListener("DOMContentLoaded", f, false);
            } else { f(); }
        })(document, window, "yandex_metrika_callbacks");
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/26918373" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
</body>
</html>
