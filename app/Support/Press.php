<?php

namespace App\Support;

use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use Illuminate\Support\Facades\Storage;

/**
 * Вёрстка галереи «Фотохроника» (и фото в очерке «О ресторане»). Перенесено из старого сайта
 * (inc/_press.php) один в один: та же раскладка колонок и тот же HTML.
 *
 * Фото здесь — массивы как в старом data/gallery.php: photo — id, size, alt, caption;
 * entry — w, h и копии: avif/webp (ширина => адрес) и jpg (адрес).
 *
 * @phpstan-type Photo array{id: string, size: string, alt: ?string, caption: ?string}
 * @phpstan-type Entry array{w: int, h: int, avif: array<int, string>, webp: array<int, string>, jpg: string}
 * @phpstan-type Plate array{photo: Photo, entry: Entry, slot: string, rubric?: string}
 * @phpstan-type Column array{kind: string, plates: list<Plate>}
 */
class Press
{
    // Сколько места фото занимает на экране — по этому браузер выбирает файл нужной ширины
    private const SIZES = [
        'wide' => '(min-width: 1248px) 680px, (min-width: 700px) 56vw, 100vw',
        'inset' => '(min-width: 1248px) 420px, (min-width: 700px) 35vw, 100vw',
        'narrow' => '(min-width: 1248px) 482px, (min-width: 700px) 40vw, 100vw',
    ];

    /**
     * Рубрики с раскладкой по колонкам и все фото одним списком в порядке страницы
     * (в этом порядке их листает окно просмотра). Фото без готовых копий пропускаются.
     *
     * @return array{rubrics: array<string, array{title: string, lede: ?string, columns: list<Column>}>, shots: list<Plate>}
     */
    public static function gallery(): array
    {
        $rubrics = [];
        $shots = [];
        $all = GalleryRubric::with('photos')->orderBy('position')->get();
        foreach ($all as $rubric) {
            $photos = [];
            $build = [];
            foreach ($rubric->photos as $photo) {
                if ($entry = self::entry($photo)) {
                    $photos[] = self::photo($photo);
                    $build[$photo->slug] = $entry;
                }
            }
            if (! $photos) {
                continue;
            }
            // Стороны чередуются: в каждой второй рубрике узкая колонка стоит слева
            $columns = self::columns($photos, $build, count($rubrics) % 2 === 1);
            foreach ($columns as $column) {
                foreach ($column['plates'] as $plate) {
                    $plate['rubric'] = $rubric->title;
                    $shots[] = $plate;
                }
            }
            $rubrics[$rubric->key] = ['title' => $rubric->title, 'lede' => $rubric->lede, 'columns' => $columns];
        }

        return ['rubrics' => $rubrics, 'shots' => $shots];
    }

    /**
     * Копии фото с адресами от корня сайта; null — копий ещё нет.
     *
     * @return Entry|null
     */
    public static function entry(?GalleryPhoto $photo): ?array
    {
        $variants = $photo?->variants;
        if (! $photo || empty($variants['jpg']) || ! $photo->width || ! $photo->height) {
            return null;
        }
        $url = fn (string $path): string => Storage::disk('public')->url($path);
        $set = fn (string $format): array => array_map($url, $variants[$format] ?? []);

        return [
            'w' => $photo->width,
            'h' => $photo->height,
            'avif' => $set('avif'),
            'webp' => $set('webp'),
            'jpg' => $url($variants['jpg']),
        ];
    }

    /** @return Photo */
    private static function photo(GalleryPhoto $photo): array
    {
        return ['id' => $photo->slug, 'size' => $photo->size->value, 'alt' => $photo->alt, 'caption' => $photo->caption];
    }

    /**
     * Строка srcset из массива «ширина => адрес».
     *
     * @param  array<int, string>  $variants
     */
    public static function srcset(array $variants): string
    {
        $parts = [];
        foreach ($variants as $width => $url) {
            $parts[] = e($url).' '.(int) $width.'w';
        }

        return implode(', ', $parts);
    }

    /**
     * <picture> одной фотографии: AVIF и WebP на выбор браузера, JPEG — запасной вариант.
     * Ширина и высота в <img> заданы, чтобы страница не прыгала при загрузке и чтобы фото не растягивалось
     * шире своего настоящего размера (стили оставляют атрибут width в силе).
     *
     * @param  Entry  $entry
     */
    public static function picture(array $entry, ?string $alt, string $sizes, bool $lazy = true): string
    {
        $html = '<picture>';
        $full = empty($entry['webp']) ? 0 : max(array_keys($entry['webp']));
        foreach (['avif', 'webp'] as $type) {
            // Набор AVIF без самого крупного размера пропускаем: иначе браузер так и не взял бы фото в полном качестве
            if (! empty($entry[$type]) && max(array_keys($entry[$type])) >= $full) {
                $html .= '<source type="image/'.$type.'" srcset="'.self::srcset($entry[$type]).'" sizes="'.e($sizes).'">';
            }
        }

        return $html.'<img src="'.e($entry['jpg']).'" width="'.(int) $entry['w'].'" height="'.(int) $entry['h'].'"'
            .' alt="'.e($alt).'"'.($lazy ? ' loading="lazy"' : '').' decoding="async"></picture>';
    }

    /**
     * Раскладка рубрики в две газетные колонки: широкую и узкую.
     * В широкой сверху стоит главное фото (lead), в узкой — вертикальные (tall). Остальные фото
     * по очереди попадают в ту колонку, которая пока короче, поэтому колонки выходят примерно одной высоты,
     * сколько бы фотографий ни добавили. У каждой пластины slot — wide (во всю широкую колонку),
     * inset (малое фото в ней же) или narrow.
     *
     * @param  list<Photo>  $photos
     * @param  array<string, Entry>  $build
     * @return list<Column>
     */
    public static function columns(array $photos, array $build, bool $flip): array
    {
        // Доли ширины листа: широкая колонка — 7 из 12, малое фото в ней — 62% колонки, узкая — 5 из 12
        $share = ['wide' => 7, 'inset' => 4.34, 'narrow' => 5];
        $plates = ['wide' => [], 'narrow' => []];
        $height = ['wide' => 0, 'narrow' => 0];
        $rest = [];
        $lead = null;

        foreach ($photos as $photo) {
            if ($lead === null && $photo['size'] === 'lead') {
                $lead = $photo;
            } else {
                $rest[] = $photo;
            }
        }
        // Нет фото с пометкой lead — главным становится первое невертикальное
        if ($lead === null) {
            foreach ($rest as $i => $photo) {
                if ($photo['size'] !== 'tall') {
                    $lead = $photo;
                    array_splice($rest, $i, 1);
                    break;
                }
            }
        }

        $queue = [];
        if ($lead !== null) {
            $queue[] = [$lead, 'wide', 'wide'];
        }
        foreach ($rest as $photo) {
            if ($photo['size'] === 'tall') {
                $queue[] = [$photo, 'narrow', 'narrow'];
            }
        }
        foreach ($rest as $photo) {
            if ($photo['size'] !== 'tall') {
                $queue[] = [$photo, null, null];
            }
        }

        foreach ($queue as [$photo, $column, $slot]) {
            $entry = $build[$photo['id']];
            if ($column === null) {
                $column = $height['wide'] <= $height['narrow'] ? 'wide' : 'narrow';
                $slot = $column === 'wide' ? 'inset' : 'narrow';
            }
            // Высота фото в тех же долях плюс примерное место под подпись
            $height[$column] += $share[$slot] * $entry['h'] / $entry['w'] + 0.6;
            $plates[$column][] = ['photo' => $photo, 'entry' => $entry, 'slot' => $slot];
        }

        $columns = [];
        foreach ($flip ? ['narrow', 'wide'] : ['wide', 'narrow'] as $kind) {
            if ($plates[$kind]) {
                $columns[] = ['kind' => $kind, 'plates' => $plates[$kind]];
            }
        }

        return $columns;
    }

    /**
     * Пластина на странице: фото в рамке со ссылкой на полный кадр и подписью.
     * Без скрипта ссылка открывает JPEG; со скриптом — просмотр во всплывающем окне.
     *
     * @param  Plate  $plate
     */
    public static function plate(array $plate, bool $lazy = true): string
    {
        $photo = $plate['photo'];
        $entry = $plate['entry'];

        return '<figure class="plate plate--'.e($photo['size']).'" id="foto-'.e($photo['id']).'">'
            .'<a class="plate__link" href="'.e($entry['jpg']).'" data-shot="'.e($photo['id']).'">'
            .'<span class="plate__media" data-press="plate">'.self::picture($entry, $photo['alt'], self::SIZES[$plate['slot']], $lazy).'</span>'
            .'</a>'
            .'<figcaption class="plate__caption">'.e($photo['caption']).'</figcaption>'
            .'</figure>';
    }

    /**
     * Ступень пропорций кадра для окна просмотра (класс shot--r150 и т. п.).
     * Стилям нужно знать пропорции фото, чтобы вписать его в экран по высоте ещё до загрузки файла,
     * а передать точное число без атрибута style нельзя. Поэтому берём ближайшую ступень снизу.
     */
    public static function ratioStep(int $width, int $height): int
    {
        $ratio = $width * 100 / $height;
        $step = 56;
        foreach ([66, 74, 100, 133, 150] as $candidate) {
            if ($ratio >= $candidate) {
                $step = $candidate;
            }
        }

        return $step;
    }

    /**
     * Слайд окна просмотра. Фото показывается не крупнее своего настоящего размера.
     * В подписи — рубрика, из которой фото: лента в окне идёт через все рубрики подряд.
     *
     * @param  Plate  $plate
     */
    public static function shot(array $plate): string
    {
        $photo = $plate['photo'];
        $entry = $plate['entry'];
        $width = (int) $entry['w'];
        $sizes = '(min-width: '.$width.'px) '.$width.'px, 100vw';
        $rubric = isset($plate['rubric']) ? '<span class="shot__rubric">'.e($plate['rubric']).'</span> ' : '';

        return '<figure class="shot shot--r'.self::ratioStep($entry['w'], $entry['h']).'" data-shot="'.e($photo['id']).'">'
            .'<div class="shot__frame">'.self::picture($entry, $photo['alt'], $sizes).'</div>'
            .'<figcaption class="shot__caption">'.$rubric.e($photo['caption']).'</figcaption>'
            .'</figure>';
    }

    /**
     * Миниатюра в ленте окна просмотра: кнопка, которая переносит к фото. Картинка — самая лёгкая копия,
     * и грузится она, только когда окно открыли (у закрытого окна нет размеров, ленивые картинки ждут).
     *
     * @param  Plate  $plate
     */
    public static function thumb(array $plate, int $n): string
    {
        $photo = $plate['photo'];

        return '<button type="button" class="viewer__thumb" data-shot="'.e($photo['id']).'"'
            .' aria-label="Фото '.$n.': '.e($photo['caption']).'">'
            .self::picture($plate['entry'], '', '96px')
            .'</button>';
    }
}
