<?php

namespace App\Filament\Resources\Promos\Schemas;

use App\Models\Promo;
use App\Support\MenuSlug;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class PromoForm
{
    public const DAYS = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Карточка на странице «Акции»')->columns(2)->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->placeholder('Счастливые часы')
                    ->required()
                    ->maxLength(80),
                TextInput::make('slug')
                    ->label('Адрес карточки')
                    ->prefix('/promos#')
                    ->helperText('Если пусто — из названия. На него ведёт плашка «Сейчас действует»; после публикации лучше не менять.')
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state, Get $get): string => MenuSlug::make($state ?: (string) $get('title'))),
                TextInput::make('discount')
                    ->label('Скидка')
                    ->placeholder('−25%')
                    ->maxLength(20),
                TextInput::make('subject')
                    ->label('На что')
                    ->placeholder('на меню кухни')
                    ->maxLength(120),
                TextInput::make('short')
                    ->label('Коротко для плашки')
                    ->placeholder('−25% на меню кухни')
                    ->helperText('Плашка «Сейчас действует» над меню: «Счастливые часы · −25% на меню кухни».')
                    ->maxLength(80)
                    ->columnSpanFull(),
                Repeater::make('rows')
                    ->label('Строки карточки')
                    ->helperText('Например «Когда» — «с пн по пт», «Часы» — «12:00 - 15:00».')
                    ->schema([
                        TextInput::make('label')->hiddenLabel()->placeholder('Когда')->required()->maxLength(30),
                        TextInput::make('value')->hiddenLabel()->placeholder('с пн по пт')->required()->maxLength(80),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Добавить строку')
                    ->reorderableWithButtons()
                    ->columnSpanFull(),
                Repeater::make('terms')
                    ->label('Условия')
                    ->simple(TextInput::make('text')->required()->maxLength(300))
                    ->defaultItems(0)
                    ->addActionLabel('Добавить условие')
                    ->reorderableWithButtons()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Показывать на сайте')
                    ->helperText('Выключенная акция не видна нигде, но остаётся в админке.')
                    ->default(true)
                    ->columnSpanFull(),
            ]),

            Section::make('Фото')->columns(2)->schema([
                View::make('filament.promos.photo')->hiddenOn('create'),
                Grid::make(1)->schema([
                    FileUpload::make('photo_upload')
                        ->label(fn (?Promo $record): string => $record?->photo_path ? 'Заменить фото' : 'Фото')
                        ->helperText('JPEG, PNG или WebP, лучше горизонтальное от 1000 px. Кадр 4:3 вырежется по центру, чёрно-белым фото сделает сайт.')
                        ->disk('local')
                        ->directory('promo-uploads')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(15 * 1024),
                    TextInput::make('alt')
                        ->label('Что на фото (для незрячих)')
                        ->placeholder('Бокал пива и закуски на столе')
                        ->maxLength(200),
                ]),
            ]),

            Section::make('Расписание для плашки «Сейчас действует»')
                ->description('Без расписания акция есть только на странице «Акции» (как «Имениннику»).')
                ->columns(2)
                ->schema([
                    Toggle::make('scheduled')
                        ->label('Привязать к дням и часам')
                        ->live()
                        ->columnSpanFull(),
                    CheckboxList::make('days')
                        ->label('Дни недели')
                        ->options(self::DAYS)
                        ->columns(7)
                        ->required(fn (Get $get): bool => (bool) $get('scheduled'))
                        ->visible(fn (Get $get): bool => (bool) $get('scheduled'))
                        ->columnSpanFull(),
                    Toggle::make('all_day')
                        ->label('Весь день')
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('scheduled'))
                        ->columnSpanFull(),
                    TimePicker::make('time_from')
                        ->label('С')
                        ->seconds(false)
                        ->required(fn (Get $get): bool => $get('scheduled') && ! $get('all_day'))
                        ->visible(fn (Get $get): bool => $get('scheduled') && ! $get('all_day')),
                    TimePicker::make('time_to')
                        ->label('До')
                        ->seconds(false)
                        ->after('time_from')
                        ->validationMessages(['after' => 'Время окончания должно быть позже начала.'])
                        ->required(fn (Get $get): bool => $get('scheduled') && ! $get('all_day'))
                        ->visible(fn (Get $get): bool => $get('scheduled') && ! $get('all_day')),
                    Toggle::make('not_holidays')
                        ->label('Не действует в праздники и дни без акций')
                        ->helperText('Списки — в разделах «Праздники» и «Дни без акций».')
                        ->visible(fn (Get $get): bool => (bool) $get('scheduled'))
                        ->columnSpanFull(),
                    DatePicker::make('valid_from')
                        ->label('Действует с')
                        ->helperText('Необязательно: для акций на время.')
                        ->visible(fn (Get $get): bool => (bool) $get('scheduled')),
                    DatePicker::make('valid_to')
                        ->label('по')
                        ->afterOrEqual('valid_from')
                        ->validationMessages(['after_or_equal' => 'Конец периода раньше начала.'])
                        ->visible(fn (Get $get): bool => (bool) $get('scheduled')),
                ]),
        ]);
    }

    /**
     * Поля базы → поля формы: «привязать к дням» и «весь день» — удобные переключатели вместо пустых значений.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data): array
    {
        $data['scheduled'] = ! empty($data['days']);
        $data['all_day'] = $data['scheduled'] && empty($data['time_from']);
        $data['days'] = array_map('intval', $data['days'] ?? []);

        return $data;
    }

    /**
     * Поля формы → поля базы. Без расписания дни, часы и период стираются, чтобы плашка их не учитывала.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        $scheduled = (bool) ($data['scheduled'] ?? false);
        $allDay = ! $scheduled || (bool) ($data['all_day'] ?? false);

        $days = array_map('intval', $data['days'] ?? []);
        sort($days);
        $data['days'] = $scheduled ? $days : null;
        // Повторители хранят строки с техническими ключами — в базу списком, порядок как в форме
        $data['rows'] = array_values($data['rows'] ?? []);
        $data['terms'] = array_values($data['terms'] ?? []);
        $data['time_from'] = $allDay ? null : substr((string) $data['time_from'], 0, 5);
        $data['time_to'] = $allDay ? null : substr((string) $data['time_to'], 0, 5);
        $data['not_holidays'] = $scheduled && ($data['not_holidays'] ?? false);
        $data['valid_from'] = $scheduled ? ($data['valid_from'] ?? null) : null;
        $data['valid_to'] = $scheduled ? ($data['valid_to'] ?? null) : null;
        unset($data['scheduled'], $data['all_day']);

        return $data;
    }
}
