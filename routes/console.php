<?php

use App\Filament\Widgets\MenuStatusWidget;
use App\Models\Booking;
use App\Models\Menu;
use App\Services\Menu\MenuPublisher;
use App\Settings\SiteSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Публикация меню, запланированного в админке на дату
Artisan::command('menu:publish-scheduled', function (MenuPublisher $publisher) {
    $menu = $publisher->publishDue();
    $this->info($menu ? "Опубликовано меню «{$menu->season}»" : 'Запланированных меню нет');
})->purpose('Опубликовать меню, у которого наступила дата публикации');

Schedule::command('menu:publish-scheduled')->everyMinute()->withoutOverlapping();

// На хостинге нет supervisor: очередь (обработка PDF меню) запускается планировщиком каждую минуту
// и сама завершается, когда задач нет или прошло 50 секунд
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();

// Страницы сайта в кэше, а акции с периодом заканчиваются в полночь — после полуночи кэш собирается заново
Schedule::command('responsecache:clear')->dailyAt('00:01')->timezone('Europe/Simferopol');

// Заявки на бронь — персональные данные: удаляем через срок из настроек (дней после даты визита)
Artisan::command('bookings:prune', function (SiteSettings $settings) {
    $deleted = Booking::whereDate('date', '<', today()->subDays($settings->booking_retention_days))->delete();
    $this->info("Удалено старых заявок: {$deleted}");
})->purpose('Удалить заявки на бронь старше срока хранения');

Schedule::command('bookings:prune')->dailyAt('03:00')->timezone('Europe/Simferopol');

// Бэкап базы и файлов каждую ночь, старше 14 дней — удаляются (config/backup.php)
Schedule::command('backup:clean')->dailyAt('04:00')->timezone('Europe/Simferopol');
Schedule::command('backup:run')->dailyAt('04:10')->timezone('Europe/Simferopol');

// Меню старше 60 дней — письмо владельцу раз в неделю: цены могли поменяться
Artisan::command('menu:remind-stale', function (SiteSettings $settings) {
    $menu = Menu::published()->latest('published_at')->first();
    $days = $menu ? (int) $menu->published_at->diffInDays(now()) : 0;
    if ($menu && $days >= MenuStatusWidget::STALE_DAYS) {
        Mail::raw(
            "Меню «{$menu->season}» опубликовано {$days} дней назад. Проверьте цены и, если меню поменялось, загрузите новый PDF в админке: ".route('filament.admin.resources.menus.index'),
            fn ($message) => $message->to($settings->email)->subject('Пора обновить меню на сайте?'),
        );
        $this->info('Напоминание отправлено');
    }
})->purpose('Напомнить, что меню на сайте старше 60 дней');

Schedule::command('menu:remind-stale')->weeklyOn(1, '10:00')->timezone('Europe/Simferopol');
