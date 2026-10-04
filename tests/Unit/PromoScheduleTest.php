<?php

use App\Services\PromoSchedule;

// Случаи общие с node --test (tests/js/promos.test.mjs): логика PHP и JS обязана совпадать
function promoCases(): array
{
    return json_decode(file_get_contents(__DIR__.'/../fixtures/promos-cases.json'), true);
}

it('shows the same promos as the script', function (array $case) {
    $fixture = promoCases();
    $rules = array_merge($fixture['rules'], $case['rules'] ?? [], ['promos' => $case['promos'] ?? $fixture['rules']['promos']]);
    $now = new DateTimeImmutable($case['at'], new DateTimeZone(PromoSchedule::TZ));

    $items = (new PromoSchedule)->items($rules, $now);

    expect(array_map(fn (array $i) => "{$i['kind']}:{$i['id']}:{$i['label']}", $items))->toBe($case['expect']);
})->with(fn () => array_combine(
    array_column(promoCases()['cases'], 'name'),
    array_map(fn (array $case) => [$case], promoCases()['cases']),
));
