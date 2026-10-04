<?php

namespace App\Services;

use App\Enums\MenuStatus;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\PromoBlackout;
use App\Support\MenuSections;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Переносит данные старого сайта (папка с data/*.php, assets/ и uploads/) в базу и в storage.
 *
 * Можно запускать повторно: записи ищутся по естественным ключам (имя меню, адрес акции, ключ рубрики,
 * адрес фото) и обновляются, разметка разделов меню пересоздаётся. Правки, сделанные в админке после
 * импорта, повторный запуск перезапишет — импорт рассчитан на переезд, а не на постоянную синхронизацию.
 * Нет какого-то файла — предупреждение, данные всё равно переносятся.
 */
class LegacyImporter
{
    // Лист A3 в пунктах — на нём печатается меню; берётся, если размер не удалось прочитать из PDF
    private const A3_PT = [841.89, 1190.55];

    // Как называются картинки страницы в старом проекте (после «{name}-{N}») и в новом (после «{N}»)
    private const PAGE_FILES = [
        '.jpg' => '-1600.jpg',
        '.webp' => '-1600.webp',
        '-1000.webp' => '-1000.webp',
        '-200.webp' => '-200.webp',
        '-3200.webp' => '-3200.webp',
    ];

    // Официальные праздники РФ: в старом сайте записаны только даты, здесь — ещё и названия для админки
    private const HOLIDAY_TITLES = [
        '01-01' => 'Новогодние каникулы', '01-02' => 'Новогодние каникулы', '01-03' => 'Новогодние каникулы',
        '01-04' => 'Новогодние каникулы', '01-05' => 'Новогодние каникулы', '01-06' => 'Новогодние каникулы',
        '01-07' => 'Рождество Христово', '01-08' => 'Новогодние каникулы',
        '02-23' => 'День защитника Отечества', '03-08' => 'Международный женский день',
        '05-01' => 'Праздник Весны и Труда', '05-09' => 'День Победы', '06-12' => 'День России',
        '11-04' => 'День народного единства',
    ];

    private string $root;

    /** @var list<string> */
    private array $warnings = [];

    /** @var array<string, int> */
    private array $counts = [];

    /**
     * @return array{counts: array<string, int>, warnings: list<string>}
     */
    public function import(string $root): array
    {
        $this->root = rtrim($root, '/');
        $this->warnings = [];
        $this->counts = [];

        foreach (['menu', 'promos', 'gallery'] as $file) {
            if (! is_file($this->path("data/{$file}.php"))) {
                throw new InvalidArgumentException("Не найден data/{$file}.php в {$this->root}: это точно папка старого сайта?");
            }
        }

        DB::transaction(function () {
            $this->importMenu();
            $this->importPromos();
            $this->importGallery();
        });

        return ['counts' => $this->counts, 'warnings' => $this->warnings];
    }

    private function importMenu(): void
    {
        $data = $this->data('menu');
        $pages = array_values($data['pages'] ?? []);

        $menu = Menu::firstOrNew(['name' => $data['name']]);
        $menu->season = $data['season'];
        $menu->pages_count = count($pages);
        $menu->processed_at ??= now();
        if (! $menu->exists) {
            // Старое меню уже висит на сайте — значит, опубликовано; остальные уходят в архив,
            // потому что опубликованным всегда бывает ровно одно меню
            Menu::published()->update(['status' => MenuStatus::Archived]);
            $menu->status = MenuStatus::Published;
            $menu->published_at = now();
        }
        $menu->save();

        // Своя папка у каждого меню: имена файлов не повторяются, и кэш браузера не покажет старое меню
        if (! $menu->storage_dir) {
            $menu->storage_dir = 'menus/'.$menu->id.'-'.Str::lower(Str::random(8));
            $menu->save();
        }
        $dir = $menu->storage_dir;

        foreach ($pages as $i => $title) {
            $number = $i + 1;
            $size = null;
            foreach (self::PAGE_FILES as $old => $new) {
                $from = "assets/img/menu/{$data['name']}-{$number}{$old}";
                if ($this->copy($from, $this->public(), "{$dir}/{$number}{$new}") && $old === '.jpg') {
                    $size = getimagesize($this->path($from)) ?: null;
                }
            }
            $menu->pages()->updateOrCreate(['number' => $number], [
                'title' => $title,
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
            ]);
        }
        $menu->pages()->where('number', '>', count($pages))->delete();

        // Исходник типографии гостям не отдаётся — он на закрытом диске; лёгкий PDF — для кнопки «Скачать»
        $source = "uploads/{$data['name']}.pdf";
        $menu->source_pdf_path = $this->copy($source, $this->private(), "{$dir}/source.pdf") ? "{$dir}/source.pdf" : null;
        $menu->web_pdf_path = $this->copy("uploads/{$data['name']}-web.pdf", $this->public(), "{$dir}/web.pdf") ? "{$dir}/web.pdf" : null;
        [$menu->sheet_w_pt, $menu->sheet_h_pt] = $this->sheetSize($source);
        $menu->save();

        $menu->sections()->delete();
        $sections = MenuSections::normalize($data['sections'] ?? [], count($pages));
        foreach ($sections as $position => $section) {
            $model = $menu->sections()->create([
                'page_number' => $section['page'],
                'title' => $section['title'],
                'slug' => $section['id'],
                'position' => $position,
            ]);
            foreach ($section['boxes'] as $boxPosition => [$x, $y, $w, $h]) {
                $model->boxes()->create(compact('x', 'y', 'w', 'h') + ['position' => $boxPosition]);
            }
        }

        $this->counts['Страниц меню'] = count($pages);
        $this->counts['Разделов меню'] = count($sections);
        $this->counts['Рамок разделов'] = array_sum(array_map(fn (array $s): int => count($s['boxes']), $sections));
    }

    private function importPromos(): void
    {
        $data = $this->data('promos');

        foreach (array_values($data['promos'] ?? []) as $position => $promo) {
            $photo = $promo['photo'] ?? null;
            $when = $promo['when'] ?? null;
            $rows = [];
            foreach ($promo['rows'] ?? [] as $label => $value) {
                $rows[] = ['label' => $label, 'value' => $value];
            }

            Promo::updateOrCreate(['slug' => $promo['id']], [
                'title' => $promo['title'],
                'discount' => $promo['discount'] ?? null,
                'subject' => $promo['subject'] ?? null,
                'short' => $promo['short'] ?? null,
                'photo_path' => $photo && $this->copy("assets/img/akcii/{$photo}.jpg", $this->public(), "promos/{$photo}.jpg") ? "promos/{$photo}.jpg" : null,
                'photo_webp_path' => $photo && $this->copy("assets/img/akcii/{$photo}.webp", $this->public(), "promos/{$photo}.webp") ? "promos/{$photo}.webp" : null,
                'alt' => $promo['alt'] ?? null,
                'rows' => $rows,
                'terms' => array_values($promo['terms'] ?? []),
                'position' => $position,
                'is_active' => empty($promo['off']),
                'days' => $when ? array_values(array_map('intval', $when['days'] ?? [])) : null,
                'time_from' => $when['from'] ?? null,
                'time_to' => $when['to'] ?? null,
                'not_holidays' => ! empty($when['not_holidays']),
            ]);
        }

        foreach ($data['holidays'] ?? [] as $monthDay) {
            $holiday = Holiday::firstOrNew(['month_day' => $monthDay]);
            $holiday->title ??= self::HOLIDAY_TITLES[$monthDay] ?? null;
            $holiday->save();
        }

        foreach ($data['no_dates'] ?? [] as $date) {
            PromoBlackout::firstOrCreate(['date' => $date]);
        }

        $this->counts['Акций'] = count($data['promos'] ?? []);
        $this->counts['Праздников'] = count($data['holidays'] ?? []);
        $this->counts['Дней без акций'] = count($data['no_dates'] ?? []);
    }

    private function importGallery(): void
    {
        $data = $this->data('gallery');
        $build = is_file($this->path('data/gallery.build.php')) ? $this->data('gallery.build') : [];
        if (! $build) {
            $this->warnings[] = 'Нет data/gallery.build.php: фото перенесены без готовых копий.';
        }

        $rubrics = [];
        $position = 0;
        foreach ($data['rubrics'] ?? [] as $key => $rubric) {
            $rubrics[$key] = GalleryRubric::updateOrCreate(['key' => $key], [
                'title' => $rubric['title'],
                'lede' => $rubric['lede'] ?? null,
                'position' => $position++,
            ]);
        }

        foreach (array_values($data['photos'] ?? []) as $position => $photo) {
            $built = $build[$photo['id']] ?? [];
            $source = $photo['src'] ?? null;
            $sourcePath = $source ? 'gallery/source/'.basename($source) : null;
            if ($source && ! $this->copy($source, $this->public(), (string) $sourcePath)) {
                $sourcePath = null;
            }

            GalleryPhoto::updateOrCreate(['slug' => $photo['id']], [
                'rubric_id' => $rubrics[$photo['rubric']]->id,
                'size' => $photo['size'] ?? 'std',
                'alt' => $photo['alt'] ?? null,
                'caption' => $photo['caption'] ?? null,
                'source_path' => $sourcePath,
                'width' => $built['w'] ?? null,
                'height' => $built['h'] ?? null,
                'variants' => $this->galleryVariants($built),
                'position' => $position,
            ]);
        }

        $this->counts['Рубрик галереи'] = count($rubrics);
        $this->counts['Фото галереи'] = count($data['photos'] ?? []);
    }

    /**
     * Копии фото из data/gallery.build.php: адреса «/assets/img/press/…» становятся путями на диске public.
     *
     * @param  array<string, mixed>  $built
     * @return array<string, mixed>
     */
    private function galleryVariants(array $built): array
    {
        $copy = function (string $url): ?string {
            $to = 'gallery/'.basename($url);

            return $this->copy(ltrim($url, '/'), $this->public(), $to) ? $to : null;
        };

        $variants = [];
        foreach (['avif', 'webp'] as $format) {
            foreach ($built[$format] ?? [] as $width => $url) {
                if ($path = $copy($url)) {
                    $variants[$format][$width] = $path;
                }
            }
        }
        if (isset($built['jpg']) && $path = $copy($built['jpg'])) {
            $variants['jpg'] = $path;
        }

        return $variants;
    }

    /**
     * Размер листа в пунктах из PDF через pdfinfo (poppler). Нет PDF или программы — лист A3,
     * как у всех меню ресторана до сих пор. Рендер через Ghostscript появится на этапе 3.
     *
     * @return array{0: float, 1: float}
     */
    private function sheetSize(string $pdf): array
    {
        if (is_file($this->path($pdf))) {
            $result = Process::timeout(30)->run(['pdfinfo', $this->path($pdf)]);
            if ($result->successful() && preg_match('/Page size:\s+([\d.]+) x ([\d.]+) pts/', $result->output(), $m)) {
                return [(float) $m[1], (float) $m[2]];
            }
        }
        $this->warnings[] = "Размер листа не прочитан из {$pdf}: записан A3.";

        return self::A3_PT;
    }

    private function copy(string $from, Filesystem $disk, string $to): bool
    {
        $source = $this->path($from);
        if (! is_file($source)) {
            $this->warnings[] = "Нет файла {$from}";

            return false;
        }
        $stream = fopen($source, 'rb');
        $disk->writeStream($to, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return true;
    }

    /**
     * @return array<mixed>
     */
    private function data(string $name): array
    {
        $data = require $this->path("data/{$name}.php");

        return is_array($data) ? $data : [];
    }

    private function path(string $relative): string
    {
        return $this->root.'/'.ltrim($relative, '/');
    }

    private function public(): Filesystem
    {
        return Storage::disk('public');
    }

    private function private(): Filesystem
    {
        return Storage::disk('local');
    }
}
