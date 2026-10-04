<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Enums\MenuStatus;
use App\Filament\Resources\Menus\MenuResource;
use App\Models\Menu;
use App\Services\Menu\MenuPdfException;
use App\Services\Menu\MenuPipeline;
use App\Services\Menu\MenuPublisher;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * @property Menu $record
 */
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    public function getTitle(): string
    {
        return 'Меню «'.$this->record->season.'»';
    }

    /** Опрос из status.blade.php: когда обработка закончилась — показываем подписи страниц и кнопки. */
    public function refreshStatus(): void
    {
        $this->record->refresh();

        if ($this->record->status !== MenuStatus::Processing) {
            $this->fillForm();
            $this->record->error
                ? Notification::make()->danger()->title('Меню не обработано')->body($this->record->error)->send()
                : Notification::make()->success()->title('Меню готово')->body('Проверьте подписи страниц и откройте предпросмотр.')->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $is = fn (MenuStatus ...$statuses): Closure => fn (): bool => in_array($this->record->status, $statuses, true);

        return [
            Action::make('preview')
                ->label('Предпросмотр')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible($is(MenuStatus::Ready, MenuStatus::Archived, MenuStatus::Published))
                // Ссылка живёт неделю: её можно переслать владельцу посмотреть с телефона
                ->url(fn (): string => URL::temporarySignedRoute('menu.preview', now()->addWeek(), ['menu' => $this->record]))
                ->openUrlInNewTab(),

            Action::make('sections')
                ->label(fn (): string => 'Разделы ('.$this->record->sections()->count().')')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->visible($is(MenuStatus::Ready, MenuStatus::Archived, MenuStatus::Published))
                ->url(fn (): string => MenuResource::getUrl('sections', ['record' => $this->record])),

            Action::make('publish')
                ->label(fn (): string => $this->record->status === MenuStatus::Archived ? 'Опубликовать снова' : 'Опубликовать сейчас')
                ->icon('heroicon-o-rocket-launch')
                ->visible($is(MenuStatus::Ready, MenuStatus::Archived))
                ->requiresConfirmation()
                ->modalDescription('Гости сразу увидят это меню, а текущее уйдёт в архив. Вернуть прошлое можно кнопкой «Откатить» в списке меню.')
                ->action(fn () => $this->run(fn (MenuPublisher $p) => $p->publish($this->record), 'Меню опубликовано')),

            Action::make('schedule')
                ->label(fn (): string => $this->record->scheduled_at ? 'Изменить дату' : 'Запланировать')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->visible($is(MenuStatus::Ready))
                ->schema([
                    DateTimePicker::make('at')
                        ->label('Опубликовать')
                        ->seconds(false)
                        ->minDate(now()->startOfDay())
                        ->required(),
                ])
                ->fillForm(fn (): array => ['at' => $this->record->scheduled_at])
                ->action(fn (array $data) => $this->run(
                    fn (MenuPublisher $p) => $p->schedule($this->record, Carbon::parse($data['at'])),
                    'Меню опубликуется '.Carbon::parse($data['at'])->translatedFormat('j F в H:i'),
                )),

            Action::make('retry')
                ->label('Повторить обработку')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->record->error !== null || MenuPipeline::stuck($this->record))
                ->action(function () {
                    app(MenuPipeline::class)->start($this->record);
                    $this->record->refresh();
                }),

            ActionGroup::make([
                Action::make('unschedule')
                    ->label('Отменить автопубликацию')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (): bool => $this->record->scheduled_at !== null && $this->record->status === MenuStatus::Ready)
                    ->action(fn () => $this->run(fn (MenuPublisher $p) => $p->unschedule($this->record), 'Автопубликация отменена')),
                Action::make('delete')
                    ->label('Удалить меню')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible($is(MenuStatus::Draft, MenuStatus::Ready, MenuStatus::Archived))
                    ->requiresConfirmation()
                    ->modalDescription('Меню удалится вместе с картинками и PDF. Вернуть его будет нельзя.')
                    ->action(function () {
                        $this->run(fn (MenuPublisher $p) => $p->delete($this->record), 'Меню удалено');
                        if (! $this->record->exists) {
                            $this->redirect(MenuResource::getUrl());
                        }
                    }),
            ]),
        ];
    }

    /** Действие с понятной ошибкой вместо страницы 500 */
    private function run(Closure $action, string $success): void
    {
        try {
            app()->call($action);
            if ($this->record->exists) {
                $this->record->refresh();
            }
            Notification::make()->success()->title($success)->send();
        } catch (MenuPdfException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}
