import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, ago, num } from './common';
import { API, overlay, createApi, paginate, tFrom, el, splitList } from './bank';

/**
 * مدیریت دیکشنری — لیست (/admin/dictionary) و ویرایشگر (/admin/dictionary/{id}/edit).
 * قرارداد داده و آدرس‌ها: app/Support/AdminDictionary.php
 *
 * 🔌 بک‌اند — همه‌ی عملیات از resources/js/admin/bank.js (createApi) می‌گذرد؛ سه راه اتصال:
 *   رویداد لغوشدنی admin:word-{save|upload|delete|bulk} ← Teodel.admin.dictionary.api.{save|upload|remove|bulk} ← data-*-url
 *   آپلود: kind = image | audio_uk | audio_us  ← { url }
 * AI: kind 'word' (فیلدهای فرم؛ تصویر و صدا در missing) و 'word_batch' (ساخت گروهی؛ پاسخ: { fields:{ items:[مدخل…] }, missing:[] })
 *
 * ⚠️ LOCAL = true: ذخیره‌ی دمو در localStorage مرورگر (لغت‌های ساخته/ویرایش‌شده در لیست دیده می‌شوند).
 *    وقتی بک‌اند وصل شد، آن را false کنید. (در حالت دمو تصویر آپلودی کوچک و داخل مرورگر نگه داشته می‌شود.)
 */
const LOCAL = true;
const LS = 'teodel:admin:dictionary';
export const TYPES = ['noun', 'verb', 'adjective', 'adverb', 'pronoun', 'preposition', 'conjunction', 'interjection', 'phrasal verb', 'idiom', 'other'];
export const PARTS = ['core', 'meaning', 'example', 'ipa', 'audio', 'image'];
const MAX_BATCH = 30;

/* ── توابع خالص (تست‌شده؛ همان قانون AdminDictionary در PHP) ───────── */

const has = (v) => String(v == null ? '' : v).trim() !== '';
const pick = (o, k) => (o && o[k]) || '';

/** کدام بخش‌ها کامل‌اند؟ */
export function parts(w) {
    return {
        core: has(w.phrase) && has(w.type) && has(w.translate),
        meaning: has(w.meaning),
        example: has(w.example) && has(w.example_tr),
        ipa: has(pick(w.ipa, 'uk')) || has(pick(w.ipa, 'us')),
        audio: has(pick(w.audio, 'uk')) || has(pick(w.audio, 'us')),
        image: has(w.image),
    };
}
export const doneCount = (w) => PARTS.filter((p) => parts(w)[p]).length;

/** کلید یکتا برای تشخیص لغت تکراری (phrase + type) */
export const dupKey = (phrase, type) => String(phrase || '').trim().toLowerCase().replace(/\s+/g, ' ') + '|' + String(type || '');

/** حرف اول برای نوار الفبا: A–Z یا # */
export const firstLetter = (phrase) => {
    const c = String(phrase || '').trim().charAt(0).toUpperCase();
    return /^[A-Z]$/.test(c) ? c : '#';
};

/** بررسی یک مدخل → [{level:'error'|'warn', code, field}]؛ taken = Set کلیدهای لغت‌های دیگر */
export function validate(w, taken) {
    const out = [];
    const add = (level, code, field) => out.push({ level, code, field });
    if (!has(w.phrase)) add('error', 'phrase_required', 'phrase');
    else if (!/^[\x20-\x7E’‘“”–—…]+$/.test(String(w.phrase))) add('warn', 'phrase_chars', 'phrase');
    if (!has(w.type) || !TYPES.includes(w.type)) add('error', 'type_required', 'type');
    if (!has(w.translate)) add('error', 'translate_required', 'translate');
    if (has(w.phrase) && taken && taken.has(dupKey(w.phrase, w.type))) add('error', 'duplicate', 'phrase');
    if (!has(w.meaning)) add('warn', 'meaning_missing', 'meaning');
    if (!has(w.example)) add('warn', 'example_missing', 'example');
    else if (!has(w.example_tr)) add('warn', 'example_tr_missing', 'example_tr');
    if (!has(pick(w.ipa, 'uk')) && !has(pick(w.ipa, 'us'))) add('warn', 'ipa_missing', 'ipa_uk');
    if (!has(pick(w.audio, 'uk')) && !has(pick(w.audio, 'us'))) add('warn', 'audio_missing', 'audio_uk');
    if (!has(w.image)) add('warn', 'image_missing', 'image');
    const self = String(w.phrase || '').trim().toLowerCase();
    if (self && [].concat(w.synonyms || [], w.antonyms || []).some((s) => String(s).trim().toLowerCase() === self)) add('warn', 'synonym_self', 'synonyms');
    return out;
}
export const isReady = (w, taken) => !validate(w, taken).some((i) => i.level === 'error');

/** آمار بالای لیست */
export function stats(rows) {
    const n = rows.length;
    const pct = (c) => (n ? Math.round((c / n) * 100) : 0);
    let img = 0;
    let aud = 0;
    let full = 0;
    let pub = 0;
    rows.forEach((r) => {
        const p = parts(r);
        if (p.image) img++;
        if (p.audio) aud++;
        if (PARTS.every((k) => p[k])) full++;
        if (r.status === 'published') pub++;
    });
    return { total: n, published: pub, draft: n - pub, image: img, image_pct: pct(img), audio: aud, audio_pct: pct(aud), complete: full, complete_pct: pct(full) };
}

/** فیلتر + مرتب‌سازی لیست */
export function filterRows(rows, f) {
    const q = String(f.q || '').trim().toLowerCase();
    let out = rows.filter((r) => {
        if (q && !((r.phrase || '') + ' ' + (r.translate || '') + ' ' + (r.meaning || '') + ' ' + (r.synonyms || []).join(' ')).toLowerCase().includes(q)) return false;
        if (f.type && f.type !== 'all' && r.type !== f.type) return false;
        if (f.status && f.status !== 'all' && r.status !== f.status) return false;
        if (f.letter && f.letter !== 'all' && firstLetter(r.phrase) !== f.letter) return false;
        const p = parts(r);
        if (f.quick === 'noimage' && p.image) return false;
        if (f.quick === 'noaudio' && p.audio) return false;
        if (f.quick === 'incomplete' && PARTS.every((k) => p[k])) return false;
        if (f.quick === 'draft' && r.status !== 'draft') return false;
        return true;
    });
    const az = (a, b) => String(a.phrase).toLowerCase().localeCompare(String(b.phrase).toLowerCase());
    out = out.slice().sort((a, b) => {
        if (f.sort === 'az') return az(a, b);
        if (f.sort === 'least') return doneCount(a) - doneCount(b) || az(a, b);
        return (b.updated_at || 0) - (a.updated_at || 0);
    });
    return out;
}

/** مدل تمیز برای ارسال */
export function cleanWord(W) {
    const t = (v) => String(v == null ? '' : v).trim();
    const list = (a) => [...new Set((a || []).map(t).filter(Boolean))];
    return {
        id: W.id || null,
        status: W.status,
        phrase: t(W.phrase),
        type: t(W.type),
        ipa: { uk: t(pick(W.ipa, 'uk')), us: t(pick(W.ipa, 'us')) },
        audio: { uk: t(pick(W.audio, 'uk')), us: t(pick(W.audio, 'us')) },
        meaning: t(W.meaning),
        example: t(W.example),
        example_tr: t(W.example_tr),
        translate: t(W.translate),
        synonyms: list(W.synonyms),
        antonyms: list(W.antonyms),
        image: t(W.image) || null,
    };
}

/** لغت‌های ورودی دیالوگ گروهی: هر خط یکی، بدون تکرار */
export function parseWords(text, max = MAX_BATCH) {
    const seen = new Set();
    const out = [];
    String(text || '').split(/\r?\n/).forEach((l) => {
        const w = l.trim().replace(/\s+/g, ' ');
        if (!w || seen.has(w.toLowerCase())) return;
        seen.add(w.toLowerCase());
        out.push(w);
    });
    return out.slice(0, max);
}

/* ── نمونه‌های AI در «حالت دمو» (بدون بک‌اند) ───────────────────── */
const E = (type, translate, meaning, example, example_tr, uk, us, synonyms, antonyms) => ({ type, translate, meaning, example, example_tr, ipa: { uk, us }, synonyms, antonyms });
const KNOWN = {
    happy: E('adjective', 'خوشحال', 'Feeling pleased and satisfied.', 'She was happy to see her old friends.', 'او از دیدن دوستان قدیمی‌اش خوشحال بود.', '/ˈhæpi/', '/ˈhæpi/', ['glad', 'cheerful'], ['sad']),
    library: E('noun', 'کتابخانه', 'A place where books can be read or borrowed.', 'I study at the library every evening.', 'من هر عصر در کتابخانه درس می‌خوانم.', '/ˈlaɪbrəri/', '/ˈlaɪbreri/', [], []),
    quickly: E('adverb', 'به‌سرعت', 'In a way that takes very little time.', 'He quickly finished his homework.', 'او به‌سرعت تکلیفش را تمام کرد.', '/ˈkwɪkli/', '/ˈkwɪkli/', ['fast', 'rapidly'], ['slowly']),
    although: E('conjunction', 'اگرچه', 'Used to introduce a fact that makes the main statement surprising.', 'Although it was late, we kept talking.', 'اگرچه دیر شده بود، به حرف‌زدن ادامه دادیم.', '/ɔːlˈðəʊ/', '/ɔːlˈðoʊ/', ['though'], []),
    'give up': E('phrasal verb', 'تسلیم شدن؛ دست کشیدن', 'To stop trying to do something.', 'Never give up on your dreams.', 'هرگز از رؤیاهایت دست نکش.', '/ɡɪv ˈʌp/', '/ɡɪv ˈʌp/', ['quit'], ['continue']),
    'break the ice': E('idiom', 'یخ را شکستن؛ شروع گفتگو', 'To say or do something to make people feel relaxed at the start of a meeting.', 'A funny story helped to break the ice.', 'یک داستان خنده‌دار به شروع راحت گفتگو کمک کرد.', '/breɪk ðə aɪs/', '/breɪk ði aɪs/', [], []),
    challenge: E('noun', 'چالش', 'Something new and difficult that tests your ability.', 'Learning a new language is a big challenge.', 'یادگیری یک زبان جدید چالش بزرگی است.', '/ˈtʃælɪndʒ/', '/ˈtʃælɪndʒ/', ['test'], []),
    abandon: E('verb', 'رها کردن', 'To leave someone or something completely.', 'They had to abandon the car in the snow.', 'آن‌ها مجبور شدند ماشین را توی برف رها کنند.', '/əˈbændən/', '/əˈbændən/', ['leave', 'desert'], ['keep']),
};

/** یک مدخل نمونه برای (لغت، نوع) */
export function demoWord(phrase, type) {
    const p = String(phrase || '').trim();
    const k = KNOWN[p.toLowerCase()];
    if (k) return Object.assign({ phrase: p }, k, { type: type || k.type });
    return {
        phrase: p, type: type || 'noun', translate: 'ترجمه‌ی نمونه', meaning: `A simple definition of “${p}”.`,
        example: `I learned the word “${p}” today.`, example_tr: `امروز لغت «${p}» را یاد گرفتم.`,
        ipa: { uk: `/${p.toLowerCase()}/`, us: `/${p.toLowerCase()}/` }, synonyms: [], antonyms: [],
    };
}

/** نمونه‌ی دموی پاسخ AI برای kind='word' (فیلدهای فرم) */
function demoWordFields(payload) {
    const d = demoWord(payload.seeds.phrase, TYPES.includes(payload.seeds.type) ? payload.seeds.type : '');
    const all = { type: d.type, translate: d.translate, meaning: d.meaning, example: d.example, example_tr: d.example_tr, ipa_uk: d.ipa.uk, ipa_us: d.ipa.us, synonyms: d.synonyms.join(', '), antonyms: d.antonyms.join(', ') };
    const out = {};
    const missing = [];
    payload.fields.forEach((f) => {
        if (/^(image|audio_uk|audio_us)$/.test(f)) return missing.push(f);
        if (all[f] != null && all[f] !== '') out[f] = all[f];
        else missing.push(f);
    });
    return { fields: out, missing };
}
function demoWordBatch(payload) {
    const type = TYPES.includes(payload.seeds.type) ? payload.seeds.type : '';
    const items = parseWords(payload.seeds.words).map((w) => demoWord(w, type));
    return { fields: { items }, missing: [] };
}

API.admin.ai = API.admin.ai || {};
API.admin.ai.demos = API.admin.ai.demos || {};
API.admin.ai.demos.word = demoWordFields;
API.admin.ai.demos.word_batch = demoWordBatch;

/** تصویر کوچک‌شده برای ذخیره‌ی دمو (فقط LOCAL) */
function localImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            try {
                const s = Math.min(1, 360 / Math.max(img.width, img.height));
                const c = document.createElement('canvas');
                c.width = Math.max(1, Math.round(img.width * s));
                c.height = Math.max(1, Math.round(img.height * s));
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                resolve({ url: c.toDataURL('image/jpeg', 0.82) });
            } catch (e) {
                resolve({ url });
            }
        };
        img.onerror = () => reject({ message: '' });
        img.src = url;
    });
}

/** تصویر کوچک لغت (یا حرف اول اگر تصویر نبود/نشد) */
function thumb(r, cls = 'dc-thumb') {
    const box = el('span', cls);
    box.setAttribute('aria-hidden', 'true');
    const fallback = () => {
        box.textContent = firstLetter(r.phrase) === '#' ? '·' : String(r.phrase || '').trim().charAt(0).toUpperCase();
        box.classList.add('is-empty');
    };
    if (r.image) {
        const img = document.createElement('img');
        img.alt = '';
        img.loading = 'lazy';
        img.decoding = 'async';
        img.addEventListener('error', () => { img.remove(); fallback(); });
        img.src = r.image;
        box.appendChild(img);
    } else fallback();
    return box;
}

/* ═════════ لیست ═════════ */
$(function () {
    const $root = $('[data-dc-list]');
    if (!$root.length) return;
    const T = readJSON($root, 'data-t', {});
    const t = tFrom(T);
    const ov = overlay(LS);
    const base = readJSON($root, 'data-rows', []);
    const rows = LOCAL ? ov.merge(base) : base;
    const editUrl = (id) => $root.attr('data-edit-url').replace('__ID__', id);
    const PER = 20;
    const F = { q: '', type: 'all', status: 'all', sort: 'newest', letter: 'all', quick: 'all', page: 1 };
    const KEYS = ['q', 'type', 'status', 'sort', 'letter', 'quick'];

    const sp = new URLSearchParams(location.search);
    KEYS.forEach((k) => sp.get(k) && (F[k] = sp.get(k)));
    F.page = parseInt(sp.get('page'), 10) || 1;

    const $rows = $root.find('[data-dc-rows]');
    const sync = () => {
        const p = new URLSearchParams();
        KEYS.forEach((k) => F[k] && F[k] !== 'all' && !(k === 'sort' && F[k] === 'newest') && p.set(k, F[k]));
        if (F.page > 1) p.set('page', F.page);
        const s = p.toString();
        try { history.replaceState(null, '', location.pathname + (s ? '?' + s : '')); } catch (e) { /* ignore */ }
    };

    function renderStats() {
        const s = stats(rows);
        const set = (k, val, sub, pct) => {
            const $c = $root.find('[data-dc-stat="' + k + '"]');
            $c.find('[data-v]').text(val);
            $c.find('[data-s]').text(sub);
            if (pct != null) $c.find('.dc-bar').css('--p', pct + '%');
        };
        set('total', num(s.total), t('stat.published', { n: num(s.published) }));
        set('image', num(s.image_pct) + '٪', t('stat.of', { a: num(s.image), b: num(s.total) }), s.image_pct);
        set('audio', num(s.audio_pct) + '٪', t('stat.of', { a: num(s.audio), b: num(s.total) }), s.audio_pct);
        set('complete', num(s.complete_pct) + '٪', t('stat.of', { a: num(s.complete), b: num(s.total) }), s.complete_pct);
    }

    function renderChips() {
        const cnt = { all: rows.length, noimage: 0, noaudio: 0, incomplete: 0, draft: 0 };
        rows.forEach((r) => {
            const p = parts(r);
            if (!p.image) cnt.noimage++;
            if (!p.audio) cnt.noaudio++;
            if (!PARTS.every((k) => p[k])) cnt.incomplete++;
            if (r.status === 'draft') cnt.draft++;
        });
        $root.find('[data-dc-quick] button').each(function () {
            const v = $(this).attr('data-v');
            $(this).attr('aria-current', F.quick === v ? 'true' : 'false').find('[data-n]').text(num(cnt[v]));
        });
        const have = new Set(rows.map((r) => firstLetter(r.phrase)));
        $root.find('[data-dc-letters] button').each(function () {
            const v = $(this).attr('data-v');
            $(this).attr('aria-current', F.letter === v ? 'true' : 'false').prop('disabled', v !== 'all' && !have.has(v));
        });
    }

    function segs(r) {
        const p = parts(r);
        const n = PARTS.filter((k) => p[k]).length;
        const box = el('div', 'dc-segs');
        box.setAttribute('role', 'img');
        box.setAttribute('aria-label', t('done_of', { a: num(n) }));
        box.setAttribute('title', t('done_of', { a: num(n) }));
        PARTS.forEach((k) => {
            const i = el('i');
            i.setAttribute('data-on', p[k] ? '1' : '0');
            i.setAttribute('data-p', k);
            box.appendChild(i);
        });
        return box;
    }

    function renderRows() {
        const list = filterRows(rows, F);
        const pg = paginate(list, F.page, PER);
        F.page = pg.page;
        $rows.empty();
        pg.rows.forEach((r) => {
            const tr = document.createElement('tr');
            const td1 = el('td');
            const a = el('a', 'ad-user');
            a.href = editUrl(r.id);
            a.appendChild(thumb(r));
            const wrap = el('span', 'min-w-0');
            const b = el('b', 'block truncate text-start');
            b.setAttribute('dir', 'ltr');
            b.textContent = r.phrase;
            const sm = el('small', 'block truncate text-start');
            sm.setAttribute('dir', 'ltr');
            sm.textContent = pick(r.ipa, 'us') || pick(r.ipa, 'uk') || '#' + r.id;
            wrap.appendChild(b);
            wrap.appendChild(sm);
            a.appendChild(wrap);
            td1.appendChild(a);
            tr.appendChild(td1);

            const cell = (label, child) => {
                const td = el('td');
                td.setAttribute('data-label', label);
                if (typeof child === 'string') td.textContent = child;
                else td.appendChild(child);
                return td;
            };
            const badge = (txt, tone) => {
                const s = el('span', 'ad-badge', txt);
                if (tone) s.setAttribute('data-tone', tone);
                return s;
            };
            tr.appendChild(cell(t('col.type'), badge(t('type.' + r.type) === 'type.' + r.type ? r.type : t('type.' + r.type), 'info')));
            const tl = el('span', 'block max-w-[14rem] truncate text-xs font-bold text-slate-600', r.translate || '—');
            tl.setAttribute('dir', 'auto');
            tr.appendChild(cell(t('col.translate'), tl));
            tr.appendChild(cell(t('col.done'), segs(r)));
            tr.appendChild(cell(t('col.status'), badge(t('status_label.' + r.status), r.status === 'published' ? 'ok' : '')));
            const upd = el('td');
            upd.setAttribute('data-label', t('col.updated'));
            if (r.updated_at) upd.appendChild(el('time', null, ago(r.updated_at)));
            else upd.textContent = '—';
            tr.appendChild(upd);
            $rows.append(tr);
        });
        const none = list.length === 0;
        $root.find('[data-dc-table]').prop('hidden', none);
        $root.find('[data-dc-empty]').prop('hidden', !none);
        $root.find('[data-dc-pager]').prop('hidden', none);
        $root.find('[data-dc-count]').text(t('result', { n: num(list.length) }) + ' · ' + t('page_of', { a: num(pg.page), b: num(pg.pages) }));
        $root.find('[data-dc-prev]').prop('disabled', pg.page <= 1);
        $root.find('[data-dc-next]').prop('disabled', pg.page >= pg.pages);
    }

    function render() {
        renderStats();
        renderChips();
        renderRows();
        sync();
    }
    $root.find('[data-dc-q]').val(F.q);
    $root.find('[data-dc-type]').val(F.type);
    $root.find('[data-dc-status]').val(F.status);
    $root.find('[data-dc-sort]').val(F.sort);

    let tm = 0;
    $root.on('input', '[data-dc-q]', function () {
        clearTimeout(tm);
        const v = $(this).val();
        tm = setTimeout(() => ((F.q = String(v)), (F.page = 1), render()), 150);
    });
    $root.on('change', '[data-dc-type]', function () { F.type = $(this).val(); F.page = 1; render(); });
    $root.on('change', '[data-dc-status]', function () { F.status = $(this).val(); F.page = 1; render(); });
    $root.on('change', '[data-dc-sort]', function () { F.sort = $(this).val(); F.page = 1; render(); });
    $root.on('click', '[data-dc-quick] button', function () { F.quick = $(this).attr('data-v'); F.page = 1; render(); });
    $root.on('click', '[data-dc-letters] button', function () { F.letter = $(this).attr('data-v'); F.page = 1; render(); });
    $root.on('click', '[data-dc-stat][data-go]', function () {
        F.quick = $(this).attr('data-go');
        F.page = 1;
        render();
    });
    $root.on('click', '[data-dc-prev]', () => { F.page--; render(); window.scrollTo({ top: 0 }); });
    $root.on('click', '[data-dc-next]', () => { F.page++; render(); window.scrollTo({ top: 0 }); });
    $root.on('click', '[data-dc-clear]', () => {
        Object.assign(F, { q: '', type: 'all', status: 'all', sort: 'newest', letter: 'all', quick: 'all', page: 1 });
        $root.find('[data-dc-q]').val('');
        $root.find('[data-dc-type]').val('all');
        $root.find('[data-dc-status]').val('all');
        $root.find('[data-dc-sort]').val('newest');
        render();
    });
    render();

    /* ── ساخت گروهی با AI ── */
    const $dlg = $('#dc-batch');
    const api = createApi({ $root, kind: 'word', ns: 'dictionary', local: LOCAL, ov, t, send });
    let items = [];
    let busy = false;
    const $err = $dlg.find('[data-bt-err]');
    const $list = $dlg.find('[data-bt-list]');
    const $add = $dlg.find('[data-bt-add]');
    const $genT = $dlg.find('[data-bt-gen-t]');
    const bv = (k) => $dlg.find('[data-bt="' + k + '"]').val();
    const takenSet = () => new Set(rows.map((r) => dupKey(r.phrase, r.type)));

    $root.on('click', '[data-dc-batch-open]', function () {
        items = [];
        $list.empty().prop('hidden', true);
        $dlg.find('[data-bt-bar], [data-bt-note]').prop('hidden', true);
        $add.prop('hidden', true);
        $err.prop('hidden', true);
        $genT.text(t('bt.go'));
        openDialog('#dc-batch');
    });

    const asList = (v) => (Array.isArray(v) ? v.map(String) : splitList(v));
    function normalizeItem(it) {
        const type = TYPES.includes(it.type) ? it.type : TYPES.includes(bv('type')) ? bv('type') : '';
        const ipa = it.ipa || {};
        return {
            id: null, status: 'draft', phrase: String(it.phrase || '').trim(), type,
            ipa: { uk: String(ipa.uk || it.ipa_uk || ''), us: String(ipa.us || it.ipa_us || '') }, audio: { uk: '', us: '' },
            meaning: String(it.meaning || '').trim(), example: String(it.example || '').trim(), example_tr: String(it.example_tr || '').trim(),
            translate: String(it.translate || '').trim(), synonyms: asList(it.synonyms), antonyms: asList(it.antonyms), image: null,
        };
    }
    function updateSum() {
        const n = $list.find('input[type=checkbox]:checked').length;
        $add.text(t('bt.add', { n: num(n) })).prop('disabled', n === 0 || busy);
        $dlg.find('[data-bt-sum]').text(num(n) + ' / ' + num(items.length));
        $dlg.find('[data-bt-all]').prop('checked', n > 0 && n === $list.find('input[type=checkbox]:not(:disabled)').length);
    }
    function showItems() {
        const taken = takenSet();
        const seen = new Set();
        $list.empty();
        items.forEach((w, i) => {
            const key = dupKey(w.phrase, w.type);
            const dup = !w.phrase || taken.has(key) || seen.has(key);
            seen.add(key);
            w._dup = dup && !!w.phrase;
            const incomplete = !w.type || !w.translate;
            const li = el('li', 'bk-bt-item' + (dup ? ' is-bad' : ''));
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = !dup;
            cb.disabled = dup;
            cb.setAttribute('data-i', i);
            cb.setAttribute('aria-label', w.phrase);
            cb.className = 'size-4 shrink-0 accent-brand-700';
            const box = el('div', 'min-w-0 flex-1');
            const head = el('div', 'flex flex-wrap items-center gap-1.5');
            const p = el('b', 'text-sm font-extrabold text-brand-900', w.phrase || '—');
            p.setAttribute('dir', 'ltr');
            head.appendChild(p);
            if (w.ipa.us || w.ipa.uk) {
                const ip = el('span', 'text-xs text-slate-400', w.ipa.us || w.ipa.uk);
                ip.setAttribute('dir', 'ltr');
                head.appendChild(ip);
            }
            if (w.type) { const b = el('span', 'ad-badge', t('type.' + w.type)); b.setAttribute('data-tone', 'info'); head.appendChild(b); }
            if (w._dup) { const b = el('span', 'ad-badge', t('bt.dup')); b.setAttribute('data-tone', 'bad'); head.appendChild(b); }
            else if (incomplete) { const b = el('span', 'ad-badge', t('bt.invalid')); b.setAttribute('data-tone', 'warn'); head.appendChild(b); }
            box.appendChild(head);
            if (w.translate) { const tr = el('p', 'bk-bt-q !font-bold', w.translate); tr.setAttribute('dir', 'auto'); box.appendChild(tr); }
            if (w.meaning) { const m = el('p', 'bk-bt-tr', w.meaning); m.setAttribute('dir', 'ltr'); box.appendChild(m); }
            if (w.example) { const m = el('p', 'bk-bt-tr', w.example); m.setAttribute('dir', 'ltr'); box.appendChild(m); }
            li.appendChild(cb);
            li.appendChild(box);
            $list.append(li);
        });
        const has1 = items.length > 0;
        $list.prop('hidden', !has1);
        $dlg.find('[data-bt-bar], [data-bt-note]').prop('hidden', !has1);
        $add.prop('hidden', !has1);
        updateSum();
    }
    $dlg.on('change', '.bk-bt-item input', updateSum);
    $dlg.on('change', '[data-bt-all]', function () {
        $list.find('input[type=checkbox]:not(:disabled)').prop('checked', $(this).prop('checked'));
        updateSum();
    });

    $dlg.on('click', '[data-bt-gen]', async function () {
        if (busy) return;
        const words = parseWords(bv('words'));
        if (!words.length) { $err.text(t('bt.none')).prop('hidden', false); return; }
        busy = true;
        $dlg.addClass('is-busy');
        $dlg.find('[data-bt-gen]').prop('disabled', true);
        $genT.text(t('bt.busy'));
        $err.prop('hidden', true);
        try {
            const seeds = { words: words.join('\n'), type: TYPES.includes(bv('type')) ? bv('type') : '' };
            const payload = { kind: 'word_batch', seeds, fields: ['items'], note: '', current: {}, locale: document.documentElement.lang };
            const r = await API.admin.ai.request(payload, { url: $root.attr('data-ai-url'), demo: () => demoWordBatch(payload) });
            items = ((r && r.fields && r.fields.items) || []).map(normalizeItem);
            if (!items.length) $err.text(t('bt.empty')).prop('hidden', false);
            showItems();
            $genT.text(t('bt.again'));
        } catch (e) {
            $err.text((e && e.message) || t('errors.save')).prop('hidden', false);
            $genT.text(t('bt.go'));
        } finally {
            busy = false;
            $dlg.removeClass('is-busy');
            $dlg.find('[data-bt-gen]').prop('disabled', false);
            updateSum();
        }
    });

    $add.on('click', async function () {
        if (busy) return;
        const chosen = $list.find('input[type=checkbox]:checked').map(function () { return cleanWord(items[parseInt($(this).attr('data-i'), 10)]); }).get();
        if (!chosen.length) return;
        busy = true;
        $add.prop('disabled', true).text(t('bt.adding'));
        try {
            const r = await api.bulk(chosen);
            toast(t('bt.added', { n: num((r && r.saved) || chosen.length) }));
            closeDialog('#dc-batch');
            if (LOCAL && !$root.attr('data-bulk-url')) {
                const merged = ov.merge(base);
                rows.length = 0;
                merged.forEach((x) => rows.push(x));
                Object.assign(F, { q: '', type: 'all', status: 'draft', sort: 'newest', letter: 'all', quick: 'all', page: 1 });
                $root.find('[data-dc-q]').val('');
                $root.find('[data-dc-type]').val('all');
                $root.find('[data-dc-status]').val('draft');
                $root.find('[data-dc-sort]').val('newest');
                render();
            } else location.reload();
        } catch (e) {
            $err.text((e && e.message) || t('errors.save')).prop('hidden', false);
        } finally {
            busy = false;
            updateSum();
        }
    });
});

/* ═════════ ویرایشگر ═════════ */
$(function () {
    const $root = $('[data-dc-edit]');
    if (!$root.length) return;
    const T = readJSON($root, 'data-t', {});
    const t = tFrom(T);
    const ov = overlay(LS);
    let W = readJSON($root, 'data-w', {});
    if (LOCAL && W.id) {
        const saved = ov.get(W.id);
        if (saved) W = Object.assign({}, W, saved);
    }
    W.ipa = Object.assign({ uk: '', us: '' }, W.ipa);
    W.audio = Object.assign({ uk: '', us: '' }, W.audio);
    W.synonyms = W.synonyms || [];
    W.antonyms = W.antonyms || [];
    const api = createApi({ $root, kind: 'word', ns: 'dictionary', local: LOCAL, ov, t, send });
    const $form = $('#dc-form');
    const f = (n) => $form.find('[data-f="' + n + '"]');
    let dirty = false;
    let busy = false;

    // لغت‌های دیگر (برای هشدار تکراری)؛ سرور باید همین را با 422 هم رد کند
    const takenBase = readJSON($root, 'data-taken', []).map((x) => ({ id: x.id, k: x.k }));
    const taken = () => {
        const set = new Set();
        const rows = LOCAL ? ov.merge(takenBase) : takenBase;
        rows.forEach((r) => {
            if (W.id && String(r.id) === String(W.id)) return;
            set.add(r.k || dupKey(r.phrase, r.type));
        });
        return set;
    };

    const markDirty = () => {
        dirty = true;
        $root.find('[data-dc-dirty]').prop('hidden', false);
    };
    const say = (m) => $root.find('[data-dc-msg], [data-dc-msg2]').text(m || '');

    /* ← مدل به فرم */
    function fill() {
        ['phrase', 'type', 'translate', 'meaning', 'example', 'example_tr'].forEach((n) => f(n).val(W[n] || ''));
        f('ipa_uk').val(W.ipa.uk);
        f('ipa_us').val(W.ipa.us);
        f('audio_uk').val(W.audio.uk);
        f('audio_us').val(W.audio.us);
        f('synonyms').val((W.synonyms || []).join(', '));
        f('antonyms').val((W.antonyms || []).join(', '));
        f('image').val(W.image || '');
        renderMedia();
    }
    /* → فرم به مدل */
    function readForm() {
        ['phrase', 'type', 'translate', 'meaning', 'example', 'example_tr'].forEach((n) => (W[n] = f(n).val() || ''));
        W.ipa = { uk: f('ipa_uk').val() || '', us: f('ipa_us').val() || '' };
        W.audio = { uk: f('audio_uk').val() || '', us: f('audio_us').val() || '' };
        W.synonyms = splitList(f('synonyms').val());
        W.antonyms = splitList(f('antonyms').val());
        W.image = f('image').val() || null;
    }
    $form.on('input change', '[data-f]', function () {
        readForm();
        markDirty();
        renderMedia();
        refresh();
    });
    $(document).on('admin:ai-applied', () => { readForm(); markDirty(); renderMedia(); refresh(); });

    /* ── تصویر و صدا ── */
    function renderMedia() {
        const img = (W.image || '').trim();
        const $prev = $root.find('[data-dc-img-prev]');
        $prev.toggleClass('has-img', !!img).css('background-image', img ? 'url("' + img.replace(/"/g, '%22') + '")' : '');
        $root.find('[data-dc-up="image"]').toggleClass('has-file', !!img);
        $root.find('[data-dc-clear="image"]').prop('hidden', !img);
        ['uk', 'us'].forEach((a) => {
            const u = (W.audio[a] || '').trim();
            $root.find('[data-dc-clear="audio_' + a + '"]').prop('hidden', !u);
            $root.find('[data-dc-up="audio_' + a + '"]').toggleClass('has-file', !!u);
        });
    }
    const MB = { image: 2, audio_uk: 2, audio_us: 2 };
    $root.on('change', '[data-dc-file]', async function () {
        const kind = $(this).attr('data-dc-file');
        const file = this.files && this.files[0];
        this.value = '';
        if (!file) return;
        const $box = $root.find('[data-dc-up="' + kind + '"]');
        const $err = $box.find('[data-dc-up-err]').prop('hidden', true);
        const isImg = kind === 'image';
        if (!new RegExp('^' + (isImg ? 'image' : 'audio') + '/').test(file.type)) { $err.text(t(isImg ? 'errors.type_image' : 'errors.type_audio')).prop('hidden', false); return; }
        if (file.size > MB[kind] * 1048576) { $err.text(t('errors.size', { n: num(MB[kind]) })).prop('hidden', false); return; }
        $box.addClass('is-busy');
        try {
            const r = isImg && LOCAL && !$root.attr('data-upload-url') && !API.admin.dictionary.api.upload ? await localImage(file) : await api.upload(file, kind);
            f(kind).val(r.url);
            readForm();
            markDirty();
            renderMedia();
            refresh();
        } catch (e) {
            $err.text((e && e.message) || t('errors.upload')).prop('hidden', false);
        } finally { $box.removeClass('is-busy'); }
    });
    $root.on('click', '[data-dc-clear]', function () {
        f($(this).attr('data-dc-clear')).val('');
        readForm();
        markDirty();
        renderMedia();
        refresh();
    });

    /* ── پخش تلفظ: فایل یا صدای مرورگر ── */
    let player = null;
    function stopSpeak() {
        try { window.speechSynthesis && window.speechSynthesis.cancel(); } catch (e) { /* ignore */ }
        if (player) { try { player.pause(); } catch (e) { /* ignore */ } player = null; }
        $root.find('[data-dc-play].is-on').removeClass('is-on');
    }
    $root.on('click', '[data-dc-play]', function () {
        const $b = $(this);
        const a = $b.attr('data-dc-play');
        if ($b.hasClass('is-on')) return stopSpeak();
        stopSpeak();
        const url = (W.audio[a] || '').trim();
        if (url) {
            player = new Audio(url);
            player.onended = stopSpeak;
            const p = player.play();
            if (p && p.catch) p.catch(() => { toast(t('v.audio_missing'), true); stopSpeak(); });
        } else if ((W.phrase || '').trim() && 'speechSynthesis' in window) {
            const u = new SpeechSynthesisUtterance(W.phrase);
            u.lang = a === 'uk' ? 'en-GB' : 'en-US';
            u.onend = stopSpeak;
            window.speechSynthesis.speak(u);
        } else { toast(t('pv.no_sound'), true); return; }
        $b.addClass('is-on');
    });

    /* ── پیش‌نمایش ── */
    function renderPreview() {
        const $pv = $root.find('[data-dc-pv]');
        const $img = $pv.find('[data-pv=img]').empty();
        $img.append(thumb(W, 'dc-pv__img'));
        const $ph = $pv.find('[data-pv=phrase]').empty();
        if ((W.phrase || '').trim()) $ph.text(W.phrase.trim());
        else $ph.append(el('span', 'text-slate-300', t('pv.ph_phrase')));
        $pv.find('[data-pv=type]').text(W.type ? t('type.' + W.type) : '').prop('hidden', !W.type);
        ['uk', 'us'].forEach((a) => {
            const $row = $pv.find('[data-pv-ipa="' + a + '"]');
            const ipa = (W.ipa[a] || '').trim();
            $row.prop('hidden', !ipa && !(W.audio[a] || '').trim());
            $row.find('[data-pv=ipa]').text(ipa || '—');
        });
        $pv.find('[data-pv=translate]').text(W.translate || '').prop('hidden', !W.translate);
        $pv.find('[data-pv=meaning]').text(W.meaning || '').prop('hidden', !W.meaning);
        const $ex = $pv.find('[data-pv=example]');
        $ex.prop('hidden', !W.example);
        $ex.find('[data-pv=ex]').text(W.example || '');
        $ex.find('[data-pv=extr]').text(W.example_tr || '').prop('hidden', !W.example_tr);
        const chips = (sel, arr) => {
            const $c = $pv.find(sel);
            $c.prop('hidden', !arr.length);
            $c.find('ul').empty().append(arr.map((x) => el('li', null, x)));
        };
        chips('[data-pv=syn]', W.synonyms);
        chips('[data-pv=ant]', W.antonyms);
    }

    /* ── بررسی (چک‌لیست زنده + نوار کامل بودن) ── */
    function renderCheck() {
        const v = validate(W, taken());
        const errs = v.filter((x) => x.level === 'error');
        const warns = v.filter((x) => x.level === 'warn');
        const $ul = $root.find('[data-dc-ck]').empty();
        if (!v.length) {
            const li = el('li', null, t('ck.ok'));
            li.setAttribute('data-s', 'ok');
            $ul.append(li);
        }
        const group = (arr, s, title) => {
            if (!arr.length) return;
            $ul.append(el('li', 'bk-ck__h', title));
            arr.forEach((x) => {
                const li = el('li', null, t('v.' + x.code));
                li.setAttribute('data-s', s);
                $ul.append(li);
            });
        };
        group(errs, 'bad', t('ck.errors'));
        group(warns, 'warn', t('ck.warns'));
        const p = parts(W);
        const n = PARTS.filter((k) => p[k]).length;
        const $s = $root.find('[data-dc-segs]').empty();
        PARTS.forEach((k) => {
            const i = el('i');
            i.setAttribute('data-on', p[k] ? '1' : '0');
            i.setAttribute('title', t('part.' + k));
            $s.append(i);
        });
        $root.find('[data-dc-done]').text(t('done_of', { a: num(n) }));
        $root.find('[data-dc-save=published]').prop('disabled', errs.length > 0);
    }
    function refresh() {
        renderPreview();
        renderCheck();
    }

    /* ── ذخیره / انتشار / حذف ── */
    function fieldOf(name) {
        const $f = f(name).first();
        return $f.closest('.ad-field');
    }
    function showErrors(errors) {
        Object.keys(errors || {}).forEach((k) => {
            const $w = fieldOf(k);
            if ($w.length) $w.addClass('has-error').find('.ad-error').first().text([].concat(errors[k])[0]).prop('hidden', false);
        });
    }
    async function save(mode) {
        if (busy) return;
        readForm();
        const status = mode === 'published' ? 'published' : mode === 'draft' ? 'draft' : W.status || 'draft';
        const v = validate(W, taken()).filter((x) => x.level === 'error');
        // پیش‌نویس فقط به لغت و عدم‌تکراری بودن نیاز دارد
        const blocking = status === 'published' ? v : v.filter((x) => x.code === 'phrase_required' || x.code === 'duplicate');
        $form.find('.has-error').removeClass('has-error').find('.ad-error').prop('hidden', true);
        if (blocking.length) {
            const first = blocking[0];
            fieldOf(first.field).addClass('has-error').find('.ad-error').first().text(t('v.' + first.code)).prop('hidden', false);
            toast(t('v.' + first.code), true);
            say(t('fix', { n: num(blocking.length) }));
            f(first.field).first().trigger('focus');
            return;
        }
        busy = true;
        $root.find('[data-dc-save]').prop('disabled', true);
        say(t('saving'));
        const payload = Object.assign(cleanWord(W), { status });
        try {
            const wasNew = !W.id;
            const r = await api.save(payload, wasNew);
            W.id = r.id || W.id;
            W.status = r.status || status;
            W.updated_at = Math.floor(Date.now() / 1000);
            dirty = false;
            $root.find('[data-dc-dirty]').prop('hidden', true);
            $root.find('[data-dc-status]').text(t('status_label.' + W.status)).attr('data-tone', W.status === 'published' ? 'ok' : '');
            say(t('saved'));
            toast(t(status === 'published' ? 'published_ok' : 'saved'));
            if (mode === 'next') {
                location.assign($root.attr('data-new-url'));
                return;
            }
            if (wasNew && W.id) {
                try { history.replaceState(null, '', r.url || $root.attr('data-edit-url').replace('__ID__', W.id)); } catch (e) { /* ignore */ }
                $root.find('[data-dc-save=next]').remove();
            }
            $root.trigger('admin:word-saved', { id: W.id, status: W.status });
        } catch (e) {
            say('');
            toast((e && e.message) || t('errors.save'), true);
            if (e && e.errors) showErrors(e.errors);
        } finally {
            busy = false;
            $root.find('[data-dc-save]').prop('disabled', false);
            renderCheck();
        }
    }
    $root.on('click', '[data-dc-save]', function () { save($(this).attr('data-dc-save')); });

    $root.on('click', '[data-dc-delete-open]', () => openDialog('#dc-delete'));
    $(document).on('click', '[data-dc-delete]', async function () {
        const $b = $(this).prop('disabled', true);
        try {
            await api.remove(W.id);
            dirty = false;
            location.assign($root.attr('data-list-url'));
        } catch (e) {
            toast((e && e.message) || t('errors.save'), true);
            $b.prop('disabled', false);
        }
    });
    window.addEventListener('beforeunload', (e) => {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    fill();
    refresh();
    dirty = false;
    $root.find('[data-dc-dirty]').prop('hidden', true);

    // برای تست و بک‌اند
    API.admin.dictionary = API.admin.dictionary || {};
    API.admin.dictionary.model = () => cleanWord(W);
});
