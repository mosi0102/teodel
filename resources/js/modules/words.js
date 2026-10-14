import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';
import { digits } from './digits';

/**
 * مرحله‌ی ۳ (words): لغت‌های دشوار داخل لیریک
 *
 * - لغت‌های هایلایت‌شده (انتخاب پشتیبان) data-w-key دارند؛ کلمه‌های دیگر data-w-word (کاربر می‌تواند خودش انتخاب کند).
 * - کلیک روی هر کدام: کارت توضیحات پر می‌شود (دسکتاپ: کنار لیریک | موبایل: Bottom Sheet).
 * - ترجمه تار است و با کلیک نمایش داده می‌شود (با هر لغت‌ِ تازه دوباره تار می‌شود).
 * - بوکمارک: ذخیره / حذف. تا وصل‌شدن بک‌اند در مرورگر هم نگه داشته می‌شود (LOCAL_SAVE).
 * - مرحله وقتی تمام می‌شود که همه‌ی لغت‌های پشتیبان دیده شده باشند.
 *
 * 🔌 بک‌اند:
 *   $(document).on('word:bookmark', (e, { song, key, text, saved, line }) => {...})   ← ذخیره / حذف بوکمارک
 *   Teodel.words.lookup = async (word, { song, line }) => ({ phrase, type, level, ipa, audio, meaning, synonyms, antonyms, translate })
 *       ← اطلاعات «کلمه‌ی دلخواه کاربر». پیش‌فرض: اگر data-lookup-url روی ریشه باشد، GET ?word=... ؛ وگرنه null (پیام «اطلاعاتی نیست»).
 */
const LOCAL_SAVE = true; // وقتی بک‌اند بوکمارک را نگه می‌دارد، false کنید (لیست اولیه از data-saved می‌آید)
const SAVED_KEY = 'teodel:words:saved';
const viewedKey = (song) => 'teodel:words:viewed:' + song;

const store = {
    get(k, fallback) {
        try {
            const v = JSON.parse(localStorage.getItem(k));
            return v == null ? fallback : v;
        } catch (e) {
            return fallback;
        }
    },
    set(k, v) {
        try {
            localStorage.setItem(k, JSON.stringify(v));
        } catch (e) { /* localStorage در دسترس نیست */ }
    },
};

const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const normKey = (s) => String(s).toLowerCase().replace(/’/g, "'").replace(/\s+/g, ' ').trim();
const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

window.Teodel = window.Teodel || {};

function init(root) {
    const $root = $(root);
    const song = Number($root.attr('data-song'));
    const $panel = $root.closest('[data-step-panel]');
    const words = JSON.parse($root.attr('data-words') || '{}'); // key → اطلاعات
    const total = Object.keys(words).length;
    const dictUrl = $root.attr('data-dict-url') || '#';

    // کارت را از قالب، هم داخل ستون دسکتاپ و هم داخل Bottom Sheet بساز
    const tpl = $root.find('[data-word-tpl]')[0];
    const $slots = $root.find('[data-word-slot]').add('#word-dialog [data-word-slot]');
    $slots.each(function () { this.appendChild(tpl.content.cloneNode(true)); });
    const $cards = $('[data-word-card]');

    // ترتیب لغت‌های پشتیبان (به‌ترتیب اولین حضور در لیریک)
    const order = [];
    $root.find('[data-w-key]').each(function () {
        const k = this.getAttribute('data-w-key');
        if (!order.includes(k)) order.push(k);
    });

    const saved = new Set(($root.attr('data-saved') ? JSON.parse($root.attr('data-saved')) : []).map(normKey));
    if (LOCAL_SAVE) Object.keys(store.get(SAVED_KEY, {})).forEach((k) => saved.add(k));
    const viewed = new Set(store.get(viewedKey(song), []).filter((k) => words[k]));

    let cur = null; // { key, text, el, line, data, free }
    let revealed = false;
    let seq = 0;
    let done = false;

    const $lyricsEls = () => $root.find('.wd');
    const keyOf = (el) => (el.hasAttribute('data-w-key') ? el.getAttribute('data-w-key') : normKey(el.textContent));

    // ---------- نمایش ----------
    function markSaved() {
        $lyricsEls().each(function () { this.classList.toggle('is-saved', saved.has(keyOf(this))); });
        $('[data-w-saved]').text(digits(saved.size));
        $('[data-w-viewed]').text(digits(viewed.size));
    }

    function contextHTML(el) {
        const line = el.closest('.wline');
        if (!line) return '';
        let out = '';
        line.childNodes.forEach((n) => {
            if (n.nodeType === 3) out += esc(n.textContent);
            else if (n.nodeType === 1) out += '<span class="' + (n === el ? 'ctx-hit' : '') + '">' + esc(n.textContent) + '</span>';
        });
        return out.trim();
    }

    const list = (arr) => (arr && arr.length ? arr.map(esc).join('<span class="text-slate-300">, </span>') : '');

    function setRow($c, name, html) {
        const $row = $c.find('[data-wc-row="' + name + '"]');
        $row.prop('hidden', !html);
        return $row;
    }

    function render() {
        $cards.each(function () {
            const $c = $(this);

            if (!cur) {
                $c.attr('data-state', 'empty');
                $c.find('[data-wc-nav]').prop('hidden', !total);
                return;
            }

            const state = cur.loading ? 'loading' : cur.data ? 'ready' : 'nodata';
            $c.attr('data-state', state);
            const d = cur.data || {};
            const phrase = d.phrase || cur.text;

            $c.find('[data-wc-phrase]').text(phrase);
            $c.find('[data-wc-type]').text(d.type || '');
            $c.find('[data-wc-type-wrap]').prop('hidden', !d.type);

            ['uk', 'us'].forEach((acc) => {
                const ipa = d.ipa && d.ipa[acc];
                const audio = d.audio && d.audio[acc];
                const $p = $c.find('[data-wc-pron="' + acc + '"]');
                $p.prop('hidden', !ipa && !audio);
                $p.find('[data-wc-ipa]').text(ipa || '');
            });

            const ctx = cur.el ? contextHTML(cur.el) : '';
            $c.find('[data-wc-ctx]').html(ctx);
            $c.find('[data-wc-ctx-wrap]').prop('hidden', !ctx);

            $c.find('[data-wc-meaning]').text(d.meaning || '').prop('hidden', !d.meaning);
            $c.find('[data-wc-nodata]').prop('hidden', state !== 'nodata');
            $c.find('[data-wc-dict]').attr('href', dictUrl + (dictUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(cur.text));
            $c.find('[data-wc-table]').prop('hidden', state !== 'ready');

            setRow($c, 'level', d.level).find('[data-wc-level]').text(d.level || '');
            setRow($c, 'synonyms', list(d.synonyms)).find('[data-wc-synonyms]').html(list(d.synonyms));
            setRow($c, 'antonyms', list(d.antonyms)).find('[data-wc-antonyms]').html(list(d.antonyms));
            setRow($c, 'translate', d.translate).find('[data-wc-translate]').text(d.translate || '');

            // ترجمه‌ی تار
            const $tr = $c.find('[data-wc-tr]');
            $tr.attr('aria-pressed', revealed ? 'true' : 'false');
            const label = $tr.attr(revealed ? 'data-label-hide' : 'data-label-show');
            $tr.attr('aria-label', label);
            $tr.find('[data-wc-tr-label]').text(label);

            // بوکمارک
            const isSaved = saved.has(cur.key);
            const $bm = $c.find('[data-wc-bookmark]');
            $bm.attr('aria-pressed', isSaved ? 'true' : 'false');
            $bm.attr('aria-label', $bm.attr(isSaved ? 'data-label-remove' : 'data-label-add'));

            // قبلی / بعدی و شماره
            const i = order.indexOf(cur.key);
            $c.find('[data-wc-pos]').text(i >= 0 ? digits(i + 1) : '—');
            $c.find('[data-wc-prev]').prop('disabled', !neighbor(-1));
            $c.find('[data-wc-next]').prop('disabled', !neighbor(1));
        });
    }

    // لغت پشتیبان قبلی/بعدی (اگر کاربر روی کلمه‌ی دلخواه است: نزدیک‌ترین لغت پشتیبان قبل/بعد از آن در لیریک)
    function neighbor(dir) {
        if (!order.length || !cur) return order.length ? order[dir > 0 ? 0 : order.length - 1] : null;
        const i = order.indexOf(cur.key);
        if (i >= 0) return order[i + dir] || null;
        let best = null;
        order.forEach((k) => {
            const el = $root.find('[data-w-key="' + k + '"]')[0];
            const pos = cur.el.compareDocumentPosition(el);
            if (dir > 0 && pos & Node.DOCUMENT_POSITION_FOLLOWING && !best) best = k;
            if (dir < 0 && pos & Node.DOCUMENT_POSITION_PRECEDING) best = k;
        });
        return best;
    }

    // ---------- پیشرفت مرحله ----------
    function progress() {
        if ($panel.prop('hidden') || !window.Teodel.lesson) return;
        const f = total ? viewed.size / total : 1;
        window.Teodel.lesson.setStepProgress(f * 0.95);
        if (f >= 1 && !done) {
            done = true;
            window.Teodel.lesson.completeStep();
        }
    }

    // ---------- انتخاب ----------
    async function select(el) {
        const key = keyOf(el);
        const text = el.textContent.trim();
        const line = Number(el.closest('.wline') ? el.closest('.wline').getAttribute('data-w-line') : 0);
        const mine = ++seq;

        $lyricsEls().removeClass('is-active');
        el.classList.add('is-active');
        revealed = false;

        if (words[key]) {
            cur = { key, text, el, line, data: words[key], free: false };
            if (!viewed.has(key)) {
                viewed.add(key);
                store.set(viewedKey(song), [...viewed]);
                markSaved();
            }
        } else {
            cur = { key, text, el, line, data: null, free: true, loading: true };
            render();
            let data = null;
            try {
                const fn = (window.Teodel.words && window.Teodel.words.lookup) || null;
                data = fn ? await fn(text, { song, line }) : null;
            } catch (err) {
                data = null;
            }
            if (mine !== seq) return; // کاربر کلمه‌ی دیگری زد
            cur.loading = false;
            cur.data = data && (data.meaning || data.translate || data.synonyms) ? data : null;
        }

        render();
        progress();
        if (!isDesktop()) openDialog('#word-dialog');
    }

    function goTo(key) {
        const el = $root.find('[data-w-key="' + key + '"]')[0];
        if (!el) return;
        select(el);
        el.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }

    // ---------- رویدادها ----------
    $root.on('click', '.wd', function () { select(this); });
    $root.on('keydown', '.wd--support', function (e) { if (e.key === 'Escape') this.blur(); });

    $(document).on('click', '[data-word-card] [data-wc-tr]', () => {
        revealed = !revealed;
        render();
    });

    $(document).on('click', '[data-word-card] [data-wc-bookmark]', () => {
        if (!cur) return;
        const on = !saved.has(cur.key);
        if (on) saved.add(cur.key);
        else saved.delete(cur.key);

        if (LOCAL_SAVE) {
            const all = store.get(SAVED_KEY, {});
            if (on) all[cur.key] = { key: cur.key, text: cur.text, song, line: cur.line };
            else delete all[cur.key];
            store.set(SAVED_KEY, all);
        }
        markSaved();
        render();
        $(document).trigger('word:bookmark', [{ song, key: cur.key, text: cur.text, saved: on, line: cur.line }]);
    });

    $(document).on('click', '[data-word-card] [data-wc-next]', () => { const k = neighbor(1); if (k) goTo(k); });
    $(document).on('click', '[data-word-card] [data-wc-prev]', () => { const k = neighbor(-1); if (k) goTo(k); });
    $(document).on('click', '[data-word-card] [data-wc-first]', () => {
        const k = order.find((x) => !viewed.has(x)) || order[0];
        if (k) goTo(k);
    });

    // تلفظ: فایل صوتی پشتیبان، وگرنه صدای خود مرورگر
    $(document).on('click', '[data-word-card] [data-wc-speak]', function () {
        if (!cur) return;
        const acc = this.getAttribute('data-wc-speak');
        const url = cur.data && cur.data.audio && cur.data.audio[acc];
        if (url) {
            new Audio(url).play().catch(() => {});
        } else if ('speechSynthesis' in window) {
            speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance((cur.data && cur.data.phrase) || cur.text);
            u.lang = acc === 'uk' ? 'en-GB' : 'en-US';
            u.rate = 0.85;
            speechSynthesis.speak(u);
        }
    });

    // با رفتن به مرحله‌ی دیگر: بستن شیت و قطع صدا
    $(document).on('lesson:step-change', () => {
        if ($panel.prop('hidden')) {
            closeDialog($('#word-dialog'));
            if ('speechSynthesis' in window) speechSynthesis.cancel();
        } else {
            progress();
        }
    });

    // کلمه‌ی دلخواه: پیش‌فرض → data-lookup-url (اگر داده شده)
    window.Teodel.words = window.Teodel.words || {};
    if (!window.Teodel.words.lookup) {
        window.Teodel.words.lookup = async (word) => {
            const url = $root.attr('data-lookup-url');
            if (!url) return null;
            const res = await fetch(url + (url.includes('?') ? '&' : '?') + 'word=' + encodeURIComponent(word), { credentials: 'same-origin' });
            return res.ok ? res.json() : null;
        };
    }

    markSaved();
    render();
    if (!total) progress();
}

$(function () {
    $('[data-words-step]').each(function () { init(this); });
});
