<?php

namespace App\Filament\Pages;

use App\Settings\SiteSettings;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Настройки сайта: контакты, адрес, часы, соцсети, Метрика. Всё, что раньше было вписано в шаблоны.
 */
class ManageSiteSettings extends SettingsPage
{
    protected static string $settings = SiteSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $title = 'Настройки сайта';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 90;

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Контакты')->columns(2)->schema([
                TextInput::make('name')->label('Название')->required()->maxLength(60),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->helperText('Как писать на сайте: +7 978 807 20 01. Кнопки «Позвонить» и «Забронировать стол» звонят на него.')
                    ->tel()
                    ->required()
                    ->regex('/^\+?[\d\s()-]{10,20}$/'),
                TextInput::make('email')->label('Почта')->email()->required(),
                TextInput::make('pr_email')->label('Почта PR-службы')->email()->helperText('Пусто — строки на странице «Контакты» не будет.'),
            ]),
            Section::make('Адрес и часы')->columns(3)->schema([
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('street')->label('Улица')->helperText('Без «ул.»: Киевская')->required(),
                TextInput::make('house')->label('Дом')->required()->maxLength(10),
                TimePicker::make('opens_at')->label('Открыто с')->seconds(false)->required(),
                TimePicker::make('closes_at')->label('до')->seconds(false)->required(),
            ]),
            Section::make('Карта')->columns(2)->schema([
                TextInput::make('lat')->label('Широта')->numeric()->required()->helperText('Точка для маршрута: 44.957436'),
                TextInput::make('lng')->label('Долгота')->numeric()->required()->helperText('34.109319'),
                TextInput::make('yandex_maps_url')
                    ->label('Карточка в Яндекс Картах')
                    ->url()
                    ->helperText('Ссылка вида https://yandex.ru/maps/org/…/1324964934/ — из неё же строится карта на странице «Контакты».')
                    ->columnSpanFull(),
            ]),
            Section::make('Соцсети')->columns(2)->schema([
                TextInput::make('vk_url')->label('ВКонтакте')->url(),
                TextInput::make('ok_url')->label('Одноклассники')->url(),
                TextInput::make('telegram_url')->label('Telegram')->url(),
                TextInput::make('whatsapp')->label('WhatsApp')->tel()->helperText('Номер, если брони принимаются в WhatsApp.'),
            ]),
            Section::make('Яндекс Метрика')->columns(2)->schema([
                Toggle::make('metrika_enabled')->label('Счётчик включён')->live()->columnSpanFull(),
                TextInput::make('metrika_id')
                    ->label('Номер счётчика')
                    ->numeric()
                    ->required(fn (Get $get): bool => (bool) $get('metrika_enabled'))
                    ->helperText('Сменить номер — новая статистика начнётся с нуля, цели нужно завести заново.'),
            ]),
            Section::make('Для поисковиков')->schema([
                TagsInput::make('cuisines')
                    ->label('Кухни')
                    ->helperText('Попадают в карточку ресторана в поиске (schema.org). Enter — добавить.'),
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Время храним как 11:00, без секунд; координаты — числом
        $data['opens_at'] = substr((string) $data['opens_at'], 0, 5);
        $data['closes_at'] = substr((string) $data['closes_at'], 0, 5);
        $data['lat'] = (float) $data['lat'];
        $data['lng'] = (float) $data['lng'];
        $data['cuisines'] = array_values($data['cuisines'] ?? []);

        return $data;
    }
}
