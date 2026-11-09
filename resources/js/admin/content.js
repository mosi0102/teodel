import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, csrf, num } from './common';
import { parseTime, fmtTime, parseLrc } from './song';

/**
 * ویرایشگر مشترک محتوا در پنل مدیریت: پادکست (سری و اپیزود)، Shadowing و کتاب.
 * همه‌ی رفتار با data-* در Blade تعریف می‌شود؛ این فایل فقط موتور است (resources/views/admin/content/editor.blade.php).
 *
 * ── نشانه‌گذاری ─────────────────────────────────────────────────────────
 *   ریشه:   [data-ce] data-kind data-model(JSON) data-need(JSON) data-t(JSON) data-list-url data-edit-url(:id)
 *           data-save-url data-upload-url data-delete-url data-ai-url (اختیاری؛ نبودن = حالت دمو)
 *   فیلد:   [data-f="title"] یا مسیر نقطه‌دار [data-f="versions.a1.enabled"] (input/select/textarea/checkbox؛ data-num = عدد)
 *   لیست:   [data-ce-list="words"] + <template data-ce-tpl="words"> با [data-ce-item]؛ فیلدهای آیتم [data-k="phrase"] (نسبی)
 *           data-ce-tpl روی لیست = نام قالب (برای لیست‌های هم‌شکل مثل versions.a1.sentences ← قالب «sentences»)
 *           [data-ce-add="path"] [data-ce-del] [data-ce-up] [data-ce-down] [data-ce-n] [data-ce-text="k"] [data-ce-href="/x/:id"]
 *           [data-ce-empty="path"] [data-ce-count="path"] ؛ data-new='{…}' روی دکمه‌ی افزودن = مقدار پیش‌فرض آیتم
 *   زمان:   [data-k="t"][data-time] ← نمایش mm:ss.xx ؛ [data-ce-now="t"] زمان فعلی پخش‌کننده ؛ [data-ce-seek="t"] پخش از آن زمان
 *   لحن:    textarea[data-ce-prosody] + [data-ce-prosody-out] (پیش‌نمایش زنده، هم‌قاعده با Shadowing::prosody) + [data-ce-pmark="^"]
 *   آپلود:  [data-ce-up="cover"] یا داخل آیتم [data-ce-up-k="audio"]؛ data-type=image|audio|file ؛ data-duration="duration" (مدت خودکار)
 *           داخلش: input[type=file][data-ce-file] ، [data-ce-up-prev] ، [data-ce-up-clear] ، [data-ce-up-meta]
 *   شرط:    [data-ce-if="versions.a1.enabled"] یا [data-ce-if="audio_source=tts"] ← نمایش فقط وقتی برقرار است
 *   AI:     [data-ce-ai="kind"] data-fields="a,b" (فیلدهای ساده) یا data-list="path" (لیست؛ data-mode=replace|append)؛
 *           داخل آیتم: [data-ce-ai-item="kind"] ← خروجی روی همان آیتم
 *   TTS:    [data-ce-tts="audio-path"] data-text="path" (متن یا لیست جمله‌ها) + data-voice/accent/speed="path"
 *   درون‌ریزی متن: [data-ce-import="transcript"] ← دیالوگ #ce-import (LRC / SRT / متن ساده؛ فایل یا پیست)
 *
 * ── 🔌 بک‌اند ─────────────────────────────────────────────────────────
 *   ذخیره:  data-save-url  POST (جدید) / PUT (ویرایش) JSON { kind, status, model } ← { id, status, url? } | 422 { message, errors }
 *           یا رویداد لغوشدنی admin:content-save { kind, payload, status, done({id,status}), fail(msg) }
 *           یا Teodel.admin.content.api = { save, upload, remove }
 *   آپلود:  data-upload-url POST multipart { file, type: image|audio|file, kind } ← { url, duration? }
 *           یا admin:content-upload { kind, file, type, done({url,duration}), fail }
 *   حذف:    data-delete-url DELETE ← { ok } | admin:content-delete { kind, id, done, fail }
 *   AI:     Teodel.admin.ai.request (ai.js) با kind های هر بخش ← { fields:{…} | fields:{ items:[…] }, missing:[…] }؛ TTS ← fields.audio (url)
 *   LOCAL = true: دمو؛ ذخیره در localStorage (teodel:admin:<kind>:<id>). با اتصال بک‌اند false کنید.
 */
const LOCAL = true;

const SAMPLE_AUDIO = '/songs/' + encodeURIComponent('Benny Rivers – Step by Step in Time.mp3');
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const clone = (o) => JSON.parse(JSON.stringify(o));
const filled = (v) => (Array.isArray(v) ? v.length > 0 : String(v == null ? '' : v).trim() !== '');
const store = {
    get(k) {
        try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; }
    },
    set(k, v) {
        try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* */ }
    },
    del(k) {
        try { localStorage.removeItem(k); } catch (e) { /* */ }
    },
};

/* ── مسیرهای نقطه‌دار ── */
export function getPath(o, path) {
    return String(path).split('.').reduce((a, k) => (a == null ? undefined : a[k]), o);
}
export function setPath(o, path, v) {
    const ks = String(path).split('.');
    let a = o;
    ks.slice(0, -1).forEach((k, i) => {
        if (a[k] == null || typeof a[k] !== 'object') a[k] = /^\d+$/.test(ks[i + 1]) ? [] : {};
        a = a[k];
    });
    a[ks[ks.length - 1]] = v;
}

/* ── لحن (هم‌قاعده با App\Support\Shadowing::prosody) ── */
const TONE_PATH = { rise: 'M4.5 11.5 11.5 4.5M6 4.5h5.5V10', fall: 'M4.5 4.5l7 7M11.5 6v5.5H6', mid: 'M3 8h10M9.5 4.5 13 8l-3.5 3.5' };
export function parseProsody(line) {
    return String(line || '').split('|').map((raw) => {
        let r = raw.trim();
        if (!r) return null;
        let tone = 'mid';
        const m = r.match(/([\^_-])$/);
        if (m) {
            tone = { '^': 'rise', _: 'fall', '-': 'mid' }[m[1]];
            r = r.slice(0, -1).trim();
        }
        const words = r.split(/\s+/).filter(Boolean).map((w) => ({ t: w.replace(/\*/g, ''), s: /^\*.+\*[^\w*]*$/.test(w) }));
        return words.length ? { tone, words } : null;
    }).filter(Boolean);
}
export function renderProsody(text) {
    const lines = String(text || '').split(/\n/).map(parseProsody).filter((l) => l.length);
    if (!lines.length) return '';
    return '<span class="pr" dir="ltr" lang="en">' + lines.map((segs) => '<span class="pr-line">' + segs.map((s) =>
        `<span class="pr-seg pr-seg--${s.tone}"><span class="pr-words">${s.words.map((w) => `<span class="pr-w${w.s ? ' is-stress' : ''}">${esc(w.t)}</span>`).join(' ')}</span><span class="pr-tone" aria-hidden="true"><svg viewBox="0 0 16 16"><path d="${TONE_PATH[s.tone]}"/></svg></span></span>`).join('') + '</span>').join('') + '</span>';
}
/** پیشنهاد خودکار لحن (دمو): جمله‌ی سؤالی بله/خیر بالا، بقیه پایین؛ ویرگول = گروه با لحن یکنواخت؛ کلمه‌ی بلند آخر هر گروه تأکید */
export function autoProsody(sentence) {
    const s = String(sentence || '').trim();
    if (!s) return '';
    const isYesNo = /\?$/.test(s) && /^(do|does|did|is|are|was|were|can|could|will|would|have|has|should)\b/i.test(s);
    const parts = s.split(/,\s*/);
    return parts.map((p, i) => {
        const ws = p.split(/\s+/);
        let k = ws.length - 1;
        while (k > 0 && ws[k].replace(/[^a-z]/gi, '').length < 4) k--;
        ws[k] = '*' + ws[k].replace(/[.!?]+$/, '') + '*' + (ws[k].match(/[.!?]+$/) || [''])[0];
        const last = i === parts.length - 1;
        return ws.join(' ') + (last ? (isYesNo ? '^' : '_') : '-');
    }).join('| ');
}

/* ── درون‌ریزی متن زمان‌دار ── */
export function parseSrt(text) {
    const out = [];
    String(text || '').replace(/\r/g, '').split(/\n\s*\n/).forEach((block) => {
        const lines = block.split('\n').filter((l) => l.trim() !== '');
        const i = lines.findIndex((l) => /-->/.test(l));
        if (i < 0) return;
        const m = lines[i].match(/(\d+):(\d+):(\d+)[,.](\d+)/);
        if (!m) return;
        const t = +m[1] * 3600 + +m[2] * 60 + +m[3] + +m[4] / 1000;
        const body = lines.slice(i + 1).join(' ').replace(/<[^>]+>/g, '').trim();
        if (body) out.push({ t: Math.round(t * 100) / 100, text: body });
    });
    return out;
}
export function parseTimed(text) {
    if (/-->/.test(text)) return parseSrt(text);
    return parseLrc(text).map((l) => ({ t: l.t, text: l.text }));
}

/* ── دموی AI (فقط تا وصل‌شدن بک‌اند) ── */
const API = (window.Teodel = window.Teodel || {});
API.admin = API.admin || {};
API.admin.content = API.admin.content || {};
API.admin.ai = API.admin.ai || {};
const demos = {
    podcast_info: () => ({ fields: { category: 'Lifestyle', desc_fa: 'سری کوتاه و صمیمی درباره‌ی زندگی روزمره؛ مناسب برای تقویت شنیدن.', desc_en: 'A short, friendly series about everyday life for listening practice.', tags: 'Lifestyle, Conversation' }, missing: ['cover'] }),
    transcript_tr: ({ current }) => ({ fields: { items: (current.transcript || []).map((l) => Object.assign({}, l, { tr: l.tr || 'ترجمه‌ی نمونه‌ی این جمله' })) } }),
    transcript_ai: () => ({ fields: { items: [{ t: 1.5, text: 'Hello and welcome to the show.', tr: '' }, { t: 5.2, text: 'Today we are talking about healthy habits.', tr: '' }, { t: 10.8, text: 'Let’s start with a simple question.', tr: '' }] } }),
    words_suggest: ({ current }) => {
        const src = JSON.stringify(current).match(/\b[a-z]{7,}\b/gi) || ['routine', 'habit'];
        const uniq = [...new Set(src.map((w) => w.toLowerCase()))].slice(0, 4);
        return { fields: { items: uniq.map((p) => ({ phrase: p, type: 'noun', level: 'B1', meaning: 'Meaning of “' + p + '” (AI demo).', translate: 'ترجمه (نمونه)', pdf: true, where: 1 })) } };
    },
    word_fill: ({ item }) => ({ fields: { type: item.type || 'noun', level: item.level || 'B1', meaning: item.meaning || 'A short English definition (AI demo).', translate: item.translate || 'ترجمه (نمونه)' } }),
    dictation_suggest: ({ current }) => {
        const tr = (current.transcript || []).filter((l) => String(l.text).split(' ').length >= 6).slice(0, 4);
        return { fields: { items: tr.map((l) => ({ text: l.text, start: Math.max(0, l.t - 0.3), end: l.t + 3.5, approved: false })) } };
    },
    shadowing_suggest: ({ current }) => {
        const tr = (current.transcript || []).slice(1, 4);
        return { fields: { items: tr.map((l) => ({ start: Math.max(0, l.t - 0.3), end: l.t + 4, text: l.text, prosody: autoProsody(l.text) })) } };
    },
    prosody: ({ item }) => ({ fields: { prosody: String(item.text || '').split('\n').map(autoProsody).join('\n') } }),
    main_idea: () => ({ fields: { main_idea: 'The speaker describes a simple daily routine and explains why starting the day slowly helps.' } }),
    shadowing_text: ({ seeds }) => ({ fields: { items: ['Hi, my name is Sam.', 'I live in a small town near the sea.', 'Every morning, I walk my dog on the beach.'].map((t) => ({ text: t, prosody: autoProsody(t) })) }, missing: [], note: seeds }),
    questions_suggest: () => ({ fields: { items: [{ q: 'Where do you live?', answer: 'I live in …', keys: 'live, town, city', criteria: 'Mentions a place.' }, { q: 'What do you do every morning?', answer: 'Every morning I …', keys: 'morning, every', criteria: 'Uses present simple.' }] } }),
    book_info: () => ({ fields: { genre: 'Adventure', year: '2020', publisher: 'Demo Press', pages: '64', summary_en: 'A short, simple story for learners (AI demo).' }, missing: ['isbn'] }),
    book_summary: () => ({ fields: { summary_fa: 'خلاصه‌ی ساده‌ی کتاب برای زبان‌آموزان (نمونه‌ی AI).', summary_en: 'A simple summary of the book for learners (AI demo).' } }),
    author_bio: () => ({ fields: { author_bio_fa: 'نویسنده‌ای که برای زبان‌آموزان با جمله‌های ساده می‌نویسد (نمونه‌ی AI).', author_bio_en: 'A writer who writes simple stories for learners (AI demo).' } }),
    chapter_summary: ({ item }) => ({ fields: { summary_fa: 'خلاصه‌ی فصل «' + (item.title || '') + '» (نمونه‌ی AI).', summary_en: 'Summary of “' + (item.title || '') + '” (AI demo).' } }),
    tts: () => ({ fields: { audio: SAMPLE_AUDIO }, missing: [] }),
};
Object.keys(demos).forEach((k) => { if (!API.admin.ai.demos || !API.admin.ai.demos[k]) { API.admin.ai.demos = API.admin.ai.demos || {}; API.admin.ai.demos[k] = demos[k]; } });

/* ── محاسبه‌ی کامل‌بودن (هم‌قاعده با App\Support\AdminContent::doneMap) ── */
function ruleOk(m, r) {
    if (r === '@any') return Object.values(m.versions || {}).some((v) => v && v.enabled);
    if (r.startsWith('@v:')) {
        const v = (m.versions || {})[r.slice(3)];
        return !v || !v.enabled || (filled(v.audio) && filled(v.sentences) && filled(v.questions));
    }
    const lm = r.match(/^(.+)\[\](?::(\w+))?$/);
    if (lm) {
        const a = getPath(m, lm[1]);
        return Array.isArray(a) && a.length > 0 && (!lm[2] || a.every((i) => filled(i && i[lm[2]])));
    }
    return filled(getPath(m, r));
}

/* ══════════════════════════════════════════════════════════════════ */
function init(root) {
    const $root = $(root);
    const kind = $root.attr('data-kind');
    const T = readJSON($root, 'data-t', {});
    const need = readJSON($root, 'data-need', {});
    const urls = {
        save: $root.attr('data-save-url'), upload: $root.attr('data-upload-url'), del: $root.attr('data-delete-url'),
        ai: $root.attr('data-ai-url'), list: $root.attr('data-list-url'), edit: $root.attr('data-edit-url'),
    };
    let M = readJSON($root, 'data-model', {});
    const localKey = () => 'teodel:admin:' + kind + ':' + (M.id || 'new');
    if (LOCAL && M.id) {
        const saved = store.get(localKey());
        if (saved && saved.id === M.id) M = saved;
    }
    let dirty = false;
    const t = (k, r = {}) => String(T[k] || k).replace(/:(\w+)/g, (m, x) => (x in r ? r[x] : m));

    /* ── وضعیت ── */
    function markDirty() {
        dirty = true;
        $root.find('[data-ce-dirty]').prop('hidden', false);
        refresh();
    }
    function doneMap() {
        const out = {};
        Object.keys(need).forEach((tab) => { out[tab] = need[tab].every((r) => ruleOk(M, r)); });
        return out;
    }
    function refresh() {
        const d = doneMap();
        const tabs = Object.keys(d);
        tabs.forEach((k) => $root.find('[data-ce-dot="' + k + '"]').attr('data-s', d[k] ? 'ok' : 'todo'));
        const ok = tabs.filter((k) => d[k]).length;
        $root.find('[data-ce-progress]').text(t('progress', { a: num(ok), b: num(tabs.length) }));
        $root.find('[data-ce-title]').text(M.title || t('untitled'));
        const d0 = Number(M.duration) || 0;
        $root.find('[data-ce-dur]').text(d0 ? Math.floor(d0 / 60) + ':' + String(d0 % 60).padStart(2, '0') : '—');
        $root.find('[data-ce-if]').each(function () {
            const [p, v] = $(this).attr('data-ce-if').split('=');
            const val = getPath(M, p);
            $(this).prop('hidden', v === undefined ? !val : String(val) !== v);
        });
        $root.find('[data-ce-count]').each(function () {
            const a = getPath(M, $(this).attr('data-ce-count'));
            $(this).text(num(Array.isArray(a) ? a.length : 0));
        });
    }

    /* ── فیلدهای ساده ── */
    function readEl(el) {
        if (el.type === 'checkbox') return el.checked;
        if (el.hasAttribute('data-time')) return parseTime(el.value);
        if (el.hasAttribute('data-num')) return el.value === '' ? '' : Number(String(el.value).replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)));
        return el.value;
    }
    function writeEl(el, v) {
        if (el.type === 'checkbox') el.checked = !!v;
        else if (el.hasAttribute('data-time')) el.value = v === '' || v == null ? '' : fmtTime(v);
        else el.value = v == null ? '' : v;
        if (el.hasAttribute('data-ce-prosody')) previewProsody(el);
    }
    function syncFields() {
        $root.find('[data-f]').each(function () { writeEl(this, getPath(M, $(this).attr('data-f'))); });
        $root.find('[data-ce-up]').each(function () { renderUpload($(this), getPath(M, $(this).attr('data-ce-up'))); });
    }
    $root.on('input change', '[data-f]', function () {
        setPath(M, $(this).attr('data-f'), readEl(this));
        $(this).closest('.ad-field').removeClass('is-ai is-missing has-error');
        if (this.hasAttribute('data-ce-prosody')) previewProsody(this);
        if ($(this).attr('data-f') === 'audio') loadPlayer();
        markDirty();
    });

    /* ── لیست‌ها ── */
    function listOf(path) {
        let a = getPath(M, path);
        if (!Array.isArray(a)) {
            a = [];
            setPath(M, path, a);
        }
        return a;
    }
    function tplFor($list) {
        const name = $list.attr('data-ce-tpl') || $list.attr('data-ce-list');
        return $root.find('template[data-ce-tpl="' + name + '"]')[0];
    }
    function fillItem($el, item, i, path) {
        $el.attr({ 'data-ce-idx': i, 'data-ce-path': path });
        $el.find('[data-ce-n]').text(num(i + 1));
        $el.find('[data-k]').each(function () { writeEl(this, getPath(item, $(this).attr('data-k'))); });
        $el.find('[data-ce-text]').each(function () {
            const k = $(this).attr('data-ce-text');
            let v = getPath(item, k);
            if ($(this).attr('data-fmt') === 'mmss') v = v ? Math.floor(v / 60) + ':' + String(Math.round(v % 60)).padStart(2, '0') : '—';
            if ($(this).attr('data-fmt') === 'status') v = t('status_' + v);
            if ($(this).attr('data-fmt') === 'num') v = num(Number(v) || 0);
            $(this).text(v == null || v === '' ? '—' : v);
        });
        $el.find('[data-ce-href]').each(function () {
            $(this).attr('href', $(this).attr('data-ce-href').replace(/:(\w+)/g, (m, k) => (k === 'model' ? M.id : item[k] != null ? item[k] : m)));
        });
        $el.find('[data-ce-up-k]').each(function () { renderUpload($(this), getPath(item, $(this).attr('data-ce-up-k'))); });
        $el.find('[data-ce-up]').prop('disabled', false);
        $el.find('[data-ce-flag]').each(function () {
            const k = $(this).attr('data-ce-flag');
            $(this).prop('hidden', !getPath(item, k));
        });
    }
    function renderList(path) {
        const $lists = $root.find('[data-ce-list="' + path + '"]');
        $lists.each(function () {
            const $list = $(this);
            const tpl = tplFor($list);
            if (!tpl) return;
            const arr = listOf(path);
            const frag = document.createDocumentFragment();
            arr.forEach((item, i) => {
                const node = tpl.content.firstElementChild.cloneNode(true);
                fillItem($(node), item, i, path);
                frag.appendChild(node);
            });
            $list.empty().append(frag);
        });
        $root.find('[data-ce-empty="' + path + '"]').prop('hidden', listOf(path).length > 0);
        refresh();
    }
    function renderAllLists() {
        const seen = new Set();
        $root.find('[data-ce-list]').each(function () { seen.add($(this).attr('data-ce-list')); });
        seen.forEach(renderList);
    }
    const itemCtx = (el) => {
        const $it = $(el).closest('[data-ce-item]');
        const path = $it.attr('data-ce-path');
        const idx = Number($it.attr('data-ce-idx'));
        return { $it, path, idx, arr: listOf(path), item: listOf(path)[idx] };
    };
    $root.on('input change', '[data-ce-item] [data-k]', function () {
        const c = itemCtx(this);
        if (!c.item) return;
        setPath(c.item, $(this).attr('data-k'), readEl(this));
        $(this).closest('.ad-field').removeClass('is-ai');
        c.$it.removeClass('is-ai');
        if (this.hasAttribute('data-ce-prosody')) previewProsody(this);
        if (hooks[kind] && hooks[kind].item) hooks[kind].item(c, $(this).attr('data-k'));
        markDirty();
    });
    $root.on('click', '[data-ce-add]', function () {
        const path = $(this).attr('data-ce-add');
        const arr = listOf(path);
        arr.push(Object.assign({}, readJSON($(this), 'data-new', {})));
        renderList(path);
        markDirty();
        const $last = $root.find('[data-ce-list="' + path + '"] [data-ce-item]').last();
        $last.find('input,textarea,select').first().trigger('focus');
        if ($last[0]) $last[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
    $root.on('click', '[data-ce-del]', function () {
        const c = itemCtx(this);
        c.arr.splice(c.idx, 1);
        if (hooks[kind] && hooks[kind].reorder) hooks[kind].reorder(c.path, c.arr);
        renderList(c.path);
        markDirty();
        toast(t('removed'));
    });
    $root.on('click', '[data-ce-up],[data-ce-down]', function () {
        const c = itemCtx(this);
        const j = c.idx + (this.hasAttribute('data-ce-up') ? -1 : 1);
        if (j < 0 || j >= c.arr.length) return;
        [c.arr[c.idx], c.arr[j]] = [c.arr[j], c.arr[c.idx]];
        if (hooks[kind] && hooks[kind].reorder) hooks[kind].reorder(c.path, c.arr);
        renderList(c.path);
        markDirty();
        $root.find('[data-ce-list="' + c.path + '"] [data-ce-item]').eq(j).find(this.hasAttribute('data-ce-up') ? '[data-ce-up]' : '[data-ce-down]').trigger('focus');
    });

    /* ── پخش‌کننده‌ی مشترک (تایم‌گیری) ── */
    const audio = $root.find('[data-ce-audio]')[0];
    function loadPlayer() {
        if (!audio) return;
        const src = M.audio || '';
        $root.find('[data-ce-player]').prop('hidden', !src);
        $root.find('[data-ce-noaudio]').prop('hidden', !!src);
        if (src && audio.getAttribute('src') !== src) audio.setAttribute('src', src);
    }
    $root.on('click', '[data-ce-now]', function () {
        if (!audio || !M.audio) return toast(t('no_audio'), true);
        const c = itemCtx(this);
        setPath(c.item, $(this).attr('data-ce-now'), Math.round(audio.currentTime * 100) / 100);
        fillItem(c.$it, c.item, c.idx, c.path);
        markDirty();
    });
    $root.on('click', '[data-ce-seek]', function () {
        if (!audio || !M.audio) return toast(t('no_audio'), true);
        const c = itemCtx(this);
        const s = Number(getPath(c.item, $(this).attr('data-ce-seek'))) || 0;
        audio.currentTime = s;
        audio.play().catch(() => {});
    });

    /* ── لحن ── */
    function previewProsody(el) {
        const $out = $(el).closest('[data-ce-prosody-wrap]').find('[data-ce-prosody-out]');
        const html = renderProsody(el.value);
        $out.html(html || '<span class="text-xs text-slate-400">' + esc(t('prosody_empty')) + '</span>');
    }
    $root.on('click', '[data-ce-pmark]', function () {
        const ta = $(this).closest('[data-ce-prosody-wrap]').find('[data-ce-prosody]')[0];
        if (!ta) return;
        const mark = $(this).attr('data-ce-pmark');
        const a = ta.selectionStart;
        const b = ta.selectionEnd;
        const v = ta.value;
        if (mark === '*') ta.value = v.slice(0, a) + '*' + (v.slice(a, b) || 'word') + '*' + v.slice(b);
        else if (mark === 'auto') {
            const src = ta.closest('[data-ce-item]') ? (itemCtx(ta).item.text || '') : '';
            ta.value = String(src || v.replace(/[*^_|-]/g, '')).split('\n').map(autoProsody).join('\n');
        } else ta.value = v.slice(0, b) + mark + v.slice(b);
        $(ta).trigger('input').trigger('focus');
    });

    /* ── آپلود ── */
    function renderUpload($u, val) {
        const type = $u.attr('data-type') || 'file';
        const $p = $u.find('[data-ce-up-prev]').first();
        $u.toggleClass('has-file', !!val);
        $u.find('[data-ce-up-clear]').first().prop('hidden', !val);
        if (type === 'image') $p.css('background-image', val ? 'url("' + val + '")' : '').toggleClass('has-img', !!val);
        const $m = $u.find('[data-ce-up-meta]').first();
        if (type === 'audio') $m.html(val ? '<audio controls preload="none" src="' + esc(val) + '" class="ce-audio"></audio>' : '');
        else if (type === 'file') $m.html(val ? '<a href="' + esc(val) + '" target="_blank" rel="noopener" class="ce-file" dir="ltr">' + esc(decodeURIComponent(String(val).split('/').pop())) + '</a>' : '');
        $u.find('[data-ce-up-url]').first().val(val || '');
    }
    async function upload(file, type) {
        const ev = $.Event('admin:content-upload');
        const viaEvent = await new Promise((resolve) => {
            $(document).trigger(ev, [{ kind, file, type, done: (r) => resolve({ ok: r }), fail: (m) => resolve({ err: m || '' }) }]);
            if (!ev.isDefaultPrevented()) resolve(null);
        });
        if (viaEvent) { if (viaEvent.err !== undefined) throw new Error(viaEvent.err); return viaEvent.ok; }
        if (API.admin.content.api && API.admin.content.api.upload) return API.admin.content.api.upload(file, type, kind);
        if (urls.upload) {
            const fd = new FormData();
            fd.append('file', file);
            fd.append('type', type);
            fd.append('kind', kind);
            const r = await fetch(urls.upload, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            if (!r.ok) throw new Error('upload');
            return r.json();
        }
        return { url: URL.createObjectURL(file) }; // دمو
    }
    const audioDuration = (src) => new Promise((resolve) => {
        const a = new Audio();
        a.preload = 'metadata';
        a.onloadedmetadata = () => resolve(isFinite(a.duration) ? Math.round(a.duration) : 0);
        a.onerror = () => resolve(0);
        a.src = src;
    });
    function setUpVal($u, val, extra = {}) {
        if ($u.is('[data-ce-up]')) {
            setPath(M, $u.attr('data-ce-up'), val);
            if ($u.attr('data-duration') && extra.duration != null) setPath(M, $u.attr('data-duration'), extra.duration);
            if ($u.attr('data-ce-up') === 'audio') loadPlayer();
        } else {
            const c = itemCtx($u[0]);
            setPath(c.item, $u.attr('data-ce-up-k'), val);
            if ($u.attr('data-duration') && extra.duration != null) setPath(c.item, $u.attr('data-duration'), extra.duration);
        }
        renderUpload($u, val);
        syncFields();
        markDirty();
    }
    $root.on('change', '[data-ce-file]', async function () {
        const file = this.files && this.files[0];
        this.value = '';
        if (!file) return;
        const $u = $(this).closest('[data-ce-up],[data-ce-up-k]');
        const type = $u.attr('data-type') || 'file';
        $u.addClass('is-busy');
        $u.find('[data-ce-up-err]').prop('hidden', true);
        try {
            const r = await upload(file, type);
            const dur = r.duration != null ? r.duration : type === 'audio' ? await audioDuration(r.url) : null;
            setUpVal($u, r.url, { duration: dur });
            toast(t('uploaded'));
        } catch (e) {
            $u.find('[data-ce-up-err]').text(t('upload_error')).prop('hidden', false);
        } finally {
            $u.removeClass('is-busy');
        }
    });
    $root.on('change', '[data-ce-up-url]', function () {
        const $u = $(this).closest('[data-ce-up],[data-ce-up-k]');
        setUpVal($u, this.value.trim());
    });
    $root.on('click', '[data-ce-up-clear]', function () {
        setUpVal($(this).closest('[data-ce-up],[data-ce-up-k]'), '', { duration: 0 });
    });

    /* ── AI ── */
    async function ai(payload) {
        return API.admin.ai.request(Object.assign({ locale: document.documentElement.lang }, payload), { url: urls.ai, demo: () => (demos[payload.kind] ? demos[payload.kind](payload) : { fields: {} }) });
    }
    function markAi(path) {
        $root.find('[data-f="' + path + '"]').closest('.ad-field').addClass('is-ai');
    }
    $root.on('click', '[data-ce-ai]', async function () {
        const $b = $(this).prop('disabled', true).addClass('is-busy');
        const aiKind = $b.attr('data-ce-ai');
        const fields = ($b.attr('data-fields') || '').split(',').map((s) => s.trim()).filter(Boolean);
        const list = $b.attr('data-list');
        try {
            const res = await ai({ kind: aiKind, fields, list, seeds: { title: M.title, author: M.author, host: M.host }, current: clone(M) });
            const f = (res && res.fields) || {};
            if (list && Array.isArray(f.items)) {
                const arr = listOf(list);
                if ($b.attr('data-mode') === 'append') arr.push(...f.items.map((x) => Object.assign({ _ai: true }, x)));
                else setPath(M, list, f.items.map((x) => Object.assign({ _ai: true }, x)));
                renderList(list);
                $root.find('[data-ce-list="' + list + '"] [data-ce-item]').addClass('is-ai');
            } else {
                Object.keys(f).forEach((k) => {
                    if (f[k] == null || f[k] === '') return;
                    if (fields.length && !fields.includes(k)) return;
                    setPath(M, k, f[k]);
                    markAi(k);
                });
                syncFields();
            }
            (res.missing || []).forEach((k) => $root.find('[data-f="' + k + '"]').closest('.ad-field').addClass('is-missing'));
            markDirty();
            toast(t('ai_done'));
            $(document).trigger('admin:ai-applied', [{ kind: aiKind, fields: Object.keys(f) }]);
        } catch (e) {
            toast((e && e.message) || t('ai_error'), true);
        } finally {
            $b.prop('disabled', false).removeClass('is-busy');
        }
    });
    $root.on('click', '[data-ce-ai-item]', async function () {
        const c = itemCtx(this);
        const $b = $(this).prop('disabled', true).addClass('is-busy');
        try {
            const res = await ai({ kind: $b.attr('data-ce-ai-item'), item: clone(c.item), current: clone(M) });
            const f = (res && res.fields) || {};
            Object.keys(f).forEach((k) => { if (f[k] != null && f[k] !== '') setPath(c.item, k, f[k]); });
            fillItem(c.$it, c.item, c.idx, c.path);
            c.$it.addClass('is-ai');
            markDirty();
            toast(t('ai_done'));
        } catch (e) {
            toast(t('ai_error'), true);
        } finally {
            $b.prop('disabled', false).removeClass('is-busy');
        }
    });

    /* ── TTS ── */
    $root.on('click', '[data-ce-tts]', async function () {
        const $b = $(this).prop('disabled', true).addClass('is-busy');
        const target = $b.attr('data-ce-tts');
        const src = getPath(M, $b.attr('data-text'));
        const text = Array.isArray(src) ? src.map((s) => s.text || '').join(' ') : String(src || '');
        if (!text.trim()) {
            toast(t('tts_empty'), true);
            $b.prop('disabled', false).removeClass('is-busy');
            return;
        }
        const opt = (k) => getPath(M, $b.attr('data-' + k) || '') || '';
        try {
            const res = await ai({ kind: 'tts', text, voice: opt('voice'), accent: opt('accent'), speed: opt('speed') });
            const url = res && res.fields && res.fields.audio;
            if (!url) throw new Error();
            setPath(M, target, url);
            const src2 = target.replace(/audio$/, 'audio_source');
            if (src2 !== target) setPath(M, src2, 'tts');
            syncFields();
            markDirty();
            toast(urls.ai ? t('tts_done') : t('tts_demo'));
        } catch (e) {
            toast(t('ai_error'), true);
        } finally {
            $b.prop('disabled', false).removeClass('is-busy');
        }
    });
    $root.on('click', '[data-ce-speak]', function () {
        const src = getPath(M, $(this).attr('data-ce-speak'));
        const text = Array.isArray(src) ? src.map((s) => s.text || '').join(' ') : String(src || '');
        if (!window.speechSynthesis || !text) return;
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'en-US';
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
    });

    /* ── درون‌ریزی متن زمان‌دار (LRC/SRT/متن ساده) ── */
    let importPath = null;
    $root.on('click', '[data-ce-import]', function () {
        importPath = $(this).attr('data-ce-import');
        $('#ce-import [data-ce-import-text]').val('');
        $('#ce-import [data-ce-import-err]').prop('hidden', true);
        openDialog('#ce-import');
    });
    $(document).on('change', '#ce-import [data-ce-import-file]', function () {
        const f = this.files && this.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = () => $('#ce-import [data-ce-import-text]').val(String(r.result || ''));
        r.readAsText(f);
        this.value = '';
    });
    $(document).on('click', '#ce-import [data-ce-import-go]', function () {
        if (!importPath) return;
        const rows = parseTimed($('#ce-import [data-ce-import-text]').val());
        if (!rows.length) {
            $('#ce-import [data-ce-import-err]').prop('hidden', false);
            return;
        }
        const keep = $('#ce-import [data-ce-import-keep]').prop('checked');
        const old = listOf(importPath);
        setPath(M, importPath, rows.map((r, i) => ({ t: r.t == null ? '' : r.t, text: r.text, tr: keep && old[i] ? old[i].tr || '' : '' })));
        renderList(importPath);
        markDirty();
        closeDialog('#ce-import');
        toast(t('imported', { n: num(rows.length) }));
    });

    /* ── تب‌ها ── */
    const $tabs = $root.find('[data-ce-tab]');
    function selectTab(k, focus) {
        $tabs.each(function () {
            const on = $(this).attr('data-ce-tab') === k;
            $(this).attr({ 'aria-selected': String(on), tabindex: on ? 0 : -1 });
            if (on && focus) this.focus();
        });
        $root.find('[data-ce-panel]').each(function () { $(this).prop('hidden', $(this).attr('data-ce-panel') !== k); });
        try { history.replaceState(history.state, '', '#' + k); } catch (e) { /* */ }
    }
    $tabs.on('click', function () { selectTab($(this).attr('data-ce-tab')); });
    $tabs.on('keydown', function (e) {
        const keys = $tabs.map(function () { return $(this).attr('data-ce-tab'); }).get();
        const i = keys.indexOf($(this).attr('data-ce-tab'));
        const rtl = document.documentElement.dir === 'rtl';
        const map = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 };
        if (e.key in map) {
            e.preventDefault();
            selectTab(keys[(i + map[e.key] + keys.length) % keys.length], true);
        }
    });

    /* ── ذخیره / انتشار / حذف ── */
    function payload(status) {
        const m = clone(M);
        m.status = status;
        // کلیدهای داخلی (_ai) حذف شوند
        JSON.stringify(m, (k, v) => { if (v && typeof v === 'object' && '_ai' in v) delete v._ai; return v; });
        return m;
    }
    async function save(status) {
        if (status === 'auto') status = M.status || 'draft';
        const body = payload(status);
        $root.find('[data-ce-msg]').text(t('saving'));
        try {
            let r = null;
            const ev = $.Event('admin:content-save');
            const viaEvent = await new Promise((resolve) => {
                $(document).trigger(ev, [{ kind, payload: body, status, done: (x) => resolve({ ok: x }), fail: (m) => resolve({ err: m || '' }) }]);
                if (!ev.isDefaultPrevented()) resolve(null);
            });
            if (viaEvent) {
                if (viaEvent.err !== undefined) throw { message: viaEvent.err };
                r = viaEvent.ok;
            } else if (API.admin.content.api && API.admin.content.api.save) r = await API.admin.content.api.save(body, kind);
            else if (urls.save) r = await send(urls.save, { kind, status, model: body }, M.id ? 'PUT' : 'POST');
            else {
                if (!LOCAL) throw { message: t('no_backend') };
                const id = M.id || Date.now() % 100000;
                body.id = id;
                store.set('teodel:admin:' + kind + ':' + id, body);
                if (!M.id) store.del(localKey());
                r = { id, status };
            }
            const wasNew = !M.id;
            M.id = r.id || M.id;
            M.status = r.status || status;
            dirty = false;
            $root.find('[data-ce-dirty]').prop('hidden', true);
            $root.find('[data-ce-status]').text(t('status_' + M.status)).attr('data-tone', M.status === 'published' ? 'ok' : '');
            $root.find('[data-ce-msg]').text(t('saved'));
            toast(M.status === 'published' && status === 'published' ? t('published') : t('saved'));
            if (wasNew && urls.edit) history.replaceState(null, '', r.url || urls.edit.replace(':id', M.id));
            $(document).trigger('admin:content-saved', [{ kind, id: M.id, status: M.status }]);
            closeDialog('#ce-publish');
        } catch (e) {
            $root.find('[data-ce-msg]').text('');
            const msg = (e && e.message) || t('save_error');
            toast(msg, true);
            if (e && e.errors) {
                Object.keys(e.errors).forEach((k) => $root.find('[data-f="' + k + '"]').closest('.ad-field').addClass('has-error').find('.ad-error').text([].concat(e.errors[k])[0]).prop('hidden', false));
            }
            $('#ce-publish [data-ce-pub-err]').text(msg).prop('hidden', false);
        }
    }
    $root.on('click', '[data-ce-save]', function () { save($(this).attr('data-ce-save')); });
    $(document).on('click', '#ce-publish [data-ce-save]', function () { save('published'); });
    $(document).on('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            save('auto');
        }
    });
    window.addEventListener('beforeunload', (e) => {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    $root.on('click', '[data-ce-publish-open]', () => {
        const d = doneMap();
        const ok = Object.values(d).every(Boolean);
        $('#ce-publish [data-ce-pub-list]').html(Object.keys(d).map((k) => `<li data-s="${d[k] ? 'ok' : 'bad'}"><i class="ed-ck"></i>${esc(T['tab_' + k] || k)}<small>${esc(d[k] ? t('ck_ok') : t('ck_todo'))}</small></li>`).join(''));
        $('#ce-publish [data-ce-pub-lead]').text(ok ? t('pub_ready') : t('pub_todo'));
        $('#ce-publish [data-ce-save]').prop('disabled', !ok);
        $('#ce-publish [data-ce-pub-err]').prop('hidden', true);
        openDialog('#ce-publish');
    });
    $root.on('click', '[data-ce-delete-open]', () => openDialog('#ce-delete'));
    $(document).on('click', '#ce-delete [data-ce-delete]', async () => {
        try {
            const ev = $.Event('admin:content-delete');
            const viaEvent = await new Promise((resolve) => {
                $(document).trigger(ev, [{ kind, id: M.id, done: () => resolve({ ok: 1 }), fail: (m) => resolve({ err: m || '' }) }]);
                if (!ev.isDefaultPrevented()) resolve(null);
            });
            if (viaEvent && viaEvent.err !== undefined) throw new Error(viaEvent.err);
            if (!viaEvent) {
                if (API.admin.content.api && API.admin.content.api.remove) await API.admin.content.api.remove(M.id, kind);
                else if (urls.del) await send(urls.del, {}, 'DELETE');
                else store.del(localKey());
            }
            dirty = false;
            location.href = urls.list || '/admin';
        } catch (e) {
            toast(t('delete_error'), true);
        }
    });

    /* ── شروع ── */
    syncFields();
    renderAllLists();
    loadPlayer();
    refresh();
    const first = (location.hash || '').slice(1);
    if (first && $tabs.filter('[data-ce-tab="' + first + '"]').length) selectTab(first);
}

/* قلاب‌های هر بخش */
const hooks = {
    // کتاب: زمان خواندن هر فصل خودکار (۱۳۰ کلمه در دقیقه)
    book: {
        item(c, k) {
            if (c.path !== 'chapters' || k !== 'text') return;
            const words = String(c.item.text || '').trim().split(/\s+/).filter(Boolean).length;
            c.item.minutes = Math.max(1, Math.round(words / 130));
            c.$it.find('[data-ce-text="minutes"]').text(num(c.item.minutes));
        },
    },
    // سری پادکست: شماره‌ی اپیزودها با ترتیب لیست
    podcast: {
        reorder(path, arr) {
            if (path === 'episodes') arr.forEach((e, i) => { e.number = i + 1; });
        },
    },
};

$('[data-ce]').each(function () { init(this); });
