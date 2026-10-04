<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Enums\MenuStatus;
use App\Filament\Resources\Menus\MenuResource;
use App\Models\Menu;
use App\Models\MenuSection;
use App\Services\Menu\MenuMarkup;
use App\Services\Menu\SectionEditor;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Редактор разделов меню: рамки на листе, порядок, названия и адреса. Перенос tools/menu-sections.php.
 * Рисование — на JS в браузере (resources/views/filament/menus/sections.blade.php), сохранение — здесь.
 */
class MenuSections extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MenuResource::class;

    protected string $view = 'filament.menus.sections';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_if(in_array($this->menu()->status, [MenuStatus::Draft, MenuStatus::Processing], true), 404);
    }

    public function getTitle(): string
    {
        return 'Разделы меню «'.$this->menu()->season.'»';
    }

    public function getBreadcrumb(): string
    {
        return 'Разделы';
    }

    /**
     * Данные для редактора: страницы с картинками и разделы в порядке чтения.
     *
     * @return array{pages: list<array<string, mixed>>, sections: list<array<string, mixed>>}
     */
    public function editorState(): array
    {
        $disk = Storage::disk('public');
        $dir = $this->menu()->storage_dir;

        return [
            'pages' => $this->menu()->pages->map(fn ($page) => [
                'n' => $page->number,
                'title' => $page->title,
                'img' => $disk->url("{$dir}/{$page->number}-1000.webp"),
                'thumb' => $disk->url("{$dir}/{$page->number}-200.webp"),
                'ratio' => $page->width && $page->height ? $page->height / $page->width : 1.414,
            ])->values()->all(),
            'sections' => $this->sections(),
        ];
    }

    /**
     * Сохранение из редактора. Если переименование меняет адрес существующего раздела, сначала вернёт
     * список изменений — редактор спросит, оставить старые адреса или сменить, и пришлёт $decision.
     *
     * @param  array<int, mixed>  $sections
     * @return array<string, mixed>
     */
    public function save(array $sections, ?string $decision = null): array
    {
        $decision = in_array($decision, [SectionEditor::KEEP, SectionEditor::CHANGE], true) ? $decision : null;
        $result = app(SectionEditor::class)->save($this->menu(), $sections, $decision);

        if (isset($result['saved'])) {
            $result['sections'] = $this->sections();
            Notification::make()->success()->title('Разделы сохранены: '.$result['saved'])
                ->body($this->menu()->status === MenuStatus::Published ? 'Гости уже видят изменения.' : null)->send();
        }

        return $result;
    }

    protected function getHeaderActions(): array
    {
        $previous = app(MenuMarkup::class)->previousWithMarkup($this->menu());

        return [
            Action::make('back')
                ->label('К меню')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(MenuResource::getUrl('edit', ['record' => $this->menu()])),
            Action::make('preview')
                ->label('Предпросмотр')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => URL::temporarySignedRoute('menu.preview', now()->addWeek(), ['menu' => $this->menu()]))
                ->openUrlInNewTab(),
            Action::make('copyPrevious')
                ->label('Скопировать разметку из прошлого меню')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible($previous !== null)
                ->requiresConfirmation()
                ->modalDescription($previous
                    ? "Разделы этого меню заменятся разделами меню «{$previous->season}». Подписи страниц не изменятся. Потом проверьте, не съехали ли рамки."
                    : null)
                ->action(function () use ($previous) {
                    app(MenuMarkup::class)->copy($previous, $this->menu(), titles: false);
                    Notification::make()->success()->title('Разметка скопирована')->send();
                    $this->redirect(MenuResource::getUrl('sections', ['record' => $this->menu()]));
                }),
        ];
    }

    private function menu(): Menu
    {
        $menu = $this->getRecord();
        abort_unless($menu instanceof Menu, 404);

        return $menu;
    }

    /** @return list<array<string, mixed>> */
    private function sections(): array
    {
        return $this->menu()->sections()->with('boxes')->get()->map(fn (MenuSection $s) => [
            'id' => $s->id,
            'page' => $s->page_number,
            'title' => $s->title,
            'slug' => $s->slug,
            'slug_locked' => $s->slug_locked,
            'boxes' => $s->boxes->map->toBox()->all(),
        ])->values()->all();
    }
}
