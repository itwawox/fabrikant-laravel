<?php

namespace App\Filament\Resources\Activities;

use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Booking;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\Page;
use App\Models\Promo;
use App\Models\PromoBlackout;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/** Журнал изменений: кто, когда и что поменял в админке. Только для владельца. */
class ActivityResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::LOG;

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $modelLabel = 'запись';

    protected static ?string $pluralModelLabel = 'Журнал изменений';

    protected static ?string $navigationLabel = 'Журнал изменений';

    protected static ?int $navigationSort = 85;

    // Что это за запись — по-человечески
    public const SUBJECTS = [
        Menu::class => 'Меню', MenuPage::class => 'Подпись страницы меню', Promo::class => 'Акция',
        Holiday::class => 'Праздник', PromoBlackout::class => 'День без акций', GalleryPhoto::class => 'Фото',
        GalleryRubric::class => 'Рубрика галереи', Page::class => 'Страница', Booking::class => 'Бронь', User::class => 'Пользователь',
    ];

    private const FIELDS = [
        'title' => 'Название', 'season' => 'Сезон', 'status' => 'Статус', 'published_at' => 'Опубликовано',
        'scheduled_at' => 'Автопубликация', 'discount' => 'Скидка', 'subject' => 'На что', 'short' => 'Для плашки',
        'is_active' => 'На сайте', 'days' => 'Дни', 'time_from' => 'С', 'time_to' => 'До', 'not_holidays' => 'Кроме праздников',
        'valid_from' => 'Действует с', 'valid_to' => 'по', 'rows' => 'Строки', 'terms' => 'Условия', 'position' => 'Порядок',
        'photo_path' => 'Фото', 'photo_webp_path' => 'Фото WebP', 'alt' => 'Описание фото', 'caption' => 'Подпись', 'size' => 'Место в сетке',
        'rubric_id' => 'Рубрика', 'slug' => 'Адрес', 'lede' => 'Подпись', 'month_day' => 'День', 'date' => 'Дата', 'reason' => 'Причина',
        'seo_title' => 'Заголовок', 'seo_description' => 'Описание', 'og_image' => 'Картинка превью', 'content' => 'Тексты',
        'name' => 'Имя', 'email' => 'Почта', 'role' => 'Роль', 'guests' => 'Гостей', 'phone' => 'Телефон', 'comment' => 'Комментарий',
        'time' => 'Время', 'carry_over' => 'Перенос разметки',
    ];

    private const EVENTS = ['created' => 'создано', 'updated' => 'изменено', 'deleted' => 'удалено', 'sections' => 'разделы', 'settings' => 'настройки'];

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['causer', 'subject']))
            ->columns([
                TextColumn::make('created_at')->label('Когда')->dateTime('j M Y, H:i')->description(fn (Activity $r): string => $r->created_at?->diffForHumans() ?? ''),
                TextColumn::make('causer.name')->label('Кто')->placeholder('система'),
                TextColumn::make('event')
                    ->label('Что')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::EVENTS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success', 'deleted' => 'danger', default => 'gray'
                    }),
                TextColumn::make('subject_type')
                    ->label('Где')
                    ->state(fn (Activity $r): string => self::subject($r))
                    ->wrap(),
                TextColumn::make('attribute_changes')
                    ->label('Изменения')
                    ->state(fn (Activity $r): string => self::changes($r))
                    ->wrap()
                    ->limit(200),
            ])
            ->filters([
                SelectFilter::make('causer_id')->label('Кто')->options(fn () => User::pluck('name', 'id')->all()),
                SelectFilter::make('subject_type')->label('Где')->options(self::SUBJECTS),
            ]);
    }

    public static function subject(Activity $activity): string
    {
        if ($activity->event === 'settings') {
            return 'Настройки сайта';
        }
        $type = self::SUBJECTS[$activity->subject_type] ?? class_basename((string) $activity->subject_type);
        $subject = $activity->subject;
        $name = $subject?->getAttribute('season') ?? $subject?->getAttribute('title') ?? $subject?->getAttribute('caption')
            ?? $subject?->getAttribute('name') ?? $subject?->getAttribute('key') ?? ($activity->subject_id ? '№'.$activity->subject_id : null);

        return $type.($name ? ' «'.Str::limit((string) $name, 40).'»' : '');
    }

    // «Название: Обед → Бизнес-ланч; Скидка: 20% → 25%» — коротко, чтобы видно было в одну-две строки
    public static function changes(Activity $activity): string
    {
        if ($activity->event !== 'updated') {
            return $activity->description;
        }
        $new = (array) ($activity->attribute_changes['attributes'] ?? []);
        $old = (array) ($activity->attribute_changes['old'] ?? []);
        $show = fn ($v): string => is_array($v) ? '…' : Str::limit((string) ($v ?? '—'), 40);

        return collect($new)->map(fn ($value, $field) => (self::FIELDS[$field] ?? $field).': '.$show($old[$field] ?? null).' → '.$show($value))->implode('; ');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
