<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Дашборд владельца: очередь, ошибки задач, последний бэкап, место под файлы сайта. */
class SystemStatus extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Состояние сайта';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManage(UserRole::SETTINGS);
    }

    protected function getStats(): array
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subWeek())->count();
        $backup = collect(Storage::disk('backups')->allFiles())->filter(fn (string $f): bool => str_ends_with($f, '.zip'))
            ->map(fn (string $f): int => Storage::disk('backups')->lastModified($f))->max();
        $backupAt = $backup ? Carbon::createFromTimestamp($backup)->timezone(config('app.timezone')) : null;
        $bytes = collect(Storage::disk('public')->allFiles())->sum(fn (string $f): int => Storage::disk('public')->size($f))
            + collect(Storage::disk('local')->allFiles())->sum(fn (string $f): int => Storage::disk('local')->size($f));

        return [
            Stat::make('Очередь задач', $pending ? "ждут: {$pending}" : 'пусто')
                ->description($failed ? "ошибок за неделю: {$failed} — см. журнал storage/logs" : 'ошибок за неделю нет')
                ->color($failed ? 'danger' : ($pending > 20 ? 'warning' : 'success')),
            Stat::make('Последний бэкап', $backupAt ? $backupAt->translatedFormat('j F, H:i') : 'ещё не было')
                ->description($backupAt ? $backupAt->diffForHumans() : 'делается каждую ночь в 04:10')
                ->color(! $backupAt || $backupAt->lt(now()->subDays(2)) ? 'danger' : 'success'),
            Stat::make('Файлы сайта', number_format($bytes / 1048576, 0, ',', ' ').' МБ')
                ->description('меню, фото, исходники (без бэкапов)'),
        ];
    }
}
