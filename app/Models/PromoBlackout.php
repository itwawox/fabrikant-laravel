<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// День без акций с «не действует в праздники»: концерт, перенос выходных
#[Fillable(['date', 'reason'])]
class PromoBlackout extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
