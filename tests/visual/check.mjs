// Проверка работы скриптов нового сайта в настоящем браузере: ошибки в консоли, ссылка из QR-кода
// (/menu.php#supy), окно увеличения на телефоне, газета 3D на ПК. Запуск: npm run visual:check
import { chromium } from 'playwright-core';
const base = process.env.NEW || 'http://fabrikant-laravel.test';
const browser = await chromium.launch({ channel: 'chrome' });
const out = [];
const blocked = /mc\.yandex\.ru|yandex\.ru\/map-widget|\.mp4/;

// 1. Ошибки в консоли на всех страницах (ПК и телефон)
for (const width of [390, 1440]) {
    for (const path of ['/', '/about', '/menu', '/gallery', '/promos', '/contacts']) {
        const ctx = await browser.newContext({ viewport: { width, height: 900 } });
        const page = await ctx.newPage();
        const errors = [];
        // Метрика, карта и видео заблокированы ниже — их «Failed to load resource» ошибкой сайта не считаем
        page.on('console', (m) => { if (m.type() === 'error' && !/browser-logs|yandex/.test(m.text()) && !blocked.test(m.location().url)) errors.push(m.text()); });
        page.on('pageerror', (e) => errors.push(String(e)));
        await page.route(blocked, (r) => r.abort());
        await page.goto(base + path, { waitUntil: 'networkidle' });
        out.push(`console ${width} ${path}: ${errors.length ? errors.join(' | ') : 'ok'}`);
        await ctx.close();
    }
}

// 2. Ссылка из QR-кода: старый адрес с якорем раздела
for (const width of [390, 1440]) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 } });
    const page = await ctx.newPage();
    await page.goto(base + '/menu.php#supy', { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    const r = await page.evaluate(() => {
        const el = document.getElementById('supy');
        const box = el.getBoundingClientRect();
        return { href: location.href, top: Math.round(box.top), visible: box.top < innerHeight && box.bottom > 0 };
    });
    out.push(`QR ${width}: ${JSON.stringify(r)}`);
    await ctx.close();
}

// 3. Окно увеличения (телефон) и газета 3D (ПК)
{
    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await ctx.newPage();
    await page.goto(base + '/menu', { waitUntil: 'networkidle' });
    await page.locator('.menu-sec__crop').first().click();
    await page.waitForTimeout(800);
    const r = await page.evaluate(() => ({ open: document.querySelector('.menu-zoom').open, src: document.querySelector('.menu-zoom__img').currentSrc || document.querySelector('.menu-zoom__img').src }));
    out.push(`zoom 390: ${JSON.stringify(r)}`);
    await ctx.close();
}
{
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    await page.goto(base + '/menu', { waitUntil: 'networkidle' });
    await page.locator('.menu-view__mode[data-mode="book"]').click();
    await page.waitForTimeout(3000);
    const r = await page.evaluate(() => ({
        mode: document.querySelector('.menu-view').dataset.mode,
        canvas: !!document.querySelector('.book__stage canvas'),
        nextEnabled: !document.querySelector('.book__btn--next').disabled,
    }));
    out.push(`book 1440: ${JSON.stringify(r)} errors=${errors.length}`);
    
    await ctx.close();
}
await browser.close();
console.log(out.join('\n'));
const bad = out.filter((l) => /^console/.test(l) ? !/: (ok|(Failed to load resource: net::ERR_FAILED( \| )?)+)$/.test(l) : /"visible":false|"open":false|"canvas":false|errors=[1-9]/.test(l));
process.exit(bad.length ? 1 : 0);
