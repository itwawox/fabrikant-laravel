<?php

namespace App\Services\Promos;

use App\Models\Promo;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Фото карточки акции, как у старого сайта (assets/img/akcii): JPEG 1000×750 — запасной, WebP 800×600 —
 * основной. Кадр 4:3 вырезается по центру. Чёрно-белым фото делает CSS, файл остаётся цветным.
 * У каждого нового фото своё имя: браузер держит картинки в кэше, и под старым именем показал бы старое фото.
 */
class PromoPhoto
{
    private const JPG = [1000, 750, 82];

    private const WEBP = [800, 600, 80];

    /** @return array{photo_path: string, photo_webp_path: string} */
    public function store(Promo $promo, string $source): array
    {
        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (! $image) {
            throw new RuntimeException('Не получилось прочитать фото — загрузите JPEG, PNG или WebP.');
        }

        $base = 'promos/'.($promo->slug ?: 'promo').'-'.Str::lower(Str::random(6));
        $disk = Storage::disk('public');
        $disk->makeDirectory('promos');

        [$w, $h, $q] = self::JPG;
        $jpg = $this->cover($image, $w, $h);
        imageinterlace($jpg, true);
        imagejpeg($jpg, $disk->path($base.'.jpg'), $q);

        [$w, $h, $q] = self::WEBP;
        imagewebp($this->cover($image, $w, $h), $disk->path($base.'.webp'), $q);

        $this->delete($promo);

        return ['photo_path' => $base.'.jpg', 'photo_webp_path' => $base.'.webp'];
    }

    /** Старые файлы фото — при замене и удалении акции */
    public function delete(Promo $promo): void
    {
        Storage::disk('public')->delete(array_filter([$promo->photo_path, $promo->photo_webp_path]));
    }

    // Кадр нужного размера из середины картинки, без полей и искажений
    private function cover(GdImage $image, int $width, int $height): GdImage
    {
        $sw = imagesx($image);
        $sh = imagesy($image);
        $scale = max($width / $sw, $height / $sh);
        $cw = (int) round($width / $scale);
        $ch = (int) round($height / $scale);
        $out = imagecreatetruecolor($width, $height);
        imagecopyresampled($out, $image, 0, 0, intdiv($sw - $cw, 2), intdiv($sh - $ch, 2), $width, $height, $cw, $ch);

        return $out;
    }
}
