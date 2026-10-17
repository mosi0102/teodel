import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';
import { digits } from './digits';

/**
 * دیکشنری (/dictionary): الفبایی، بدون سطح‌بندی، لیست جمع‌وجور برای تعداد زیاد.
 *
 * - جست‌وجو (لغت، ترجمه، تعریف)، نوار الفبا (حروفِ بدون لغت غیرفعال)، فیلتر نوع (اسم/فعل/صفت/قید/عبارت)، «ذخیره‌شده‌ها» و مرتب‌سازی A–Z / Z–A.
 * - ترجمه‌ی فارسی هر ردیف تار است و با کلیک باز می‌شود؛ دکمه‌ی «نمایش ترجمه‌ها» همه را یکجا باز می‌کند (انتخاب در مرورگر می‌ماند).
 * - کلیک روی ردیف ← offcanvas جزئیات (تلفظ UK/US، ترجمه و ترجمه‌ی مثال تار، مترادف/متضاد، قبلی/بعدی، کلید ←/→، کشیدن انگشت).
 * - بوکمارک = ذخیره در «لغات و عبارت‌های هایلایت‌شده» (همان صفحه‌ی /words).
 * - بارگذاری: ۶۰تا۶۰تا با اسکرول (IntersectionObserver) و دکمه‌ی «نمایش بیشتر».
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 *   دو حالت (جزئیات: app/Support/Dictionary.php):
 *     ۱) data-words = کل لیست ← فیلتر در مرورگر.
 *     ۲) data-endpoint ← GET ?q=&letter=&type=&saved=&sort=az|za&page=&per= → { data: [...], total, has_more }.
 *   $(document).on('word:bookmark', (e, { id, key, text, saved, song, source }) => {...})   ← ذخیره (saved=true) / حذف (saved=false)؛ source='dictionary'
 *   تلفظ: audio.uk / audio.us اگر باشد پخش می‌شود، وگرنه صدای خود مرورگر.
 */
const LOCAL = true; // دمو: ذخیره‌ها در localStorage (همان کلید مرحله‌ی ۳ و /words) و انتخاب «نمایش ترجمه‌ها». با اتصال بک‌اند false کنید.
const SAVED_KEY = 'teodel:words:saved';
const REVEAL_KEY = 'teodel:dict:reveal';
const PAGE = 60;
const TOAST_MS = 3200;

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

const fill = (tpl, map) => Object.keys(map).reduce((s, k) => s.replace(':' + k, map[k]), tpl);
const rtl = () => document.documentElement.dir === 'rtl';
// ی/ک عربی و فارسی یکی حساب شوند تا جست‌وجوی فارسی دقیق باشد
const norm = (s) => String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[ً-ٟ‌]/g, '');
const typeGroup = (t) => (['noun', 'verb', 'adjective', 'adverb'].includes(String(t).toLowerCase()) ? String(t).toLowerCase() : 'other');
const firstLetter = (p) => {
    const c = String(p).trim().charAt(0).toLowerCase();
    return /[a-z]/.test(c) ? c : '#';
};

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

function clean(w) {
    return Object.assign({
        type: '', meaning: '', example: '', example_tr: '', translate: '', synonyms: [], antonyms: [],
    }, w, {
        key: w.key || String(w.phrase).toLowerCase(),
        ipa: Object.assign({ uk: '', us: '' }, w.ipa),
        audio: Object.assign({ uk: '', us: '' }, w.audio),
    });
}

function init(root) {
    const $root = $(root);
    const T = (n) => $root.attr('data-t-' + n) || '';
    const endpoint = $root.attr('data-endpoint') || '';
    const remote = !!endpoint;
    const $q = $root.find('[data-dd-q]');
    const $clear = $root.find('[data-dd-clear]');
    const $type = $root.find('[data-dd-type]');
    const $sort = $root.find('[data-dd-sort]');
    const $savedBtn = $root.find('[data-dd-saved]');
    const $revealBtn = $root.find('[data-dd-reveal]');
    const $letters = $root.find('[data-dd-letters] .dd-letter-btn');
    const $list = $root.find('[data-dd-list]');
    const $dlg = $('#dd-dialog');
    const tpl = $root.find('[data-dd-row-tpl]')[0];
    const parse = (a, fb) => { try { return JSON.parse($root.attr(a) || ''); } catch (e) { return fb; } };

    let all = remote ? [] : parse('data-words', []).map(clean);
    const saved = new Set(parse('data-saved', []).map((k) => String(k).toLowerCase()));
    if (LOCAL) Object.keys(store.get(SAVED_KEY, {})).forEach((k) => saved.add(k));

    // حروفی که لغت دارند (حالت لیست کامل: از خود لیست؛ حالت endpoint: از data-letters)
    let letterSet = null;
    if (!remote) letterSet = new Set(all.map((w) => firstLetter(w.phrase)));
    else {
        const l = parse('data-letters', []);
        if (l.length) letterSet = new Set(l.map((x) => String(x).toLowerCase()));
    }
    $letters.each(function () {
        const l = this.getAttribute('data-letter');
        if (l && letterSet && !letterSet.has(l)) this.disabled = true;
    });

    // ---------- وضعیت ----------
    const st = { q: '', letter: '', type: 'all', saved: false, sort: 'az' };
    let items = [];
    let total = 0;
    let hasMore = false;
    let page = 1;
    let loading = false;
    let reqId = 0;
    let io = null;
    const sentinelEl = $root.find('[data-dd-sentinel]')[0];

    function view() {
        const q = norm(st.q.trim());
        let list = all.filter((w) => {
            if (st.letter && firstLetter(w.phrase) !== st.letter) return false;
            if (st.type !== 'all' && typeGroup(w.type) !== st.type) return false;
            if (st.saved && !saved.has(w.key)) return false;
            if (q && !norm([w.phrase, w.translate, w.meaning].join(' ')).includes(q)) return false;
            return true;
        });
        const dir = st.sort === 'za' ? -1 : 1;
        const abc = (a, b) => dir * a.phrase.localeCompare(b.phrase, 'en', { sensitivity: 'base' });
        list = list.slice().sort(q
            ? (a, b) => (norm(a.phrase).startsWith(q) ? 0 : 1) - (norm(b.phrase).startsWith(q) ? 0 : 1) || abc(a, b)
            : abc);
        return list;
    }

    // ---------- بارگذاری ----------
    async function load(reset) {
        if (reset) { page = 1; items = []; }
        const id = ++reqId;
        $root.find('[data-dd-error]').prop('hidden', true);
        if (!remote) {
            const v = view();
            items = v.slice(0, page * PAGE);
            total = v.length;
            hasMore = v.length > items.length;
            paint();
            return;
        }
        loading = true;
        $list.attr('aria-busy', 'true').addClass('is-loading');
        try {
            const u = new URL(endpoint, window.location.href);
            const p = { q: st.q.trim(), letter: st.letter, type: st.type, sort: st.sort, page, per: PAGE };
            if (st.saved) p.saved = 1;
            Object.keys(p).forEach((k) => { if (p[k] !== '' && p[k] != null) u.searchParams.set(k, p[k]); });
            const res = await fetch(u.toString(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('http ' + res.status);
            const j = await res.json();
            if (id !== reqId) return;
            const data = (j.data || []).map(clean);
            items = reset ? data : items.concat(data);
            total = typeof j.total === 'number' ? j.total : items.length;
            hasMore = !!j.has_more;
            paint();
        } catch (e) {
            if (id !== reqId) return;
            if (!reset) page = Math.max(1, page - 1);
            $root.find('[data-dd-error]').prop('hidden', false);
            $root.find('[data-dd-more-wrap]').prop('hidden', true);
        } finally {
            if (id === reqId) { loading = false; $list.removeAttr('aria-busy').removeClass('is-loading'); }
        }
    }
    function loadMore() {
        if (loading || !hasMore) return Promise.resolve();
        page++;
        return load(false);
    }

    // ---------- نمایش ----------
    function rowFor(w) {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.setAttribute('data-id', w.id);
        node.setAttribute('data-key', w.key);
        node.querySelector('[data-r="phrase"]').textContent = w.phrase;
        node.querySelector('[data-r="type"]').textContent = w.type;
        const tr = node.querySelector('[data-dd-tr]');
        node.querySelector('[data-r="translate"]').textContent = w.translate;
        tr.hidden = !w.translate;
        setBm(node.querySelector('[data-dd-bm]'), saved.has(w.key));
        return node;
    }
    function setBm(btn, on) {
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('aria-label', on ? T('unsave') : T('save'));
    }

    function paint() {
        const frag = document.createDocumentFragment();
        const group = !st.q.trim();
        let last = '';
        items.forEach((w) => {
            if (group) {
                const l = firstLetter(w.phrase);
                if (l !== last) {
                    last = l;
                    const h = document.createElement('h3');
                    h.className = 'dd-head';
                    h.textContent = l.toUpperCase();
                    h.setAttribute('aria-hidden', 'true');
                    frag.appendChild(h);
                }
            }
            frag.appendChild(rowFor(w));
        });
        $list.empty().append(frag);

        const n = digits(total);
        $root.find('[data-dd-summary]').text(st.letter
            ? fill(T('count-letter'), { n, l: st.letter.toUpperCase() })
            : fill(T('count'), { n }));
        $root.find('[data-dd-noresult]').prop('hidden', items.length > 0);
        $root.find('[data-dd-more-wrap]').prop('hidden', !hasMore);
        $clear.prop('hidden', !st.q);
        // اگر بعد از رندر هنوز جا هست (صفحه‌ی کوتاه)، ناظر دوباره بررسی کند؛ وگرنه صفحه‌ی بعد هیچ‌وقت نمی‌آید
        if (io && hasMore) { io.unobserve(sentinelEl); io.observe(sentinelEl); }
    }

    // ---------- کنترل‌ها ----------
    function reload() { load(true); }
    function scrollToTop() {
        const bar = $root.find('[data-dd-bar]')[0];
        const top = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--topbar-h')) * 16 || 64;
        const y = $list.offset().top - (bar ? bar.offsetHeight : 0) - top - 8;
        if (window.scrollY > y) window.scrollTo({ top: Math.max(y, 0) });
    }

    let qTimer = 0;
    $q.on('input', function () {
        clearTimeout(qTimer);
        qTimer = setTimeout(() => { st.q = $q.val(); reload(); }, 150);
        $clear.prop('hidden', !this.value);
    });
    $clear.on('click', () => { $q.val('').trigger('focus'); st.q = ''; reload(); });
    $type.on('change', function () { st.type = this.value; reload(); });
    $sort.on('change', function () { st.sort = this.value; reload(); });
    $savedBtn.on('click', function () {
        st.saved = !st.saved;
        this.setAttribute('aria-pressed', st.saved ? 'true' : 'false');
        reload();
    });
    $letters.on('click', function () {
        const l = this.getAttribute('data-letter');
        st.letter = st.letter === l ? '' : l; // دوباره کلیک روی حرف فعال = همه
        $letters.each(function () {
            this.setAttribute('aria-pressed', (this.getAttribute('data-letter') === st.letter) ? 'true' : 'false');
        });
        reload();
        scrollToTop();
    });
    $root.on('click', '[data-dd-reset]', () => {
        st.q = ''; st.letter = ''; st.type = 'all'; st.saved = false;
        $q.val(''); $type.val('all');
        $savedBtn.attr('aria-pressed', 'false');
        $letters.each(function () { this.setAttribute('aria-pressed', this.getAttribute('data-letter') === '' ? 'true' : 'false'); });
        reload();
    });
    $root.on('click', '[data-dd-retry]', () => load(false));
    $root.on('click', '[data-dd-more]', () => { loadMore(); });

    // «/» = رفتن به جست‌وجو
    $(document).on('keydown', (e) => {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey || $dlg.hasClass('is-open')) return;
        if ($(e.target).is('input,textarea,select,[contenteditable]')) return;
        e.preventDefault();
        $q.trigger('focus');
    });

    // بارگذاری خودکار با اسکرول
    const sentinel = $root.find('[data-dd-sentinel]')[0];
    if ('IntersectionObserver' in window && sentinel) {
        io = new IntersectionObserver((es) => {
            if (es.some((x) => x.isIntersecting) && hasMore && !loading) loadMore();
        }, { rootMargin: '600px 0px' });
        io.observe(sentinel);
    }

    // نمایش/پنهان‌کردن همه‌ی ترجمه‌ها
    function setReveal(on, persist) {
        $list.toggleClass('is-revealed', on);
        $revealBtn.attr('aria-pressed', on ? 'true' : 'false');
        $revealBtn.find('[data-dd-reveal-label]').text(on ? T('hide') : T('reveal'));
        if (persist && LOCAL) store.set(REVEAL_KEY, on);
    }
    $revealBtn.on('click', () => setReveal(!$list.hasClass('is-revealed'), true));
    setReveal(LOCAL && store.get(REVEAL_KEY, false) === true, false);

    // ---------- پیام کوتاه ----------
    const $toast = $root.find('[data-dd-toast]');
    let toastTimer = 0;
    function toast(text, linkText, href) {
        $toast.empty().append(document.createTextNode(text));
        if (linkText) {
            const a = document.createElement('a');
            a.href = href;
            a.textContent = linkText;
            a.className = 'dd-toast__a';
            $toast.append(a);
        }
        $toast.prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => $toast.removeClass('is-in').prop('hidden', true), TOAST_MS);
    }

    // ---------- ذخیره (بوکمارک) ----------
    function toggleSave(w) {
        const on = !saved.has(w.key);
        if (on) saved.add(w.key); else saved.delete(w.key);
        if (LOCAL) {
            const m = store.get(SAVED_KEY, {});
            if (on) {
                m[w.key] = {
                    key: w.key, text: w.phrase, song: 0, line: 0, at: Math.floor(Date.now() / 1000),
                    info: { phrase: w.phrase, type: w.type, ipa: w.ipa, audio: w.audio, meaning: w.meaning, example: w.example, example_tr: w.example_tr, translate: w.translate, synonyms: w.synonyms, antonyms: w.antonyms },
                };
            } else delete m[w.key];
            store.set(SAVED_KEY, m);
        }
        $(document).trigger('word:bookmark', [{ id: w.id, key: w.key, text: w.phrase, saved: on, song: null, source: 'dictionary' }]);
        $list.find('[data-dd-row]').each(function () {
            if (this.getAttribute('data-key') === w.key) setBm(this.querySelector('[data-dd-bm]'), on);
        });
        syncDialogBm();
        toast(fill(T(on ? 'saved' : 'unsaved'), { w: w.phrase }), on ? $root.attr('data-t-see-saved') || null : null, $root.attr('data-words-url'));
    }

    // ---------- ردیف‌ها ----------
    const byId = (id) => items.find((w) => String(w.id) === String(id));
    const rowOf = (el) => $(el).closest('[data-dd-row]');

    $list.on('click', '[data-dd-tr]', function () {
        this.setAttribute('aria-pressed', this.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
    });
    $list.on('click', '[data-dd-speak]', function () {
        const w = byId(rowOf(this).attr('data-id'));
        if (w) speak(w, 'uk');
    });
    $list.on('click', '[data-dd-bm]', function () {
        const w = byId(rowOf(this).attr('data-id'));
        if (w) toggleSave(w);
    });
    $list.on('click', '[data-dd-row]', function (e) {
        if ($(e.target).closest('button:not([data-dd-open])').length) return; // دکمه‌های داخل ردیف کار خودشان را می‌کنند
        const w = byId(this.getAttribute('data-id'));
        if (w) openDetail(w);
    });

    // ---------- جزئیات (offcanvas) ----------
    const d = { i: 0 };
    const $b = $dlg.find('.sw-b');
    const cur = () => items[d.i];
    const setBlur = () => $b.attr('aria-pressed', 'false');
    $dlg.on('click', '.sw-b', function () {
        this.setAttribute('aria-pressed', this.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
    });

    function setList(k, arr) {
        $dlg.find('[data-d="' + k + '"]').text((arr || []).join(', '));
        $dlg.find('[data-d-row="' + k + '"]').prop('hidden', !(arr && arr.length));
    }
    function syncDialogBm() {
        const w = cur();
        if (!w) return;
        const on = saved.has(w.key);
        const $bm = $dlg.find('[data-d-bm]');
        $bm.attr('aria-pressed', on ? 'true' : 'false').attr('aria-label', on ? T('unsave') : T('save'));
    }

    function showWord() {
        const w = cur();
        if (!w) { closeDialog($dlg); return; }
        const $d = (k) => $dlg.find('[data-d="' + k + '"]');
        $d('phrase').text(w.phrase);
        $d('type').text(w.type);
        ['uk', 'us'].forEach((a) => {
            $dlg.find('[data-d-pron="' + a + '"]').prop('hidden', !w.ipa[a]).find('[data-d-ipa]').text(w.ipa[a]);
        });
        $d('translate').text(w.translate);
        $dlg.find('[data-d-tr-wrap]').prop('hidden', !w.translate);
        $d('meaning').text(w.meaning).prop('hidden', !w.meaning);
        $d('example').text(w.example);
        $d('example_tr').text(w.example_tr);
        $dlg.find('[data-d-extr-wrap]').prop('hidden', !w.example_tr);
        $dlg.find('[data-d-ex-wrap]').prop('hidden', !w.example);
        setList('synonyms', w.synonyms);
        setList('antonyms', w.antonyms);
        $dlg.find('[data-dd-body]').closest('[data-dd-scroll]')[0].scrollTop = 0;
        setBlur();
        syncDialogBm();
        $dlg.find('[data-d-pos]').text(fill(T('position'), { n: digits(d.i + 1), total: digits(Math.max(total, items.length)) }));
        $dlg.find('[data-d-prev]').prop('disabled', d.i === 0);
        $dlg.find('[data-d-next]').prop('disabled', d.i >= items.length - 1 && !hasMore);
        const body = $dlg.find('[data-dd-body]')[0];
        body.classList.remove('is-swap');
        void body.offsetWidth;
        body.classList.add('is-swap');
    }

    function openDetail(w) {
        d.i = Math.max(items.indexOf(w), 0);
        showWord();
        openDialog('#dd-dialog');
    }
    async function go(step) {
        let n = d.i + step;
        if (n < 0) return;
        if (n >= items.length) {
            if (!hasMore) return;
            const before = items.length;
            await loadMore();
            if (items.length <= before) return;
            n = d.i + step;
        }
        d.i = n;
        showWord();
    }
    $dlg.on('click', '[data-d-prev]', () => { go(-1); });
    $dlg.on('click', '[data-d-next]', () => { go(1); });
    $dlg.on('click', '[data-d-speak]', function () {
        const w = cur();
        if (w) speak(w, this.getAttribute('data-d-speak'));
    });
    $dlg.on('click', '[data-d-bm]', () => {
        const w = cur();
        if (w) toggleSave(w);
    });

    $(document).on('keydown', (e) => {
        if (!$dlg.hasClass('is-open') || e.target.matches('input,textarea,select')) return;
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        const toStart = rtl() ? e.key === 'ArrowRight' : e.key === 'ArrowLeft';
        go(toStart ? -1 : 1);
    });
    let tx = 0;
    let ty = 0;
    $dlg.find('.dialog-panel').on('touchstart', (e) => {
        tx = e.originalEvent.touches[0].clientX;
        ty = e.originalEvent.touches[0].clientY;
    }).on('touchend', (e) => {
        const t = e.originalEvent.changedTouches[0];
        const dx = t.clientX - tx;
        if (Math.abs(dx) > 70 && Math.abs(t.clientY - ty) < 40) {
            const next = rtl() ? dx > 0 : dx < 0;
            go(next ? 1 : -1);
        }
    });
    $dlg.on('dialog:close', () => {
        if (audio) audio.pause();
        if ('speechSynthesis' in window) speechSynthesis.cancel();
    });

    load(true);

    window.Teodel = window.Teodel || {};
    window.Teodel.dictionary = { reload };
}

$(function () {
    $('[data-dd]').each(function () { init(this); });
});
