<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReceived;
use App\Notifications\Channels\TelegramChannel;
use App\Settings\SiteSettings;
use App\Support\PageTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Онлайн-заявка на бронь. Включается в «Настройках сайта», и только когда вписана политика обработки
 * персональных данных: без неё нельзя просить согласие.
 */
class BookingController extends Controller
{
    public function __construct(private readonly SiteSettings $settings) {}

    public static function available(SiteSettings $settings): bool
    {
        // Без политики и отдельного текста согласия (152-ФЗ, ст. 9 и 18.1) принимать заявки нельзя
        $texts = PageTexts::for('privacy');

        return $settings->booking_enabled && $texts->get('text') && $texts->get('consent');
    }

    public function show(): View
    {
        abort_unless(self::available($this->settings), 404);

        return view('pages.booking');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(self::available($this->settings), 404);

        // Ловушка для ботов: поле спрятано от людей, бот его заполняет. Делаем вид, что всё хорошо
        if (filled($request->input('website'))) {
            return redirect()->route('booking')->with('booked', true);
        }

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:+60 days'],
            'time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:60'],
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^[\d\s()+-]{10,30}$/'],
            'comment' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
        ], [
            'date.after_or_equal' => 'Дата уже прошла.',
            'date.before_or_equal' => 'Бронь — не дальше чем на два месяца вперёд.',
            'phone.regex' => 'Проверьте номер телефона.',
            'consent.accepted' => 'Нужно согласие на обработку персональных данных — иначе мы не сможем перезвонить.',
        ], [
            'date' => 'дата', 'time' => 'время', 'guests' => 'гостей', 'name' => 'имя', 'phone' => 'телефон', 'comment' => 'комментарий',
        ]);

        // Не больше 5 заявок в час с одного адреса — защита от спама
        $key = 'booking:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->withErrors(['name' => 'Слишком много заявок подряд. Позвоните нам, пожалуйста.']);
        }
        RateLimiter::hit($key, 3600);

        $booking = Booking::create([
            ...array_diff_key($data, ['consent' => true]),
            'status' => BookingStatus::New,
            'source' => 'site',
            'consent_at' => now(),
        ]);

        // Уведомления — сразу после ответа гостю, не дожидаясь очереди (на хостинге она идёт раз в минуту)
        $settings = $this->settings;
        dispatch(fn () => Notification::route('mail', $settings->booking_email ?: $settings->email)
            ->route(TelegramChannel::class, config('services.telegram.chat_id'))
            ->notifyNow(new BookingReceived($booking)))->afterResponse();

        return redirect()->route('booking')->with('booked', true);
    }

    public function privacy(): View
    {
        $text = PageTexts::for('privacy')->get('text');
        abort_unless((bool) $text, 404);

        return view('pages.privacy', ['text' => $text, 'heading' => 'Политика обработки персональных данных']);
    }

    /** Согласие на обработку персональных данных — отдельный документ, на него ведёт галочка в форме брони */
    public function consent(): View
    {
        $text = PageTexts::for('privacy')->get('consent');
        abort_unless((bool) $text, 404);

        return view('pages.privacy', ['text' => $text, 'heading' => 'Согласие на обработку персональных данных']);
    }
}
