# Хостинг

Виртуальный хостинг reg.ru, nginx перед Apache. Имя аккаунта, пути и ключи — в `docs/private/hosting.md`
(папка в `.gitignore`, репозиторий публичный). Проверено по SSH, только чтение, 4 октября 2026.

## Что есть на сервере

| Что | Значение | Вывод |
|---|---|---|
| PHP | 8.4.25 (`/opt/php/8.4/bin/php`), 8.3 | Все команды — через полный путь к 8.4 |
| Расширения | gd (WebP да, AVIF нет), imagick 3.5.1 на ImageMagick 6.9.13, intl, mbstring, pdo_mysql, zip, fileinfo, exif | |
| `proc_open` | доступна (`disable_functions` пуст) | Можно звать `gs` из PHP |
| ImageMagick | форматы JPEG, PNG, PS, WEBP, PDF, **но** `policy.xml` разрешает только `GIF, JPEG, PNG, WEBP` | PDF через Imagick **не читается**; AVIF нет |
| Ghostscript | 9.27, `/usr/bin/gs` | Рендер PDF меню |
| poppler, tesseract, ffmpeg, qpdf | нет | см. `decisions.md` |
| MySQL | сервер Percona 5.7 | Без JSON-индексов и CTE в миграциях |
| git | 2.43 | Деплой через `git pull` |
| Node | 10.24 | Vite на сервере не собрать |
| composer | нет в PATH | `composer.phar` в аккаунт |
| cron | есть, пример: `* * * * * /opt/php/8.4/bin/php <проект>/artisan schedule:run` | Планировщик + очередь `--stop-when-empty` |
| Квота диска | `quota`: none; аккаунт занимает 18 ГБ | Уточнить тариф в панели |
| Корень домена на `public/` | у `car-on-time-dev` папка домена — симлинк на `<проект>/public` | Так же |

## Лимиты PHP 8.4 (CLI, `/opt/php/8.4/etc/php.ini`)

| Параметр | Значение |
|---|---|
| `upload_max_filesize` | 2M |
| `post_max_size` | 8M |
| `memory_limit` | 128M |
| `max_execution_time` | 0 (CLI) |

Это значения CLI. Для веб-запросов их задаёт панель reg.ru → «Настройки PHP», и они могут отличаться.
**PDF из типографии весит ~40 МБ** — при 2M/8M он не загрузится. Варианты (решить до этапа 3):
1. поднять в панели `upload_max_filesize`/`post_max_size` до 100M и проверить лимит nginx (`client_max_body_size`);
2. загрузка по частям (chunked upload) в админке — не зависит от лимитов.

## Ещё проверить

- Веб-лимиты PHP (не CLI): `phpinfo()` в временном файле или панель.
- `client_max_body_size` у nginx — пробной загрузкой.
- `memory_limit` 128M хватает ли на страницу 3200×4526 в GD (≈ 58 МБ на truecolor-картинку) — на этапе 3.
- gs 9.27 на сервере против poppler — повторить сравнение на этапе 3.

## На проде сейчас (старый сайт)

В папке сайта лежат `.DS_Store` и `.idea/` — их залили по FTP, удалить при переезде (или раньше).
