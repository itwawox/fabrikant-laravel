<?php

namespace App\Services\Menu;

use App\Models\Menu;
use Illuminate\Support\Facades\DB;

/**
 * Подписи страниц и разметка разделов. Меню выходит раз в пару месяцев и обычно сохраняет вёрстку, поэтому
 * новое меню получает их от опубликованного — остаётся проверить съехавшие рамки.
 */
class MenuMarkup
{
    /** Перенос из опубликованного меню, если в нём столько же страниц. Своих разделов меню не теряет. */
    public function carryOver(Menu $menu): bool
    {
        $from = Menu::published()->whereKeyNot($menu->getKey())->latest('published_at')->first();
        if ($from === null || $from->pages_count !== $menu->pages_count || $menu->sections()->exists()) {
            return false;
        }

        $this->copy($from, $menu);

        return true;
    }

    /** Копия подписей страниц и разделов с рамками из $from в $to (разделы $to заменяются). */
    public function copy(Menu $from, Menu $to): void
    {
        DB::transaction(function () use ($from, $to) {
            foreach ($from->pages as $page) {
                $to->pages()->where('number', $page->number)->update(['title' => $page->title]);
            }

            $to->sections()->delete();
            foreach ($from->sections()->with('boxes')->get() as $section) {
                $copy = $to->sections()->create($section->only(['page_number', 'title', 'slug', 'slug_locked', 'position']));
                foreach ($section->boxes as $box) {
                    $copy->boxes()->create($box->only(['x', 'y', 'w', 'h', 'position']));
                }
            }
        });
    }
}
