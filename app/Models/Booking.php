<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

#[Fillable(['date', 'time', 'guests', 'name', 'phone', 'comment', 'status', 'source', 'consent_at'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    use Notifiable;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'guests' => 'integer',
            'status' => BookingStatus::class,
            'consent_at' => 'datetime',
        ];
    }

    /** «пт, 9 октября, 19:30» */
    public function when(): string
    {
        return $this->date->translatedFormat('D, j F').', '.$this->time;
    }
}
