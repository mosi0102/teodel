import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, csrf } from './common';

/**
 * ویرایشگر موزیک (/admin/songs/create و /admin/songs/{id}/edit): اطلاعات + هشت مرحله‌ی آموزش.
 * قرارداد داده (مدل موزیک، ذخیره، آپلود، حذف): app/Support/AdminSongs.php و README.
 *
 * 🔌 بک‌اند — هر کدام از این‌ها را می‌توانید وصل کنید (به ترتیب اولویت: رویداد ← Teodel.admin.song.api ← آدرس روی ریشه ← حالت دمو):
 *   ذخیره   data-save-url   POST (جدید) / PUT (ویرایش) JSON کامل مدل  ← { id, status, url? }  یا 422 { message, errors:{field:[…]} }
 *   آپلود   data-upload-url POST multipart { file, kind: cover|audio|audio_vocal|audio_backing } ← { url, duration? }
 *   حذف     data-delete-url DELETE ← { ok:true }
 *   رویداد لغوشدنی:  $(document).on('admin:song-save', (e, { payload, status, done, fail }) => { e.preventDefault(); … done({id,status}) })
 *                     admin:song-upload { file, kind, done({url,duration}), fail(msg) } | admin:song-delete { id, done, fail }
 *   یا:  Teodel.admin.song.api = { save: async (payload) => ({id,status}), upload: async (file, kind) => ({url,duration}), remove: async (id) => ({}) }
 * 🔌 AI (همه‌ی دکمه‌های AI از Teodel.admin.ai.request در ai.js رد می‌شوند): kind ∈ lyrics_tr | words_suggest | word_fill | listening_suggest |
 *   shadowing_suggest | details — خروجی: { fields:{…}, missing:[…] } (شکل هرکدام در README). بدون بک‌اند «حالت دمو» نتیجه‌ی نمونه می‌سازد.
 * LOCAL = true: فقط برای دمو، ذخیره در localStorage مرورگر. وقتی بک‌اند وصل شد false کنید.
 */
const LOCAL = true;

/* ── توابع خالص (تست‌شده) ───────────────────────────────────── */

/** «1:05.2» / «01:05.20» / «65.2» → ثانیه (null اگر خالی/نامعتبر) */
export function parseTime(v) {
    const s = String(v == null ? '' : v).trim().replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[٫،,]/g, '.');
    if (!s) return null;
    const m = s.match(/^(?:(\d+):)?(\d+):(\d+(?:\.\d+)?)$/) || s.match(/^()(\d+):(\d+(?:\.\d+)?)$/);
    if (m) return Math.round(((m[1] ? +m[1] * 3600 : 0) + +m[2] * 60 + +m[3]) * 100) / 100;
    if (/^\d+(?:\.\d+)?$/.test(s)) return Math.round(+s * 100) / 100;
    return null;
}

/** ثانیه → «mm:ss.xx» ('' اگر عدد نبود) */
export function fmtTime(sec) {
    if (sec === '' || sec == null || isNaN(sec)) return '';
    const t = Math.max(0, Math.round(+sec * 100) / 100);
    const m = Math.floor(t / 60);
    const s = t - m * 60;
    return String(m).padStart(2, '0') + ':' + s.toFixed(2).padStart(5, '0');
}

/** متن LRC یا خط‌های ساده → [{t, text}] (t = null برای خط بدون زمان) — تگ‌های [ar:…] نادیده گرفته می‌شوند */
export function parseLrc(text) {
    const out = [];
    String(text || '').split(/\r?\n/).forEach((raw) => {
        let line = raw.trim();
        if (!line || /^\[[a-z]{2,}:[^\]]*\]$/i.test(line)) return;
        const times = [];
        let m;
        while ((m = line.match(/^\[(\d+):(\d+(?:[.:]\d+)?)\]\s*/))) {
            times.push(Math.round((+m[1] * 60 + parseFloat(m[2].replace(':', '.'))) * 100) / 100);
            line = line.slice(m[0].length);
        }
        line = line.trim();
        if (!line && !times.length) return;
        if (times.length) times.forEach((t) => out.push({ t, text: line }));
        else out.push({ t: null, text: line });
    });
    return out.some((l) => l.t != null) ? out.sort((a, b) => (a.t == null) - (b.t == null) || a.t - b.t) : out;
}

export function toLrc(lines) {
    return lines
        .filter((l) => l.t !== '' && l.t != null && !isNaN(l.t) && String(l.text || '').trim())
        .map((l) => '[' + fmtTime(l.t) + ']' + String(l.text).trim())
        .join('\n');
}

export const splitList = (s) => String(s || '').split(/[,،]/).map((x) => x.trim()).filter(Boolean);
export const norm = (s) => String(s || '').toLowerCase().replace(/[’‘]/g, "'");
export const wordsOf = (text) => String(text || '').match(/[A-Za-z0-9’'-]+/g) || [];

/** پایان تقریبی خواندن یک خط (هم‌قاعده با Lrc::sungEnd در PHP) */
export function sungEnd(line, nextT) {
    const end = +line.t + Math.max(1.2, String(line.text || '').length * 0.08);
    return Math.round((nextT != null ? Math.min(end, nextT) : end) * 100) / 100;
}

/** بخش‌های خوانده‌شده [[شروع، پایان]] (هم‌قاعده با Lrc::vocals) */
export function sungRanges(lyrics) {
    const lines = lyrics.filter((l) => l.t !== '' && l.t != null && !isNaN(l.t) && String(l.text || '').trim()).sort((a, b) => a.t - b.t);
    const segs = [];
    lines.forEach((l, i) => {
        const e = sungEnd(l, lines[i + 1] ? lines[i + 1].t : null);
        const last = segs[segs.length - 1];
        if (last && l.t - last[1] < 3) last[1] = e;
        else segs.push([+l.t, e]);
    });
    return segs;
}

/** زمان شروع/پایان و متن برای یک بازه از خط‌های لیریک (شماره‌ها از ۰) */
export function lineRange(lyrics, i, j) {
    const a = Math.min(i, j);
    const b = Math.max(i, j);
    const L = lyrics.filter((l) => l.t !== '' && l.t != null && !isNaN(l.t)).sort((x, y) => x.t - y.t);
    if (!L[a] || !L[b]) return null;
    return { start: +L[a].t, end: sungEnd(L[b], L[b + 1] ? L[b + 1].t : null), text: L.slice(a, b + 1).map((l) => l.text).join('\n') };
}

/** پاسخ داخل متن هست؟ (بدون حساسیت به حروف، با مرز کلمه) */
export function answerInText(text, answer) {
    const a = norm(answer).trim();
    if (!a) return false;
    const re = new RegExp("(^|[^a-z0-9'])" + a.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+') + "($|[^a-z0-9'])");
    return re.test(norm(text));
}

/** متن با جای خالی (برای پیش‌نمایش): اولین پاسخ با خط‌تیره عوض می‌شود */
export function blankText(text, answer) {
    const a = String(answer || '').trim();
    if (!a || !answerInText(text, a)) return String(text || '');
    const re = new RegExp("(^|[^A-Za-z0-9'])(" + a.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+') + ")(?=$|[^A-Za-z0-9'])", 'i');
    return String(text).replace(re, (m, p, w) => p + w.replace(/[^\s]/g, '_'));
}

const hasT = (l) => l.t !== '' && l.t != null && !isNaN(l.t);
const filled = (v) => String(v == null ? '' : v).trim() !== '';

/** کامل بودن هر تب — همان قاعده‌ی AdminSongs::completeness در PHP */
export function completeness(S) {
    const lyr = S.lyrics || [];
    const timed = lyr.length > 0 && lyr.every((l) => hasT(l) && filled(l.text));
    return {
        info: filled(S.title) && filled(S.artist) && !!S.level && !!S.cover,
        play: !!S.audio,
        lyrics: timed,
        words: (S.words || []).some((w) => filled(w.phrase) && filled(w.translate)),
        listening: ((S.listening || {}).easy || []).length > 0 && ((S.listening || {}).hard || []).length > 0,
        shadowing: (S.shadowing || []).length > 0,
        karaoke: !!S.audio_vocal && !!S.audio_backing,
        repeat: !!S.audio,
        details: filled(S.interpretation) || (S.notes || []).length > 0,
    };
}

/** بررسی مدل → [{ tab, level:'error'|'warn', code, path? }] (متن‌ها از lang می‌آیند) */
export function validate(S) {
    const out = [];
    const add = (tab, level, code, path) => out.push({ tab, level, code, path });
    if (!filled(S.title)) add('info', 'error', 'title', 'title');
    if (!filled(S.artist)) add('info', 'error', 'artist', 'artist');
    if (filled(S.year) && !/^(19|20)\d{2}$/.test(String(S.year).trim())) add('info', 'error', 'year', 'year');
    ['wpm', 'points'].forEach((k) => filled(S[k]) && !/^\d+$/.test(String(S[k]).trim()) && add('info', 'error', 'number', k));
    const rep = +S.shadowing_repeats;
    if (!(rep >= 1 && rep <= 10)) add('shadowing', 'error', 'repeats', 'shadowing_repeats');

    let prev = -1;
    (S.lyrics || []).forEach((l, i) => {
        if (filled(l.text) && !hasT(l)) add('lyrics', 'warn', 'no_time', 'lyrics.' + i);
        else if (hasT(l) && !filled(l.text)) add('lyrics', 'warn', 'no_text', 'lyrics.' + i);
        if (hasT(l)) {
            if (+l.t < prev) add('lyrics', 'warn', 'order', 'lyrics.' + i);
            prev = Math.max(prev, +l.t);
        }
    });
    (S.words || []).forEach((w, i) => {
        if (!filled(w.phrase)) add('words', 'error', 'phrase', 'words.' + i);
        else if (!filled(w.translate)) add('words', 'warn', 'translate', 'words.' + i);
    });
    ['easy', 'hard'].forEach((m) =>
        ((S.listening || {})[m] || []).forEach((c, i) => {
            const p = 'listening.' + m + '.' + i;
            if (!filled(c.text)) return add('listening', 'error', 'text', p);
            if (!(+c.end > +c.start) || !hasT({ t: c.start }) || !hasT({ t: c.end })) return add('listening', 'error', 'range', p);
            if (!filled(c.answer)) return add('listening', 'error', 'answer', p);
            if (!answerInText(c.text, c.answer)) return add('listening', 'error', 'answer_not_in_text', p);
            if (m === 'easy' && wordsOf(c.answer).length !== 1) add('listening', 'error', 'easy_one_word', p);
        })
    );
    (S.shadowing || []).forEach((c, i) => {
        const p = 'shadowing.' + i;
        if (!filled(c.text)) return add('shadowing', 'error', 'text', p);
        if (!(+c.end > +c.start)) add('shadowing', 'error', 'range', p);
    });
    return out;
}

/** مدل تمیز برای ارسال: مرتب‌سازی لیریک، حذف آیتم‌های خالی، تبدیل عددها، حذف کلیدهای داخلی (_ai) */
export function cleanSong(S) {
    const c = JSON.parse(JSON.stringify(S));
    const strip = (o) => {
        if (Array.isArray(o)) o.forEach(strip);
        else if (o && typeof o === 'object') {
            delete o._ai;
            Object.values(o).forEach(strip);
        }
        return o;
    };
    strip(c);
    c.lyrics = (c.lyrics || [])
        .filter((l) => filled(l.text) || hasT(l))
        .map((l) => ({ t: hasT(l) ? +l.t : null, text: String(l.text || '').trim(), tr: String(l.tr || '').trim() }));
    if (c.lyrics.every((l) => l.t != null)) c.lyrics.sort((a, b) => a.t - b.t);
    c.words = (c.words || []).filter((w) => filled(w.phrase));
    ['easy', 'hard'].forEach((m) => {
        c.listening[m] = (c.listening[m] || []).filter((x) => filled(x.text)).map((x) => ({ start: +x.start || 0, end: +x.end || 0, text: x.text.trim(), answer: String(x.answer || '').trim() }));
    });
    c.shadowing = (c.shadowing || []).filter((x) => filled(x.text)).map((x) => ({ start: +x.start || 0, end: +x.end || 0, text: x.text.trim() }));
    c.notes = (c.notes || []).map((n) => ({ ...n, items: (n.items || []).filter((i) => filled(i.term) || filled(i.text)) })).filter((n) => filled(n.title) || n.items.length);
    ['year', 'wpm', 'points'].forEach((k) => (c[k] = filled(c[k]) ? +c[k] : ''));
    c.shadowing_repeats = +c.shadowing_repeats || 3;
    c.duration = Math.round(+c.duration || 0);
    return c;
}

const getp = (o, p) => String(p).split('.').reduce((a, k) => (a == null ? a : a[k]), o);
function setp(o, p, v) {
    const ks = String(p).split('.');
    const last = ks.pop();
    const t = ks.reduce((a, k) => (a[k] == null || typeof a[k] !== 'object' ? (a[k] = {}) : a[k]), o);
    t[last] = v;
}
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/* ── نمونه‌های AI در «حالت دمو» (بدون بک‌اند) ─────────────────── */
function demoAI(kind, payload) {
    const lines = ((payload.current || {}).lyrics || []).filter((l) => hasT(l) && filled(l.text));
    const longWords = () => {
        const seen = new Set();
        const out = [];
        lines.forEach((l) => wordsOf(l.text).forEach((w) => { const k = norm(w); if (k.length >= 6 && !seen.has(k)) { seen.add(k); out.push(k); } }));
        return out.slice(0, 4);
    };
    switch (kind) {
        case 'lyrics_tr':
            return { fields: { tr: lines.map((l) => 'ترجمه‌ی نمونه: ' + l.text.slice(0, 24)) }, missing: [] };
        case 'words_suggest':
            return { fields: { words: longWords().map((w) => ({ phrase: w, type: 'noun', level: w.length > 7 ? 'B2' : 'B1', ipa: { uk: '/' + w + '/', us: '/' + w + '/' }, meaning: 'Sample meaning of “' + w + '”.', synonyms: ['sample'], antonyms: [], translate: 'معنی نمونه‌ی «' + w + '»' })) }, missing: ['audio'] };
        case 'word_fill': {
            const w = (payload.seeds || {}).phrase || 'word';
            return { fields: { type: 'noun', level: 'B1', ipa: { uk: '/' + w + '/', us: '/' + w + '/' }, meaning: 'Sample meaning of “' + w + '”.', synonyms: ['sample'], antonyms: [], translate: 'معنی نمونه‌ی «' + w + '»' }, missing: ['audio'] };
        }
        case 'listening_suggest': {
            const hard = (payload.seeds || {}).mode === 'hard';
            const picks = lines.filter((_, i) => i % 3 === 1).slice(0, 3);
            return { fields: { items: picks.map((l) => {
                const ws = wordsOf(l.text);
                const i = lines.indexOf(l);
                const end = sungEnd(l, lines[i + 1] ? lines[i + 1].t : null);
                const ans = hard ? ws.slice(-Math.min(3, ws.length)).join(' ') : ws.slice().sort((a, b) => b.length - a.length)[0];
                return { start: +l.t, end, text: l.text, answer: ans || '' };
            }) }, missing: [] };
        }
        case 'shadowing_suggest': {
            const items = [];
            for (let i = 0; i + 1 < lines.length && items.length < 4; i += 2) {
                const r = lineRange(lines, i, i + 1);
                if (r) items.push(r);
            }
            return { fields: { items }, missing: [] };
        }
        case 'details':
            return { fields: {
                interpretation: 'تفسیر نمونه: این آهنگ درباره‌ی گذر زمان و پذیرفتن آهنگ شخصی زندگی است.\n\nخواننده یاد گرفته با آرامش جلو برود.',
                notes: [{ title: 'آرایه‌های ادبی', type: 'literary', items: [{ term: 'Metaphor — استعاره', example: lines[4] ? lines[4].text : '', text: 'توضیح نمونه‌ی آرایه.' }] }, { title: 'نکته‌های زبان', type: 'language', items: [{ term: 'Present perfect', example: lines[3] ? lines[3].text : '', text: 'توضیح نمونه‌ی نکته‌ی گرامری.' }] }],
            }, missing: [] };
        default:
            return { fields: {}, missing: [] };
    }
}

/* ── صفحه ──────────────────────────────────────────────────── */
$(function () {
    const $root = $('[data-ed]');
    if (!$root.length) return;

    const API = (window.Teodel = window.Teodel || {});
    API.admin = API.admin || {};
    API.admin.song = API.admin.song || {};
    API.admin.song.api = API.admin.song.api || {};

    const T = readJSON($root, 'data-t', {});
    const DEF = readJSON($root, 'data-defaults', {});
    const lang = document.documentElement.lang === 'fa' ? 'fa' : 'en';
    const t = (path, rep) => {
        let v = String(path).split('.').reduce((o, k) => (o == null ? o : o[k]), T);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };
    const fa = lang === 'fa';
    const nf = new Intl.NumberFormat(fa ? 'fa-IR' : 'en-GB');
    const nn = (n) => nf.format(n);

    /* مدل */
    const blank = () => ({ id: null, status: 'draft', title: '', artist: '', cover: '', level: 'a', is_free: false, points: 50, accent: '', year: '', genre: '', wpm: '', topic: '', album: '', duration: 0, desc_fa: '', desc_en: '', audio: '', audio_vocal: '', audio_backing: '', q_play: { fa: '', en: '' }, q_repeat: { fa: '', en: '' }, lyrics: [], words: [], listening: { easy: [], hard: [] }, shadowing: [], shadowing_repeats: 3, interpretation: '', notes: [] });
    let S = Object.assign(blank(), readJSON($root, 'data-song', {}));
    S.listening = Object.assign({ easy: [], hard: [] }, S.listening || {});
    ['q_play', 'q_repeat'].forEach((k) => (S[k] = Object.assign({ fa: '', en: '' }, S[k] || {})));
    S.lyrics = (S.lyrics || []).map((l) => ({ t: l.t == null ? '' : l.t, text: l.text || '', tr: l.tr || '' }));
    S.words = (S.words || []).map((w) => Object.assign({ phrase: '', type: '', level: '', ipa: { uk: '', us: '' }, audio: { uk: '', us: '' }, meaning: '', synonyms: [], antonyms: [], translate: '' }, w, { ipa: Object.assign({ uk: '', us: '' }, w.ipa || {}), audio: Object.assign({ uk: '', us: '' }, w.audio || {}) }));

    const lsKey = 'teodel:admin:song:' + (S.id || 'new');
    if (LOCAL) {
        try {
            const saved = JSON.parse(localStorage.getItem(lsKey) || 'null');
            if (saved && saved.title !== undefined) { S = Object.assign(S, saved); $root.attr('data-restored', '1'); }
        } catch (e) { /* بدون localStorage */ }
    }

    let dirty = false;
    let busy = false;
    let uid = 0;
    const $msg = $root.find('[data-ed-msg]');
    const say = (m) => $msg.text(m || '');
    const markDirty = () => {
        dirty = true;
        $root.find('[data-ed-dirty]').prop('hidden', false);
        schedule();
    };
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    /* ── پخش‌کننده‌ی مشترک ── */
    const audio = $root.find('[data-ed-audio]')[0];
    const $player = $root.find('[data-ed-player]');
    const $range = $root.find('[data-ed-range]');
    const $clock = $root.find('[data-ed-clock]');
    let stopAt = null;
    const now = () => Math.round((audio.currentTime || 0) * 100) / 100;
    function loadAudio() {
        const src = S.audio || '';
        if ((audio.getAttribute('src') || '') !== src) {
            if (src) audio.setAttribute('src', src); else audio.removeAttribute('src');
            audio.load();
        }
        syncPlayerVisibility();
    }
    audio.addEventListener('loadedmetadata', () => {
        $range.attr('max', audio.duration || 100);
        if (isFinite(audio.duration) && audio.duration > 0 && !(+S.duration > 0)) { S.duration = Math.round(audio.duration); markDirty(); }
        renderVocals();
    });
    audio.addEventListener('timeupdate', () => {
        $range.val(audio.currentTime);
        $clock.text(fmtTime(audio.currentTime));
        if (stopAt != null && audio.currentTime >= stopAt) { audio.pause(); stopAt = null; }
        liveRow();
    });
    audio.addEventListener('play', () => $player.addClass('is-playing'));
    audio.addEventListener('pause', () => $player.removeClass('is-playing'));
    audio.addEventListener('ended', () => $player.removeClass('is-playing'));
    $root.on('click', '[data-ed-play]', () => { stopAt = null; if (audio.paused) audio.play().catch(() => {}); else audio.pause(); });
    $root.on('click', '[data-ed-seek]', function () { audio.currentTime = Math.max(0, audio.currentTime + +$(this).attr('data-ed-seek')); });
    $range.on('input', function () { audio.currentTime = +this.value; });
    $root.on('change', '[data-ed-speed]', function () { audio.playbackRate = +this.value; });
    function playRange(a, b) {
        if (!S.audio || !(b > a)) return;
        audio.currentTime = a;
        stopAt = b;
        audio.play().catch(() => {});
    }

    /* ── تب‌ها ── */
    const TABS = $root.find('[data-ed-tab]').map((_, e) => $(e).attr('data-ed-tab')).get();
    let current = 'info';
    function syncPlayerVisibility() {
        const needsTime = ['lyrics', 'listening', 'shadowing'].includes(current);
        const show = !!S.audio && ['play', 'repeat', 'lyrics', 'listening', 'shadowing'].includes(current);
        $player.prop('hidden', !show);
        $root.find('[data-ed-noaudio]').prop('hidden', !(needsTime && !S.audio));
    }
    function showTab(k, focus) {
        if (!TABS.includes(k)) k = 'info';
        current = k;
        $root.find('[data-ed-tab]').each(function () {
            const on = $(this).attr('data-ed-tab') === k;
            $(this).attr({ 'aria-selected': on ? 'true' : 'false', tabindex: on ? 0 : -1 });
            if (on && focus) this.focus();
            if (on) this.scrollIntoView({ block: 'nearest', inline: 'center' });
        });
        $root.find('[data-ed-panel]').each(function () { $(this).prop('hidden', $(this).attr('data-ed-panel') !== k); });
        syncPlayerVisibility();
        try { history.replaceState(null, '', '#' + k); } catch (e) { /* ignore */ }
        if (k === 'play' || k === 'repeat') renderVocals();
    }
    $root.on('click', '[data-ed-tab]', function () { showTab($(this).attr('data-ed-tab')); });
    $root.on('keydown', '[data-ed-tab]', function (e) {
        const i = TABS.indexOf($(this).attr('data-ed-tab'));
        const rtl = document.documentElement.dir === 'rtl';
        const next = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 }[e.key];
        if (next) { e.preventDefault(); showTab(TABS[(i + next + TABS.length) % TABS.length], true); }
        if (e.key === 'Home') { e.preventDefault(); showTab(TABS[0], true); }
        if (e.key === 'End') { e.preventDefault(); showTab(TABS[TABS.length - 1], true); }
    });

    /* ── فیلدهای ساده (data-f) ── */
    function fillFlat() {
        $root.find('[data-f]').each(function () {
            const v = getp(S, $(this).attr('data-f'));
            if (this.type === 'checkbox') this.checked = !!v;
            else $(this).val(v == null ? '' : v);
        });
        $root.find('[data-ed-title]').text(S.title || t('new_song'));
    }
    $root.on('input change', '[data-f]', function () {
        const k = $(this).attr('data-f');
        setp(S, k, this.type === 'checkbox' ? this.checked : $(this).val());
        if (k === 'title') $root.find('[data-ed-title]').text(S.title || t('new_song'));
        if (k === 'cover') renderUploads();
        if (k.startsWith('q_')) renderQuestions();
        $(this).closest('.ad-field').removeClass('is-ai is-missing has-error');
        markDirty();
    });
    // پس از اعمال AI روی فرم اطلاعات، مقدارها از DOM خوانده می‌شوند (رویداد input در ai.js فرستاده می‌شود)

    /* ── لیست‌ها ── */
    const LISTS = {
        lyrics: { tpl: 'lyrics', blank: () => ({ t: '', text: '', tr: '' }) },
        words: { tpl: 'words', blank: () => ({ phrase: '', type: '', level: '', ipa: { uk: '', us: '' }, audio: { uk: '', us: '' }, meaning: '', synonyms: [], antonyms: [], translate: '' }) },
        'listening.easy': { tpl: 'clip', blank: () => ({ start: '', end: '', text: '', answer: '' }) },
        'listening.hard': { tpl: 'clip', blank: () => ({ start: '', end: '', text: '', answer: '' }) },
        shadowing: { tpl: 'shadowing', blank: () => ({ start: '', end: '', text: '' }) },
        notes: { tpl: 'notes', blank: () => ({ title: '', type: 'literary', items: [{ term: '', example: '', text: '' }] }) },
    };
    const arr = (key) => { const a = getp(S, key); return Array.isArray(a) ? a : (setp(S, key, []), getp(S, key)); };
    const tplOf = (name) => $root.find('template[data-ed-tpl="' + name + '"]')[0].content.firstElementChild;

    function label($el) {
        $el.find('.ad-field').each(function () {
            const $l = $(this).children('label.ad-label').first();
            const $i = $(this).find('input:not([type=checkbox]),select,textarea').first();
            if ($l.length && $i.length && !$i.attr('id')) { const id = 'ed-u' + ++uid; $i.attr('id', id); $l.attr('for', id); }
        });
    }
    function lineOptions($sel, selectedIdx) {
        const L = timedLines();
        $sel.empty().append('<option value="">—</option>');
        L.forEach((l, i) => $sel.append($('<option>').val(i).text(String(i + 1).padStart(2, '0') + ' · ' + l.text.slice(0, 48))));
        if (selectedIdx != null && selectedIdx >= 0) $sel.val(String(selectedIdx));
    }
    const timedLines = () => (S.lyrics || []).filter((l) => hasT(l) && filled(l.text)).slice().sort((a, b) => a.t - b.t);

    function fillItem($item, key, i) {
        const item = arr(key)[i];
        $item.attr('data-i', i).toggleClass('is-ai', !!item._ai).attr('data-ai-label', t('ai_made'));
        $item.find('.ed-item__n').text(t('item_n', { n: nn(i + 1) })).attr('data-ai-label', t('ai_made'));
        $item.find('.ed-idx').text(nn(i + 1));
        $item.find('[data-k]').each(function () {
            const k = $(this).attr('data-k');
            let v = getp(item, k);
            if ($(this).is('[data-time]')) v = fmtTime(v);
            else if ($(this).is('[data-list]')) v = (v || []).join(', ');
            $(this).val(v == null ? '' : v);
        });
        label($item);
        if (key === 'notes') renderSub($item, i);
        refreshItem($item, key, i);
    }

    function render(key) {
        const $list = $root.find('[data-ed-list="' + key + '"]');
        if (!$list.length) return;
        const cfg = LISTS[key];
        $list.empty();
        arr(key).forEach((_, i) => {
            const el = tplOf(cfg.tpl).cloneNode(true);
            $list.append(el);
            fillItem($(el), key, i);
        });
        $root.find('[data-ed-empty="' + key + '"]').prop('hidden', arr(key).length > 0);
        if (key === 'lyrics') { renderTapPos(); }
        schedule();
    }
    const renderAll = () => Object.keys(LISTS).forEach(render);

    /* اطلاعات مشتق هر آیتم (بدون ساخت دوباره‌ی DOM تا فوکوس نپرد) */
    function refreshItem($item, key, i) {
        const item = arr(key)[i];
        if (!item) return;
        if (key === 'words') {
            const p = norm(item.phrase).trim();
            const all = norm(S.lyrics.map((l) => l.text).join('\n'));
            const $f = $item.find('[data-ed-inlyrics]');
            if (!p) $f.text('').removeAttr('data-s');
            else if (all.includes(p)) $f.text(t('words.in_lyrics')).attr('data-s', 'ok');
            else $f.text(t('words.not_in_lyrics')).attr('data-s', 'warn');
        }
        if (key.startsWith('listening.')) {
            const easy = key === 'listening.easy';
            $item.find('[data-ed-blank]').text(item.text ? blankText(item.text, item.answer) : '');
            const $w = $item.find('[data-ed-words]');
            $w.prop('hidden', !easy || !item.text);
            if (easy) {
                $w.empty();
                [...new Set(wordsOf(item.text))].forEach((w) => $w.append($('<button type="button" class="ed-w">').attr('data-w', w).toggleClass('is-on', norm(w) === norm(item.answer)).text(w)));
            }
            $item.find('[data-ed-ans-l]').text(t(easy ? 'clip.answer_easy' : 'clip.answer_hard'));
            lineOptions($item.find('[data-ed-pick-line]'), pickedIndex(item));
        }
        if (key === 'shadowing') {
            const a = timedLines().findIndex((l) => Math.abs(l.t - item.start) < 0.05);
            lineOptions($item.find('[data-ed-from-line]'), a >= 0 ? a : null);
            lineOptions($item.find('[data-ed-to-line]'), a >= 0 ? a + Math.max(0, String(item.text || '').split(/\n/).filter(Boolean).length - 1) : null);
        }
        issues($item, key, i);
    }
    const pickedIndex = (item) => { const k = timedLines().findIndex((l) => Math.abs(l.t - item.start) < 0.05 && norm(l.text) === norm(item.text)); return k >= 0 ? k : null; };

    function issues($item, key, i) {
        const path = key + '.' + i;
        const list = validate(S).filter((v) => v.path === path);
        let $e = $item.find('> .ed-itemerr');
        if (!$e.length) $e = $('<p class="ed-itemerr" role="alert" hidden>').appendTo($item);
        const first = list[0];
        $item.toggleClass('has-issue', !!first).toggleClass('is-warn', !!first && first.level === 'warn');
        $e.prop('hidden', !first).text(first ? t('v.' + first.code) : '');
    }

    function itemOf(el) {
        const $item = $(el).closest('[data-ed-item]');
        const $list = $item.closest('[data-ed-list]');
        return { $item, $list, key: $list.attr('data-ed-list'), i: $list.children('[data-ed-item]').index($item) };
    }

    /* ورودی‌های آیتم‌ها */
    $root.on('input', '[data-ed-item] [data-k]', function () {
        const { $item, key, i } = itemOf(this);
        const item = arr(key)[i];
        if (!item) return;
        const k = $(this).attr('data-k');
        if ($(this).is('[data-time]')) {
            const v = parseTime($(this).val());
            $(this).toggleClass('is-bad', filled($(this).val()) && v == null);
            if (v != null || !filled($(this).val())) setp(item, k, v == null ? '' : v);
        } else if ($(this).is('[data-list]')) setp(item, k, splitList($(this).val()));
        else setp(item, k, $(this).val());
        delete item._ai;
        $item.removeClass('is-ai');
        refreshItem($item, key, i);
        if (key === 'lyrics') $root.find('[data-ed-list="words"] [data-ed-item]').each(function () { const w = itemOf(this); refreshItem($(this), 'words', w.i); });
        markDirty();
    });
    $root.on('change', '[data-ed-item] [data-time]', function () {
        const { key, i } = itemOf(this);
        const v = getp(arr(key)[i], $(this).attr('data-k'));
        $(this).val(fmtTime(v)).removeClass('is-bad');
    });

    $root.on('click', '[data-ed-add]', function () {
        const key = $(this).attr('data-ed-add');
        const item = LISTS[key].blank();
        if (key === 'lyrics' && audio.currentTime > 0 && S.audio) item.t = now();
        arr(key).push(item);
        render(key);
        $root.find('[data-ed-list="' + key + '"] > :last-child').find('input,textarea,select').filter(':visible').first().trigger('focus');
        markDirty();
    });
    $root.on('click', '[data-ed-del]', function () {
        const { key, i } = itemOf(this);
        arr(key).splice(i, 1);
        render(key);
        if (key === 'lyrics') { renderTimeDependents(); }
        markDirty();
    });
    $root.on('click', '[data-ed-now]', function () {
        const $in = $(this).siblings('[data-time]').first();
        if (!S.audio) { toast(t('player.none'), true); return; }
        $in.val(fmtTime(now())).trigger('input').trigger('change');
    });
    $root.on('click', '[data-ed-range-play]', function () {
        const { key, i } = itemOf(this);
        const it = arr(key)[i];
        if (!S.audio) { toast(t('player.none'), true); return; }
        playRange(+it.start, +it.end);
    });

    /* انتخاب خط لیریک (شنیداری / شدوینگ) */
    $root.on('change', '[data-ed-pick-line]', function () {
        const { $item, key, i } = itemOf(this);
        const idx = $(this).val();
        if (idx === '') return;
        const r = lineRange(timedLines(), +idx, +idx);
        if (!r) return;
        const it = arr(key)[i];
        Object.assign(it, { start: r.start, end: r.end, text: r.text });
        if (!answerInText(it.text, it.answer)) it.answer = '';
        delete it._ai;
        fillItem($item, key, i);
        markDirty();
    });
    $root.on('change', '[data-ed-from-line],[data-ed-to-line]', function () {
        const { $item, key, i } = itemOf(this);
        let a = $item.find('[data-ed-from-line]').val();
        let b = $item.find('[data-ed-to-line]').val();
        if (a === '' && b === '') return;
        if (a === '') a = b;
        if (b === '') b = a;
        const r = lineRange(timedLines(), +a, +b);
        if (!r) return;
        Object.assign(arr(key)[i], { start: r.start, end: r.end, text: r.text });
        delete arr(key)[i]._ai;
        fillItem($item, key, i);
        $item.find('[data-ed-from-line]').val(String(Math.min(+a, +b)));
        $item.find('[data-ed-to-line]').val(String(Math.max(+a, +b)));
        markDirty();
    });
    $root.on('click', '[data-ed-words] [data-w]', function () {
        const { $item, key, i } = itemOf(this);
        arr(key)[i].answer = $(this).attr('data-w');
        $item.find('[data-k=answer]').val(arr(key)[i].answer);
        refreshItem($item, key, i);
        markDirty();
    });

    /* یادداشت‌ها (تب جزئیات): آیتم‌های داخلی هر بخش */
    function renderSub($item, ni) {
        const $box = $item.find('[data-ed-sublist]').empty();
        (S.notes[ni].items || []).forEach((it, j) => {
            const el = tplOf('note-item').cloneNode(true);
            $box.append(el);
            $(el).find('[data-sk]').each(function () { $(this).val(it[$(this).attr('data-sk')] || ''); });
            label($(el));
        });
    }
    $root.on('input', '[data-sk]', function () {
        const { key, i } = itemOf(this);
        const j = $(this).closest('[data-ed-sublist]').children().index($(this).closest('[data-ed-subitem]'));
        S.notes[i].items[j][$(this).attr('data-sk')] = $(this).val();
        delete S.notes[i]._ai;
        markDirty();
    });
    $root.on('click', '[data-ed-add-sub]', function () {
        const { $item, i } = itemOf(this);
        S.notes[i].items.push({ term: '', example: '', text: '' });
        renderSub($item, i);
        $item.find('[data-ed-subitem]:last input').first().trigger('focus');
        markDirty();
    });
    $root.on('click', '[data-ed-del-sub]', function () {
        const { $item, i } = itemOf(this);
        const j = $(this).closest('[data-ed-sublist]').children().index($(this).closest('[data-ed-subitem]'));
        S.notes[i].items.splice(j, 1);
        renderSub($item, i);
        markDirty();
    });

    /* ── لیریک: چسباندن / کپی / تایم‌گیری ── */
    $root.on('click', '[data-ed-lrc-open]', () => { $('[data-ed-lrc-text]').val(toLrc(S.lyrics)); openDialog('#ed-lrc'); });
    $(document).on('click', '[data-ed-lrc-apply]', function () {
        const parsed = parseLrc($('[data-ed-lrc-text]').val());
        if (!parsed.length) { toast(t('lyrics.parse_empty'), true); return; }
        const keep = $('[data-ed-lrc-keep]').prop('checked');
        const old = S.lyrics;
        S.lyrics = parsed.map((l, i) => ({ t: l.t == null ? '' : l.t, text: l.text, tr: keep && old[i] && norm(old[i].text) === norm(l.text) ? old[i].tr : '' }));
        closeDialog('#ed-lrc');
        tap.pos = firstUntimed();
        render('lyrics');
        renderTimeDependents();
        markDirty();
        toast(t('lyrics.parsed', { n: nn(parsed.length) }));
    });
    $root.on('click', '[data-ed-lrc-copy]', async function () {
        const text = toLrc(S.lyrics);
        if (!text) { toast(t('lyrics.nothing'), true); return; }
        try { await navigator.clipboard.writeText(text); } catch (e) {
            const ta = document.createElement('textarea'); ta.value = text; ta.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); } catch (err) { /* ignore */ } ta.remove();
        }
        toast(t('lyrics.copied'));
    });

    const tap = { on: false, pos: 0, hist: [] };
    const firstUntimed = () => { const i = S.lyrics.findIndex((l) => !hasT(l)); return i < 0 ? 0 : i; };
    function renderTapPos() {
        const $rows = $root.find('[data-ed-list="lyrics"] > li').removeClass('is-next');
        if (!tap.on) { $root.find('[data-ed-tap-pos]').text(''); return; }
        $rows.eq(tap.pos).addClass('is-next');
        $root.find('[data-ed-tap-pos]').text(S.lyrics.length ? t('lyrics.tap_pos', { a: nn(Math.min(tap.pos + 1, S.lyrics.length)), b: nn(S.lyrics.length) }) : '');
    }
    $root.on('click', '[data-ed-tap-toggle]', function () {
        tap.on = !tap.on;
        $(this).attr('aria-pressed', tap.on ? 'true' : 'false');
        $root.find('[data-ed-tap]').prop('hidden', !tap.on);
        if (tap.on) { tap.pos = firstUntimed(); tap.hist = []; }
        renderTapPos();
    });
    function tapMark() {
        if (!tap.on) return;
        if (!S.audio) { toast(t('player.none'), true); return; }
        if (tap.pos >= S.lyrics.length) { toast(t('lyrics.tap_done')); return; }
        const l = S.lyrics[tap.pos];
        tap.hist.push({ pos: tap.pos, t: l.t });
        l.t = now();
        const $row = $root.find('[data-ed-list="lyrics"] > li').eq(tap.pos);
        $row.find('[data-time]').val(fmtTime(l.t));
        tap.pos++;
        renderTapPos();
        const $n = $root.find('[data-ed-list="lyrics"] > li').eq(tap.pos);
        if ($n.length && $n[0].scrollIntoView) $n[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        if (tap.pos >= S.lyrics.length) toast(t('lyrics.tap_done'));
        renderTimeDependents();
        markDirty();
    }
    $root.on('click', '[data-ed-tap-mark]', tapMark);
    $root.on('click', '[data-ed-tap-undo]', function () {
        const h = tap.hist.pop();
        if (!h) return;
        S.lyrics[h.pos].t = h.t;
        tap.pos = h.pos;
        $root.find('[data-ed-list="lyrics"] > li').eq(h.pos).find('[data-time]').val(fmtTime(h.t));
        renderTapPos();
        renderTimeDependents();
        markDirty();
    });
    document.addEventListener('keydown', (e) => {
        if (!tap.on || e.code !== 'Space' || current !== 'lyrics') return;
        if ($(e.target).is('input,textarea,select,button')) return;
        e.preventDefault();
        tapMark();
    });

    /* خط زنده‌ی لیریک هنگام پخش */
    let liveIdx = -1;
    function liveRow() {
        if (current !== 'lyrics') return;
        const L = S.lyrics;
        let idx = -1;
        for (let i = 0; i < L.length; i++) if (hasT(L[i]) && L[i].t <= audio.currentTime + 0.05) idx = i;
        if (idx === liveIdx) return;
        liveIdx = idx;
        $root.find('[data-ed-list="lyrics"] > li').removeClass('is-live').eq(idx).addClass('is-live');
    }

    /* هر چیزی که به زمان‌های لیریک وابسته است */
    function renderTimeDependents() {
        renderVocals();
        ['listening.easy', 'listening.hard', 'shadowing'].forEach((k) => $root.find('[data-ed-list="' + k + '"] [data-ed-item]').each(function () { const it = itemOf(this); refreshItem($(this), k, it.i); }));
    }

    /* ── نوار «بخش‌های خوانده‌شده» (مرحله‌ی ۱ و ۷) ── */
    function renderVocals() {
        const total = Math.max(+S.duration || 0, isFinite(audio.duration) ? audio.duration : 0, ...S.lyrics.filter(hasT).map((l) => +l.t + 5), 1);
        const segs = sungRanges(S.lyrics);
        $root.find('[data-ed-vocals]').each(function () {
            $(this).empty();
            segs.forEach(([a, b]) => $('<i>').css({ insetInlineStart: (a / total * 100) + '%', width: Math.max(0.6, (b - a) / total * 100) + '%' }).appendTo(this));
        });
        $root.find('[data-ed-vocals-hint]').text(segs.length ? t('play.vocals_n', { n: nn(segs.length) }) : t('play.vocals_none'));
    }

    /* ── پرسش «چقدر متوجه شدی؟» ── */
    function renderQuestions() {
        $root.find('[data-ed-qdef]').each(function () {
            const k = $(this).attr('data-ed-qdef');
            const step = k === 'q_repeat' ? 'repeat' : 'play';
            $(this).attr('placeholder', (DEF[$(this).attr('data-ed-q')] || {})[step] || '');
        });
        ['q_play', 'q_repeat'].forEach((k) => {
            const step = k === 'q_repeat' ? 'repeat' : 'play';
            $root.find('[data-ed-q-prev="' + k + '"]').text(S[k][lang] || (DEF[lang] || {})[step] || '');
        });
    }

    /* ── آپلود ── */
    const LIMIT = { cover: 3 * 1024 * 1024, audio: 40 * 1024 * 1024, audio_vocal: 40 * 1024 * 1024, audio_backing: 40 * 1024 * 1024 };
    const audioDur = (url) => new Promise((res) => { const a = new Audio(); a.preload = 'metadata'; a.onloadedmetadata = () => res(isFinite(a.duration) ? a.duration : 0); a.onerror = () => res(0); a.src = url; setTimeout(() => res(0), 4000); });
    const durs = {};

    API.admin.song.api.upload = API.admin.song.api.upload || (async (file, kind) => {
        const url = $root.attr('data-upload-url');
        if (!url) { // دمو
            const u = URL.createObjectURL(file);
            return { url: u, duration: kind === 'cover' ? undefined : await audioDur(u) };
        }
        const fd = new FormData();
        fd.append('file', file); fd.append('kind', kind);
        let res;
        try { res = await fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: fd }); } catch (e) { throw { message: t('errors.network') }; }
        let data = {};
        try { data = await res.json(); } catch (e) { /* خالی */ }
        if (!res.ok || !data.url) throw { message: data.message || t('errors.upload') };
        return data;
    });
    function viaEvent(name, detail) {
        return new Promise((resolve) => {
            const ev = $.Event(name);
            $(document).trigger(ev, Object.assign({}, detail, { done: (r) => resolve({ ok: r || {} }), fail: (m) => resolve({ err: m }) }));
            if (!ev.isDefaultPrevented()) resolve(null);
        });
    }
    async function doUpload(file, kind) {
        const ev = await viaEvent('admin:song-upload', { file, kind });
        if (ev) { if (ev.err !== undefined) throw { message: ev.err }; return ev.ok; }
        return API.admin.song.api.upload(file, kind);
    }
    $root.on('change', '[data-ed-file]', async function () {
        const $up = $(this).closest('[data-ed-up]');
        const kind = $up.attr('data-ed-up');
        const f = this.files && this.files[0];
        this.value = '';
        if (!f) return;
        const $err = $up.find('[data-ed-up-err]').prop('hidden', true);
        const accepts = ($up.attr('data-accept') || '').split(',');
        const okType = kind === 'cover' ? /^image\/(jpeg|png|webp)$/.test(f.type) : /^audio\//.test(f.type);
        if (!okType || (accepts[0] && !accepts[0].startsWith(kind === 'cover' ? 'image' : 'audio'))) { $err.text(t('errors.type')).prop('hidden', false); return; }
        if (f.size > LIMIT[kind]) { $err.text(t('errors.size', { n: nn(Math.round(LIMIT[kind] / 1048576)) })).prop('hidden', false); return; }
        $up.addClass('is-busy');
        try {
            const r = await doUpload(f, kind);
            S[kind] = r.url;
            if (kind === 'audio' && r.duration) S.duration = Math.round(r.duration);
            durs[kind] = r.duration || 0;
            if (kind === 'audio') loadAudio();
            renderUploads();
            fillFlat();
            markDirty();
            schedule();
        } catch (e) {
            $err.text((e && e.message) || t('errors.upload')).prop('hidden', false);
        } finally { $up.removeClass('is-busy'); }
    });
    $root.on('click', '[data-ed-up-clear]', function () {
        const kind = $(this).closest('[data-ed-up]').attr('data-ed-up');
        S[kind] = '';
        if (kind === 'audio') { loadAudio(); S.duration = 0; }
        renderUploads(); fillFlat(); markDirty(); schedule();
    });
    async function renderUploads() {
        $root.find('[data-ed-up]').each(function () {
            const kind = $(this).attr('data-ed-up');
            const url = S[kind] || '';
            $(this).toggleClass('has-file', !!url);
            $(this).find('[data-ed-up-clear]').prop('hidden', !url);
            const $prev = $(this).find('[data-ed-up-prev]');
            if (kind === 'cover') $prev.css('background-image', url ? 'url("' + url.replace(/"/g, '%22') + '")' : '').toggleClass('has-img', !!url);
            else {
                const name = url ? decodeURIComponent(url.split('?')[0].split('/').pop() || '') : '';
                $(this).find('[data-ed-up-meta]').text(url ? (name.startsWith('blob') ? '' : name) + (durs[kind] ? ' · ' + fmtTime(durs[kind]).slice(0, 5) : kind === 'audio' && S.duration ? ' · ' + fmtTime(S.duration).slice(0, 5) : '') : '');
                const $a = $(this).find('[data-ed-up-audio]');
                if ($a.length) { $a.toggleClass('hidden', !url); if (url) { if ($a.attr('src') !== url) $a.attr('src', url); } else $a.removeAttr('src'); }
            }
        });
        // کارائوکه: هم‌طول بودن دو فایل
        if (S.audio_vocal && S.audio_backing) {
            const a = durs.audio_vocal || (durs.audio_vocal = await audioDur(S.audio_vocal));
            const b = durs.audio_backing || (durs.audio_backing = await audioDur(S.audio_backing));
            $root.find('[data-ed-karaoke-warn]').prop('hidden', !(a && b && Math.abs(a - b) > 0.3));
        } else $root.find('[data-ed-karaoke-warn]').prop('hidden', true);
    }

    /* ── نشانه‌های تب‌ها، چک‌لیست‌ها ── */
    let raf = 0;
    function schedule() { cancelAnimationFrame(raf); raf = requestAnimationFrame(refreshChrome); }
    function refreshChrome() {
        const c = completeness(S);
        const v = validate(S);
        TABS.forEach((k) => {
            const bad = v.some((x) => x.tab === k && x.level === 'error');
            $root.find('[data-ed-dot="' + k + '"]').attr('data-s', bad ? 'bad' : c[k] ? 'ok' : 'todo');
        });
        const n = TABS.filter((k) => k !== 'info' && c[k]).length;
        $root.find('[data-ed-progress]').text(t('steps_of', { n: nn(n) }));
        $root.find('[data-ed-need]').each(function () {
            const k = $(this).attr('data-ed-need');
            $(this).toggleClass('is-ok', k === 'lyrics' ? c.lyrics : !!S[k]);
        });
        const $st = $root.find('[data-ed-status]');
        $st.text(t('status.' + S.status)).attr('data-tone', S.status === 'published' ? 'ok' : '');
    }

    /* ── ذخیره / انتشار ── */
    API.admin.song.api.save = API.admin.song.api.save || (async (payload) => {
        const url = $root.attr('data-save-url');
        if (!url) { // دمو
            if (!LOCAL) throw { message: t('errors.no_backend') };
            localStorage.setItem(lsKey, JSON.stringify(payload));
            return { id: payload.id || Math.floor(Date.now() / 1000) % 100000, status: payload.status };
        }
        return send(url, payload, S.id ? 'PUT' : 'POST');
    });
    function serverErrors(errors) {
        const map = { title: 'title', artist: 'artist', year: 'year' };
        Object.keys(errors || {}).forEach((k) => {
            const $f = $root.find('[data-f="' + k + '"]').first();
            if (!$f.length) return;
            $f.closest('.ad-field').addClass('has-error').find('.ad-error').first().text([].concat(errors[k])[0]).prop('hidden', false);
            if (map[k]) showTab('info');
        });
    }
    async function save(mode) {
        if (busy) return;
        const status = mode === 'published' ? 'published' : mode === 'draft' ? 'draft' : (S.status || 'draft');
        const v = validate(S).filter((x) => x.level === 'error');
        const blocking = v.filter((x) => status === 'published' || x.code === 'title' || x.code === 'artist');
        if (blocking.length) {
            const f = blocking[0];
            closeDialog('#ed-publish');
            showTab(f.tab);
            toast(t('v.' + f.code), true);
            say(t('save.fix', { n: nn(blocking.length) }));
            if (f.path && !f.path.includes('.')) $root.find('[data-f="' + f.path + '"]').first().closest('.ad-field').addClass('has-error').find('.ad-error').first().text(t('v.' + f.code)).prop('hidden', false);
            return;
        }
        busy = true;
        $root.addClass('is-saving').find('[data-ed-save]').prop('disabled', true);
        say(t('save.saving'));
        const payload = Object.assign(cleanSong(S), { status });
        try {
            const ev = await viaEvent('admin:song-save', { payload, status });
            let r;
            if (ev) { if (ev.err !== undefined) throw { message: ev.err }; r = ev.ok; } else r = await API.admin.song.api.save(payload);
            const wasNew = !S.id;
            S.id = r.id || S.id;
            S.status = r.status || status;
            S.updated_at = Math.floor(Date.now() / 1000);
            dirty = false;
            $root.find('[data-ed-dirty]').prop('hidden', true);
            closeDialog('#ed-publish');
            say(t('save.saved'));
            toast(t(status === 'published' ? 'save.published' : 'save.saved'));
            if (wasNew && r.url) { try { history.replaceState(null, '', r.url + location.hash); } catch (e) { /* ignore */ } }
            $root.trigger('admin:song-saved', { id: S.id, status: S.status });
            schedule();
        } catch (e) {
            say('');
            toast((e && e.message) || t('errors.save'), true);
            if (e && e.errors) serverErrors(e.errors);
        } finally {
            busy = false;
            $root.removeClass('is-saving').find('[data-ed-save]').prop('disabled', false);
        }
    }
    $root.on('click', '[data-ed-save]', function () { save($(this).attr('data-ed-save')); });
    $(document).on('click', '#ed-publish [data-ed-save]', function () { save('published'); });

    $root.on('click', '[data-ed-publish-open]', function () {
        const c = completeness(S);
        const v = validate(S).filter((x) => x.level === 'error');
        const hard = ['info', 'play', 'lyrics'];
        const $ul = $('[data-ed-pub-list]').empty();
        TABS.forEach((k) => {
            const bad = v.some((x) => x.tab === k);
            $ul.append($('<li>').attr('data-s', bad ? 'bad' : c[k] ? 'ok' : hard.includes(k) ? 'bad' : 'warn').append($('<span class="ed-ck">'), $('<span>').text(t('tabs.' + k)), $('<small>').text(bad ? t('pub.fix') : c[k] ? '' : hard.includes(k) ? t('pub.required') : t('pub.optional'))));
        });
        const blocked = hard.some((k) => !c[k]) || v.length > 0;
        const missingSoft = TABS.filter((k) => !hard.includes(k) && !c[k]).length;
        $('[data-ed-pub-lead]').text(blocked ? t('pub.blocked') : missingSoft ? t('pub.soft', { n: nn(missingSoft) }) : t('pub.ready'));
        $('#ed-publish [data-ed-save]').prop('disabled', blocked);
        openDialog('#ed-publish');
    });

    /* حذف */
    $root.on('click', '[data-ed-delete-open]', () => openDialog('#ed-delete'));
    $(document).on('click', '[data-ed-delete]', async function () {
        const $b = $(this).prop('disabled', true);
        try {
            const ev = await viaEvent('admin:song-delete', { id: S.id });
            if (ev) { if (ev.err !== undefined) throw { message: ev.err }; } else if (API.admin.song.api.remove) await API.admin.song.api.remove(S.id);
            else if ($root.attr('data-delete-url')) await send($root.attr('data-delete-url'), {}, 'DELETE');
            else if (LOCAL) localStorage.removeItem(lsKey);
            dirty = false;
            location.assign($root.attr('data-list-url'));
        } catch (e) { toast((e && e.message) || t('errors.save'), true); $b.prop('disabled', false); }
    });

    /* ── AI ── */
    function currentForAI() {
        return { lyrics: S.lyrics.map((l) => ({ t: l.t === '' ? null : l.t, text: l.text })), words: S.words.map((w) => w.phrase), title: S.title, artist: S.artist };
    }
    async function aiRequest(kind, seeds, fields) {
        const payload = { kind, seeds: Object.assign({ title: S.title, artist: S.artist }, seeds || {}), fields, note: '', current: currentForAI(), locale: document.documentElement.lang };
        const ask = API.admin.ai && API.admin.ai.request;
        if (!ask) return demoAI(kind, payload);
        return ask(payload, { url: $root.attr('data-ai-url') || '', demo: () => demoAI(kind, payload) });
    }
    const aiCount = (n) => toast(n ? t('ai.applied', { n: nn(n) }) : t('ai.nothing'), !n);
    const aiMissing = (r, extra) => { const m = (r.missing || []).filter((x) => extra.includes(x)); if (m.length) setTimeout(() => toast(t('ai.missing_' + m[0]), true), 1900); };

    $root.on('click', '[data-ed-ai]', async function () {
        const kind = $(this).attr('data-ed-ai');
        const $b = $(this);
        if (kind !== 'details' && !S.lyrics.some((l) => hasT(l) && filled(l.text)) && kind !== 'lyrics_tr') { toast(t('ai.need_lyrics'), true); return; }
        if (kind === 'lyrics_tr' && !S.lyrics.some((l) => filled(l.text))) { toast(t('ai.need_lyrics'), true); return; }
        if (kind === 'details' && !S.lyrics.some((l) => filled(l.text)) && !filled(S.title)) { toast(t('ai.need_lyrics'), true); return; }
        $b.prop('disabled', true).addClass('is-busy');
        try {
            const mode = $b.attr('data-mode');
            const r = await aiRequest(kind, mode ? { mode } : {}, [kind]);
            const f = (r && r.fields) || {};
            let n = 0;
            if (kind === 'lyrics_tr') {
                const trs = f.tr || [];
                const L = S.lyrics.filter((l) => filled(l.text));
                L.forEach((l, i) => { if (trs[i] && !filled(l.tr)) { l.tr = trs[i]; l._ai = true; n++; } });
                render('lyrics');
            } else if (kind === 'words_suggest') {
                const have = new Set(S.words.map((w) => norm(w.phrase)));
                (f.words || []).forEach((w) => { if (!have.has(norm(w.phrase))) { S.words.push(Object.assign(LISTS.words.blank(), w, { ipa: Object.assign({ uk: '', us: '' }, w.ipa || {}), _ai: true })); n++; } });
                render('words');
                aiMissing(r, ['audio']);
            } else if (kind === 'listening_suggest') {
                const key = 'listening.' + (mode || 'easy');
                (f.items || []).forEach((c) => { arr(key).push(Object.assign(LISTS[key].blank(), c, { _ai: true })); n++; });
                render(key);
            } else if (kind === 'shadowing_suggest') {
                (f.items || []).forEach((c) => { S.shadowing.push(Object.assign(LISTS.shadowing.blank(), c, { _ai: true })); n++; });
                render('shadowing');
            } else if (kind === 'details') {
                if (f.interpretation && !filled(S.interpretation)) { S.interpretation = f.interpretation; n++; fillFlat(); $root.find('[data-f=interpretation]').closest('.ad-field').addClass('is-ai'); }
                (f.notes || []).forEach((x) => { if (!S.notes.some((o) => norm(o.title) === norm(x.title))) { S.notes.push(Object.assign(LISTS.notes.blank(), x, { _ai: true })); n++; } });
                render('notes');
            }
            aiCount(n);
            if (n) { markDirty(); renderTimeDependents(); }
        } catch (e) { toast((e && e.message) || t('ai.error'), true); } finally { $b.prop('disabled', false).removeClass('is-busy'); }
    });

    $root.on('click', '[data-ed-ai-item]', async function () {
        const { $item, key, i } = itemOf(this);
        const w = arr(key)[i];
        if (!filled(w.phrase)) { toast(t('ai.need_phrase'), true); return; }
        const $b = $(this).prop('disabled', true).addClass('is-busy');
        try {
            const line = (S.lyrics.find((l) => norm(l.text).includes(norm(w.phrase))) || {}).text || '';
            const r = await aiRequest('word_fill', { phrase: w.phrase, line }, ['type', 'level', 'ipa', 'meaning', 'synonyms', 'antonyms', 'translate']);
            const f = (r && r.fields) || {};
            let n = 0;
            ['type', 'level', 'meaning', 'translate'].forEach((k) => { if (f[k] && !filled(w[k])) { w[k] = f[k]; n++; } });
            ['synonyms', 'antonyms'].forEach((k) => { if ((f[k] || []).length && !(w[k] || []).length) { w[k] = f[k]; n++; } });
            ['uk', 'us'].forEach((k) => { if (f.ipa && f.ipa[k] && !filled(w.ipa[k])) { w.ipa[k] = f.ipa[k]; n++; } });
            if (n) w._ai = true;
            fillItem($item, key, i);
            aiCount(n);
            aiMissing(r, ['audio']);
            if (n) markDirty();
        } catch (e) { toast((e && e.message) || t('ai.error'), true); } finally { $b.prop('disabled', false).removeClass('is-busy'); }
    });

    // پس از اعمال AI روی فرم اطلاعات، مقدارها را از DOM به مدل برگردان
    $(document).on('admin:ai-applied', function () {
        $root.find('[data-f]').each(function () { const k = $(this).attr('data-f'); if (this.type !== 'checkbox' && $(this).closest('#ed-info-form,.ed-up').length) setp(S, k, $(this).val()); });
        fillFlat(); renderUploads(); markDirty();
    });

    /* ── شروع ── */
    fillFlat();
    renderAll();
    renderQuestions();
    renderVocals();
    renderUploads();
    loadAudio();
    refreshChrome();
    showTab((location.hash || '').slice(1) || 'info');
    // برای تست و بک‌اند
    API.admin.song.state = () => S;
    API.admin.song.show = showTab;
});
