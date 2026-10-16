import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';
import { digits } from './digits';

/**
 * لغات و عبارت‌های هایلایت‌شده (صفحه‌ی /words)
 *
 * - لیست در مرورگر فیلتر، جست‌وجو و مرتب می‌شود (بدون رفرش)؛ تب‌های سطح شمارنده دارند.
 * - بوکمارک روی کارت = حذف از لیست؛ بلافاصله حذف می‌شود و یک پیام «بازگردانی» (۶ ثانیه) می‌آید.
 * - «اطلاعات بیشتر» یک offcanvas باز می‌کند (قبلی/بعدی، کلید ←/→، کشیدن انگشت). فقط ترجمه‌های فارسی تارند و با کلیک نمایش داده می‌شوند.
 * - «شروع مرور»: ترجمه‌ها تارند تا کاربر اول خودش یادآوری کند، بعد «نمایش جواب» و «یاد گرفتم / هنوز نه».
 *   دکمه‌های قبلی/بعدی هم هست و جواب هر لغت جدا نگه داشته و قابل‌تغییر است.
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 *   لیست: app/Support/SavedWords.php (data-words).
 *   $(document).on('word:bookmark', (e, { id, key, text, saved, song }) => {...})   ← حذف (saved=false) / بازگردانی (saved=true)
 *   $(document).on('words:review',  (e, { id, key, known }) => {...})              ← جواب هر لغت در مرور (known=true → status=learned، وگرنه learning)
 *   $(document).on('words:review-done', (e, { total, known }) => {...})
 *   تلفظ: audio.uk / audio.us اگر باشد پخش می‌شود، وگرنه صدای خود مرورگر.
 */
const LOCAL = true; // دمو: حذف‌ها و نتیجه‌ی مرور در مرورگر می‌ماند و لغت‌های ذخیره‌شده‌ی مرحله‌ی ۳ هم اضافه می‌شوند. با اتصال بک‌اند false کنید.
const SAVED_KEY = 'teodel:words:saved'; // همان کلیدِ words.js (مرحله‌ی ۳)
const REMOVED_KEY = 'teodel:words:removed';
const REVIEW_KEY = 'teodel:words:review';
const PAGE = 12;
const UNDO_MS = 6000;

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
        try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* ignore */ }
    },
};

const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1'];
const fill = (tpl, map) => Object.keys(map).reduce((s, k) => s.replace(':' + k, map[k]), tpl);
const rtl = () => document.documentElement.dir === 'rtl';

/** تلفظ: فایل صوتی، وگرنه صدای مرورگر */
let audio = null;
function speak(item, acc) {
    const url = item.audio && (item.audio[acc] || item.audio.uk || item.audio.us);
    if (audio) audio.pause();
    if (url) {
        audio = new Audio(url);
        audio.play().catch(() => {});
        return;
    }
    if ('speechSynthesis' in window) {
        speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(item.phrase);
        u.lang = acc === 'us' ? 'en-US' : 'en-GB';
        u.rate = 0.85;
        speechSynthesis.speak(u);
    }
}

function init(root) {
    const $root = $(root);
    const $q = $root.find('[data-sw-q]');
    const $filter = $root.find('[data-sw-filter]');
    const $sort = $root.find('[data-sw-sort]');
    const $tabs = $root.find('[data-sw-tabs] .sw-tab');
    const $grid = $root.find('[data-sw-grid]');
    const $dlg = $('#sw-dialog');
    const tplCard = $root.find('[data-sw-card-tpl]')[0];
    const T = (n) => $root.attr('data-t-' + n) || '';
    const songUrl = $root.attr('data-song-url') || '#';

    // ---------- داده ----------
    let all = [];
    try { all = JSON.parse($root.attr('data-words') || '[]'); } catch (e) { all = []; }

    if (LOCAL) {
        const have = new Set(all.map((w) => w.key));
        const removed = new Set(store.get(REMOVED_KEY, []).map(String));
        all = all.filter((w) => !removed.has(String(w.id)));

        // لغت‌هایی که کاربر در مرحله‌ی ۳ ذخیره کرده (اگر اطلاعاتشان ذخیره شده باشد)
        const local = store.get(SAVED_KEY, {});
        Object.keys(local).forEach((k) => {
            const v = local[k];
            const info = v && v.info;
            if (!info || have.has(k)) return;
            all.push({
                id: 'l:' + k, key: k, local: true, phrase: info.phrase || v.text || k, type: info.type || '',
                level: LEVELS.includes(String(info.level || '').toLowerCase()) ? String(info.level).toLowerCase() : '',
                ipa: info.ipa || { uk: '', us: '' }, audio: info.audio || { uk: '', us: '' },
                meaning: info.meaning || '', example: info.example || '', example_tr: info.example_tr || '',
                translate: info.translate || '', synonyms: info.synonyms || [], antonyms: info.antonyms || [],
                image: null, song: v.song ? { id: v.song, title: '' } : null, saved_at: v.at || 0, status: 'new',
            });
        });

        const review = store.get(REVIEW_KEY, {});
        all.forEach((w) => { if (review[w.id]) w.status = review[w.id]; });
    }

    // ---------- وضعیت لیست ----------
    const st = { q: '', filter: 'all', sort: 'newest', level: 'all', shown: PAGE };
    const norm = (s) => String(s).toLowerCase();

    function view() {
        const q = norm(st.q.trim());
        let list = all.filter((w) => {
            if (st.level !== 'all' && w.level !== st.level) return false;
            if (st.filter !== 'all' && w.status !== st.filter) return false;
            if (q && !norm([w.phrase, w.translate, w.meaning].join(' ')).includes(q)) return false;
            return true;
        });
        const cmp = {
            newest: (a, b) => b.saved_at - a.saved_at,
            oldest: (a, b) => a.saved_at - b.saved_at,
            az: (a, b) => a.phrase.localeCompare(b.phrase, 'en', { sensitivity: 'base' }),
            level: (a, b) => (LEVELS.indexOf(a.level) + 1 || 9) - (LEVELS.indexOf(b.level) + 1 || 9) || a.phrase.localeCompare(b.phrase),
        }[st.sort];
        list = list.slice().sort(cmp);
        return list;
    }

    // ---------- کارت‌ها ----------
    const hueOf = (s) => [...String(s)].reduce((n, c) => (n * 31 + c.charCodeAt(0)) % 360, 7);

    function thumb(el, w) {
        el.textContent = '';
        if (w.image) {
            const img = new Image();
            img.src = w.image;
            img.alt = '';
            img.loading = 'lazy';
            img.className = 'size-full object-cover';
            el.appendChild(img);
            el.style.background = '';
        } else {
            const h = hueOf(w.phrase);
            el.style.background = 'linear-gradient(135deg, hsl(' + h + ' 70% 56%), hsl(' + ((h + 50) % 360) + ' 65% 38%))';
            const s = document.createElement('span');
            s.textContent = w.phrase.charAt(0).toUpperCase();
            s.className = 'sw-thumb__ch';
            el.appendChild(s);
        }
    }

    const statusText = (w) => (w.status === 'new' ? '' : T('status-' + w.status));

    function cardFor(w) {
        const node = tplCard.content.firstElementChild.cloneNode(true);
        node.setAttribute('data-id', w.id);
        node.setAttribute('data-status', w.status);
        const set = (k, v) => { const e = node.querySelector('[data-c="' + k + '"]'); if (e) e.textContent = v; };
        set('phrase', w.phrase);
        set('type', w.type ? '(' + w.type + ')' : '');
        set('ipa', w.ipa.uk || w.ipa.us || '');
        set('meaning', w.meaning);
        const badge = node.querySelector('[data-c="status"]');
        badge.textContent = statusText(w);
        badge.hidden = !badge.textContent;
        if (w.level) {
            const lv = document.createElement('span');
            lv.className = 'sw-level';
            lv.textContent = w.level.toUpperCase();
            badge.before(lv);
        }
        node.querySelector('[data-c-speak]').hidden = false;
        thumb(node.querySelector('.sw-thumb'), w);
        return node;
    }

    function render() {
        const list = view();
        const frag = document.createDocumentFragment();
        list.slice(0, st.shown).forEach((w) => frag.appendChild(cardFor(w)));
        $grid.empty().append(frag);

        // تب‌ها: شمارنده‌ی هر سطح (مستقل از جست‌وجو و فیلتر)
        $tabs.each(function () {
            const lv = this.getAttribute('data-level');
            const n = lv === 'all' ? all.length : all.filter((w) => w.level === lv).length;
            $(this).find('[data-sw-tab-n]').text(digits(n));
            this.classList.toggle('is-empty', n === 0 && lv !== 'all');
            if (lv === st.level) this.setAttribute('aria-current', 'page');
            else this.removeAttribute('aria-current');
        });

        const learned = all.filter((w) => w.status === 'learned').length;
        $root.find('[data-sw-summary]').text(all.length ? fill(T('count'), { n: digits(list.length) }) + (learned ? '  •  ' + digits(learned) + ' ✓' : '') : '');

        const none = !all.length;
        $root.find('[data-sw-empty]').prop('hidden', !none);
        $root.find('[data-sw-noresult]').prop('hidden', none || list.length > 0);
        $root.find('[data-sw-toolbar], [data-sw-summary]').prop('hidden', none);
        $root.find('[data-sw-more-wrap]').prop('hidden', list.length <= st.shown);
        $root.find('[data-sw-cta]').prop('hidden', !list.length);
        $root.find('[data-sw-review-label]').text(T('start') + ' (' + digits(list.length) + ')');
    }

    function rerender() { st.shown = PAGE; render(); }

    // ---------- جست‌وجو / فیلتر / مرتب‌سازی ----------
    let qTimer = 0;
    $q.on('input', function () {
        clearTimeout(qTimer);
        qTimer = setTimeout(() => { st.q = $q.val(); rerender(); }, 120);
    });
    $filter.on('change', function () { st.filter = this.value; rerender(); });
    $sort.on('change', function () { st.sort = this.value; rerender(); });
    $tabs.on('click', function () { st.level = this.getAttribute('data-level'); rerender(); });
    $root.on('click', '[data-sw-more]', () => { st.shown += PAGE; render(); });
    $root.on('click', '[data-sw-reset]', () => {
        st.q = ''; st.filter = 'all'; st.level = 'all';
        $q.val(''); $filter.val('all');
        rerender();
    });

    // ---------- حذف + بازگردانی ----------
    const $toast = $root.find('[data-sw-toast]');
    const undoStack = [];
    let toastTimer = 0;

    function showToast(text) {
        $toast.find('[data-sw-toast-text]').text(text);
        $toast.prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(hideToast, UNDO_MS);
    }
    function hideToast() {
        $toast.removeClass('is-in').prop('hidden', true);
        undoStack.length = 0;
    }

    function persistRemoved(w, removed) {
        if (!LOCAL) return;
        if (w.local) {
            const m = store.get(SAVED_KEY, {});
            if (removed) delete m[w.key];
            else m[w.key] = { key: w.key, text: w.phrase, song: w.song ? w.song.id : 0, line: 0, at: w.saved_at, info: w };
            store.set(SAVED_KEY, m);
            return;
        }
        const ids = store.get(REMOVED_KEY, []).map(String).filter((x) => x !== String(w.id));
        if (removed) ids.push(String(w.id));
        store.set(REMOVED_KEY, ids);
    }

    function removeWord(w) {
        const idx = all.indexOf(w);
        if (idx < 0) return;
        all.splice(idx, 1);
        undoStack.push({ w, idx });
        persistRemoved(w, true);
        $(document).trigger('word:bookmark', [{ id: w.id, key: w.key, text: w.phrase, saved: false, song: w.song ? w.song.id : null }]);
        showToast(fill(T('removed'), { w: w.phrase }));
    }

    $toast.on('click', '[data-sw-undo]', () => {
        const last = undoStack.pop();
        if (!last) return;
        all.splice(Math.min(last.idx, all.length), 0, last.w);
        persistRemoved(last.w, false);
        $(document).trigger('word:bookmark', [{ id: last.w.id, key: last.w.key, text: last.w.phrase, saved: true, song: last.w.song ? last.w.song.id : null }]);
        if (undoStack.length) showToast(fill(T('removed'), { w: undoStack[undoStack.length - 1].w.phrase }));
        else hideToast();
        render();
        if ($dlg.hasClass('is-open') && d.mode === 'view') refreshListInDialog();
    });

    const byId = (id) => all.find((w) => String(w.id) === String(id));
    const idOf = (el) => $(el).closest('[data-sw-card]').attr('data-id');

    $grid.on('click', '[data-c-remove]', function () {
        const w = byId(idOf(this));
        if (w) { removeWord(w); render(); }
    });
    $grid.on('click', '[data-c-speak]', function () {
        const w = byId(idOf(this));
        if (w) speak(w, 'uk');
    });
    $grid.on('click', '[data-c-open]', function () {
        const w = byId(idOf(this));
        if (w) openView(w);
    });

    // ---------- دیالوگ ----------
    const d = { mode: 'view', list: [], i: 0, total: 0, answers: {}, done: false, unknown: [] };
    const $b = $dlg.find('.sw-b');
    const cur = () => d.list[d.i];

    function setBlur(on) { $b.attr('aria-pressed', on ? 'false' : 'true'); }

    $dlg.on('click', '.sw-b', function () {
        const on = this.getAttribute('aria-pressed') !== 'true';
        this.setAttribute('aria-pressed', on ? 'true' : 'false');
    });

    function setList(el, arr) {
        const $e = $dlg.find('[data-d="' + el + '"]');
        $e.text((arr || []).join(', '));
        $dlg.find('[data-d-row="' + el + '"]').prop('hidden', !(arr && arr.length));
    }

    function showWord(w) {
        const $d = (k) => $dlg.find('[data-d="' + k + '"]');
        $d('phrase').text(w.phrase);
        $d('type').text(w.type ? '(' + w.type + ')' : '');
        $d('level').text(w.level.toUpperCase()).prop('hidden', !w.level);
        ['uk', 'us'].forEach((a) => {
            const $r = $dlg.find('[data-d-pron="' + a + '"]');
            $r.prop('hidden', !w.ipa[a]).find('[data-d-ipa]').text(w.ipa[a]);
        });
        $d('translate').text(w.translate);
        $dlg.find('[data-d-tr-wrap]').prop('hidden', !w.translate);
        $d('meaning').text(w.meaning);
        $dlg.find('[data-b="meaning"]').prop('hidden', !w.meaning);
        $d('example').text(w.example);
        $d('example_tr').text(w.example_tr);
        $dlg.find('[data-d-extr-wrap]').prop('hidden', !w.example_tr);
        $dlg.find('[data-d-ex-wrap]').prop('hidden', !w.example);
        setList('synonyms', w.synonyms);
        setList('antonyms', w.antonyms);
        thumb($dlg.find('[data-d-img]')[0], w);

        const $s = $dlg.find('[data-d-song]');
        const hasSong = !!(w.song && w.song.id);
        $s.prop('hidden', !hasSong);
        if (hasSong) {
            $s.attr('href', songUrl + '/' + w.song.id + '/step/3');
            $s.find('[data-d="song"]').text(w.song.title || '#' + w.song.id);
        }
        setBlur(true); // ترجمه‌ها همیشه تار شروع می‌شوند
        $dlg.find('[data-sw-scroll]')[0].scrollTop = 0;
        const body = $dlg.find('[data-sw-body]')[0];
        body.classList.remove('is-swap');
        void body.offsetWidth;
        body.classList.add('is-swap');
    }

    function foot(name) {
        $dlg.find('[data-foot]').each(function () { this.hidden = this.getAttribute('data-foot') !== name; });
    }

    function paintView() {
        const w = cur();
        if (!w) { closeDialog($dlg); return; }
        showWord(w);
        $dlg.find('[data-d-pos]').text(fill($root.attr('data-t-position'), { n: digits(d.i + 1), total: digits(d.list.length) }));
        $dlg.find('[data-d-prev]').prop('disabled', d.i === 0);
        $dlg.find('[data-d-next]').prop('disabled', d.i >= d.list.length - 1);
    }

    function openView(w) {
        d.mode = 'view';
        d.done = false;
        d.list = view();
        d.i = Math.max(d.list.indexOf(w), 0);
        $dlg.attr('data-mode', 'view');
        $dlg.find('[data-sw-body]').prop('hidden', false);
        $dlg.find('[data-sw-done]').prop('hidden', true);
        foot('view');
        paintView();
        openDialog('#sw-dialog');
    }

    function refreshListInDialog() {
        const w = cur();
        d.list = view();
        d.i = Math.max(d.list.indexOf(w), 0);
        paintView();
    }

    function go(step) {
        const n = d.i + step;
        if (n < 0 || n >= d.list.length) return;
        d.i = n;
        paintView();
    }
    // قبلی/بعدی: در دیدن همان لیست، در مرور بدون ثبت جواب هم می‌شود جابه‌جا شد
    function move(step) {
        if (d.mode === 'review') goReview(step);
        else go(step);
    }
    $dlg.on('click', '[data-d-prev]', () => go(-1));
    $dlg.on('click', '[data-d-next]', () => go(1));
    $dlg.on('click', '[data-d-speak]', function () {
        const w = cur();
        if (w) speak(w, this.getAttribute('data-d-speak'));
    });
    $dlg.on('click', '[data-d-remove]', () => {
        const w = cur();
        if (!w) return;
        removeWord(w);
        d.list.splice(d.i, 1);
        if (d.i >= d.list.length) d.i = d.list.length - 1;
        render();
        if (!d.list.length) closeDialog($dlg);
        else paintView();
    });

    // کلید ←/→ (جهتِ فلش‌ها در راست‌به‌چپ برعکس است) و کشیدن انگشت؛ در هر دو حالتِ دیدن و مرور
    $(document).on('keydown', (e) => {
        if (!$dlg.hasClass('is-open') || d.done || e.target.matches('input,textarea,select')) return;
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        // «قبلی» سمتِ شروع است: در راست‌به‌چپ سمت راست، در چپ‌به‌راست سمت چپ
        const toStart = rtl() ? e.key === 'ArrowRight' : e.key === 'ArrowLeft';
        move(toStart ? -1 : 1);
    });
    let tx = 0;
    let ty = 0;
    $dlg.find('[data-sw-panel]').on('touchstart', (e) => {
        tx = e.originalEvent.touches[0].clientX;
        ty = e.originalEvent.touches[0].clientY;
    }).on('touchend', (e) => {
        if (d.done) return;
        const t = e.originalEvent.changedTouches[0];
        const dx = t.clientX - tx;
        if (Math.abs(dx) > 70 && Math.abs(t.clientY - ty) < 40) {
            const next = rtl() ? dx > 0 : dx < 0; // مثل ورق‌زدن کتاب: در راست‌به‌چپ «بعدی» سمت چپ است
            move(next ? 1 : -1);
        }
    });

    // ---------- مرور ----------
    // d.answers: کلیدِ هر لغت ← true (بلد بودم) | false (هنوز نه). با «قبلی» می‌شود جواب را عوض کرد.
    const keyOf = (w) => String(w.id);
    const counts = () => {
        let known = 0;
        let answered = 0;
        d.list.forEach((w) => {
            const a = d.answers[keyOf(w)];
            if (a === undefined) return;
            answered++;
            if (a) known++;
        });
        return { known, answered };
    };

    function startReview(list) {
        d.mode = 'review';
        d.list = list;
        d.i = 0;
        d.total = list.length;
        d.answers = {};
        d.done = false;
        $dlg.attr('data-mode', 'review');
        $dlg.find('[data-sw-body]').prop('hidden', false);
        $dlg.find('[data-sw-done]').prop('hidden', true);
        paintReview();
        openDialog('#sw-dialog');
    }

    function paintReview() {
        const w = cur();
        showWord(w); // ترجمه‌ها تار
        const a = d.answers[keyOf(w)];
        const answered = a !== undefined;
        if (answered) setBlur(false); // لغتِ جواب‌داده: ترجمه باز
        $dlg.find('[data-r-reveal-wrap]').prop('hidden', answered);
        $dlg.find('[data-r-answer-wrap]').prop('hidden', !answered);
        $dlg.find('[data-r-answer]').each(function () {
            const yes = this.getAttribute('data-r-answer') === 'yes';
            if (answered && yes === a) this.setAttribute('data-picked', '');
            else this.removeAttribute('data-picked');
        });
        $dlg.find('[data-sw-rbar]').css('width', (counts().answered / d.total) * 100 + '%');
        $dlg.find('[data-sw-rcount]').text(fill(T('review-count'), { n: digits(d.i + 1), total: digits(d.total) }));
        $dlg.find('[data-r-prev]').prop('disabled', d.i === 0);
        $dlg.find('[data-r-next]').prop('disabled', d.i >= d.list.length - 1);
        foot('review');
    }

    function goReview(step) {
        const n = d.i + step;
        if (n < 0 || n >= d.list.length) return;
        d.i = n;
        paintReview();
    }
    $dlg.on('click', '[data-r-prev]', () => goReview(-1));
    $dlg.on('click', '[data-r-next]', () => goReview(1));

    $root.on('click', '[data-sw-review]', () => {
        const list = view();
        if (list.length) startReview(list);
    });
    $dlg.on('click', '[data-r-reveal]', () => {
        setBlur(false);
        $dlg.find('[data-r-reveal-wrap]').prop('hidden', true);
        $dlg.find('[data-r-answer-wrap]').prop('hidden', false);
    });
    $dlg.on('click', '[data-r-answer]', function () {
        const w = cur();
        const known = this.getAttribute('data-r-answer') === 'yes';
        d.answers[keyOf(w)] = known;
        w.status = known ? 'learned' : 'learning';
        if (LOCAL) {
            const r = store.get(REVIEW_KEY, {});
            r[w.id] = w.status;
            store.set(REVIEW_KEY, r);
        }
        $(document).trigger('words:review', [{ id: w.id, key: w.key, known }]);

        // برو به اولین لغتِ بی‌جواب بعدی (دورانی)؛ اگر همه جواب داده شد ← پایان
        for (let k = 1; k <= d.list.length; k++) {
            const idx = (d.i + k) % d.list.length;
            if (d.answers[keyOf(d.list[idx])] === undefined) {
                d.i = idx;
                paintReview();
                return;
            }
        }
        finishReview();
    });

    function finishReview() {
        const { known } = counts();
        const unknown = d.list.filter((w) => d.answers[keyOf(w)] === false);
        d.done = true;
        d.unknown = unknown;
        $dlg.find('[data-sw-body]').prop('hidden', true);
        $dlg.find('[data-sw-done]').prop('hidden', false);
        $dlg.find('[data-sw-rbar]').css('width', '100%');
        $dlg.find('[data-sw-done-text]').text(fill(T('done-text'), { known: digits(known), total: digits(d.total) }));
        const $again = $dlg.find('[data-r-again]');
        $again.prop('hidden', !unknown.length).text(unknown.length ? fill($root.attr('data-t-again') || '', { n: digits(unknown.length) }) : '');
        $dlg.find('[data-sw-scroll]')[0].scrollTop = 0;
        foot('done');
        $(document).trigger('words:review-done', [{ total: d.total, known }]);
    }
    $dlg.on('click', '[data-r-again]', () => startReview(d.unknown.slice()));

    $dlg.on('dialog:close', () => {
        if (audio) audio.pause();
        if ('speechSynthesis' in window) speechSynthesis.cancel();
        render();
    });

    render();
}

$(function () {
    $('[data-sw]').each(function () { init(this); });
});
