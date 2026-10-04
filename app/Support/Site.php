<?php

namespace App\Support;

use App\Settings\SiteSettings;

/**
 * Контакты из настроек сайта — готовыми строками в тех видах, что стоят в шаблонах:
 * «Симферополь, Киевская, 54», «ул. Киевская, 54», «tel:+79788072001». В шаблонах доступно как $site.
 */
class Site
{
    public function __construct(public readonly SiteSettings $settings) {}

    /** Полный адрес страницы сайта: для карточки в поиске */
    public function url(string $path = '/'): string
    {
        return rtrim(url('/'), '/').$path;
    }

    public function name(): string
    {
        return $this->settings->name;
    }

    /** +7 978 807 20 01 */
    public function phone(): string
    {
        return $this->settings->phone;
    }

    /** +79788072001 — для ссылки и карточки в поиске */
    public function phoneDigits(): string
    {
        return (string) preg_replace('/[^\d+]/', '', $this->settings->phone);
    }

    public function tel(): string
    {
        return 'tel:'.$this->phoneDigits();
    }

    public function email(): string
    {
        return $this->settings->email;
    }

    public function prEmail(): ?string
    {
        return $this->settings->pr_email;
    }

    public function city(): string
    {
        return $this->settings->city;
    }

    /** Киевская, 54 */
    public function address(): string
    {
        return $this->settings->street.', '.$this->settings->house;
    }

    /** ул. Киевская, 54 */
    public function streetAddress(): string
    {
        return 'ул. '.$this->address();
    }

    /** улица Киевская, 54 */
    public function streetAddressFull(): string
    {
        return 'улица '.$this->address();
    }

    public function opensAt(): string
    {
        return $this->settings->opens_at;
    }

    public function closesAt(): string
    {
        return $this->settings->closes_at;
    }

    /** Маршрут до ресторана в Яндекс Картах (уже с &amp; — вставлять как есть) */
    public function routeUrl(): string
    {
        return 'https://yandex.ru/maps/?rtext=~'.$this->settings->lat.'%2C'.$this->settings->lng.'&amp;rtt=auto';
    }

    public function yandexMapsUrl(): ?string
    {
        return $this->settings->yandex_maps_url;
    }

    /** Карта на странице «Контакты»: виджет организации из ссылки на её карточку в Яндекс Картах */
    public function mapWidgetUrl(): ?string
    {
        if (! preg_match('#/maps/org/([^/]+/\d+)#', (string) $this->settings->yandex_maps_url, $m)) {
            return null;
        }

        return 'https://yandex.ru/map-widget/v1/org/'.$m[1].'/?ll='.$this->settings->lng.'%2C'.$this->settings->lat.'&amp;z=16';
    }

    public function vkUrl(): ?string
    {
        return $this->settings->vk_url;
    }

    public function okUrl(): ?string
    {
        return $this->settings->ok_url;
    }

    public function metrikaId(): ?string
    {
        return $this->settings->metrika_enabled && $this->settings->metrika_id ? $this->settings->metrika_id : null;
    }

    /**
     * Карточка ресторана для поисковиков (schema.org Restaurant): адрес, телефон, часы, кухни.
     *
     * @return array<string, mixed>
     */
    public function restaurantSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $this->settings->name,
            'description' => 'Ресторан с собственной пивоварней',
            'url' => $this->url('/'),
            'image' => $this->url('/assets/img/hero/hall.webp'),
            'telephone' => $this->phoneDigits(),
            'servesCuisine' => $this->settings->cuisines,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->streetAddress(),
                'addressLocality' => $this->settings->city,
            ],
            'openingHours' => 'Mo-Su '.$this->settings->opens_at.'-'.$this->settings->closes_at,
            'acceptsReservations' => true,
        ];
    }
}
