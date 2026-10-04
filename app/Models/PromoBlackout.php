<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// День без акций с «не действует в праздники»: концерт, перенос выходных
#[Fillable(['date', 'reason'])]
class PromoBlackout extends Model
{
    use LogsChanges;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
