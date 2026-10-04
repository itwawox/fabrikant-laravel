<?php

namespace App\Filament\Resources\Promos\Tables;

use App\Filament\Resources\Promos\Schemas\PromoForm;
use App\Models\Promo;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description('Порядок карточек на странице «Акции» — перетаскиванием.')
            ->reorderable('position')
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                ImageColumn::make('photo_webp_path')->label('')->disk('public')->imageHeight(48),
                TextColumn::make('title')
                    ->label('Акция')
                    ->weight('bold')
                    ->description(fn (Promo $record): string => trim($record->discount.' '.$record->subject)),
                TextColumn::make('schedule')
                    ->label('В плашке')
                    ->state(fn (Promo $record): string => self::schedule($record))
                    ->wrap(),
                ToggleColumn::make('is_active')->label('На сайте'),
            ])
            ->recordActions([
                Action::make('site')
                    ->label('На сайте')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Promo $record): string => route('promos').'#'.$record->slug)
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    // «Пн–Пт 12:00–15:00, кроме праздников» — расписание одной строкой
    public static function schedule(Promo $promo): string
    {
        if (! $promo->hasSchedule()) {
            return 'Без расписания — только на странице «Акции»';
        }
        $days = array_map('intval', $promo->days ?? []);
        sort($days);
        $text = $days === range($days[0], end($days)) && count($days) > 2
            ? PromoForm::DAYS[$days[0]].'–'.PromoForm::DAYS[end($days)]
            : implode(', ', array_map(fn (int $d): string => PromoForm::DAYS[$d], $days));
        $text .= $promo->time_from ? ' '.$promo->time_from.'–'.$promo->time_to : ', весь день';
        $text .= $promo->not_holidays ? ', кроме праздников' : '';
        if ($promo->valid_from || $promo->valid_to) {
            $text .= ' · '.($promo->valid_from ? 'с '.$promo->valid_from->translatedFormat('j M') : '')
                .($promo->valid_to ? ' по '.$promo->valid_to->translatedFormat('j M Y') : '');
        }

        return $text;
    }
}
