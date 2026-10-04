// Сравнение нового сайта со старым (эталон — тег legacy-2026-10-04): HTML, скриншоты на 4 ширинах, работа скриптов.
// Запуск: npm run visual (нужны оба сайта локально и Google Chrome).
//   OLD=http://fabrikant.test NEW=http://fabrikant-laravel.test WIDTHS=390,768,1209,1440 npm run visual
// Результат — в storage/visual/: снимки *-old.png / *-new.png, разница *-diff.png, отчёт report.txt.
import { chromium } from 'playwright-core';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import fs from 'node:fs';

const OLD = process.env.OLD || 'http://fabrikant.test';
const NEW = process.env.NEW || 'http://fabrikant-laravel.test';
const widths = (process.env.WIDTHS || '390,768,1209,1440').split(',').map(Number);
const out = 'storage/visual';
const pages = [['index.php', '/'], ['about.php', '/about'], ['menu.php', '/menu'], ['gallery.php', '/gallery'],
    ['calendar.php', '/promos'], ['contacts.php', '/contacts'], ['404.php', '/no-such-page']];
// Счётчик, карта и видео к вёрстке не относятся и делают снимки неповторяемыми
const blocked = /mc\.yandex\.ru|yandex\.ru\/map-widget|\.mp4/;
fs.mkdirSync(out, { recursive: true });

// HTML без того, что меняется законно: метки версий, адреса страниц и картинок, хост, отладочный скрипт Boost
function normalize(html) {
    let s = html.replace(/<script id="browser-logger-active">[\s\S]*?<\/script>/, '')
        .replace(/\?v=\d+/g, '')
        .replaceAll(OLD.replace('http:', 'https:'), 'HOST').replaceAll(OLD, 'HOST').replaceAll(NEW, 'HOST')
        .replace(/\s*<link rel="canonical"[^>]*>/, '')
        .replace(/\/storage\/menus\/\d+-[a-z0-9]+\//g, 'MENU/')
        .replace(/\/assets\/img\/menu\/[a-z0-9_]+-(\{n\}|\d+)(-1000\.webp|-200\.webp|-3200\.webp|\.webp|\.jpg)/g,
            (m, n, suffix) => `MENU/${n}${{ '.webp': '-1600.webp', '.jpg': '-1600.jpg' }[suffix] ?? suffix}`)
        .replace(/\/uploads\/[a-z0-9_]+-web\.pdf/g, 'MENU/web.pdf')
        .replaceAll('/assets/img/akcii/', '/storage/promos/').replaceAll('/assets/img/press/', '/storage/gallery/');
    for (const [a, b] of [['/index.php', '/'], ['/about.php', '/about'], ['/menu.php', '/menu'], ['/gallery.php', '/gallery'],
        ['/calendar.php', '/promos'], ['/contacts.php', '/contacts']]) {
        s = s.replaceAll(a, b);
    }
    // Пробелы между тегами на вёрстку не влияют
    return s.replace(/\s+/g, ' ');
}

const browser = await chromium.launch({ channel: 'chrome' });
async function shot(url, width, file) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 }, reducedMotion: 'reduce', deviceScaleFactor: 1 });
    const page = await ctx.newPage();
    await page.route(blocked, (r) => r.abort());
    await page.goto(url, { waitUntil: 'networkidle' });
    // Прокрутка до низа — чтобы догрузились ленивые картинки
    await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); }
        window.scrollTo(0, 0);
    });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
    await page.screenshot({ path: file, fullPage: true, animations: 'disabled' });
    await ctx.close();
}

const report = [];
let failed = false;
for (const [oldPath, newPath] of pages) {
    const name = oldPath.replace('.php', '');
    const [a, b] = await Promise.all([fetch(`${OLD}/${oldPath}`).then((r) => r.text()), fetch(NEW + newPath).then((r) => r.text())]);
    const same = normalize(a) === normalize(b);
    failed ||= !same;
    report.push(`html ${name}: ${same ? 'same' : 'DIFFERENT'}`);
    for (const w of widths) {
        const file = `${out}/${name}-${w}`;
        await shot(`${OLD}/${oldPath}`, w, `${file}-old.png`);
        await shot(NEW + newPath, w, `${file}-new.png`);
        const pa = PNG.sync.read(fs.readFileSync(`${file}-old.png`));
        const pb = PNG.sync.read(fs.readFileSync(`${file}-new.png`));
        if (pa.width !== pb.width || pa.height !== pb.height) {
            failed = true;
            report.push(`  ${w}px: size differs ${pa.width}x${pa.height} vs ${pb.width}x${pb.height}`);
            continue;
        }
        const diff = new PNG({ width: pa.width, height: pa.height });
        const n = pixelmatch(pa.data, pb.data, diff.data, pa.width, pa.height, { threshold: 0.1 });
        if (n) {
            failed = true;
            fs.writeFileSync(`${file}-diff.png`, PNG.sync.write(diff));
        }
        report.push(`  ${w}px: ${pa.width}x${pa.height}, differing pixels ${n}`);
    }
}
await browser.close();
fs.writeFileSync(`${out}/report.txt`, report.join('\n') + '\n');
console.log(report.join('\n'));
process.exit(failed ? 1 : 0);
