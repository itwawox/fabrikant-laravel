{{-- Политика обработки персональных данных и согласие на обработку — тексты из админки («Страницы и тексты»).
     Абзацы — через пустую строку; короткая строка вида «1. Общие положения» становится заголовком раздела --}}
<x-layouts.site
    :title="$heading.' | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь'"
    :heading="$heading"
    hero="hall"
    :css="['/assets/css/gazette.css', '/assets/css/booking.css']"
>
    <section class="gazette">
        <div class="gazette__sheet privacy__text">
@foreach (preg_split('/\R{2,}/', trim($text)) as $paragraph)
@if (preg_match('/^\d+\.\s\S.{0,80}$/u', trim($paragraph)))
            <h2 class="privacy__heading">{{ trim($paragraph) }}</h2>
@else
            <p>{!! nl2br(e($paragraph)) !!}</p>
@endif
@endforeach
        </div>
    </section>
</x-layouts.site>
