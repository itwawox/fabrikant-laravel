<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Контакты и служебные данные сайта, которые раньше были вписаны в шаблоны: правятся в админке
 * («Настройки сайта»). Готовые строки для шаблонов собирает App\Support\Site.
 */
class SiteSettings extends Settings
{
    public string $name;

    // Как телефон написан на сайте: +7 978 807 20 01
    public string $phone;

    public string $email;

    public ?string $pr_email;

    public string $city;

    public string $street;

    public string $house;

    // Каждый день с … до …
    public string $opens_at;

    public string $closes_at;

    // Точка ресторана на карте (для маршрута)
    public float $lat;

    public float $lng;

    public ?string $yandex_maps_url;

    public ?string $vk_url;

    public ?string $ok_url;

    public ?string $telegram_url;

    public ?string $whatsapp;

    public bool $metrika_enabled;

    public ?string $metrika_id;

    /** @var list<string> кухни для карточки ресторана в поиске (schema.org) */
    public array $cuisines;

    public static function group(): string
    {
        return 'site';
    }
}
