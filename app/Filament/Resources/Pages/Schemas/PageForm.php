<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Форма страницы: SEO у всех, тексты — поля под конкретные блоки страницы (без редактора вёрстки).
 */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var Page|null $page */
        $page = $schema->getRecord();

        return $schema->columns(1)->components([
            ...self::content($page?->key),
            Section::make('Для поисковиков и превью ссылок')->collapsible()->schema([
                TextInput::make('seo_title')
                    ->label('Заголовок во вкладке и в поиске')
                    ->required()
                    ->maxLength(120),
                Textarea::make('seo_description')
                    ->label('Описание в поиске')
                    ->helperText($page?->key === 'menu'
                        ? '{сезон} заменится на сезон опубликованного меню, например «осень 2026». До 160 знаков.'
                        : 'Одно-два предложения, до 160 знаков. Пусто — поисковик возьмёт текст со страницы.')
                    ->rows(3)
                    ->maxLength(300),
                FileUpload::make('og_image')
                    ->label('Картинка превью ссылки')
                    ->helperText('Её показывают Telegram и ВКонтакте, когда пересылают ссылку. Горизонтальная, от 1200×630. Пусто — картинка по умолчанию.')
                    ->disk('public')
                    ->directory('pages')
                    ->image()
                    ->maxSize(5 * 1024),
            ]),
        ]);
    }

    /** @return list<Component> */
    private static function content(?string $key): array
    {
        $text = fn (string $path, string $label, int $rows = 2) => Textarea::make('content.'.$path)->label($label)->rows($rows)->required()->maxLength(1500);
        $line = fn (string $path, string $label) => TextInput::make('content.'.$path)->label($label)->required()->maxLength(200);
        $paragraphs = fn (string $path, string $label) => Repeater::make('content.'.$path)
            ->label($label)
            ->simple(Textarea::make('text')->rows(4)->required()->maxLength(1500))
            ->minItems(1)
            ->addActionLabel('Добавить абзац')
            ->reorderableWithButtons();
        $items = fn (string $path, string $label, string $add) => Repeater::make('content.'.$path)
            ->label($label)
            ->simple(TextInput::make('text')->required()->maxLength(200))
            ->minItems(1)
            ->addActionLabel($add)
            ->reorderableWithButtons();

        return match ($key) {
            'about' => [
                Section::make('Шапка')->schema([$text('lede', 'Подзаголовок под «О ресторане»')]),
                Section::make('Пивоварня')->collapsible()->schema([
                    $text('brewery.lede', 'Вступление'),
                    $paragraphs('brewery.text', 'Текст'),
                    $line('brewery.beers_title', 'Заголовок списка сортов'),
                    Repeater::make('content.brewery.beers')
                        ->label('Сорта пива')
                        ->schema([
                            TextInput::make('name')->hiddenLabel()->placeholder('Пильзенское светлое')->required()->maxLength(60),
                            TextInput::make('note')->hiddenLabel()->placeholder('алк. 4,8%')->required()->maxLength(30),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->addActionLabel('Добавить сорт')
                        ->reorderableWithButtons(),
                    $line('brewery.beers_note', 'Подпись под сортами'),
                ]),
                Section::make('Кухня')->collapsible()->schema([
                    $text('kitchen.lede', 'Вступление'),
                    $paragraphs('kitchen.text', 'Текст (идёт в две колонки)'),
                    $line('kitchen.plaque_title', 'Заголовок плашки'),
                    $items('kitchen.plaque', 'Плашка: что делаем сами', 'Добавить строку'),
                ]),
                Section::make('Атмосфера')->collapsible()->schema([
                    $text('atmosphere.lede', 'Вступление'),
                    $paragraphs('atmosphere.text', 'Текст'),
                    $line('atmosphere.welcome', 'Приглашение над логотипом'),
                ]),
                Section::make('Ищем в команду')->collapsible()->schema([
                    Toggle::make('content.job.show')->label('Показывать объявление о работе')->helperText('Нет вакансий — выключите: пропадёт и пункт «Работа у нас».'),
                    $line('job.title', 'Заголовок'),
                    $text('job.lede', 'Кого ищем'),
                    $items('job.items', 'Что предлагаем', 'Добавить пункт'),
                ]),
            ],
            'contacts' => [
                Section::make('Тексты')->schema([
                    $line('title', 'Заголовок'),
                    $text('note', 'Подпись под кнопками'),
                ])->description('Телефон, адрес, часы и соцсети — в «Настройках сайта».'),
            ],
            'privacy' => [
                Section::make('Текст политики')
                    ->description('Текст должен подготовить владелец или юрист: организация, ИНН, адрес, какие данные, зачем и сколько храним (152-ФЗ). Пока поле пустое, страница /privacy не открывается и форму брони включить нельзя.')
                    ->schema([
                        Textarea::make('content.text')->hiddenLabel()->rows(20)->maxLength(30000)
                            ->helperText('Абзацы разделяйте пустой строкой.'),
                    ]),
            ],
            '404' => [
                Section::make('Тексты')->schema([
                    $line('title', 'Заголовок'),
                    $text('text', 'Пояснение'),
                ]),
            ],
            default => [],
        };
    }
}
