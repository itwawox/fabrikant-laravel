<?php

namespace App\Support;

/**
 * Адрес раздела меню латиницей: «Рыба и морепродукты» → «ryba-i-moreprodukty». По нему раздел открывается
 * ссылкой (/menu#supy) — для QR на столах, соцсетей и рассылок. Перенесено один в один из старого сайта
 * (inc/_menu.php, menu_slug): адреса уже напечатаны, менять правило нельзя.
 */
class MenuSlug
{
    private const MAP = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
    ];

    public static function make(string $title): string
    {
        $slug = strtr(mb_strtolower($title), self::MAP);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        // Пустой или похожий на адрес страницы («page-3») — с приставкой, чтобы не спутать
        return $slug === '' || preg_match('/^page-\d+$/', $slug) ? 'razdel-'.$slug : $slug;
    }

    /**
     * Адрес, которого ещё нет в $used: два раздела «Салаты» получают «salaty» и «salaty-2».
     *
     * @param  array<string, true>  $used  занятые адреса; новый добавляется сюда же
     */
    public static function unique(string $slug, array &$used): string
    {
        $id = $slug;
        for ($i = 2; isset($used[$id]); $i++) {
            $id = $slug.'-'.$i;
        }
        $used[$id] = true;

        return $id;
    }
}
