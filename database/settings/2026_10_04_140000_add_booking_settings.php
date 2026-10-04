<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Форма брони выключена, пока владелец не впишет политику обработки персональных данных
        $this->migrator->add('site.booking_enabled', false);
        $this->migrator->add('site.booking_email', null);
        $this->migrator->add('site.booking_retention_days', 90);
    }
};
