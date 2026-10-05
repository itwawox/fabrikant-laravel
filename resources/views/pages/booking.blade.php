@php
    // Время брони — каждые полчаса в часы работы, последняя бронь за час до закрытия
    $slots = [];
    $from = \Carbon\CarbonImmutable::createFromFormat('H:i', $site->opensAt());
    $to = \Carbon\CarbonImmutable::createFromFormat('H:i', $site->closesAt())->subHour();
    for ($t = $from; $t <= $to; $t = $t->addMinutes(30)) {
        $slots[] = $t->format('H:i');
    }
@endphp
<x-layouts.site
    title="Забронировать стол | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь"
    description="Онлайн-заявка на бронь стола в ресторане-пивоварне ФабрикантЪ в Симферополе: мы перезвоним и подтвердим."
    heading="Бронь стола"
    hero="hall"
    :css="['/assets/css/gazette.css', '/assets/css/booking.css']"
>
    <section class="gazette">
        <div class="gazette__sheet">
            <h2 class="gazette__title">Заявка на бронь</h2>
            <hr class="gz-rule">
            <p class="booking__lede">Оставьте заявку — мы перезвоним и подтвердим бронь. Быстрее всего — по телефону <a href="{{ $site->tel() }}">{{ $site->phone() }}</a>.</p>

@if (session('booked'))
            <div class="booking__done" role="status">
                <strong>Заявка принята</strong>
                Мы перезвоним в ближайшее время, чтобы подтвердить бронь. Если что-то срочно — звоните: <a href="{{ $site->tel() }}">{{ $site->phone() }}</a>.
            </div>
@else
            <form class="booking__form" method="post" action="{{ route('booking.store') }}" novalidate>
                @csrf
                <div class="booking__field">
                    <label for="b-date">Дата</label>
                    <input id="b-date" type="date" name="date" required value="{{ old('date', today()->toDateString()) }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(60)->toDateString() }}">
                    @error('date')<p class="booking__error">{{ $message }}</p>@enderror
                </div>
                <div class="booking__field">
                    <label for="b-time">Время</label>
                    <select id="b-time" name="time" required>
@foreach ($slots as $slot)
                        <option value="{{ $slot }}" @selected(old('time', '19:00') === $slot)>{{ $slot }}</option>
@endforeach
                    </select>
                    @error('time')<p class="booking__error">{{ $message }}</p>@enderror
                </div>
                <div class="booking__field">
                    <label for="b-guests">Гостей</label>
                    <input id="b-guests" type="number" name="guests" required min="1" max="60" value="{{ old('guests', 2) }}" inputmode="numeric">
                    @error('guests')<p class="booking__error">{{ $message }}</p>@enderror
                </div>
                <div class="booking__field">
                    <label for="b-name">Имя</label>
                    <input id="b-name" type="text" name="name" required maxlength="80" autocomplete="name" value="{{ old('name') }}">
                    @error('name')<p class="booking__error">{{ $message }}</p>@enderror
                </div>
                <div class="booking__field">
                    <label for="b-phone">Телефон</label>
                    <input id="b-phone" type="tel" name="phone" required autocomplete="tel" placeholder="+7 978 …" value="{{ old('phone') }}">
                    @error('phone')<p class="booking__error">{{ $message }}</p>@enderror
                </div>
                <div class="booking__field booking__field--wide">
                    <label for="b-comment">Комментарий</label>
                    <textarea id="b-comment" name="comment" rows="3" maxlength="500" placeholder="Летний сад, день рождения, детский стул…">{{ old('comment') }}</textarea>
                </div>
                {{-- Ловушка для ботов: людям поле не видно --}}
                <div class="booking__trap" aria-hidden="true"><label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <label class="booking__consent">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                    <span>Даю <a href="{{ route('consent') }}" target="_blank">согласие на обработку персональных данных</a> — имя и телефон нужны, чтобы подтвердить бронь. С <a href="{{ route('privacy') }}" target="_blank">политикой обработки персональных данных</a> ознакомлен(а).</span>
                </label>
                @error('consent')<p class="booking__error booking__field--wide">{{ $message }}</p>@enderror
                <button class="gz-plaque booking__submit" type="submit">Отправить заявку</button>
            </form>
@endif
        </div>
    </section>
</x-layouts.site>
