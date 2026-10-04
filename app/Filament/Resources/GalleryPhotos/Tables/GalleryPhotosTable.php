<?php

namespace App\Filament\Resources\GalleryPhotos\Tables;

use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class GalleryPhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description('Порядок фото внутри рубрики — перетаскиванием. Сетку страницы раскладывает сайт сам.')
            ->reorderable('position')
            ->defaultSort('position')
            ->defaultGroup(Group::make('rubric.title')->label('Рубрика')->orderQueryUsing(fn ($query, string $direction) => $query->orderBy(
                GalleryRubric::select('position')->whereColumn('gallery_rubrics.id', 'gallery_photos.rubric_id'), $direction,
            )))
            ->paginated(false)
            // Пока у какого-то фото режутся копии, список обновляется сам
            ->poll(fn () => GalleryPhoto::whereNull('variants')->orWhere('variants', '[]')->exists() ? '5s' : null)
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->state(fn (GalleryPhoto $record): ?string => $record->variants['webp'][480] ?? null)
                    ->disk('public')
                    ->imageHeight(56),
                TextColumn::make('caption')
                    ->label('Подпись')
                    ->weight('bold')
                    ->description(fn (GalleryPhoto $record): string => '#foto-'.$record->slug)
                    ->wrap(),
                TextColumn::make('size')->label('В сетке')->badge(),
                TextColumn::make('status')
                    ->label('Копии')
                    ->state(fn (GalleryPhoto $record): string => empty($record->variants['jpg']) ? 'готовятся' : 'готовы')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'готовы' ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('rubric')->label('Рубрика')->relationship('rubric', 'title'),
            ])
            ->recordActions([
                Action::make('site')
                    ->label('На сайте')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (GalleryPhoto $record): string => route('gallery').'#foto-'.$record->slug)
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }
}
