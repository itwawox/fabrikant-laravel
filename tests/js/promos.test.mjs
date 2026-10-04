// Тесты плашки «Сейчас действует»: node --test tests/js/
// Случаи — общие с Pest (tests/fixtures/promos-cases.json): логика PHP и JS обязана совпадать.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// promos-now.js — обычный скрипт для браузера, а package.json проекта объявляет .js модулями.
// Поэтому выполняем его в песочнице как CommonJS: он сам отдаёт promoItems через module.exports
const load = (path) => {
    const module = { exports: {} };
    vm.runInNewContext(readFileSync(new URL(path, import.meta.url), 'utf8'), { module });
    return module.exports;
};
const { promoItems } = load('../../public/assets/js/promos-now.js');
const fixture = JSON.parse(readFileSync(new URL('../fixtures/promos-cases.json', import.meta.url), 'utf8'));

// «2026-10-05 14:00» → {y, m, d, min}, как nowIn() в promos-now.js
const at = (s) => {
    const [, y, m, d, h, min] = s.match(/^(\d+)-(\d+)-(\d+) (\d+):(\d+)$/).map(Number);
    return { y, m, d, min: h * 60 + min };
};
// Array.from — массив из песочницы с чужим прототипом, deepStrictEqual его иначе не примет
const brief = (items) => Array.from(items, (i) => `${i.kind}:${i.id}:${i.label}`);

for (const c of fixture.cases) {
    test(c.name, () => {
        const rules = { ...fixture.rules, ...(c.rules ?? {}), promos: c.promos ?? fixture.rules.promos };
        assert.deepEqual(brief(promoItems(rules, at(c.at))), c.expect);
    });
}
