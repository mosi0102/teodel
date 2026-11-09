import $ from 'jquery';
import { openDialog } from './dialogs';
import { digits } from './digits';

/**
 * کتاب‌ها — صفحه‌ی آموزش (kind=book): مرحله‌ی ۱ (معرفی)، ۲ (خواننده) و ۳ (مرور).
 *
 * خواننده (مرحله‌ی ۲):
 *   - فصل‌ها یکی‌یکی (انتخاب از منو، قبلی/بعدی). فصل قفل فقط کارت اشتراک دارد.
 *   - جای خواندن (فصل + پاراگراف) خودکار ذخیره می‌شود و با ورود دوباره «از جایی که ماندی» ادامه می‌دهد؛ نشانک دستی هم دارد.
 *   - تنظیمات برای هر کتاب جدا: تم (light|sepia|dark)، فونت (sans|serif)، اندازه (15–26px).
 *   - لمس لغت ← کارت معنی (اول از لغات کتاب، بعد lookup)، با بوکمارک (همان ذخیره‌ی لغات مرحله‌ی ۳ موزیک ← صفحه‌ی /words).
 *   - انتخاب متن ← دکمه‌ی «ذخیره‌ی جمله» ← هایلایت داخل کتاب + لیست مرحله‌ی ۳ + تب «جمله‌های محبوب» در /words.
 *   - پیشرفت مرحله = درصد خوانده‌شده‌ی فصل‌های باز؛ رسیدن به انتهای آخرین فصل باز = تکمیل مرحله.
 *
 * 🔌 بک‌اند (هر کدام اختیاری؛ بدون آن‌ها LOCAL=true در مرورگر ذخیره می‌کند):
 *   رویدادها:
 *     book:progress  { book, chapter, offset, percent }           ← جای خواندن (throttle ۱.۵ ثانیه)
 *     book:bookmark  { book, chapter, offset }                    ← نشانک دستی
 *     book:settings  { book, theme, font, size }
 *     sentence:save  { id, book, book_title, chapter, text, ref } | sentence:remove { id, book }
 *     word:bookmark  { song: book, key, text, saved, line: chapter, kind: 'book' }   (همان رویداد موزیک)
 *     book:chapter-summary-request { book, chapter, done(text), fail(msg) }  ← لغوشدنی (e.preventDefault())
 *   آدرس‌ها روی ریشه (POST JSON + CSRF): data-progress-url, data-settings-url, data-sentences-url (POST ذخیره / DELETE {id})،
 *     data-lookup-url (GET ?word= ← {phrase,type,level,ipa,meaning,synonyms,antonyms,translate})، data-summary-url (POST {book,chapter} ← {text})
 *   توابع:  Teodel.books.lookup = async (word, { book, chapter }) => info|null
 *           Teodel.books.summarize = async ({ book, chapter }) => 'متن خلاصه'
 *   داده‌ی سرور (وقتی LOCAL=false): جمله‌ها از window.Teodel.books.sentences (یا SavedWords::sentences در /words) بیاید.
 */
const LOCAL = true;
const SENT_KEY = 'teodel:sentences'; // [{ id, book, book_title, chapter, text, ref, at }] — همان کلیدِ saved-sentences.js
const WORDS_KEY = 'teodel:words:saved'; // همان کلیدِ words.js / saved-words.js
const posKey = (b) => 'teodel:book:pos:' + b;
const bmKey = (b) => 'teodel:book:bm:' + b;
const setKey = (b) => 'teodel:book:settings:' + b;

const store = {
    get(k, fb) {
        try {
            const v = JSON.parse(localStorage.getItem(k));
            return v == null ? fb : v;
        } catch (e) {
            return fb;
        }
    },
    set(k, v) {
        try {
            localStorage.setItem(k, JSON.stringify(v));
        } catch (e) { /* در دسترس نیست */ }
    },
};

const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const csrf = () => $('meta[name="csrf-token"]').attr('content') || '';
const tpl = (s, o) => String(s || '').replace(/:(\w+)/g, (m, k) => (k in o ? o[k] : m));
const norm = (s) => String(s || '').toLowerCase().replace(/[’‘]/g, "'").replace(/^[^a-z0-9']+|[^a-z0-9']+$/gi, '');

function post(url, body, method = 'POST') {
    if (!url) return Promise.resolve(null);
    return fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    }).then((r) => (r.ok ? r.json().catch(() => ({})) : Promise.reject(r)));
}

const API = (window.Teodel = window.Teodel || {});
API.books = API.books || {};

/* ── جمله‌های ذخیره‌شده (مشترک بین خواننده، مرور و /words) ─────────────── */
export const sentences = {
    all() {
        return LOCAL ? store.get(SENT_KEY, []) : API.books.sentences || [];
    },
    of(book) {
        return this.all().filter((s) => String(s.book) === String(book));
    },
    add(s) {
        const list = this.all().filter((x) => !(String(x.book) === String(s.book) && x.text === s.text));
        list.unshift(s);
        if (LOCAL) store.set(SENT_KEY, list);
        else API.books.sentences = list;
        $(document).trigger('sentence:save', [s]);
        $(document).trigger('sentences:changed', [s.book]);
    },
    remove(id) {
        const list = this.all();
        const s = list.find((x) => x.id === id);
        const rest = list.filter((x) => x.id !== id);
        if (LOCAL) store.set(SENT_KEY, rest);
        else API.books.sentences = rest;
        if (s) {
            $(document).trigger('sentence:remove', [{ id, book: s.book }]);
            $(document).trigger('sentences:changed', [s.book]);
        }
    },
};

/* ── مرحله‌ی ۲: خواننده ─────────────────────────────────────────────── */
function initReader(root) {
    const $root = $(root);
    const book = Number($root.attr('data-book'));
    const title = $root.attr('data-title') || '';
    const total = Number($root.attr('data-total')) || 1;
    const words = JSON.parse($root.attr('data-words') || '{}');
    const T = JSON.parse($root.attr('data-t') || '{}');
    const urls = {
        progress: $root.attr('data-progress-url'),
        settings: $root.attr('data-settings-url'),
        sentences: $root.attr('data-sentences-url'),
        lookup: $root.attr('data-lookup-url'),
    };
    const $panel = $root.closest('[data-step-panel]');
    const $page = $root.find('[data-bk-page]');
    const $chs = $root.find('[data-bk-ch]');
    const $select = $root.find('[data-bk-chapter-select]');
    const $toast = $root.find('[data-bk-toast]');
    const $saveBtn = $root.find('[data-bk-save-sentence]');
    const openChs = $chs.filter(function () { return $(this).find('[data-bk-text]').length > 0; }).map(function () { return Number($(this).attr('data-bk-ch')); }).get();

    let cur = 1;
    let maxRead = {}; // chapter → بیشترین کسر خوانده‌شده (۰–۱)
    let done = false;
    let resumed = false;
    const active = () => !$panel.prop('hidden');

    /* توکن‌سازی: هر کلمه یک span؛ لغت‌های کلیدی کتاب کلاس is-key */
    $root.find('[data-bk-p]').each(function () {
        const text = this.textContent;
        let html = '';
        let i = 0;
        text.replace(/[A-Za-z][A-Za-z’'-]*/g, (w, at) => {
            html += esc(text.slice(i, at));
            const k = norm(w);
            const key = words[k] ? k : null;
            html += '<span class="bk-w' + (key ? ' is-key' : '') + '" data-w="' + esc(w) + '">' + esc(w) + '</span>';
            i = at + w.length;
            return w;
        });
        html += esc(text.slice(i));
        this.innerHTML = html;
    });

    /* ── تنظیمات ── */
    const defaults = { theme: 'light', font: 'sans', size: 18 };
    let settings = Object.assign({}, defaults, store.get(setKey(book), {}));
    function applySettings(save) {
        settings.size = Math.min(26, Math.max(15, Number(settings.size) || 18));
        $page.removeClass('bk-theme-light bk-theme-sepia bk-theme-dark bk-font-sans bk-font-serif')
            .addClass('bk-theme-' + settings.theme + ' bk-font-' + settings.font)
            .css('--bk-size', settings.size + 'px');
        $root.find('[data-bk-theme]').each(function () { $(this).attr('aria-checked', String($(this).attr('data-bk-theme') === settings.theme)); });
        $root.find('[data-bk-font]').each(function () { $(this).attr('aria-checked', String($(this).attr('data-bk-font') === settings.font)); });
        $root.find('[data-bk-size-range]').val(settings.size);
        if (!save) return;
        store.set(setKey(book), settings);
        const data = { book, theme: settings.theme, font: settings.font, size: settings.size };
        $(document).trigger('book:settings', [data]);
        post(urls.settings, data).catch(() => {});
    }
    applySettings(false);

    const $set = $root.find('[data-bk-settings]');
    const $setBtn = $root.find('[data-bk-settings-open]');
    function toggleSettings(open) {
        $set.prop('hidden', !open);
        $setBtn.attr('aria-expanded', String(open));
    }
    $setBtn.on('click', () => toggleSettings($set.prop('hidden')));
    $(document).on('click', (e) => {
        if (!$set.prop('hidden') && !$(e.target).closest('[data-bk-settings],[data-bk-settings-open]').length) toggleSettings(false);
    });
    $(document).on('keydown', (e) => {
        if (e.key === 'Escape' && !$set.prop('hidden')) {
            toggleSettings(false);
            $setBtn.trigger('focus');
        }
    });
    $root.on('click', '[data-bk-theme]', function () { settings.theme = $(this).attr('data-bk-theme'); applySettings(true); });
    $root.on('click', '[data-bk-font]', function () { settings.font = $(this).attr('data-bk-font'); applySettings(true); });
    $root.on('click', '[data-bk-size]', function () { settings.size += Number($(this).attr('data-bk-size')); applySettings(true); });
    $root.on('input change', '[data-bk-size-range]', function () { settings.size = Number(this.value); applySettings(true); });

    /* ── پیام کوتاه ── */
    let tt = 0;
    function toast(msg) {
        $toast.text(msg).prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(tt);
        tt = setTimeout(() => $toast.prop('hidden', true), 2200);
    }

    /* ── فصل‌ها ── */
    function showChapter(n, opts = {}) {
        n = Math.min(total, Math.max(1, Number(n) || 1));
        cur = n;
        $chs.each(function () { $(this).prop('hidden', Number($(this).attr('data-bk-ch')) !== n); });
        $select.val(String(n));
        markSaved();
        if (opts.para != null) {
            const p = $chs.filter('[data-bk-ch="' + n + '"]').find('[data-bk-p="' + opts.para + '"]')[0];
            if (p) setTimeout(() => p.scrollIntoView({ block: 'start', behavior: opts.smooth ? 'smooth' : 'auto' }), 30);
        } else if (opts.top !== false) {
            const bar = $root.find('[data-bk-bar]')[0];
            if (bar && bar.getBoundingClientRect().top < 0) bar.scrollIntoView({ block: 'start' });
        }
        updateProgress();
    }
    $select.on('change', function () { showChapter(this.value); });
    $root.on('click', '[data-bk-goto]', function () { showChapter($(this).attr('data-bk-goto')); });

    /* ── پیشرفت خواندن ── */
    function currentPara() {
        const $ps = $chs.filter('[data-bk-ch="' + cur + '"]').find('[data-bk-p]');
        let idx = 0;
        const top = 120;
        $ps.each(function (i) {
            if (this.getBoundingClientRect().top <= top) idx = i;
        });
        return idx;
    }
    function chapterFraction() {
        const sec = $chs.filter('[data-bk-ch="' + cur + '"]').find('[data-bk-text]')[0];
        if (!sec) return 0;
        const r = sec.getBoundingClientRect();
        const seen = window.innerHeight - r.top;
        return Math.min(1, Math.max(0, seen / Math.max(1, r.height)));
    }
    function percent() {
        if (!openChs.length) return 0;
        const sum = openChs.reduce((a, n) => a + (maxRead[n] || 0), 0);
        return sum / openChs.length;
    }
    let lastSent = 0;
    function updateProgress() {
        if (!active()) return;
        if (openChs.includes(cur)) maxRead[cur] = Math.max(maxRead[cur] || 0, chapterFraction());
        const p = percent();
        $root.find('[data-bk-pct]').text(digits(tpl(T.pct, { n: Math.round(p * 100) })));
        store.set(posKey(book), { chapter: cur, offset: currentPara(), read: maxRead, at: Date.now() });
        if (window.Teodel.lesson && !done) {
            if (p >= 0.99) {
                done = true;
                window.Teodel.lesson.completeStep();
                toast(T.finish);
            } else {
                window.Teodel.lesson.setStepProgress(Math.min(0.95, p));
            }
        }
        const now = Date.now();
        if (now - lastSent > 1500) {
            lastSent = now;
            const data = { book, chapter: cur, offset: currentPara(), percent: Math.round(p * 100) };
            $(document).trigger('book:progress', [data]);
            post(urls.progress, data).catch(() => {});
        }
    }
    let raf = 0;
    $(window).on('scroll resize', () => {
        if (raf) return;
        raf = requestAnimationFrame(() => {
            raf = 0;
            updateProgress();
        });
    });

    /* ── نشانک ── */
    const $bm = $root.find('[data-bk-bookmark]');
    function renderBm() {
        const bm = store.get(bmKey(book), null);
        $bm.attr('aria-pressed', String(!!bm && bm.chapter === cur));
    }
    $bm.on('click', () => {
        const bm = { chapter: cur, offset: currentPara() };
        store.set(bmKey(book), bm);
        $(document).trigger('book:bookmark', [Object.assign({ book }, bm)]);
        renderBm();
        toast(T.bm);
    });

    /* ── ورود به مرحله: ادامه از جای قبلی ── */
    $(document).on('lesson:step-change', () => {
        if (!active()) return;
        const want = API.books._goChapter;
        API.books._goChapter = null;
        if (want) {
            showChapter(want);
        } else if (!resumed) {
            const pos = store.get(posKey(book), null) || store.get(bmKey(book), null);
            if (pos && pos.read) maxRead = pos.read;
            if (pos && (pos.chapter > 1 || pos.offset > 0)) {
                showChapter(pos.chapter, { para: pos.offset });
                toast(T.resumed);
            } else {
                showChapter(cur, { top: false });
            }
        }
        resumed = true;
        renderBm();
    });

    /* ── کارت لغت ── */
    const $dlg = $('#bk-word');
    let curWord = null;
    let seq = 0;
    function savedWords() {
        return store.get(WORDS_KEY, {});
    }
    function renderWord(w, info) {
        $dlg.find('[data-bw-phrase]').text(info ? info.phrase : w.text);
        $dlg.find('[data-bw-type]').text(info ? info.type || '' : '');
        $dlg.find('[data-bw-type-wrap]').prop('hidden', !(info && info.type));
        const ipa = info && info.ipa ? info.ipa.uk || info.ipa.us || '' : '';
        $dlg.find('[data-bw-ipa]').text(ipa);
        $dlg.find('[data-bw-level]').text(info ? info.level || '' : '').prop('hidden', !(info && info.level));
        $dlg.find('[data-bw-meaning]').text(info ? info.meaning || '' : '').prop('hidden', !info);
        $dlg.find('[data-bw-nodata]').prop('hidden', !!info);
        $dlg.find('[data-bw-dict]').attr('href', ($root.attr('data-dict-url') || '/dictionary') + '?q=' + encodeURIComponent(w.text));
        $dlg.find('[data-bw-table]').prop('hidden', !info);
        $dlg.find('[data-bw-row="synonyms"]').prop('hidden', !(info && info.synonyms && info.synonyms.length));
        $dlg.find('[data-bw-row="antonyms"]').prop('hidden', !(info && info.antonyms && info.antonyms.length));
        $dlg.find('[data-bw-synonyms]').text(info && info.synonyms ? info.synonyms.join(', ') : '');
        $dlg.find('[data-bw-antonyms]').text(info && info.antonyms ? info.antonyms.join(', ') : '');
        $dlg.find('[data-bw-row="translate"]').prop('hidden', !(info && info.translate));
        $dlg.find('[data-bw-translate]').text(info ? info.translate || '' : '');
        $dlg.find('[data-bw-tr]').attr('aria-pressed', 'false');
        $dlg.find('[data-bw-ctx]').text(w.ctx || '');
        $dlg.find('[data-bw-ctx-wrap]').prop('hidden', !w.ctx);
        const key = info ? norm(info.phrase) : norm(w.text);
        const on = !!savedWords()[key];
        const $b = $dlg.find('[data-bw-bookmark]');
        $b.attr('aria-pressed', String(on)).attr('aria-label', on ? $b.attr('data-label-remove') : $b.attr('data-label-add')).prop('disabled', !info);
    }
    async function lookup(text) {
        const k = norm(text);
        if (words[k]) return words[k];
        // شکل‌های ساده‌ی صرفی (waves → wave, shook ✗)
        for (const cand of [k.replace(/(ies)$/, 'y'), k.replace(/(es|s|ed|ing|d)$/, ''), k.replace(/(ing|ed)$/, 'e')]) {
            if (cand && words[cand]) return words[cand];
        }
        if (typeof API.books.lookup === 'function') return API.books.lookup(text, { book, chapter: cur });
        if (urls.lookup) {
            const r = await fetch(urls.lookup + (urls.lookup.includes('?') ? '&' : '?') + 'word=' + encodeURIComponent(text), { headers: { Accept: 'application/json' } });
            return r.ok ? r.json() : null;
        }
        return null;
    }
    $root.on('click', '.bk-w', async function () {
        const sel = window.getSelection && String(window.getSelection());
        if (sel && sel.trim().split(/\s+/).length > 1) return; // انتخاب جمله، نه کلیک روی لغت
        const text = $(this).attr('data-w');
        const ctx = sentenceAround(this);
        $root.find('.bk-w.is-active').removeClass('is-active');
        $(this).addClass('is-active');
        curWord = { text, ctx, info: null };
        const my = ++seq;
        renderWord(curWord, null);
        $dlg.find('[data-bw-nodata]').prop('hidden', true);
        $dlg.find('[data-bw-loading]').prop('hidden', false);
        openDialog('#bk-word');
        let info = null;
        try {
            info = await lookup(text);
        } catch (e) {
            info = null;
        }
        if (my !== seq) return;
        curWord.info = info;
        $dlg.find('[data-bw-loading]').prop('hidden', true);
        renderWord(curWord, info);
    });
    $dlg.on('click', '[data-bw-tr]', function () { $(this).attr('aria-pressed', String($(this).attr('aria-pressed') !== 'true')); });
    $dlg.on('click', '[data-bw-speak]', () => {
        if (!curWord || !window.speechSynthesis) return;
        const u = new SpeechSynthesisUtterance(curWord.info ? curWord.info.phrase : curWord.text);
        u.lang = 'en-GB';
        u.rate = 0.9;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
    });
    $dlg.on('click', '[data-bw-bookmark]', () => {
        if (!curWord || !curWord.info) return;
        const info = curWord.info;
        const key = norm(info.phrase);
        const all = savedWords();
        const on = !all[key];
        if (on) all[key] = { key, text: info.phrase, song: book, kind: 'book', book_title: title, line: cur, at: Math.floor(Date.now() / 1000), info };
        else delete all[key];
        if (LOCAL) store.set(WORDS_KEY, all);
        $(document).trigger('word:bookmark', [{ song: book, key, text: info.phrase, saved: on, line: cur, kind: 'book' }]);
        $(document).trigger('sentences:changed', [book]); // مرحله‌ی مرور هم لغت‌ها را تازه کند
        renderWord(curWord, info);
    });
    $dlg.on('dialog:close', () => $root.find('.bk-w.is-active').removeClass('is-active'));

    /* ── ذخیره‌ی جمله ── */
    function sentenceAround(el) {
        const p = $(el).closest('[data-bk-p]')[0];
        if (!p) return '';
        const text = p.textContent;
        const w = $(el).text();
        let at = 0;
        $(p).find('.bk-w').each(function () {
            if (this === el) return false;
            at = text.indexOf($(this).text(), at) + $(this).text().length;
            return undefined;
        });
        const pos = text.indexOf(w, at);
        // جمله‌ها (بدون lookbehind تا Safari قدیمی هم کار کند)
        const parts = text.match(/[^.!?…]+[.!?…]+["”’]?\s*|[^.!?…]+$/g) || [text];
        let acc = 0;
        for (const s of parts) {
            const end = acc + s.length;
            if (pos >= acc && pos < end) return s.trim();
            acc = end;
        }
        return '';
    }

    let pending = null;
    function readSelection() {
        const sel = window.getSelection ? window.getSelection() : null;
        if (!sel || sel.isCollapsed || !sel.rangeCount) return null;
        const range = sel.getRangeAt(0);
        const $p = $(range.commonAncestorContainer.nodeType === 1 ? range.commonAncestorContainer : range.commonAncestorContainer.parentNode).closest('[data-bk-text]');
        if (!$p.length || !$.contains($root[0], $p[0])) return null;
        const text = String(sel).replace(/\s+/g, ' ').trim();
        if (text.length < 6 || text.split(' ').length < 2) return null;
        const $startP = $(range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentNode).closest('[data-bk-p]');
        return { text: text.slice(0, 400), ref: Number($startP.attr('data-bk-p')) || 0, rect: range.getBoundingClientRect() };
    }
    function placeSaveBtn() {
        pending = readSelection();
        if (!pending) {
            $saveBtn.prop('hidden', true);
            return;
        }
        $saveBtn.prop('hidden', false);
        if (window.matchMedia('(min-width: 768px)').matches) {
            const r = pending.rect;
            const w = $saveBtn.outerWidth();
            $saveBtn.css({ position: 'fixed', top: Math.max(8, r.top - 52) + 'px', left: Math.min(window.innerWidth - w - 8, Math.max(8, r.left + r.width / 2 - w / 2)) + 'px', bottom: '' });
        } else {
            $saveBtn.css({ position: '', top: '', left: '', bottom: '' });
        }
    }
    let selT = 0;
    document.addEventListener('selectionchange', () => {
        if (!active()) return;
        clearTimeout(selT);
        selT = setTimeout(placeSaveBtn, 180);
    });
    $(window).on('scroll', () => { if (!$saveBtn.prop('hidden')) placeSaveBtn(); });
    $saveBtn.on('mousedown touchstart', (e) => e.preventDefault()); // انتخاب از بین نرود
    $saveBtn.on('click', () => {
        if (!pending) return;
        const s = { id: 's' + Date.now().toString(36), book, book_title: title, chapter: cur, text: pending.text, ref: pending.ref, at: Math.floor(Date.now() / 1000) };
        sentences.add(s);
        post(urls.sentences, s).catch(() => {});
        if (window.getSelection) window.getSelection().removeAllRanges();
        $saveBtn.prop('hidden', true);
        pending = null;
        markSaved();
        toast(T.saved);
    });

    /* هایلایت جمله‌های ذخیره‌شده: کلمه‌هایی که داخل متن جمله می‌افتند is-saved می‌گیرند */
    function markSaved() {
        const $ch = $chs.filter('[data-bk-ch="' + cur + '"]');
        $ch.find('.bk-w.is-saved').removeClass('is-saved is-saved-start is-saved-end');
        const list = sentences.of(book).filter((s) => Number(s.chapter) === cur);
        if (!list.length) return;
        $ch.find('[data-bk-p]').each(function () {
            const p = this;
            const text = p.textContent;
            const spans = [];
            let at = 0;
            $(p).find('.bk-w').each(function () {
                const t = $(this).text();
                const i = text.indexOf(t, at);
                spans.push({ el: this, a: i, b: i + t.length });
                at = i + t.length;
            });
            list.forEach((s) => {
                const i = text.indexOf(s.text);
                if (i < 0) return;
                const j = i + s.text.length;
                const hit = spans.filter((x) => x.b > i && x.a < j);
                hit.forEach((x, k) => {
                    $(x.el).addClass('is-saved');
                    if (k === 0) $(x.el).addClass('is-saved-start');
                    if (k === hit.length - 1) $(x.el).addClass('is-saved-end');
                });
            });
        });
    }
    $(document).on('sentences:changed', (e, b) => { if (String(b) === String(book)) markSaved(); });

    /* پرش از مرحله‌ی مرور به یک جمله */
    API.books.gotoSentence = (s) => {
        if (window.Teodel.lesson) window.Teodel.lesson.go(Number($panel.attr('data-step-panel')));
        setTimeout(() => showChapter(s.chapter, { para: s.ref, smooth: true }), 60);
    };

    showChapter(1, { top: false });
}

/* ── مرحله‌ی ۱: معرفی — «ادامه از جایی که ماندی» و لینک فصل‌ها ── */
function initIntro(root) {
    const $root = $(root);
    const book = $root.attr('data-book');
    const pos = store.get(posKey(book), null);
    if (pos && (pos.chapter > 1 || pos.offset > 0)) {
        const $l = $root.find('[data-bk-start-label]');
        $l.text($l.attr('data-resume'));
    }
    $root.on('click', '[data-bk-go]', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || !window.Teodel.lesson) return;
        e.preventDefault();
        const ch = $(this).attr('data-bk-ch-link');
        if (ch) API.books._goChapter = Number(ch);
        window.Teodel.lesson.go(Number($(this).attr('data-bk-go')));
    });
}

/* ── مرحله‌ی ۳: مرور ── */
function initReview(root) {
    const $root = $(root);
    const book = Number($root.attr('data-book'));
    const demo = JSON.parse($root.attr('data-demo') || '{}');
    const url = $root.attr('data-summary-url');
    const tChapter = $root.attr('data-t-chapter') || ':n';

    function renderSentences() {
        const list = sentences.of(book);
        $root.find('[data-bk-rs-count]').text(digits(list.length));
        $root.find('[data-bk-rs-empty]').prop('hidden', list.length > 0);
        $root.find('[data-bk-rs-list]').html(list.map((s) => `
            <li class="bk-sent" data-id="${esc(s.id)}">
                <p dir="ltr" lang="en">“${esc(s.text)}”</p>
                <div class="bk-sent__f">
                    <span>${esc(digits(tpl(tChapter, { n: s.chapter })))}</span>
                    <button type="button" class="bk-link" data-bk-rs-goto>${esc($root.attr('data-t-goto'))}</button>
                    <button type="button" class="bk-link bk-link--bad" data-bk-rs-del>${esc($root.attr('data-t-remove'))}</button>
                </div>
            </li>`).join(''));
    }
    function renderWords() {
        const all = store.get(WORDS_KEY, {});
        const list = Object.values(all).filter((w) => w.kind === 'book' && String(w.song) === String(book));
        $root.find('[data-bk-rw-count]').text(digits(list.length));
        $root.find('[data-bk-rw-empty]').prop('hidden', list.length > 0);
        $root.find('[data-bk-rw-list]').html(list.map((w) => `<li><span class="bk-chip" title="${esc(w.info ? w.info.translate || '' : '')}">${esc(w.text)}</span></li>`).join(''));
    }
    $root.on('click', '[data-bk-rs-del]', function () {
        sentences.remove($(this).closest('[data-id]').attr('data-id'));
    });
    $root.on('click', '[data-bk-rs-goto]', function () {
        const s = sentences.of(book).find((x) => x.id === $(this).closest('[data-id]').attr('data-id'));
        if (s && API.books.gotoSentence) API.books.gotoSentence(s);
    });
    $(document).on('sentences:changed', (e, b) => {
        if (String(b) !== String(book)) return;
        renderSentences();
        renderWords();
    });
    $(document).on('lesson:step-change', () => {
        renderSentences();
        renderWords();
    });
    renderSentences();
    renderWords();

    /* خلاصه‌ی AI */
    async function summarize(chapter) {
        const ev = $.Event('book:chapter-summary-request');
        const viaEvent = await new Promise((resolve) => {
            $(document).trigger(ev, [{ book, chapter, done: (t) => resolve({ ok: t }), fail: (m) => resolve({ err: m || 'error' }) }]);
            if (!ev.isDefaultPrevented()) resolve(null);
        });
        if (viaEvent) {
            if (viaEvent.err) throw new Error(viaEvent.err);
            return viaEvent.ok;
        }
        if (typeof API.books.summarize === 'function') return API.books.summarize({ book, chapter });
        if (url) {
            const r = await post(url, { book, chapter });
            return r && r.text;
        }
        await new Promise((r) => setTimeout(r, 900)); // دمو
        if (!demo[chapter]) throw new Error('no demo');
        return demo[chapter];
    }
    $root.on('click', '[data-bk-sum-go]', async function () {
        const $li = $(this).closest('[data-bk-sum]');
        const n = Number($li.attr('data-bk-sum'));
        const $btn = $(this).prop('disabled', true);
        $li.attr('data-state', 'loading');
        $li.find('[data-bk-sum-load]').prop('hidden', false);
        $li.find('[data-bk-sum-err]').prop('hidden', true);
        try {
            const text = await summarize(n);
            if (!text) throw new Error('empty');
            $li.find('[data-bk-sum-text]').text(text);
            $li.find('[data-bk-sum-out]').prop('hidden', false);
            $li.attr('data-state', 'done');
            const $lab = $li.find('[data-bk-sum-label]');
            $lab.text($lab.attr('data-again'));
        } catch (e) {
            $li.find('[data-bk-sum-err]').prop('hidden', false);
            $li.attr('data-state', 'error');
        } finally {
            $li.find('[data-bk-sum-load]').prop('hidden', true);
            $btn.prop('disabled', false);
        }
    });
}

$('[data-bk-reader]').each(function () { initReader(this); });
$('[data-bk-intro]').each(function () { initIntro(this); });
$('[data-bk-review]').each(function () { initReview(this); });
