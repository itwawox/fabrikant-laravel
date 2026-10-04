<?php

use App\Models\Booking;
use App\Services\Menu\MenuPublisher;
use App\Settings\SiteSettings;
use Illuminate\Support\Facades\Artisan;
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
