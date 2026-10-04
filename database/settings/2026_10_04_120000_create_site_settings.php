<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// Значения — как были вписаны в шаблоны старого сайта
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.name', 'ФабрикантЪ');
        $this->migrator->add('site.phone', '+7 978 807 20 01');
        $this->migrator->add('site.email', 'info@fabrikant-simf.ru');
        $this->migrator->add('site.pr_email', 'pr@fabrikant-simf.ru');
        $this->migrator->add('site.city', 'Симферополь');
        $this->migrator->add('site.street', 'Киевская');
        $this->migrator->add('site.house', '54');
        $this->migrator->add('site.opens_at', '11:00');
        $this->migrator->add('site.closes_at', '23:00');
        $this->migrator->add('site.lat', 44.957436);
        $this->migrator->add('site.lng', 34.109319);
        $this->migrator->add('site.yandex_maps_url', 'https://yandex.ru/maps/org/fabrikant/1324964934/');
        $this->migrator->add('site.vk_url', 'https://vk.com/fabricantsimferopol');
        $this->migrator->add('site.ok_url', 'https://ok.ru/group/54607657435147');
        $this->migrator->add('site.telegram_url', null);
        $this->migrator->add('site.whatsapp', null);
        $this->migrator->add('site.metrika_enabled', true);
        $this->migrator->add('site.metrika_id', '26918373');
        $this->migrator->add('site.cuisines', ['Русская', 'Немецкая', 'Чешская', 'Австрийская']);
    }
};
