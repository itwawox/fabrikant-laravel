# Бэкапы и восстановление

## Что сохраняется

Каждую ночь в 04:10 (`routes/console.php`) `php artisan backup:run` собирает zip-архив:

- дамп базы данных (`db-dumps/…sql`);
- `storage/app` — картинки и PDF меню, исходники типографии, фото галереи и акций, загруженные файлы.

Код в архив не входит: он в git. Архивы лежат в `storage/app/backups/<APP_NAME>/` и хранятся 14 дней
(`backup:clean` в 04:00). Письмо приходит только при сбое — на `BACKUP_MAIL_TO` из `.env`.

На хостинге для дампа MySQL нужен `mysqldump` — проверить при выкладке (этап 10). Архивы лежат на том же сервере:
от поломки сайта защищают, от потери сервера — нет. Раз в месяц стоит скачивать свежий архив к себе.

## Как восстановить

1. Взять нужный архив: `storage/app/backups/<APP_NAME>/2026-10-04-04-10-00.zip`.
2. Распаковать во временную папку: `unzip архив.zip -d /tmp/restore`.
3. Файлы: скопировать `storage/app` из архива поверх `storage/app` проекта
   (`rsync -a /tmp/restore/storage/app/ storage/app/ --exclude backups`).
4. База:
   - MySQL: `mysql -u USER -p DBNAME < /tmp/restore/db-dumps/mysql-DBNAME.sql`;
   - SQLite (локально): `sqlite3 database/database.sqlite < /tmp/restore/db-dumps/sqlite-….sql` в пустой файл базы.
5. `php artisan optimize:clear && php artisan responsecache:clear`.
6. Открыть сайт и админку, проверить меню, акции, галерею.

Проверка, что бэкапы делаются: на дашборде админки — «Последний бэкап»; вручную — `php artisan backup:list`.
