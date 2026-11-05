/**
 * «اطلاعات بیشتر لغت» — بخش‌های تکمیلی پنجره‌ی جزئیات (مشترک بین دیکشنری و لغت‌های ذخیره‌شده).
 * استفاده:  <div data-wx data-t='@json(__('wordextra'))'></div>  داخل پنجره، و در JS:  renderExtra(el, word)
 * هر بخش فقط وقتی داده دارد ساخته می‌شود: سطح و دسته‌ها، بار معنایی (connotation)، نقش‌های دستوری دیگر،
 * کالوکیشن‌ها، لغت‌های هم‌خانواده، ریشه و ساختار (morphemes) و توضیح ریشه‌شناسی.
 * قرارداد داده: app/Support/WordExtras.php. همه‌ی متن‌ها با textContent می‌آیند (بدون HTML).
 */
function el(tag, cls, text) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
}

function section(title) {
    const s = el('section', 'wx-sec');
    s.appendChild(el('h3', 'wx-h', title));
    return s;
}

/** متن تار (مثل ترجمه‌های دیگر): با کلیک آشکار می‌شود — رویداد کلیک را خود صفحه روی .sw-b مدیریت می‌کند */
function blurred(text, see) {
    const b = el('button', 'sw-b sw-b--text');
    b.type = 'button';
    b.setAttribute('aria-pressed', 'false');
    b.setAttribute('aria-label', see);
    b.appendChild(el('span', 'sw-b__content text-sm font-extrabold text-brand-900', text)).setAttribute('dir', 'auto');
    const v = el('span', 'sw-b__veil');
    v.appendChild(el('span', '', see));
    b.appendChild(v);
    return b;
}

export function renderExtra(mount, w, opts) {
    const plain = !!(opts && opts.plain); // پنل مدیریت: ترجمه‌ها تار نیستند
    if (!mount) return;
    let T = {};
    try { T = JSON.parse(mount.getAttribute('data-t') || '{}') || {}; } catch (e) { T = {}; }
    const tr = (path) => path.split('.').reduce((o, k) => (o == null ? o : o[k]), T) || '';
    mount.textContent = '';

    // ۱) سطح + دسته‌ها + بار معنایی
    const tags = Array.isArray(w.tags) ? w.tags : [];
    if (w.level || tags.length || w.connotation) {
        const row = el('div', 'wx-meta');
        if (w.level) row.appendChild(el('span', 'wx-chip wx-chip--lv', String(w.level).toUpperCase()));
        tags.forEach((t) => row.appendChild(el('span', 'wx-chip wx-chip--tag', tr('tags.' + t) || t)));
        if (w.connotation) {
            const c = el('span', 'wx-chip wx-chip--conn');
            c.setAttribute('data-conn', w.connotation);
            c.appendChild(el('i', 'wx-dot'));
            c.appendChild(document.createTextNode(tr('connotation.title') + ': ' + tr('connotation.' + w.connotation)));
            row.appendChild(c);
        }
        mount.appendChild(row);
        if (w.connotation) mount.appendChild(el('p', 'wx-hint', tr('connotation.hint.' + w.connotation)));
    }

    // ۲) نقش‌های دستوری دیگر (هرکدام معنی/مثال/ترجمه‌ی خودش)
    const senses = Array.isArray(w.senses) ? w.senses : [];
    if (senses.length) {
        const s = section(tr('senses'));
        senses.forEach((x) => {
            const box = el('div', 'wx-sense');
            const head = el('p', 'wx-sense__head');
            if (x.pos) head.appendChild(el('b', 'wx-pos', x.pos));
            if (x.meaning) head.appendChild(document.createTextNode(' ' + x.meaning));
            head.setAttribute('dir', 'ltr');
            box.appendChild(head);
            if (x.example) {
                const ex = el('p', 'wx-sense__ex', x.example);
                ex.setAttribute('dir', 'ltr');
                box.appendChild(ex);
            }
            const trs = (txt) => {
                if (!plain) return blurred(txt, tr('see'));
                const p = el('p', 'text-sm text-slate-600', txt);
                p.setAttribute('dir', 'auto');
                return p;
            };
            if (x.translate) box.appendChild(trs(x.translate));
            if (x.example_tr) box.appendChild(trs(x.example_tr));
            s.appendChild(box);
        });
        mount.appendChild(s);
    }

    // ۳) کالوکیشن‌ها
    const col = Array.isArray(w.collocations) ? w.collocations : [];
    if (col.length) {
        const s = section(tr('collocations'));
        const ul = el('ul', 'wx-chips');
        ul.setAttribute('dir', 'ltr');
        col.forEach((c) => ul.appendChild(el('li', 'wx-chip', c)));
        s.appendChild(ul);
        mount.appendChild(s);
    }

    // ۴) هم‌خانواده‌ها
    const fam = Array.isArray(w.family) ? w.family : [];
    if (fam.length) {
        const s = section(tr('family'));
        const ul = el('ul', 'wx-chips');
        ul.setAttribute('dir', 'ltr');
        fam.forEach((f) => {
            const li = el('li', 'wx-chip');
            li.appendChild(document.createTextNode(f.word));
            if (f.pos) li.appendChild(el('small', 'wx-pos-s', f.pos));
            ul.appendChild(li);
        });
        s.appendChild(ul);
        mount.appendChild(s);
    }

    // ۵) ریشه و ساختار: «speech + less» و معنی هر جزء
    const mor = Array.isArray(w.morphemes) ? w.morphemes : [];
    if (mor.length || w.origin) {
        const s = section(tr(mor.length ? 'morphemes' : 'origin'));
        if (mor.length) {
            const eq = el('p', 'wx-eq');
            eq.setAttribute('dir', 'ltr');
            mor.forEach((m, i) => {
                if (i) eq.appendChild(el('span', 'wx-plus', '+'));
                eq.appendChild(el('b', '', m.part));
            });
            s.appendChild(eq);
            const dl = el('dl', 'wx-parts');
            mor.forEach((m) => {
                if (!m.meaning) return;
                const r = el('div');
                r.appendChild(el('dt', '', m.part));
                const dd = el('dd', '', m.meaning);
                dd.setAttribute('dir', 'auto');
                r.appendChild(dd);
                dl.appendChild(r);
            });
            if (dl.childNodes.length) s.appendChild(dl);
        }
        if (w.origin) {
            if (mor.length) s.appendChild(el('h3', 'wx-h wx-h--sub', tr('origin')));
            const p = el('p', 'wx-origin', w.origin);
            p.setAttribute('dir', 'ltr');
            s.appendChild(p);
        }
        mount.appendChild(s);
    }
    mount.hidden = !mount.childNodes.length;
}
