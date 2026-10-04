<?php

namespace App\Services\Menu;

use App\Models\Menu;
use App\Models\MenuSection;
use App\Support\MenuSlug;
use Illuminate\Support\Facades\DB;

/**
 * Сохранение разметки разделов из редактора в админке (замена tools/menu-sections.php старого сайта).
 *
 * Адрес раздела (/menu#supy) печатают в QR-кодах, поэтому если переименование меняет адрес уже
 * существующего раздела, сохранение сначала спрашивает: оставить старый адрес (закрепить) или сменить.
 */
class SectionEditor
{
    public const KEEP = 'keep';

    public const CHANGE = 'change';

    /**
     * @param  array<int, mixed>  $input  разделы в порядке чтения: id?, page, title, slug?, slug_locked?, boxes [[x, y, w, h], …]
     * @param  self::KEEP|self::CHANGE|null  $decision  ответ на вопрос о сменившихся адресах
     * @return array{saved?: int, changes?: list<array{title: string, from: string, to: string}>}
     */
    public function save(Menu $menu, array $input, ?string $decision = null): array
    {
        $sections = $this->clean($input, $menu->pages_count);
        $old = $menu->sections()->pluck('slug', 'id')->all();

        $slugs = $this->slugs($sections);
        $changes = [];
        foreach ($sections as $i => $section) {
            $was = $old[$section['id'] ?? 0] ?? null;
            if ($was !== null && ! $section['slug_locked'] && $was !== $slugs[$i]) {
                $changes[$i] = ['title' => $section['title'], 'from' => $was, 'to' => $slugs[$i]];
            }
        }

        if ($changes && $decision === null) {
            return ['changes' => array_values($changes)];
        }
        if ($changes && $decision === self::KEEP) {
            // Старые адреса закрепляем — и пересчитываем остальные, чтобы не было повторов
            foreach (array_keys($changes) as $i) {
                $sections[$i]['slug'] = $changes[$i]['from'];
                $sections[$i]['slug_locked'] = true;
            }
            $slugs = $this->slugs($sections);
        }

        DB::transaction(function () use ($menu, $sections, $slugs) {
            $keep = array_filter(array_column($sections, 'id'));
            $menu->sections()->whereNotIn('id', $keep)->delete();

            foreach ($sections as $position => $section) {
                $model = $section['id'] ? $menu->sections()->find($section['id']) : null;
                $model ??= new MenuSection(['menu_id' => $menu->id]);
                // Уникальность адреса в меню проверяет база: на время пересохранения снимаем адрес,
                // иначе обмен адресами двух разделов упрётся в индекс
                $model->fill([
                    'page_number' => $section['page'],
                    'title' => $section['title'],
                    'slug' => $model->exists ? 'tmp-'.$model->id : 'tmp-new-'.$position,
                    'slug_locked' => $section['slug_locked'],
                    'position' => $position,
                ])->save();
                $model->boxes()->delete();
                foreach ($section['boxes'] as $boxPosition => [$x, $y, $w, $h]) {
                    $model->boxes()->create(compact('x', 'y', 'w', 'h') + ['position' => $boxPosition]);
                }
            }
            foreach ($menu->sections()->orderBy('position')->get() as $position => $model) {
                $model->update(['slug' => $slugs[$position]]);
            }
        });

        return ['saved' => count($sections)];
    }

    /**
     * Разделы, которые можно сохранить: со страницей, названием и хотя бы одной рамкой не меньше 2 % листа.
     * Порядок — по страницам, внутри страницы — как в списке (так их читают на телефоне).
     *
     * @param  array<int, mixed>  $input
     * @return list<array{id: int|null, page: int, title: string, slug: string, slug_locked: bool, boxes: list<array{0: float, 1: float, 2: float, 3: float}>}>
     */
    private function clean(array $input, int $pages): array
    {
        $result = [];
        foreach ($input as $section) {
            if (! is_array($section)) {
                continue;
            }
            $page = (int) ($section['page'] ?? 0);
            $title = mb_substr(trim((string) preg_replace('/\s+/u', ' ', (string) ($section['title'] ?? ''))), 0, 60);
            $boxes = [];
            foreach (is_array($section['boxes'] ?? null) ? $section['boxes'] : [] as $box) {
                if (! is_array($box) || count($box) !== 4) {
                    continue;
                }
                [$x, $y, $w, $h] = array_map(fn ($v): float => round(max(0, min(1, (float) $v)), 3), array_values($box));
                $w = round(min(1 - $x, $w), 3);
                $h = round(min(1 - $y, $h), 3);
                if ($w > 0.02 && $h > 0.02) {
                    $boxes[] = [$x, $y, $w, $h];
                }
            }
            if ($page < 1 || $page > $pages || $title === '' || ! $boxes) {
                continue;
            }
            // Закреплённый адрес без букв ничего не закрепляет — тогда адрес из названия
            $locked = (bool) ($section['slug_locked'] ?? false) && preg_match('/[a-z0-9]/i', (string) ($section['slug'] ?? ''));
            $result[] = [
                'id' => is_numeric($section['id'] ?? null) ? (int) $section['id'] : null,
                'page' => $page,
                'title' => $title,
                // Свой адрес — только латиница, цифры и дефис, как у адресов из названий
                'slug' => $locked ? MenuSlug::make((string) ($section['slug'] ?? '')) : '',
                'slug_locked' => $locked,
                'boxes' => $boxes,
            ];
        }
        usort($result, fn (array $a, array $b): int => $a['page'] <=> $b['page']);

        return $result;
    }

    /**
     * Адреса разделов: закреплённые занимают свои первыми, остальные — из названия, без повторов.
     *
     * @param  list<array{slug: string, slug_locked: bool, title: string}>  $sections
     * @return array<int, string>
     */
    private function slugs(array $sections): array
    {
        $used = [];
        $slugs = [];
        foreach ($sections as $i => $section) {
            if ($section['slug_locked']) {
                $slugs[$i] = MenuSlug::unique($section['slug'], $used);
            }
        }
        foreach ($sections as $i => $section) {
            $slugs[$i] ??= MenuSlug::unique(MenuSlug::make($section['title']), $used);
        }
        ksort($slugs);

        return $slugs;
    }
}
