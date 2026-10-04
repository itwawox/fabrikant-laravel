<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function show(): View
    {
        $menu = Menu::published()->with(['pages', 'sections.boxes'])->latest('published_at')->first();
        abort_if($menu === null, 503, 'Меню обновляется');

        $disk = Storage::disk('public');
        $dir = $menu->storage_dir;
        $count = $menu->pages->count();
        $url = fn (int|string $n, string $suffix): string => $disk->url("{$dir}/{$n}-{$suffix}");

        // Газете, которую листают (3D), нужны адреса страниц двух размеров: лёгкая копия и полная, для увеличения.
        // Вместо {n} скрипт подставляет номер страницы. Если лёгких копий нет хотя бы у одной страницы — берём JPEG.
        // Миниатюры для содержания и копии 3200 px для увеличения необязательны.
        $webp = $thumbs = $zoom = true;
        foreach ($menu->pages as $page) {
            $n = $page->number;
            $webp = $webp && $disk->exists("{$dir}/{$n}-1600.webp") && $disk->exists("{$dir}/{$n}-1000.webp");
            $thumbs = $thumbs && $disk->exists("{$dir}/{$n}-200.webp");
            $zoom = $zoom && $disk->exists("{$dir}/{$n}-3200.webp");
        }

        // Скачивают лёгкую копию PDF (3–5 МБ). Исходник типографии (~40 МБ) гостям не отдаётся
        $pdfBytes = $menu->web_pdf_path && $disk->exists($menu->web_pdf_path) ? $disk->size($menu->web_pdf_path) : 0;

        // Разделы на страницах: кадры на телефоне, оглавление и адреса вида #supy
        $byPage = array_fill(1, max($count, 1), []);
        foreach ($menu->sections as $section) {
            /** @var MenuSection $section */
            // Раздел без рамок (только что добавлен в админке) или на странице, которой нет, гостю не показываем —
            // как и старый сайт: кадр вырезать не из чего
            if ($section->boxes->isEmpty() || ! isset($byPage[$section->page_number])) {
                continue;
            }
            $byPage[$section->page_number][] = [
                'id' => $section->slug,
                'title' => $section->title,
                'boxes' => $section->boxes->map->toBox()->all(),
            ];
        }

        $first = $menu->pages->first();

        return view('pages.menu', [
            'menu' => $menu,
            'pages' => $menu->pages->pluck('title', 'number')->all(),
            'count' => $count,
            'pageW' => $first->width ?? 1600,
            'pageH' => $first->height ?? 2263,
            'urls' => fn (int $n): array => ['jpg' => $url($n, '1600.jpg'), 'webp' => $url($n, '1600.webp'), 'webp1000' => $url($n, '1000.webp')],
            'webp' => $webp,
            'thumbs' => $thumbs,
            'zoom' => $zoom,
            'tiny' => $url('{n}', '200.webp'),
            'zoomSrc' => $url('{n}', '3200.webp'),
            'bookLow' => $url('{n}', $webp ? '1000.webp' : '1600.jpg'),
            'bookHigh' => $url('{n}', $webp ? '1600.webp' : '1600.jpg'),
            'pdf' => $pdfBytes ? $disk->url((string) $menu->web_pdf_path) : null,
            // Под этим именем файл сохранится у гостя: «Меню ФабрикантЪ, осень 2026.pdf», а не служебное имя
            'pdfFile' => 'Меню ФабрикантЪ, '.mb_strtolower($menu->season).'.pdf',
            'pdfSize' => $pdfBytes ? number_format($pdfBytes / 1048576, 1, ',', '').' МБ' : '',
            'byPage' => $byPage,
        ]);
    }

    /** Старая ссылка на PDF меню (/uploads/….pdf) — на лёгкий PDF опубликованного меню. */
    public function legacyPdf(): RedirectResponse
    {
        $path = Menu::published()->latest('published_at')->value('web_pdf_path');
        abort_if($path === null, 404);

        return redirect(Storage::disk('public')->url($path), 301);
    }
}
