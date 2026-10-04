<?php

namespace App\Services\Menu;

use GdImage;

/**
 * Картинки одной страницы меню из PDF — то же, что делали tools/new-menu.php, build-menu-thumbs.php
 * и build-menu-web.php старого сайта, с теми же размерами и качеством (config/menu.php).
 */
class MenuPageBuilder
{
    public function __construct(private readonly PdfRenderer $renderer) {}

    /**
     * @return array{0: int, 1: int} ширина и высота листа 1600 px — для width/height у <img>
     */
    public function build(MenuFiles $files, PdfInfo $info, int $number): array
    {
        if (! function_exists('imagewebp')) {
            throw new MenuPdfException('На сервере PHP без WebP в GD — сообщите разработчику.');
        }
        // Страница 3200 px в памяти GD — около 60 МБ, а на хостинге memory_limit 128M
        ini_set('memory_limit', '512M');

        $files->ensurePublicDir();
        $page = config('menu.page');
        $tmp = $files->tmp();
        $png = "{$tmp}/{$number}-render.png";

        // Каждый размер рисуем из PDF заново, как старый сайт: так мелкий шрифт чётче, чем при уменьшении
        // большой картинки
        // 1. Лист 1600 px → JPEG, WebP 1600 и 1000, миниатюра 200
        $this->renderer->renderPng($files->source(), $info, $number, $page['width'], $png);
        $image = $this->load($png);
        imageinterlace($image, true);
        $this->save(fn () => imagejpeg($image, $files->page($number, '1600.jpg'), $page['jpg_quality']));
        $this->save(fn () => imagewebp($image, $files->page($number, '1600.webp'), $page['webp_quality']));
        $this->scaled($image, $page['low_width'], fn (GdImage $low) => imagewebp($low, $files->page($number, '1000.webp'), $page['webp_quality']));
        $thumb = config('menu.thumb');
        $this->scaled($image, $thumb['width'], fn (GdImage $small) => imagewebp($small, $files->page($number, '200.webp'), $thumb['webp_quality']));
        $size = [imagesx($image), imagesy($image)];
        unset($image);

        // 2. Копия 3200 px для окна увеличения
        $zoom = config('menu.zoom');
        $this->renderer->renderPng($files->source(), $info, $number, $zoom['width'], $png);
        $image = $this->load($png);
        $this->save(fn () => imagewebp($image, $files->page($number, '3200.webp'), $zoom['webp_quality']));
        unset($image);

        // 3. Страница лёгкого PDF: JPEG 1800 px
        $web = config('menu.web_pdf');
        $this->renderer->renderPng($files->source(), $info, $number, $web['width'], $png);
        $image = $this->load($png);
        $this->save(fn () => imagejpeg($image, $files->webPdfPage($number), $web['jpg_quality']));
        unset($image);

        @unlink($png);

        return $size;
    }

    private function load(string $png): GdImage
    {
        $image = @imagecreatefrompng($png);
        if (! $image) {
            throw new MenuPdfException('Не получилось прочитать картинку страницы — попробуйте ещё раз.');
        }

        return $image;
    }

    /** @param  callable(GdImage): bool  $write */
    private function scaled(GdImage $image, int $width, callable $write): void
    {
        $small = imagescale($image, $width, -1, IMG_BICUBIC);
        if (! $small) {
            throw new MenuPdfException('Не получилось уменьшить картинку страницы.');
        }
        $this->save(fn () => $write($small));
    }

    /** @param  callable(): bool  $write */
    private function save(callable $write): void
    {
        if (! $write()) {
            throw new MenuPdfException('Не получилось записать картинку — возможно, на сервере кончилось место.');
        }
    }
}
