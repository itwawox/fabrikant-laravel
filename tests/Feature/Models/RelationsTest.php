<?php

use App\Enums\MenuStatus;
use App\Enums\PhotoSize;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuSection;
use App\Models\MenuSectionBox;
use App\Models\Promo;

it('links a menu to its pages, sections and boxes in order', function () {
    $menu = Menu::factory()->create();
    MenuPage::factory()->for($menu)->create(['number' => 2]);
    MenuPage::factory()->for($menu)->create(['number' => 1]);
    $second = MenuSection::factory()->for($menu)->create(['position' => 1]);
    $first = MenuSection::factory()->for($menu)->create(['position' => 0]);
    MenuSectionBox::factory()->for($first, 'section')->create(['position' => 1, 'x' => 0.5]);
    MenuSectionBox::factory()->for($first, 'section')->create(['position' => 0, 'x' => 0.05]);

    expect($menu->pages->pluck('number')->all())->toBe([1, 2])
        ->and($menu->sections->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($first->boxes->first()->toBox())->toBe([0.05, 0.1, 0.4, 0.3]);
});

it('casts menu status and finds the published menu', function () {
    Menu::factory()->create();
    $published = Menu::factory()->create(['status' => MenuStatus::Published]);

    expect(Menu::published()->sole()->is($published))->toBeTrue()
        ->and($published->status->getLabel())->toBe('Опубликовано');
});

it('deletes pages, sections and boxes together with the menu', function () {
    $section = MenuSection::factory()->create();
    MenuSectionBox::factory()->for($section, 'section')->create();

    $section->menu->delete();

    expect(MenuSection::count())->toBe(0)
        ->and(MenuSectionBox::count())->toBe(0);
});

it('knows whether a promo has a schedule', function () {
    expect(Promo::factory()->create()->hasSchedule())->toBeTrue()
        ->and(Promo::factory()->create(['days' => null])->hasSchedule())->toBeFalse();
});

it('links gallery photos to rubrics', function () {
    $rubric = GalleryRubric::factory()->create();
    GalleryPhoto::factory()->for($rubric, 'rubric')->create(['size' => PhotoSize::Lead]);

    expect($rubric->photos)->toHaveCount(1)
        ->and($rubric->photos->first()->size)->toBe(PhotoSize::Lead);
});
