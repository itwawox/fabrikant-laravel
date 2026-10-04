<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Enums\MenuStatus;
use App\Models\Menu;
use App\Services\Menu\MenuPipeline;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Меню ещё не загружено')
            ->emptyStateDescription('Нажмите «Загрузить новое меню» вверху справа и выберите PDF от типографии.')
            ->emptyStateIcon('heroicon-o-book-open')
            // Пока какое-то меню обрабатывается, список обновляется сам
            ->poll(fn () => Menu::query()->where('status', MenuStatus::Processing)->exists() ? '3s' : null)
            ->columns([
                TextColumn::make('season')
                    ->label('Сезон')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (Menu $record): string => match (true) {
                        $record->error !== null => 'danger',
                        $record->status === MenuStatus::Published => 'success',
                        $record->status === MenuStatus::Ready => 'info',
                        $record->status === MenuStatus::Processing => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (Menu $record): string => $record->error !== null ? 'Ошибка' : $record->status->getLabel())
                    ->description(fn (Menu $record): ?string => MenuPipeline::progressLabel($record)
                        ?? ($record->scheduled_at && $record->status === MenuStatus::Ready
                            ? 'Опубликуется '.$record->scheduled_at->translatedFormat('j F в H:i') : null)),
                TextColumn::make('pages_count')
                    ->label('Страниц')
                    ->numeric(),
                TextColumn::make('sections_count')
                    ->label('Разделов')
                    ->counts('sections'),
                TextColumn::make('published_at')
                    ->label('Опубликовано')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('—'),
                TextColumn::make('creator.name')
                    ->label('Загрузил')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make()->label('Открыть'),
            ]);
    }
}
