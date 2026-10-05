@use('App\Support\Html')
{{-- Акции — из базы (админка, этап 5). Общие для всех акций условия выводятся один раз внизу страницы --}}
@php($texts = \App\Support\PageTexts::for('promos'))
<x-layouts.site
    :title="$texts->title()"
    :description="$texts->description()"
    :image="$texts->image()"
    heading="Акции в ресторане ФабрикантЪ"
    crumb="Акции"
    hero="stage"
    :css="['/assets/css/gazette.css']"
    :js="['/assets/js/promos-now.js']"
>
      <section class="gazette">
        <div class="gazette__sheet">
          <p class="gazette__running">Акции &bull; Ресторан с собственной пивоварней &bull; Симферополь</p>
          <h2 class="gazette__title">Акции и спецпредложения</h2>
          <hr class="gz-rule">

          <x-promo-bar />

          <div class="gazette__columns">
@foreach ($promos as $promo)
            <article class="promo" id="{{ $promo->slug }}">
@if ($promo->photo_path)
              <figure class="promo__photo">
                <span class="promo__photo-ink">{!! Html::picture($promo->photoUrl('webp') ?? $promo->photoUrl('jpg'), $promo->photoUrl('jpg'), 'width="800" height="600" alt="'.e($promo->alt).'" loading="lazy"') !!}</span>
              </figure>
@endif
              <h3 class="promo__title">{{ $promo->title }}</h3>
              <p class="gz-plaque promo__plaque">
                <span class="promo__discount">Скидка - {{ $promo->discount }}</span>
                <span class="promo__subject">{{ $promo->subject }}</span>
              </p>
              <dl class="gz-rows">
@foreach ($promo->rows ?? [] as $row)
                <div class="gz-row"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
@endforeach
              </dl>
              <ul class="promo__terms">
@foreach ($promo->terms ?? [] as $term)
                <li>{{ $term }}</li>
@endforeach
              </ul>
            </article>
@endforeach
          </div>

          <hr class="gz-rule">

          <div class="gazette__terms orn-scroll">
            <p>Акции не суммируются с другими акциями и скидками ресторана и действуют только на территории ресторана (на вынос не распространяются).</p>
          </div>

        </div>
      </section>

</x-layouts.site>
