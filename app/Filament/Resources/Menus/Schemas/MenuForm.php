<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Enums\MenuStatus;
use App\Models\Menu;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Состояние обработки: прогресс, ошибка, публикация. Пока меню обрабатывается — обновляется само
            View::make('filament.menus.status')->hiddenOn('create')->columnSpanFull(),

            TextInput::make('season')
                ->label('Сезон')
                ->placeholder('Зима 2026')
                ->default(fn (): string => self::currentSeason())
                ->helperText('Так меню называется в админке и в имени PDF, который скачивают гости.')
                ->required()
                ->maxLength(60),

            // Тип файла не проверяем по имени: браузер судит по расширению и отказал бы файлу «меню_ФИНАЛ»
            // без .pdf. Что это PDF, сервер смотрит по содержимому — файл PDF всегда начинается с «%PDF-»
            FileUpload::make('upload')
                ->label('PDF меню от типографии')
                ->helperText('Перетащите файл сюда или нажмите «выберите». Имя файла может быть любым — сайт сам разложит страницы. До 100 МБ.')
                ->disk('local')
                ->directory('menu-uploads')
                ->rules([
                    // Внешняя функция — для Filament (он вычисляет замыкания в правилах), внутренняя — само правило;
                    // проверяемое значение — список загруженных файлов
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach (Arr::wrap($value) as $file) {
                            // Читаем только начало через хранилище Livewire: у файла без расширения путь на диске другой
                            $stream = $file instanceof TemporaryUploadedFile ? $file->readStream() : null;
                            $head = is_resource($stream) ? (string) fread($stream, 1024) : '';
                            if (is_resource($stream)) {
                                fclose($stream);
                            }
                            if (! str_contains($head, '%PDF-')) {
                                $fail('Это не PDF. Загрузите файл меню от типографии в формате PDF — имя может быть любым.');

                                return;
                            }
                        }
                    },
                ])
                ->maxSize(config('menu.max_upload_kb'))
                ->required()
                ->visibleOn('create')
                ->columnSpanFull(),

            Toggle::make('carry_over')
                ->label('Перенести подписи страниц и разделы из текущего меню')
                ->helperText('Сработает, если страниц столько же. Потом проверьте, не съехали ли рамки разделов.')
                ->default(true)
                ->visibleOn('create'),

            DateTimePicker::make('scheduled_at')
                ->label('Опубликовать автоматически')
                ->helperText('Необязательно. Можно опубликовать и вручную, когда проверите меню.')
                ->seconds(false)
                ->minDate(now()->startOfDay())
                ->visibleOn('create'),

            Section::make('Подписи страниц')
                ->description('Коротко и словами гостя: «Супы и салаты», «Горячее и гриль». Подписи видны в содержании и читаются незрячим.')
                ->hiddenOn('create')
                ->visible(fn (?Menu $record): bool => $record?->status !== MenuStatus::Processing && (bool) $record?->pages()->exists())
                ->columnSpanFull()
                ->schema([
                    Repeater::make('pages')
                        ->hiddenLabel()
                        ->relationship(modifyQueryUsing: fn ($query) => $query->orderBy('number'))
                        ->schema([
                            TextInput::make('title')
                                ->hiddenLabel()
                                ->required()
                                ->maxLength(120),
                        ])
                        ->itemLabel(fn (array $state): string => 'Страница '.($state['number'] ?? ''))
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->grid(2),
                ]),
        ]);
    }

    /** «Осень 2026» — сезон по сегодняшней дате, чтобы для загрузки хватило выбрать файл */
    public static function currentSeason(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $season = match (true) {
            in_array($date->month, [12, 1, 2], true) => 'Зима',
            $date->month <= 5 => 'Весна',
            $date->month <= 8 => 'Лето',
            default => 'Осень',
        };
        // Декабрьское меню — уже зимнее меню следующего года
        $year = $date->month === 12 ? $date->year + 1 : $date->year;

        return $season.' '.$year;
    }
}
