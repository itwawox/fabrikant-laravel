<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Журнал изменений (ТЗ 5.7): кто, когда и что поменял. Пишем только изменившиеся поля; служебные
 * (прогресс обработки, копии картинок, пароль) в журнал не попадают — см. $logIgnore в модели.
 */
trait LogsChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $ignore = property_exists($this, 'logIgnore') ? $this->logIgnore : [];

        return LogOptions::defaults()
            ->logFillable()
            ->logExcept($ignore)
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly([...$ignore, 'updated_at']);
    }
}
