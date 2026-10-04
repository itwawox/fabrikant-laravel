<?php

namespace App\Services\Gallery;

use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;
use Imagick;
use RuntimeException;

/**
 * Копии фото галереи — перенос tools/build-images.php старого сайта с тем же рецептом:
 * AVIF и WebP шириной 480, 720 и исходная (AVIF — только где он легче WebP), один JPEG в исходную ширину.
 * Имя файла: {адрес}-{ширина}.{хэш}.{формат}, хэш — от исходника и рецепта: новое фото или новые настройки —
 * новые имена, и браузер не покажет старое из кэша. Копии лежат на публичном диске в gallery/.
 */
class GalleryImages
{
    // Рецепт входит в хэш: поменяли любое число — у всех копий будут новые имена
    public const RECIPE = [
        'version' => 1,
        'widths' => [480, 720],
        'resize' => ['filter' => 'lanczos', 'unsharp' => [0, 0.6, 0.5, 0.01]],
        'avif' => ['quality' => 58, 'speed' => 3, 'chroma' => '420'],
        'webp' => ['quality' => 82, 'method' => 6],
        'jpg' => ['quality' => 82, 'sampling' => '4:2:0', 'progressive' => true],
    ];

    // Где обычно лежит профиль sRGB: нужен для исходников в Display P3 / Adobe RGB, иначе цвета блёклые
    private const SRGB_PROFILES = [
        '/System/Library/ColorSync/Profiles/sRGB Profile.icc',
        '/usr/share/color/icc/colord/sRGB.icc',
        '/usr/share/color/icc/sRGB.icc',
        '/usr/share/color/icc/ghostscript/srgb.icc',
    ];

    /** Путь к исходнику: новые лежат на закрытом диске, перенесённые со старого сайта — могли на публичном */
    public static function source(GalleryPhoto $photo): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if ($photo->source_path && Storage::disk($disk)->exists($photo->source_path)) {
                return Storage::disk($disk)->path($photo->source_path);
            }
        }

        return null;
    }

    public function build(GalleryPhoto $photo): void
    {
        $source = self::source($photo) ?? throw new RuntimeException('Нет исходника фото — загрузите его заново.');
        // Числа в настройках Imagick — с точкой при любом языке системы
        setlocale(LC_NUMERIC, 'C');
        ini_set('memory_limit', '512M');

        $master = $this->load($source);
        $width = $master->getImageWidth();
        $height = $master->getImageHeight();
        $hash = substr(sha1((string) file_get_contents($source).json_encode(self::RECIPE)), 0, 8);
        $widths = array_values(array_filter(self::RECIPE['widths'], fn (int $w): bool => $w < $width));
        $widths[] = $width;

        $disk = Storage::disk('public');
        $disk->makeDirectory('gallery');
        $name = fn (int $w, string $ext): string => "gallery/{$photo->slug}-{$w}.{$hash}.{$ext}";
        $scaled = function (int $w) use ($master, $width, $height): Imagick {
            $image = clone $master;
            if ($w < $width) {
                $image->resizeImage($w, (int) round($height * $w / $width), Imagick::FILTER_LANCZOS, 1);
                $image->unsharpMaskImage(...self::RECIPE['resize']['unsharp']);
            }

            return $image;
        };
        $avif = Imagick::queryFormats('AVIF') !== [];

        $variants = ['avif' => [], 'webp' => [], 'jpg' => ''];
        foreach ($widths as $w) {
            $image = $scaled($w);
            $webp = $name($w, 'webp');
            $this->encode($image, 'webp', $disk->path($webp));
            $variants['webp'][$w] = $webp;
            // AVIF нужен только там, где он легче WebP: иначе браузер зря выбрал бы более тяжёлый файл.
            // На хостинге ImageMagick без AVIF — тогда только WebP и JPEG
            if ($avif) {
                $file = $name($w, 'avif');
                $this->encode($image, 'avif', $disk->path($file));
                if ($disk->size($file) < $disk->size($webp)) {
                    $variants['avif'][$w] = $file;
                } else {
                    $disk->delete($file);
                }
            }
            $image->clear();
        }
        $variants['jpg'] = $name($width, 'jpg');
        $this->encode($scaled($width), 'jpg', $disk->path($variants['jpg']));

        $old = $this->files($photo->variants ?? []);
        $photo->update(['width' => $width, 'height' => $height, 'variants' => $variants]);
        // Старые копии больше не нужны
        $disk->delete(array_values(array_diff($old, $this->files($variants))));
    }

    /** Все копии фото — при удалении фото */
    public function delete(GalleryPhoto $photo): void
    {
        Storage::disk('public')->delete($this->files($photo->variants ?? []));
        if ($photo->source_path) {
            Storage::disk('local')->delete($photo->source_path);
        }
    }

    /**
     * @param  array<string, mixed>  $variants
     * @return list<string>
     */
    private function files(array $variants): array
    {
        return array_values(array_filter([...array_values($variants['avif'] ?? []), ...array_values($variants['webp'] ?? []), $variants['jpg'] ?? null]));
    }

    // Исходник, готовый к нарезке: повёрнут как надо, без прозрачности, в sRGB и без EXIF (координаты съёмки
    // посетителю не нужны, а весят немало)
    private function load(string $source): Imagick
    {
        $image = new Imagick($source.'[0]');
        $image->autoOrient();
        if ($image->getImageAlphaChannel()) {
            // В JPEG прозрачности нет — подкладываем белую «бумагу», на которой фото и стоит на странице
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        $profile = in_array('icc', $image->getImageProfiles('*', false), true) ? (string) $image->getImageProperty('icc:description') : null;
        if ($profile !== null && stripos($profile, 'sRGB') === false) {
            $srgb = null;
            foreach (self::SRGB_PROFILES as $candidate) {
                if (is_file($candidate)) {
                    $srgb = (string) file_get_contents($candidate);
                    break;
                }
            }
            if ($srgb === null) {
                throw new RuntimeException("Цветовой профиль фото «{$profile}», а профиля sRGB на сервере нет. Сохраните фото в sRGB.");
            }
            $image->profileImage('icc', $srgb);
        }
        if ($image->getImageColorspace() !== Imagick::COLORSPACE_SRGB) {
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        $image->stripImage();

        return $image;
    }

    private function encode(Imagick $image, string $format, string $target): void
    {
        $out = clone $image;
        $quality = self::RECIPE[$format]['quality'];
        // Качество — обоими способами: кодер AVIF читает одно значение, WebP и JPEG — другое
        $out->setCompressionQuality($quality);
        $out->setImageCompressionQuality($quality);
        if ($format === 'avif') {
            $out->setImageFormat('avif');
            $out->setOption('heic:speed', (string) self::RECIPE['avif']['speed']);
            $out->setOption('heic:chroma', self::RECIPE['avif']['chroma']);
        } elseif ($format === 'webp') {
            $out->setImageFormat('webp');
            $out->setOption('webp:method', (string) self::RECIPE['webp']['method']);
        } else {
            $out->setImageFormat('jpeg');
            $out->setOption('jpeg:sampling-factor', self::RECIPE['jpg']['sampling']);
            // Прогрессивный JPEG проявляется целиком и уточняется, а не грузится полосой сверху вниз
            $out->setInterlaceScheme(Imagick::INTERLACE_JPEG);
        }
        // Пишем рядом и переименовываем: сайт не должен отдать половину картинки
        $part = $target.'.part';
        $out->writeImage(($format === 'jpg' ? 'jpeg' : $format).':'.$part);
        $out->clear();
        if (! is_file($part) || ! rename($part, $target)) {
            throw new RuntimeException('Не получилось записать копию фото — возможно, на сервере кончилось место.');
        }
    }
}
