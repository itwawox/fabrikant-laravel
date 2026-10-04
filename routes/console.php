<?php

use App\Services\Menu\MenuPublisher;
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
