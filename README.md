# ФабрикантЪ — сайт на Laravel

Сайт ресторана-пивоварни «ФабрикантЪ» (Симферополь) с админкой. Переезд со старого сайта на PHP:
[itwawox/fabrikant-site](https://github.com/itwawox/fabrikant-site), тег `legacy-2026-10-04`.
Задание — `TZ-laravel.md` в старом репозитории.

Стек: Laravel 13, PHP 8.4, Filament 5 (`/admin`), Pest 5, Larastan, Pint. Локально — Laravel Herd и SQLite.

## Запуск

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan make:filament-user     # первый пользователь админки
php artisan legacy:import ~/Herd/fabrikant   # меню, акции, галерея со старого сайта
```

`legacy:import` можно запускать повторно: он обновляет записи, а не дублирует. Правки из админки он
перезапишет данными старого сайта.

Сайт: http://fabrikant-laravel.test, админка: http://fabrikant-laravel.test/admin.

## Проверки (то же гоняет CI)

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
```

## Документы

- `docs/decisions.md` — принятые решения и ответы заказчика
- `docs/hosting.md` — что есть на хостинге и какие лимиты
- `docs/admin-guide.md` — инструкция по админке для ресторана
- `docs/visual/` — сравнения картинок
