{{--
    Плашка «Сейчас действует». Правила (дни, часы, праздники) уходят в атрибут data-promos,
    чтобы скрипт promos-now.js пересчитывал её без сервера. Акций в ближайшую неделю нет — плашка скрыта.
--}}
@props(['class' => ''])
@inject('schedule', 'App\Services\PromoSchedule')
@php
    $rules = $schedule->rules();
    $items = $schedule->items($rules);
@endphp
<aside class="promo-now{{ $class ? ' '.$class : '' }}" aria-label="Акции сегодня" data-promos="{{ json_encode($rules, JSON_UNESCAPED_UNICODE) }}"{{ $items ? '' : ' hidden' }}><ul class="promo-now__list">@foreach ($items as $item)<li class="promo-now__item is-{{ $item['kind'] }}"><a class="promo-now__link" href="/promos#{{ $item['id'] }}"><span class="promo-now__when">{{ $item['label'] }}</span> <span class="promo-now__title">{{ $item['title'] }}</span> <span class="promo-now__what">{{ $item['short'] }}</span></a></li>@endforeach</ul></aside>
