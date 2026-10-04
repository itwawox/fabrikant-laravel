<?php

use App\Models\Menu;
use App\Services\Menu\SectionEditor;

beforeEach(function () {
    $this->menu = Menu::factory()->create(['pages_count' => 3]);
    $this->editor = app(SectionEditor::class);
});

function sectionsOf(Menu $menu): array
{
    return $menu->sections()->with('boxes')->get()
        ->map(fn ($s) => [$s->page_number, $s->title, $s->slug, $s->slug_locked, $s->boxes->map->toBox()->all()])->all();
}

it('saves sections in reading order with slugs from titles', function () {
    $result = $this->editor->save($this->menu, [
        ['page' => 2, 'title' => 'Гриль', 'boxes' => [[0.1, 0.1, 0.5, 0.5]]],
        ['page' => 1, 'title' => ' Супы  и   бульоны ', 'boxes' => [[0.1, 0.2, 0.3, 0.4], [0.6, 0.2, 0.3, 0.4]]],
        ['page' => 1, 'title' => 'Салаты', 'boxes' => [[0.5, 0.5, 0.9, 0.9]]],
    ]);

    expect($result)->toBe(['saved' => 3])
        ->and(sectionsOf($this->menu))->toBe([
            [1, 'Супы и бульоны', 'supy-i-bulony', false, [[0.1, 0.2, 0.3, 0.4], [0.6, 0.2, 0.3, 0.4]]],
            // Рамка за краем листа обрезается
            [1, 'Салаты', 'salaty', false, [[0.5, 0.5, 0.5, 0.5]]],
            [2, 'Гриль', 'gril', false, [[0.1, 0.1, 0.5, 0.5]]],
        ]);
});

it('skips sections without a title, page or real box and keeps slugs unique', function () {
    $this->editor->save($this->menu, [
        ['page' => 1, 'title' => 'Салаты', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 2, 'title' => 'Салаты', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 1, 'title' => '', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 9, 'title' => 'Нет страницы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 1, 'title' => 'Клик мышью', 'boxes' => [[0.1, 0.1, 0.01, 0.3]]],
    ]);

    expect(array_column(sectionsOf($this->menu), 2))->toBe(['salaty', 'salaty-2']);
});

it('asks before changing the address of an existing section and can keep the old one', function () {
    $this->editor->save($this->menu, [['page' => 1, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]]);
    $id = $this->menu->sections()->value('id');
    $renamed = [['id' => $id, 'page' => 1, 'title' => 'Супы и бульоны', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]];

    expect($this->editor->save($this->menu, $renamed))
        ->toBe(['changes' => [['title' => 'Супы и бульоны', 'from' => 'supy', 'to' => 'supy-i-bulony']]])
        ->and($this->menu->sections()->value('title'))->toBe('Супы');

    $this->editor->save($this->menu, $renamed, SectionEditor::KEEP);
    expect(sectionsOf($this->menu)[0])->toBe([1, 'Супы и бульоны', 'supy', true, [[0.1, 0.1, 0.3, 0.3]]]);

    // Закреплённый адрес дальше не спрашивается и не меняется
    $renamed[0]['slug'] = 'supy';
    $renamed[0]['slug_locked'] = true;
    $renamed[0]['title'] = 'Первые блюда';
    expect($this->editor->save($this->menu, $renamed))->toBe(['saved' => 1])
        ->and($this->menu->sections()->value('slug'))->toBe('supy');
});

it('changes the address when asked to', function () {
    $this->editor->save($this->menu, [['page' => 1, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]]);
    $id = $this->menu->sections()->value('id');

    $this->editor->save($this->menu, [['id' => $id, 'page' => 1, 'title' => 'Бульоны', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]], SectionEditor::CHANGE);

    expect($this->menu->sections()->sole()->only(['id', 'slug']))->toBe(['id' => $id, 'slug' => 'bulony']);
});

it('swaps two sections without tripping the unique slug index and deletes removed ones', function () {
    $this->editor->save($this->menu, [
        ['page' => 1, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 1, 'title' => 'Салаты', 'boxes' => [[0.1, 0.5, 0.3, 0.3]]],
        ['page' => 2, 'title' => 'Гриль', 'boxes' => [[0.1, 0.5, 0.3, 0.3]]],
    ]);
    [$soup, $salad] = $this->menu->sections()->orderBy('position')->pluck('id')->all();

    $this->editor->save($this->menu, [
        ['id' => $salad, 'page' => 1, 'title' => 'Салаты', 'boxes' => [[0.1, 0.5, 0.3, 0.3]]],
        ['id' => $soup, 'page' => 1, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
    ]);

    expect(sectionsOf($this->menu))->toHaveCount(2)
        ->and($this->menu->sections()->orderBy('position')->pluck('slug', 'id')->all())->toBe([$salad => 'salaty', $soup => 'supy']);
});

it('lets a custom pinned address win over a title slug', function () {
    $this->editor->save($this->menu, [
        ['page' => 1, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]],
        ['page' => 1, 'title' => 'Бульоны', 'slug' => 'Supy', 'slug_locked' => true, 'boxes' => [[0.1, 0.5, 0.3, 0.3]]],
    ]);

    expect(array_column(sectionsOf($this->menu), 2))->toBe(['supy-2', 'supy']);
});
