<?php

namespace App\Support;

/**
 * Разметка страницы «Меню», перенесённая из старого сайта (menu.php) один в один.
 */
class MenuView
{
    // Прозрачная точка: её получает <picture> там, где картинка не видна (лист на телефоне, кадр на ПК), —
    // так браузер не качает то, что всё равно скрыто
    public const BLANK = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * Лист меню картинкой: WebP двух ширин (или JPEG); $blank — условие media, при котором грузить не нужно.
     *
     * @param  array{jpg: string, webp: string, webp1000: string}  $urls
     */
    public static function picture(array $urls, bool $webp, string $sizes, string $attrs, string $blank): string
    {
        return '<picture>'
            .($blank ? '<source media="'.$blank.'" srcset="'.self::BLANK.'">' : '')
            .($webp ? '<source type="image/webp" srcset="'.$urls['webp1000'].' 1000w, '.$urls['webp'].' 1600w" sizes="'.$sizes.'">' : '')
            .'<img src="'.$urls['jpg'].'" '.$attrs.'></picture>';
    }

    /** Рамка раздела на листе — переменными для стилей (доли листа). */
    public static function boxVars(float $x, float $y, float $w, float $h): string
    {
        return sprintf('--x:%.3F;--y:%.3F;--w:%.3F;--h:%.3F', $x, $y, $w, $h);
    }

    // Описание страницы для незрячих и поисковиков: «Меню, страница 3: горячее и гриль»
    public static function alt(int $n, ?string $title): string
    {
        return 'Меню ресторана ФабрикантЪ, страница '.$n.': '.mb_strtolower((string) $title);
    }
}
