<?php

namespace App\Notifications;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Новая заявка на бронь — в Telegram ресторана и на почту. */
class BookingReceived extends Notification
{
    public function __construct(public Booking $booking) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', TelegramChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;

        return (new MailMessage)
            ->subject("Бронь: {$b->when()}, {$b->guests} чел., {$b->name}")
            ->greeting('Новая заявка на бронь с сайта')
            ->line("Когда: {$b->when()}")
            ->line("Гостей: {$b->guests}")
            ->line("Имя: {$b->name}")
            ->line("Телефон: {$b->phone}")
            ->lineIf((bool) $b->comment, "Комментарий: {$b->comment}")
            ->action('Открыть в админке', BookingResource::getUrl('index'))
            ->salutation('Перезвоните гостю, чтобы подтвердить бронь.');
    }

    /**
     * В Telegram — без имени, телефона и комментария: серверы мессенджера за рубежом, а это была бы
     * трансграничная передача персональных данных (152-ФЗ, ст. 12). Кто и как связаться — в админке и в письме.
     */
    public function toTelegram(object $notifiable): string
    {
        $b = $this->booking;

        return "🍺 Бронь с сайта\n{$b->when()} · {$b->guests} чел."
            ."\n\nИмя и телефон гостя — в заявке: ".BookingResource::getUrl('index');
    }
}
