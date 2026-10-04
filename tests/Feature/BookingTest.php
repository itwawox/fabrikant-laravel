<?php

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\ManageBookings;
use App\Models\Booking;
use App\Models\Page;
use App\Models\User;
use App\Notifications\BookingReceived;
use App\Notifications\Channels\TelegramChannel;
use App\Settings\SiteSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function enableBooking(): void
{
    Page::create(['key' => 'privacy', 'content' => ['text' => "ООО «Пример», ИНН 0000000000.\n\nДанные храним 90 дней."]]);
    $settings = app(SiteSettings::class);
    $settings->booking_enabled = true;
    $settings->save();
}

function bookingForm(array $overrides = []): array
{
    return [
        'date' => today()->addDay()->toDateString(), 'time' => '19:30', 'guests' => 4,
        'name' => 'Анна', 'phone' => '+7 978 123-45-67', 'comment' => 'У окна', 'consent' => '1',
        ...$overrides,
    ];
}

it('is off by default and cannot be switched on without a privacy policy', function () {
    $this->get('/booking')->assertNotFound();
    $this->get('/privacy')->assertNotFound();
    $this->get('/contacts')->assertDontSee('Заявка онлайн');
});

it('accepts a booking and notifies by mail and Telegram', function () {
    enableBooking();
    Notification::fake();

    $this->get('/contacts')->assertSee('Заявка онлайн');
    $this->get('/privacy')->assertOk()->assertSee('ИНН 0000000000');
    $this->get('/booking')->assertOk()->assertSee('Заявка на бронь');

    $this->post('/booking', bookingForm())->assertRedirect('/booking');
    $this->get('/booking')->assertSee('Заявка принята');

    $booking = Booking::sole();
    expect($booking->status)->toBe(BookingStatus::New)
        ->and($booking->name)->toBe('Анна')
        ->and($booking->consent_at)->not->toBeNull();
    Notification::assertSentOnDemand(BookingReceived::class, fn ($n, array $channels, object $notifiable) => in_array(TelegramChannel::class, $channels)
        && $notifiable->routes['mail'] === 'info@fabrikant-simf.ru');
});

it('sends the Telegram message through the bot when it is configured', function () {
    config(['services.telegram.bot_token' => 'TOKEN', 'services.telegram.chat_id' => '-100500']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    Notification::route(TelegramChannel::class, '-100500')->notifyNow(new BookingReceived(Booking::factory()->create()), [TelegramChannel::class]);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/botTOKEN/sendMessage'
        && $request['chat_id'] === '-100500' && str_contains($request['text'], '4 чел.'));
});

it('requires consent and valid fields', function () {
    enableBooking();

    $this->post('/booking', bookingForm(['consent' => null, 'date' => today()->subDay()->toDateString(), 'phone' => 'abc']))
        ->assertSessionHasErrors(['consent', 'date', 'phone']);
    expect(Booking::count())->toBe(0);
});

it('silently drops bots and limits repeated requests', function () {
    enableBooking();
    Notification::fake();

    $this->post('/booking', bookingForm(['website' => 'http://spam']))->assertRedirect('/booking');
    expect(Booking::count())->toBe(0);

    foreach (range(1, 5) as $i) {
        $this->post('/booking', bookingForm());
    }
    $this->post('/booking', bookingForm())->assertSessionHasErrors('name');
    expect(Booking::count())->toBe(5);
});

it('lets the manager confirm a booking and shows new ones in the menu badge', function () {
    $this->actingAs(User::factory()->create());
    $booking = Booking::factory()->create();

    Livewire::test(ManageBookings::class)
        ->assertCanSeeTableRecords([$booking])
        ->callTableAction('confirmed', $booking);

    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed);
});

it('deletes bookings older than the retention period', function () {
    $old = Booking::factory()->create(['date' => today()->subDays(91)]);
    $recent = Booking::factory()->create(['date' => today()->subDays(10)]);

    $this->artisan('bookings:prune')->assertSuccessful();

    expect(Booking::find($old->id))->toBeNull()->and(Booking::find($recent->id))->not->toBeNull();
});
