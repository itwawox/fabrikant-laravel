<?php

use App\Support\MenuSections;
use App\Support\MenuSlug;
use App\Support\Plural;

// Эталон снят со старого сайта: на эти адреса напечатаны QR-коды
function legacyExpected(): array
{
    return json_decode(file_get_contents(__DIR__.'/../fixtures/legacy/expected-sections.json'), true);
}

it('makes the same slug as the old site', function (string $title, string $slug) {
    expect(MenuSlug::make($title))->toBe($slug);
})->with(fn () => legacyExpected()['slugs']);

it('adds a suffix to repeated slugs', function () {
    $used = [];

    expect(MenuSlug::unique('salaty', $used))->toBe('salaty')
        ->and(MenuSlug::unique('salaty', $used))->toBe('salaty-2')
        ->and(MenuSlug::unique('salaty', $used))->toBe('salaty-3');
});

it('normalizes the autumn 2026 menu exactly like the old site', function () {
    $menu = require __DIR__.'/../fixtures/legacy/data/menu.php';

    $sections = MenuSections::normalize($menu['sections'], count($menu['pages']));

    expect($sections)->toHaveCount(35)
        ->and(json_encode($sections, JSON_PRESERVE_ZERO_FRACTION))
        ->toBe(json_encode(legacyExpected()['sections'], JSON_PRESERVE_ZERO_FRACTION));
});

it('skips broken sections and clamps boxes to the sheet', function () {
    $sections = MenuSections::normalize([
        ['page' => 3, 'title' => 'Супы', 'boxes' => [[0.1, 0.1, 0.2, 0.2]]],
        ['page' => 1, 'title' => 'Пиво', 'boxes' => [[0.1, 0.1, 0.2, 0.2]]],
        ['page' => 9, 'title' => 'Нет такой страницы', 'boxes' => [[0.1, 0.1, 0.2, 0.2]]],
        ['page' => 1, 'title' => '  ', 'boxes' => [[0.1, 0.1, 0.2, 0.2]]],
        ['page' => 1, 'title' => 'Случайный клик', 'boxes' => [[0.1, 0.1, 0.01, 0.2]]],
        ['page' => 1, 'title' => 'Супы', 'boxes' => [[0.9, -0.2, 0.5, 0.5], [1, 2]]],
    ], 3);

    // Адреса раздаются в порядке записи, потом разделы сортируются по страницам
    expect(array_column($sections, 'id'))->toBe(['pivo', 'supy-2', 'supy'])
        ->and(array_column($sections, 'page'))->toBe([1, 1, 3])
        ->and($sections[1]['boxes'])->toHaveCount(1)
        ->and($sections[1]['boxes'][0])->toEqualWithDelta([0.9, 0.0, 0.1, 0.5], 1e-9);
});

it('declines Russian nouns after numbers', function (int $n, string $word) {
    expect(Plural::ru($n, 'день', 'дня', 'дней'))->toBe($word);
})->with([[1, 'день'], [2, 'дня'], [5, 'дней'], [11, 'дней'], [14, 'дней'], [21, 'день'], [22, 'дня'], [70, 'дней'], [111, 'дней']]);
