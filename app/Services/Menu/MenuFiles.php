<?php

namespace App\Services\Menu;

use App\Models\Menu;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Где лежат файлы меню. Картинки и лёгкий PDF — на публичном диске (/storage/menus/…), исходник типографии и
 * промежуточные файлы — на закрытом: гостям они не отдаются.
 */
class MenuFiles
{
    // Что делается из каждой страницы: без этих файлов страница не готова
    public const PAGE_SUFFIXES = ['1600.jpg', '1600.webp', '1000.webp', '200.webp', '3200.webp'];

    public function __construct(private readonly Menu $menu) {}

    /**
     * Своя папка у каждого меню: браузер держит картинки в кэше месяц, и под старым именем гость увидел бы
     * старые цены. Случайная часть — чтобы адрес нового меню нельзя было угадать до публикации.
     */
    public static function ensureDir(Menu $menu): string
    {
        if (! $menu->storage_dir) {
            $menu->storage_dir = 'menus/'.$menu->id.'-'.Str::lower(Str::random(8));
            $menu->save();
        }

        return $menu->storage_dir;
    }

    public function source(): string
    {
        return Storage::disk('local')->path($this->menu->storage_dir.'/source.pdf');
    }

    /** Публичный файл страницы: 3-1600.webp */
    public function page(int $number, string $suffix): string
    {
        return Storage::disk('public')->path("{$this->menu->storage_dir}/{$number}-{$suffix}");
    }

    public function ensurePublicDir(): void
    {
        Storage::disk('public')->makeDirectory($this->menu->storage_dir);
    }

    public function pageReady(int $number): bool
    {
        foreach (self::PAGE_SUFFIXES as $suffix) {
            if (! is_file($this->page($number, $suffix))) {
                return false;
            }
        }

        return true;
    }

    /** Страница для лёгкого PDF: нужна, пока PDF не собран, поэтому на закрытом диске */
    public function webPdfPage(int $number): string
    {
        return $this->tmp()."/{$number}-pdf.jpg";
    }

    public function webPdfRelative(): string
    {
        return $this->menu->storage_dir.'/web.pdf';
    }

    public function tmp(): string
    {
        $dir = Storage::disk('local')->path($this->menu->storage_dir.'/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function deleteTmp(): void
    {
        Storage::disk('local')->deleteDirectory($this->menu->storage_dir.'/tmp');
    }

    /** Все файлы меню — при удалении архивного меню */
    public function deleteAll(): void
    {
        if ($this->menu->storage_dir) {
            Storage::disk('public')->deleteDirectory($this->menu->storage_dir);
            Storage::disk('local')->deleteDirectory($this->menu->storage_dir);
        }
    }
}
