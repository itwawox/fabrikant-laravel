<?php

namespace App\Support;

/**
 * Проверка разметки разделов, перенесённая из старого сайта (inc/_menu.php, menu_sections).
 * Нужна при импорте старых данных и при переносе разметки в новое меню: кривые записи (нет страницы,
 * рамка за листом) пропускаются, рамки обрезаются по листу, адреса не повторяются.
 */
class MenuSections
{
    /**
     * @param  array<int, mixed>  $sections  записи вида ['page' => 1, 'title' => 'Супы', 'boxes' => [[x, y, w, h], …]]
     * @return list<array{id: string, page: int, title: string, boxes: list<array{0: float, 1: float, 2: float, 3: float}>}>
     */
    public static function normalize(array $sections, int $pagesCount): array
    {
        $used = [];
        $result = [];
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }
            $page = (int) ($section['page'] ?? 0);
            $title = trim((string) ($section['title'] ?? ''));
            if ($page < 1 || $page > $pagesCount || $title === '' || empty($section['boxes']) || ! is_array($section['boxes'])) {
                continue;
            }
            $boxes = [];
            foreach ($section['boxes'] as $box) {
                if (! is_array($box) || count($box) !== 4) {
                    continue;
                }
                [$x, $y, $w, $h] = array_map('floatval', array_values($box));
                $x = max(0, min(1, $x));
                $y = max(0, min(1, $y));
                $w = min(1 - $x, $w);
                $h = min(1 - $y, $h);
                // Совсем узкая рамка — случайный клик мышью, а не раздел
                if ($w > 0.02 && $h > 0.02) {
                    $boxes[] = [$x, $y, $w, $h];
                }
            }
            if (! $boxes) {
                continue;
            }
            $id = MenuSlug::unique(MenuSlug::make($title), $used);
            $result[] = ['id' => $id, 'page' => $page, 'title' => $title, 'boxes' => $boxes];
        }

        // По страницам; внутри страницы — в том порядке, в каком записаны (так их читают).
        // Сортировка в PHP 8 устойчивая, поэтому порядок внутри страницы сохраняется.
        usort($result, fn (array $a, array $b): int => $a['page'] <=> $b['page']);

        return $result;
    }
}
