<x-layouts.site
    title="Политика обработки персональных данных | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь"
    heading="Политика конфиденциальности"
    hero="hall"
    :css="['/assets/css/gazette.css', '/assets/css/booking.css']"
>
    <section class="gazette">
        <div class="gazette__sheet privacy__text">
@foreach (preg_split('/\R{2,}/', trim($text)) as $paragraph)
            <p>{!! nl2br(e($paragraph)) !!}</p>
@endforeach
        </div>
    </section>
</x-layouts.site>
