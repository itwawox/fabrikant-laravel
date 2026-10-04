<x-filament-panels::page>
    <style>
        .msx { display: grid; grid-template-columns: 150px minmax(0, 1fr) 360px; gap: 1rem; height: calc(100vh - 13rem); min-height: 560px; }
        .msx > * { overflow: auto; min-height: 0; }
        .msx button { cursor: pointer; }
        .msx-pages { display: grid; gap: .5rem; align-content: start; padding-right: .25rem; }
        .msx-pages button { display: grid; gap: .25rem; text-align: left; padding: .375rem; border: 1px solid var(--gray-200); border-radius: .5rem; background: var(--color-white, #fff); font-size: .75rem; color: var(--gray-600); }
        .msx-pages button[aria-current] { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); color: var(--gray-950); }
        .msx-pages img { width: 100%; display: block; border-radius: .25rem; }
        .msx-pages small { color: var(--gray-500); }
        .msx-pages small.is-empty { color: var(--warning-600); }
        .msx-stage { display: grid; justify-items: center; align-content: start; background: var(--gray-100); border-radius: .75rem; padding: 1rem; }
        .msx-sheet { position: relative; width: min(100%, 760px); user-select: none; cursor: crosshair; touch-action: none; box-shadow: 0 1px 3px rgb(0 0 0 / .15); background: #fff; }
        .msx-sheet img { display: block; width: 100%; pointer-events: none; }
        .msx-box { position: absolute; border: 2px solid #d8187c; background: rgb(216 24 124 / .1); cursor: move; }
        .msx-box.is-on { border-color: #1a73e8; background: rgb(26 115 232 / .16); }
        .msx-box span { position: absolute; left: -2px; top: -22px; background: #d8187c; color: #fff; padding: 2px 6px; white-space: nowrap; font-size: 12px; line-height: 16px; pointer-events: none; border-radius: 3px 3px 0 0; }
        .msx-box.is-on span { background: #1a73e8; }
        .msx-handle { position: absolute; width: 14px; height: 14px; background: #1a73e8; border: 2px solid #fff; border-radius: 2px; }
        .msx-handle.tl { left: -8px; top: -8px; cursor: nwse-resize; }
        .msx-handle.br { right: -8px; bottom: -8px; cursor: nwse-resize; }
        .msx-box:not(.is-on) .msx-handle { display: none; }
        .msx-draft { position: absolute; border: 2px dashed #1a73e8; pointer-events: none; }
        .msx-side { display: grid; gap: .75rem; align-content: start; padding-right: .25rem; }
        .msx-help { color: var(--gray-500); font-size: .8125rem; margin: 0; }
        .msx-mode { color: #1a73e8; font-weight: 600; font-size: .8125rem; margin: 0; }
        .msx-item { border: 1px solid var(--gray-200); border-radius: .5rem; padding: .5rem; display: grid; gap: .375rem; background: var(--color-white, #fff); }
        .msx-item.is-on { border-color: #1a73e8; box-shadow: 0 0 0 1px #1a73e8; }
        .msx-item.is-drop { border-style: dashed; border-color: var(--primary-500); }
        .msx-item input[type=text] { width: 100%; font: inherit; font-size: .875rem; padding: .375rem .5rem; border: 1px solid var(--gray-300); border-radius: .375rem; background: transparent; color: inherit; }
        .msx-item .msx-head { display: flex; gap: .375rem; align-items: center; }
        .msx-grip { cursor: grab; color: var(--gray-400); padding: 0 .125rem; font-size: 1rem; line-height: 1; }
        .msx-slug { display: flex; gap: .375rem; align-items: center; font-size: .75rem; color: var(--gray-500); flex-wrap: wrap; }
        .msx-slug code { color: var(--gray-700); }
        .msx-slug input[type=text] { width: 9rem; padding: .125rem .375rem; font-size: .75rem; }
        .msx-row { display: flex; gap: .25rem; flex-wrap: wrap; }
        .msx-row button { border: 1px solid var(--gray-200); border-radius: .375rem; padding: .125rem .5rem; font-size: .75rem; color: var(--gray-700); background: transparent; }
        .msx-row button.is-danger { color: var(--danger-600); }
        .msx-phone { border: 1px solid var(--gray-200); border-radius: 1rem; padding: .5rem; background: #1c0a02; display: grid; gap: .25rem; width: 220px; justify-self: center; }
        .msx-phone p { color: #fff; font-size: .75rem; margin: 0 0 .25rem; }
        .msx-crop { position: relative; overflow: hidden; background: #fff; }
        .msx-crop img { position: absolute; max-width: none; }
        .msx-save { display: grid; gap: .5rem; position: sticky; bottom: 0; background: var(--gray-50); padding: .5rem 0; }
        .msx-status { font-size: .8125rem; min-height: 1.25rem; margin: 0; color: var(--gray-600); }
        .msx-status.is-error { color: var(--danger-600); }
        .msx-ask { border: 1px solid var(--warning-500); background: var(--warning-50); border-radius: .5rem; padding: .75rem; font-size: .8125rem; display: grid; gap: .5rem; color: var(--gray-800); }
        .msx-ask ul { margin: 0; padding-left: 1rem; list-style: disc; }
        .msx-ask .msx-row button { font-size: .8125rem; padding: .25rem .625rem; }
        :is(.dark) .msx-pages button, :is(.dark) .msx-item { background: var(--gray-900); border-color: var(--gray-700); color: var(--gray-200); }
        :is(.dark) .msx-stage { background: var(--gray-800); }
        :is(.dark) .msx-save { background: var(--gray-950); }
        :is(.dark) .msx-ask { background: var(--gray-900); color: var(--gray-100); }
        @media (max-width: 1100px) { .msx { grid-template-columns: 110px minmax(0, 1fr) 300px; } }
    </style>

    <script>
        // Адрес раздела из названия — для подсказки в списке. Правило то же, что App\Support\MenuSlug
        // (окончательный адрес считает сервер при сохранении)
        window.menuSlug ??= (title) => {
            const map = { а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'e', ж: 'zh', з: 'z', и: 'i', й: 'y', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't', у: 'u', ф: 'f', х: 'h', ц: 'c', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya' };
            const s = [...String(title).toLowerCase()].map((c) => map[c] ?? c).join('').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            return s === '' || /^page-\d+$/.test(s) ? 'razdel-' + s : s;
        };

        window.menuSectionsEditor ??= (root, initial, $wire) => {
            const ref = (name) => root.querySelector(`[data-ref="${name}"]`);
            const sheet = ref('sheet'), img = ref('img'), list = ref('list');
            const state = { pages: initial.pages, sections: initial.sections };
            let page = 1;
            let selected = null;   // { s: раздел, b: номер рамки }
            let addingTo = null;   // раздел, к которому добавляем ещё рамку
            let dirty = false;
            let dragItem = null;   // раздел, который перетаскивают в списке
            const round = (v) => Math.round(Math.max(0, Math.min(1, v)) * 1000) / 1000;
            const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const pageOf = (n) => state.pages.find((p) => p.n === n);
            const onPage = () => state.sections.filter((s) => s.page === page);
            const slugOf = (s) => s.slug_locked ? s.slug : window.menuSlug(s.title || '');
            const touch = () => { dirty = true; ref('status').textContent = 'Есть несохранённые изменения'; ref('status').className = 'msx-status'; };

            function renderPages() {
                ref('pages').innerHTML = state.pages.map((p) => {
                    const c = state.sections.filter((s) => s.page === p.n).length;
                    return `<button type="button" data-n="${p.n}"${p.n === page ? ' aria-current="true"' : ''}><img src="${esc(p.thumb)}" alt="" loading="lazy">${p.n}. ${esc(p.title || '')}<small class="${c ? '' : 'is-empty'}">${c ? 'разделов: ' + c : 'не размечена'}</small></button>`;
                }).join('');
            }

            function renderBoxes() {
                sheet.querySelectorAll('.msx-box').forEach((el) => el.remove());
                onPage().forEach((s) => s.boxes.forEach((b, i) => {
                    const el = document.createElement('div');
                    el.className = 'msx-box' + (selected && selected.s === s && selected.b === i ? ' is-on' : '');
                    Object.assign(el.style, { left: b[0] * 100 + '%', top: b[1] * 100 + '%', width: b[2] * 100 + '%', height: b[3] * 100 + '%' });
                    el.innerHTML = '<span></span><i class="msx-handle tl"></i><i class="msx-handle br"></i>';
                    el.querySelector('span').textContent = (s.title || 'без названия') + (s.boxes.length > 1 ? ` (${i + 1})` : '');
                    el._ref = { s, b: i };
                    sheet.appendChild(el);
                }));
                renderPhone();
            }

            // Как раздел выглядит на телефоне: рамки вырезаются из листа и идут друг под другом
            function renderPhone() {
                const box = ref('phone');
                if (!selected) { box.innerHTML = ''; return; }
                const s = selected.s, p = pageOf(s.page);
                box.innerHTML = `<div class="msx-phone"><p>Так на телефоне: «${esc(s.title || 'без названия')}»</p>` + s.boxes.map(([x, y, w, h]) =>
                    `<div class="msx-crop" style="aspect-ratio:${w} / ${h * p.ratio}"><img src="${esc(p.img)}" alt="" style="width:${100 / w}%;height:${100 / h}%;left:${-x / w * 100}%;top:${-y / h * 100}%"></div>`
                ).join('') + '</div>';
            }

            function renderList() {
                const secs = onPage();
                list.innerHTML = secs.length ? '' : '<p class="msx-help">На этой странице разделов нет — на телефоне она покажется целиком.</p>';
                secs.forEach((s) => {
                    const item = document.createElement('div');
                    item.className = 'msx-item' + (selected && selected.s === s ? ' is-on' : '');
                    item.draggable = true;
                    item.innerHTML = `<div class="msx-head"><span class="msx-grip" title="Перетащите, чтобы поменять порядок">⋮⋮</span><input type="text" data-f="title" placeholder="Название, как на кнопке: «Супы»" maxlength="60"></div>
                        <div class="msx-slug">адрес: <code>/menu#<span data-f="slug-text"></span></code><input type="text" data-f="slug" maxlength="60" hidden>
                        <label title="Закреплённый адрес не меняется при переименовании — нужно, если на него напечатан QR"><input type="checkbox" data-f="lock"> закрепить</label></div>
                        <div class="msx-row"><button type="button" data-a="up" title="Выше">↑</button><button type="button" data-a="down" title="Ниже">↓</button><button type="button" data-a="more">+ рамка</button>${s.boxes.length > 1 ? '<button type="button" data-a="last">− рамка</button>' : ''}<button type="button" data-a="del" class="is-danger">Удалить</button></div>`;
                    const title = item.querySelector('[data-f=title]'), slug = item.querySelector('[data-f=slug]'), slugText = item.querySelector('[data-f=slug-text]'), lock = item.querySelector('[data-f=lock]');
                    const showSlug = () => { slugText.textContent = slugOf(s); slug.hidden = !s.slug_locked; slugText.parentElement.hidden = s.slug_locked; };
                    title.value = s.title; slug.value = s.slug || ''; lock.checked = !!s.slug_locked; showSlug();
                    title.addEventListener('focus', () => select(s));
                    title.addEventListener('input', () => { s.title = title.value; touch(); showSlug(); renderBoxes(); });
                    slug.addEventListener('input', () => { s.slug = slug.value; touch(); });
                    lock.addEventListener('change', () => { s.slug_locked = lock.checked; if (lock.checked && !s.slug) { s.slug = window.menuSlug(s.title || ''); slug.value = s.slug; } touch(); showSlug(); });
                    item.querySelector('.msx-row').addEventListener('click', (e) => {
                        const a = e.target.dataset.a; if (!a) return;
                        const all = state.sections, i = all.indexOf(s);
                        const same = all.map((x, k) => x.page === page ? k : -1).filter((k) => k >= 0);
                        const pos = same.indexOf(i);
                        if (a === 'up' && pos > 0) { [all[i], all[same[pos - 1]]] = [all[same[pos - 1]], all[i]]; }
                        if (a === 'down' && pos < same.length - 1) { [all[i], all[same[pos + 1]]] = [all[same[pos + 1]], all[i]]; }
                        if (a === 'del') { all.splice(i, 1); selected = null; }
                        if (a === 'last') { s.boxes.pop(); selected = null; }
                        if (a === 'more') { addingTo = s; ref('mode').hidden = false; return; }
                        touch(); render();
                    });
                    // Перетаскивание в списке: раздел встаёт на место того, на который его бросили
                    item.addEventListener('dragstart', (e) => { if (e.target.tagName === 'INPUT') { e.preventDefault(); return; } dragItem = s; e.dataTransfer.effectAllowed = 'move'; });
                    item.addEventListener('dragover', (e) => { if (dragItem && dragItem !== s) { e.preventDefault(); item.classList.add('is-drop'); } });
                    item.addEventListener('dragleave', () => item.classList.remove('is-drop'));
                    item.addEventListener('drop', (e) => {
                        e.preventDefault(); item.classList.remove('is-drop');
                        if (!dragItem || dragItem === s) return;
                        const all = state.sections;
                        all.splice(all.indexOf(dragItem), 1);
                        const to = all.indexOf(s);
                        all.splice(to + (e.offsetY > item.offsetHeight / 2 ? 1 : 0), 0, dragItem);
                        dragItem = null; touch(); render();
                    });
                    item.addEventListener('dragend', () => { dragItem = null; });
                    item._s = s;
                    list.appendChild(item);
                });
            }

            function select(s, b = 0) { selected = { s, b }; renderBoxes(); list.querySelectorAll('.msx-item').forEach((it) => it.classList.toggle('is-on', it._s === s)); }
            function render() { renderPages(); renderBoxes(); renderList(); }
            function show(n) { page = n; selected = null; addingTo = null; ref('mode').hidden = true; img.src = pageOf(n).img; render(); }

            ref('pages').addEventListener('click', (e) => { const b = e.target.closest('button'); if (b) show(Number(b.dataset.n)); });

            // Мышь: обвести новую рамку, подвинуть рамку или потянуть её за угол
            const point = (e) => { const r = sheet.getBoundingClientRect(); return { x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height }; };
            let drag = null;
            sheet.addEventListener('pointerdown', (e) => {
                sheet.setPointerCapture(e.pointerId);
                const p = point(e), box = e.target.closest('.msx-box');
                if (box) {
                    selected = box._ref;
                    const b = selected.s.boxes[selected.b].slice();
                    drag = { kind: e.target.classList.contains('tl') ? 'tl' : e.target.classList.contains('br') ? 'br' : 'move', start: p, orig: b };
                    select(selected.s, selected.b);
                    return;
                }
                const el = document.createElement('div'); el.className = 'msx-draft'; sheet.appendChild(el);
                drag = { kind: 'new', start: p, el };
            });
            sheet.addEventListener('pointermove', (e) => {
                if (!drag) return;
                const p = point(e), dx = p.x - drag.start.x, dy = p.y - drag.start.y;
                if (drag.kind === 'new') {
                    Object.assign(drag.el.style, { left: Math.min(p.x, drag.start.x) * 100 + '%', top: Math.min(p.y, drag.start.y) * 100 + '%', width: Math.abs(dx) * 100 + '%', height: Math.abs(dy) * 100 + '%' });
                    return;
                }
                let [x, y, w, h] = drag.orig;
                if (drag.kind === 'move') { x = Math.min(1 - w, Math.max(0, x + dx)); y = Math.min(1 - h, Math.max(0, y + dy)); }
                if (drag.kind === 'br') { w = Math.max(.02, w + dx); h = Math.max(.02, h + dy); }
                if (drag.kind === 'tl') { const nx = Math.min(x + w - .02, x + dx), ny = Math.min(y + h - .02, y + dy); w += x - nx; h += y - ny; x = nx; y = ny; }
                selected.s.boxes[selected.b] = [round(x), round(y), round(Math.min(w, 1 - x)), round(Math.min(h, 1 - y))];
                touch(); renderBoxes();
            });
            sheet.addEventListener('pointerup', (e) => {
                if (!drag) return;
                if (drag.kind === 'new') {
                    drag.el.remove();
                    const p = point(e);
                    const b = [round(Math.min(p.x, drag.start.x)), round(Math.min(p.y, drag.start.y)), round(Math.abs(p.x - drag.start.x)), round(Math.abs(p.y - drag.start.y))];
                    if (b[2] > .02 && b[3] > .02) {
                        if (addingTo) {
                            addingTo.boxes.push(b); selected = { s: addingTo, b: addingTo.boxes.length - 1 };
                            addingTo = null; ref('mode').hidden = true;
                        } else {
                            const s = { id: null, page, title: '', slug: '', slug_locked: false, boxes: [b] };
                            // Новый раздел — после последнего раздела этой страницы
                            const last = state.sections.map((x, k) => x.page <= page ? k : -1).filter((k) => k >= 0).pop();
                            state.sections.splice(last === undefined ? 0 : last + 1, 0, s);
                            selected = { s, b: 0 };
                        }
                        touch(); render();
                        const input = [...list.querySelectorAll('.msx-item')].find((it) => it._s === selected.s)?.querySelector('[data-f=title]');
                        if (input && !selected.s.title) input.focus();
                    }
                }
                drag = null;
            });
            document.addEventListener('keydown', (e) => {
                if (!root.isConnected) return;
                if (e.key === 'Escape') { addingTo = null; ref('mode').hidden = true; selected = null; render(); }
                if ((e.key === 'Delete' || e.key === 'Backspace') && selected && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                    const s = selected.s;
                    s.boxes.splice(selected.b, 1);
                    if (!s.boxes.length) state.sections.splice(state.sections.indexOf(s), 1);
                    selected = null; touch(); render();
                }
            });

            // Сохранение. Если сменятся адреса существующих разделов — спрашиваем, на них могли напечатать QR
            async function save(decision = null) {
                const st = ref('status'), ask = ref('ask');
                const untitled = state.sections.filter((s) => !String(s.title).trim());
                if (untitled.length) {
                    st.className = 'msx-status is-error';
                    st.textContent = `Без названия: ${untitled.length} (страница ${untitled[0].page}). Впишите название или удалите рамку.`;
                    show(untitled[0].page);
                    return;
                }
                st.className = 'msx-status'; st.textContent = 'Сохраняю…'; ask.hidden = true;
                try {
                    const out = await $wire.save(state.sections, decision);
                    if (out.changes) {
                        st.textContent = '';
                        ask.innerHTML = `<div class="msx-ask"><strong>Адреса разделов изменятся.</strong> Если на них напечатаны QR-коды или ссылки, старые перестанут вести к разделу.
                            <ul>${out.changes.map((c) => `<li>«${esc(c.title)}»: было #${esc(c.from)}, станет #${esc(c.to)}</li>`).join('')}</ul>
                            <div class="msx-row"><button type="button" data-d="keep">Оставить старые адреса</button><button type="button" data-d="change">Сменить адреса</button></div></div>`;
                        ask.hidden = false;
                        ask.querySelectorAll('[data-d]').forEach((b) => b.addEventListener('click', () => save(b.dataset.d)));
                        return;
                    }
                    // Сервер вернул разделы с номерами и окончательными адресами
                    const keep = selected ? { page: selected.s.page, index: onPage().indexOf(selected.s) } : null;
                    state.sections = out.sections;
                    selected = null; dirty = false;
                    st.textContent = `Сохранено разделов: ${out.saved}.`;
                    render();
                    if (keep && keep.page === page && onPage()[keep.index]) select(onPage()[keep.index]);
                } catch (err) {
                    st.className = 'msx-status is-error'; st.textContent = 'Не получилось сохранить. Проверьте интернет и попробуйте ещё раз.';
                }
            }
            ref('save').addEventListener('click', () => save());
            window.addEventListener('beforeunload', (e) => { if (dirty && root.isConnected) e.preventDefault(); });

            show(state.pages[0]?.n ?? 1);
        };
    </script>

    {{-- Редактор живёт в браузере: Livewire его не перерисовывает, только принимает «Сохранить» --}}
    <div class="msx" wire:ignore x-data x-init="window.menuSectionsEditor($el, @js($this->editorState()), $wire)">
        <nav class="msx-pages" data-ref="pages" aria-label="Страницы меню"></nav>
        <div class="msx-stage"><div class="msx-sheet" data-ref="sheet"><img data-ref="img" alt=""></div></div>
        <div class="msx-side">
            <p class="msx-help">Обведите раздел мышью: от заголовка до последней цены. Рамку можно двигать и тянуть за синие углы.
                Порядок в списке — порядок чтения на телефоне, его меняют перетаскиванием за ⋮⋮ или стрелками.
                Раздел, который переходит в соседнюю колонку, — одна запись с двумя рамками («+ рамка»).</p>
            <p class="msx-mode" data-ref="mode" hidden>Обведите продолжение раздела — рамка добавится к нему. Esc — отмена.</p>
            <div class="msx-list" data-ref="list" style="display:grid;gap:.5rem"></div>
            <div data-ref="phone"></div>
            <div class="msx-save">
                <div data-ref="ask" hidden></div>
                <x-filament::button data-ref="save" type="button">Сохранить разделы</x-filament::button>
                <p class="msx-status" data-ref="status" role="status"></p>
            </div>
        </div>
    </div>

</x-filament-panels::page>
