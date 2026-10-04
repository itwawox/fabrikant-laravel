{{-- Страница «не найдено»: с шапкой и подвалом сайта и с дорогой туда, куда чаще всего идут, — вместо голой ошибки --}}
<x-layouts.site
    title="Страница не найдена | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь"
    heading="Страница не найдена"
    crumb="Ошибка 404"
    hero="hall"
    :css="['/assets/css/gazette.css']"
    :canonical="false"
>
    <x-slot:head>
    {{-- Поисковикам эту страницу в выдачу не брать --}}
    <meta name="robots" content="noindex">
    </x-slot:head>
    <section class="gazette">
        <div class="gazette__sheet">
            <p class="gazette__running">Ошибка 404 &bull; Ресторан с собственной пивоварней &bull; Симферополь</p>
            <h2 class="gazette__title">Такой страницы у нас нет</h2>
            <hr class="gz-rule">
            <p class="gazette__lost">Возможно, ссылка устарела или в адресе опечатка. Зато меню, зал и пивоварня на месте.</p>
            <div class="contact__actions">
                <a class="gz-plaque contact__action" href="/menu">Меню ресторана</a>
                <a class="gz-plaque contact__action" href="/">На главную</a>
                <a class="gz-plaque contact__action" href="{{ $site->tel() }}">Позвонить</a>
            </div>
        </div>
    </section>

</x-layouts.site>
