<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Database\Factories\MenuPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['menu_id', 'number', 'title', 'width', 'height'])]
class MenuPage extends Model
{
    use LogsChanges;

    /** @var list<string> служебные поля — в журнал не пишем */
    protected array $logIgnore = ['width', 'height'];

    /** @use HasFactory<MenuPageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
