import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';
import { digits } from './digits';

/**
 * آزمون تعیین سطح (/level-test): معرفی ← انتخاب آزمون ← آزمون ← نتیجه و مرور (همه در یک صفحه، بدون رفرش).
 * سه آزمون: دایره واژگان (vocab)، گرامر (grammar)، درک شنیداری (listening)؛ هر کدام ۱۵ سؤال چهارگزینه‌ای (data-count).
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 * سؤال‌ها (قرارداد کامل: app/Support/PlacementTest.php):
 *   • data-start-url روی ریشه:  GET ?section=vocab  ←  { "questions": [ { id, section, level, title, fa, q, options[4], answer, audio?, transcript?, explain? } ] }
 *   • یا بدون آن: data-bank (همه‌ی بانک) و مرورگر برای هر بار آزمون به‌طور تصادفی و با توزیع مساوی از A1 تا C1 انتخاب می‌کند (دمو).
 *
 * پایان آزمون:
 *   $(document).on('placement:finish', (e, payload) => {
 *       e.preventDefault();                                   // یعنی «خودم ذخیره می‌کنم»
 *       save(payload).then(() => Teodel.placement.done(true, { overall: 'b1' }))   // overall اختیاری: سطح کلی که سرور حساب کرده
 *                    .catch(() => Teodel.placement.done(false));
 *   });
 *   payload = {
 *     section: 'vocab', answers: [{ id, option }],            // option: اندیس انتخاب‌شده (۰ تا ۳) یا null
 *     correct, total, score (۰ تا ۱۰۰), level ('a1'…'c1'),   // محاسبه‌ی فرانت فقط نمایشی است؛ سرور دوباره حساب کند
 *     perLevel: { a1: { correct, total }, … }, overall: 'b1' | null, skills: { listening: 'B1' }
 *   }
 *   بدون preventDefault: اگر data-finish-url باشد POST + JSON + CSRF می‌شود؛ وگرنه حالت دمو (ذخیره‌ی فوری در مرورگر).
 * سایر رویدادها: placement:start {section}، placement:answer {section, id, option}، placement:level {overall, skills}
 *
 * سطح کلی = میانگین سطح آخرین نمره‌ی آزمون‌هایی که داده شده؛ هر المانی با [data-user-level] (سایدبار) خودکار به‌روز می‌شود.
 *
 * نوع آزمون: standard (A1–C1، رایگان) و advanced (B1–C2، ویژه‌ی اشتراک؛ data-advanced="1" یعنی پلن کاربر advanced_level دارد).
 *   با data-start-url پارامتر &track=standard|advanced هم می‌رود؛ در payload و data-results هم track هست.
 * محدودیت ۱۵ روز: data-next-at (unix ثانیه، از بک‌اند) + data-lock-days + data-lock-scope (all | each). در دمو از localStorage حساب می‌شود.
 * انتخاب دستی سطح (بدون محدودیت زمانی): $(document).on('placement:set-level', (e, { level }) => {...}) یا data-level-url (POST JSON {level}).
 *   اگر preventDefault کنید خودتان ذخیره می‌کنید و با Teodel.placement.levelSaved(true|false, level) جواب می‌دهید.
 *
 * LOCAL: در دمو نمره‌ها و آزمون نیمه‌کاره در localStorage می‌مانند. با اتصال بک‌اند false کنید (نمره‌ها از data-results می‌آید).
 */
const LOCAL = true;
const KEY = 'teodel:placement';
const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
const STD_LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1'];
const ADV_LEVELS = ['b1', 'b2', 'c1', 'c2'];
const DAY = 86400;
const PASS = 0.6; // حداقل درصد درست در یک سطح تا آن سطح «قبول» حساب شود

const store = {
    get() {
        try {
            return JSON.parse(localStorage.getItem(KEY) || '{}') || {};
        } catch (e) {
            return {};
        }
    },
    set(v) {
        try {
            localStorage.setItem(KEY, JSON.stringify(v));
        } catch (e) {
            /* حالت خصوصی / حافظه پر */
        }
    },
};

/* ── محاسبه‌ها (ساده و بدون وابستگی به DOM) ─────────────────────────── */

/** از روی پاسخ‌ها: { perLevel, correct, total, score, level } */
export function grade(questions, answers) {
    const perLevel = {};
    LEVELS.forEach((l) => {
        perLevel[l] = { correct: 0, total: 0 };
    });
    let correct = 0;
    questions.forEach((q) => {
        const p = perLevel[q.level];
        if (!p) return;
        p.total++;
        if (answers[q.id] === q.answer) {
            p.correct++;
            correct++;
        }
    });
    const total = questions.length;
    const score = total ? Math.round((correct / total) * 100) : 0;
    return { perLevel, correct, total, score, level: estimate(perLevel) };
}

/**
 * بالاترین سطحی که پشت‌سرهم قبول شده. آزمون معمولی از A1 شروع می‌شود؛ در آزمون پیشرفته (سطح‌های پایین سؤال ندارند)
 * اگر اولین سطح هم قبول نشود، سطح زیرِ آن گزارش می‌شود (یعنی «پایین‌تر از دامنه‌ی این آزمون»).
 */
export function estimate(perLevel) {
    const tested = LEVELS.filter((l) => perLevel[l] && perLevel[l].total > 0);
    if (!tested.length) return LEVELS[0];
    let best = Math.max(0, LEVELS.indexOf(tested[0]) - (tested[0] === LEVELS[0] ? 0 : 1));
    for (let i = LEVELS.indexOf(tested[0]); i < LEVELS.length; i++) {
        const p = perLevel[LEVELS[i]];
        if (!p || !p.total) break;
        if (p.correct / p.total >= PASS) best = i;
        else break;
    }
    return LEVELS[best];
}

/** میانگین سطح آزمون‌های داده‌شده؛ null اگر هیچ‌کدام */
export function overallLevel(results) {
    const ls = Object.values(results || {})
        .map((r) => LEVELS.indexOf(r && r.level))
        .filter((i) => i >= 0);
    if (!ls.length) return null;
    return LEVELS[Math.min(LEVELS.length - 1, Math.round(ls.reduce((a, b) => a + b, 0) / ls.length + 1e-9))];
}

function shuffle(a) {
    const r = a.slice();
    for (let i = r.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [r[i], r[j]] = [r[j], r[i]];
    }
    return r;
}

/** انتخاب سؤال از بانک با توزیع مساوی سطح‌ها (معمولی: A1–C1، پیشرفته: B1–C2)؛ ترتیب از آسان به سخت و گزینه‌ها شافل (جواب هم جابه‌جا می‌شود) */
export function pick(bank, section, count, track) {
    const L = track === 'advanced' ? ADV_LEVELS : STD_LEVELS;
    const pool = bank.filter((q) => q.section === section);
    const base = Math.floor(count / L.length);
    let extra = count - base * L.length;
    const need = {};
    // باقی‌مانده از سطح‌های میانی شروع می‌شود تا به یک سطح بیش از حد وزن داده نشود
    [1, 2, 3, 0, 4].filter((i) => i < L.length).forEach((i) => {
        need[L[i]] = base + (extra-- > 0 ? 1 : 0);
    });
    const out = [];
    L.forEach((l) => {
        out.push(...shuffle(pool.filter((q) => q.level === l)).slice(0, need[l]));
    });
    return out.map((q) => {
        const order = shuffle([0, 1, 2, 3]);
        return Object.assign({}, q, { options: order.map((i) => q.options[i]), answer: order.indexOf(q.answer) });
    });
}

/** سطح ذخیره‌شده‌ی دمو (آخرین چیزی که تازه‌تر است: انتخاب دستی یا میانگین آزمون‌ها) — برای سایدبار در همه‌ی صفحه‌ها */
function localLevel() {
    const s = store.get();
    const rs = Object.values(s.results || {});
    const ov = overallLevel(s.results);
    const tAt = rs.reduce((m, r) => Math.max(m, (r && r.at) || 0), 0);
    const m = s.manual;
    if (m && LEVELS.includes(m.level) && (!ov || m.at >= tAt)) return m.level;
    return ov;
}

/* ── صفحه ────────────────────────────────────────────────────────────── */
$(function () {
    const fa = document.documentElement.lang === 'fa';
    const dig = (n) => (fa ? digits(n) : String(n));

    // سطح کلی روی همه‌ی صفحه‌ها (سایدبار): فقط دمو
    const syncLevel = (lv) => $('[data-user-level]').text(lv.toUpperCase());
    if (LOCAL) {
        const o = localLevel();
        if (o) syncLevel(o);
    }

    const $root = $('[data-pt]');
    if (!$root.length) return;

    /* داده‌ها و ترجمه‌ها */
    const parse = (a, d) => {
        try {
            return JSON.parse($root.attr(a) || '') || d;
        } catch (e) {
            return d;
        }
    };
    const TT = parse('data-t', {});
    const bank = parse('data-bank', []);
    const COUNT = parseInt($root.attr('data-count'), 10) || 15;
    const CAN_ADV = $root.attr('data-advanced') === '1';
    const LOCK_DAYS = parseInt($root.attr('data-lock-days'), 10) || 15;
    const LOCK_SCOPE = $root.attr('data-lock-scope') === 'each' ? 'each' : 'all';
    const NEXT_AT = parseInt($root.attr('data-next-at'), 10) || 0;
    const levelUrl = $root.attr('data-level-url') || '';
    const SERVER_LEVEL = $root.attr('data-level') || '';
    const startUrl = $root.attr('data-start-url') || '';
    const finishUrl = $root.attr('data-finish-url') || '';

    const t = (path, rep) => {
        let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };
    const levelName = (l) => t('levels.' + l);
    const sectionTitle = (s) => t('sections.' + s + '.title');

    /* وضعیت */
    let results = Object.assign({}, parse('data-results', {}));
    if (LOCAL) results = Object.assign({}, results, store.get().results || {});
    let session = null; // { section, questions, answers:{id:option}, idx, track }
    let stage = 'intro';
    let mode = 'standard'; // نوع آزمونی که در صفحه‌ی انتخاب دیده می‌شود
    let manual = LOCAL ? store.get().manual || null : null; // { level, at }

    /** روزهای مانده تا باز شدن آزمون (۰ = باز است). section فقط وقتی scope=each مهم است */
    function lockLeft(section) {
        let until = NEXT_AT;
        if (LOCAL) {
            const f = store.get().finished || {};
            const last = LOCK_SCOPE === 'each' ? f[section] || 0 : Math.max(0, ...Object.values(f));
            if (last) until = Math.max(until, last + LOCK_DAYS * DAY);
        }
        const left = until - Math.floor(Date.now() / 1000);
        return left > 0 ? Math.ceil(left / DAY) : 0;
    }
    function lockedAll() {
        // برای نوار هشدار: قفل کلی (scope=all) یا قفل همه‌ی آزمون‌ها (scope=each)
        const ks = $root.find('[data-pt-card]').map((i, el) => $(el).attr('data-pt-card')).get();
        const ls = ks.map((k) => lockLeft(k));
        return ls.length && ls.every((x) => x > 0) ? Math.min(...ls) : 0;
    }
    /** سطح فعلی کاربر: تازه‌ترین بین انتخاب دستی و میانگین آزمون‌ها (یا سطح سرور) */
    function currentLevel() {
        const rs = Object.values(results);
        const ov = overallLevel(results);
        const tAt = rs.reduce((m, r) => Math.max(m, (r && r.at) || 0), 0);
        if (manual && LEVELS.includes(manual.level) && (!ov || manual.at >= tAt)) return { level: manual.level, manual: true };
        if (ov) return { level: ov, manual: false };
        return SERVER_LEVEL ? { level: SERVER_LEVEL, manual: true } : null;
    }

    const persist = () => {
        if (!LOCAL) return;
        const s = store.get();
        s.results = results;
        s.session = session;
        store.set(s);
    };
    if (LOCAL) session = store.get().session || null;
    if (session && (!session.questions || !session.questions.length)) session = null;

    /* ── مراحل (stage) ─────────────────────────────────────────────── */
    const $stages = $root.find('[data-pt-stage]');
    function show(name, push) {
        stage = name;
        $stages.each(function () {
            $(this).prop('hidden', $(this).attr('data-pt-stage') !== name);
        });
        $('html').toggleClass('pt-live', name === 'exam');
        if (push) history.pushState({ pt: name }, '');
        window.scrollTo({ top: 0, behavior: 'auto' });
        if (name !== 'exam') audio.stop();
    }
    history.replaceState({ pt: 'intro' }, '');
    window.addEventListener('popstate', (e) => {
        const to = (e.state && e.state.pt) || 'intro';
        if (stage === 'exam') {
            // دکمه‌ی «برگشت» وسط آزمون: خروج را تأیید بگیر و در همان‌جا بمان
            history.pushState({ pt: 'exam' }, '');
            openDialog('#pt-exit-dialog');
            return;
        }
        if (to === 'exam') return show('choose'); // آزمون با «برگشت مرورگر» دوباره باز نشود
        if (to === 'result' && !$root.find('[data-pt-r-title]').text()) return show('choose');
        if (to === 'choose') renderChoose();
        if (to === 'levels') renderLevels();
        show(to);
    });

    $root.on('click', '[data-pt-go]', function () {
        show($(this).attr('data-pt-go'), true);
        if ($(this).attr('data-pt-go') === 'choose') renderChoose();
        if ($(this).attr('data-pt-go') === 'levels') renderLevels();
    });

    /* ── انتخاب آزمون ──────────────────────────────────────────────── */
    function renderChoose() {
        const n = Object.keys(results).length;
        const cur = currentLevel();
        const $ov = $root.find('[data-pt-overall]').toggleClass('is-empty', !cur);
        $ov.find('[data-pt-overall-level]').text(cur ? cur.level.toUpperCase() : '—');
        $ov.find('[data-pt-overall-text]').text(cur ? (cur.manual ? t('manual.current') : t('choose.overall_text', { n: dig(n) })) : t('choose.overall_none'));
        $root.find('[data-pt-scale]').each(function () {
            $(this).toggleClass('is-me', !!cur && $(this).attr('data-pt-scale') === cur.level);
        });
        $root.find('[data-pt-manual-current]').prop('hidden', !cur || !cur.manual).find('[data-pt-manual-level]').text(cur ? cur.level.toUpperCase() : '');

        // قفل ۱۵ روزه (نوار هشدار)
        const all = lockedAll();
        const $lock = $root.find('[data-pt-lock]').prop('hidden', !all);
        if (all) $lock.find('[data-pt-lock-title]').text(t('lock.banner', { n: dig(all) }));

        // نوع آزمون
        $root.find('[data-pt-mode]').each(function () {
            $(this).attr('aria-checked', $(this).attr('data-pt-mode') === mode ? 'true' : 'false');
        });
        $root.find('[data-pt-pro]').prop('hidden', CAN_ADV);
        const showUpsell = mode === 'advanced' && !CAN_ADV;
        $root.find('[data-pt-upsell]').prop('hidden', !showUpsell);
        $root.find('[data-pt-cards]').prop('hidden', showUpsell);

        $root.find('[data-pt-card]').each(function () {
            const $c = $(this);
            const k = $c.attr('data-pt-card');
            const r = results[k];
            const resume = session && session.section === k && Object.keys(session.answers).length > 0;
            const left = resume ? 0 : lockLeft(k);
            $c.find('[data-pt-card-count]').text(t('card.questions', { n: dig(COUNT) }));
            $c.find('[data-pt-card-min]').text(t('card.minutes', { n: dig(Math.ceil((COUNT * 2) / 3)) }));
            $c.find('[data-pt-card-level]').prop('hidden', !r).text(r ? r.level.toUpperCase() : '');
            $c.find('[data-pt-card-bar]').css('width', r ? r.score + '%' : '0%');
            $c.find('[data-pt-card-label]').text(r ? t('card.last_score') : t('card.not_taken'));
            $c.find('[data-pt-card-num]').text(r ? dig(r.score) + (fa ? '٪' : '%') : '');
            $c.find('[data-pt-card-cta]').text(
                left ? t('lock.btn', { n: dig(left) }) : resume ? t('card.resume') : r ? t('card.retake') : t('card.start')
            );
            $c.find('[data-pt-start]').prop('disabled', left > 0).attr('aria-disabled', left > 0 ? 'true' : null);
            $c.toggleClass('has-resume', !!resume).toggleClass('is-locked', left > 0);
        });
    }
    $root.on('click', '[data-pt-mode]', function () {
        mode = $(this).attr('data-pt-mode');
        renderChoose();
    });

    /* ── راهنمای سطح‌ها و انتخاب دستی ───────────────────────────────── */
    function renderLevels() {
        const cur = currentLevel();
        $root.find('[data-pt-lvl]').each(function () {
            const l = $(this).attr('data-pt-lvl');
            const mine = !!cur && cur.level === l;
            $(this).toggleClass('is-me', mine);
            const $b = $(this).find('[data-pt-pick-level]');
            $b.text(mine ? $b.attr('data-t-chosen') : $b.attr('data-t-choose')).toggleClass('is-chosen', mine).attr('aria-pressed', mine ? 'true' : 'false');
        });
    }
    $root.on('click', '[data-pt-pick-level]', function () {
        const lv = $(this).attr('data-pt-pick-level');
        if (!LEVELS.includes(lv)) return;
        const prev = manual;
        manual = { level: lv, at: Math.floor(Date.now() / 1000) };
        if (LOCAL) {
            const s = store.get();
            s.manual = manual;
            store.set(s);
        }
        syncLevel(lv);
        renderLevels();
        $(document).trigger('placement:level', { overall: lv, skills: {} });
        const ev = $.Event('placement:set-level');
        $(document).trigger(ev, { level: lv });
        if (ev.isDefaultPrevented()) {
            levelPrev = prev;
            return; // بک‌اند با Teodel.placement.levelSaved جواب می‌دهد
        }
        if (levelUrl) {
            levelPrev = prev;
            const csrf = $('meta[name="csrf-token"]').attr('content') || '';
            fetch(levelUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify({ level: lv }),
            })
                .then((r) => {
                    if (!r.ok) throw new Error('http ' + r.status);
                    levelSaved(true, lv);
                })
                .catch(() => levelSaved(false, lv));
        } else {
            levelSaved(true, lv); // دمو
        }
    });
    let levelPrev = null;
    function levelSaved(ok, lv) {
        if (ok) return toast(t('manual.saved', { level: String(lv).toUpperCase() }));
        // خطا: برگرد به انتخاب قبلی
        manual = levelPrev;
        if (LOCAL) {
            const s = store.get();
            s.manual = manual;
            store.set(s);
        }
        const c = currentLevel();
        if (c) syncLevel(c.level);
        renderLevels();
        toast(t('manual.failed'), true);
    }

    /* ── شروع آزمون ────────────────────────────────────────────────── */
    $root.on('click', '[data-pt-start]', function () {
        const k = $(this).attr('data-pt-start');
        if (session && session.section === k && Object.keys(session.answers).length > 0) return openExam();
        begin(k, $(this));
    });

    async function begin(k, $btn) {
        let qs = [];
        const track = mode === 'advanced' && CAN_ADV ? 'advanced' : 'standard';
        const left = lockLeft(k);
        if (left) {
            renderChoose();
            show('choose', true);
            return toast(t('lock.blocked', { n: dig(left) }), true);
        }
        if ($btn) $btn.prop('disabled', true).find('[data-pt-card-cta]').text(t('exam.loading'));
        try {
            if (startUrl) {
                const res = await fetch(startUrl + (startUrl.indexOf('?') < 0 ? '?' : '&') + 'section=' + encodeURIComponent(k) + '&track=' + track, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('http ' + res.status);
                qs = ((await res.json()) || {}).questions || [];
            } else {
                qs = pick(bank, k, COUNT, track);
            }
        } catch (e) {
            qs = [];
        }
        if ($btn) $btn.prop('disabled', false);
        if (!qs.length) {
            renderChoose();
            return toast(t('exam.failed'), true);
        }
        session = { section: k, questions: qs, answers: {}, idx: 0, track };
        persist();
        $(document).trigger('placement:start', { section: k, track });
        openExam();
    }

    function openExam() {
        show('exam', true);
        renderQ(false);
    }

    /* ── صفحه‌ی سؤال ───────────────────────────────────────────────── */
    const $q = $root.find('[data-pt-q]');
    const $opts = $root.find('[data-pt-opts]');
    const $dots = $root.find('[data-pt-dots]');
    const LETTERS = ['A', 'B', 'C', 'D'];

    function renderQ(animate) {
        const qs = session.questions;
        const q = qs[session.idx];
        const n = qs.length;
        const done = Object.keys(session.answers).length;

        $root.find('[data-pt-exam-title]').text(sectionTitle(session.section));
        $root.find('[data-pt-exam-count]').text(t('exam.question_of', { n: dig(session.idx + 1), total: dig(n) }));
        $root.find('[data-pt-q-type]').text(q.title || sectionTitle(session.section));
        $root.find('[data-pt-q-fa]').text(q.fa || '');
        $root.find('[data-pt-q-text]').text(q.q);

        $opts.empty();
        q.options.forEach((o, i) => {
            const on = session.answers[q.id] === i;
            $('<button type="button" class="pt-opt" role="radio"></button>')
                .attr({ 'aria-checked': on ? 'true' : 'false', 'data-i': i, tabindex: on || (session.answers[q.id] == null && i === 0) ? 0 : -1 })
                .append($('<span class="pt-opt__k" dir="ltr"></span>').text(LETTERS[i]), $('<span class="min-w-0 flex-1"></span>').text(o))
                .appendTo($opts);
        });

        $root.find('[data-pt-n-done]').text(dig(done));
        $root.find('[data-pt-n-left]').text(dig(n - done));
        const pct = Math.round((done / n) * 100);
        $root.find('[data-pt-progress]').attr('aria-valuenow', pct).find('[data-pt-progress-bar]').css('width', pct + '%');

        $dots.empty();
        qs.forEach((x, i) => {
            $('<button type="button" class="pt-dot"></button>')
                .toggleClass('is-done', session.answers[x.id] != null)
                .toggleClass('is-current', i === session.idx)
                .attr({ 'data-go': i, 'aria-label': t('exam.question_of', { n: dig(i + 1), total: dig(n) }) })
                .attr('aria-current', i === session.idx ? 'step' : null)
                .text(dig(i + 1))
                .appendTo($dots);
        });
        const cur = $dots.find('.is-current')[0];
        if (cur && $dots[0].scrollWidth > $dots[0].clientWidth) {
            $dots[0].scrollTo({ left: cur.offsetLeft - $dots[0].clientWidth / 2 + cur.offsetWidth / 2, behavior: animate ? 'smooth' : 'auto' });
        }

        const last = session.idx === n - 1;
        $root.find('[data-pt-prev]').prop('disabled', session.idx === 0);
        $root.find('[data-pt-next-label]').text(last ? t('exam.finish') : t('exam.next'));

        audio.load(q);

        if (animate) {
            $q.removeClass('is-in');
            void $q[0].offsetWidth;
            $q.addClass('is-in');
        }
        persist();
    }

    function go(i) {
        if (i < 0 || i >= session.questions.length) return;
        session.idx = i;
        renderQ(true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    $opts.on('click', '.pt-opt', function () {
        const q = session.questions[session.idx];
        const i = parseInt($(this).attr('data-i'), 10);
        session.answers[q.id] = i;
        $(document).trigger('placement:answer', { section: session.section, id: q.id, option: i });
        // فقط وضعیت را تازه کن (بدون ساخت دوباره‌ی کل سؤال؛ فوکوس و صدا نپرد)
        $opts.find('.pt-opt').each(function () {
            const on = parseInt($(this).attr('data-i'), 10) === i;
            $(this).attr({ 'aria-checked': on ? 'true' : 'false', tabindex: on ? 0 : -1 });
        });
        const done = Object.keys(session.answers).length;
        const n = session.questions.length;
        $root.find('[data-pt-n-done]').text(dig(done));
        $root.find('[data-pt-n-left]').text(dig(n - done));
        const pct = Math.round((done / n) * 100);
        $root.find('[data-pt-progress]').attr('aria-valuenow', pct).find('[data-pt-progress-bar]').css('width', pct + '%');
        $dots.find('.pt-dot').eq(session.idx).addClass('is-done');
        persist();
    });

    // کلیدهای ۱ تا ۴ و جهت‌ها (فقط وقتی روی ورودی‌های دیگر نیستیم)
    $(document).on('keydown', function (e) {
        if (stage !== 'exam' || !session || $('.dialog[aria-hidden="false"]').length) return;
        if ($(e.target).is('input, textarea, select')) return;
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        const q = session.questions[session.idx];
        if (/^[1-4]$/.test(e.key)) {
            $opts.find('.pt-opt').eq(parseInt(e.key, 10) - 1).trigger('click').trigger('focus');
        } else if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            if ($(e.target).is('.pt-opt')) return; // کلید جهت‌ها بین گزینه‌ها
            const rtl = document.documentElement.dir === 'rtl';
            const fwd = (e.key === 'ArrowRight') !== rtl;
            go(session.idx + (fwd ? 1 : -1));
        } else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && $(e.target).is('.pt-opt') && q) {
            e.preventDefault();
            const $all = $opts.find('.pt-opt');
            const cur = $all.index(e.target);
            const nx = (cur + (e.key === 'ArrowDown' ? 1 : -1) + $all.length) % $all.length;
            $all.eq(nx).trigger('focus');
        }
    });

    $root.on('click', '[data-pt-prev]', () => go(session.idx - 1));
    $root.on('click', '[data-pt-next]', () => {
        if (session.idx === session.questions.length - 1) return askFinish();
        go(session.idx + 1);
    });
    $dots.on('click', '.pt-dot', function () {
        go(parseInt($(this).attr('data-go'), 10));
    });

    /* ── خروج / پایان ──────────────────────────────────────────────── */
    $root.on('click', '[data-pt-exit]', () => openDialog('#pt-exit-dialog'));
    $(document).on('click', '[data-pt-exit-ok]', () => {
        closeDialog('#pt-exit-dialog');
        renderChoose();
        show('choose', true);
    });

    $root.on('click', '[data-pt-finish]', askFinish);
    function askFinish() {
        const left = session.questions.length - Object.keys(session.answers).length;
        const $d = $('#pt-finish-dialog');
        $d.find('#pt-fin-title').text(t('confirm_finish.title'));
        $d.find('[data-pt-fin-text]').text(left ? t('confirm_finish.text', { n: dig(left) }) : t('confirm_finish.all_text'));
        $d.find('[data-pt-fin-ok]').text(left ? t('confirm_finish.ok') : t('confirm_finish.all_ok'));
        openDialog('#pt-finish-dialog');
    }
    $(document).on('click', '[data-pt-fin-ok]', () => {
        closeDialog('#pt-finish-dialog');
        finish();
    });

    async function finish() {
        const sec = session.section;
        const qs = session.questions;
        const answers = session.answers;
        const g = grade(qs, answers);
        const track = session.track || 'standard';
        const rec = { score: g.score, level: g.level, correct: g.correct, total: g.total, track, at: Math.floor(Date.now() / 1000) };
        const nextResults = Object.assign({}, results, { [sec]: rec });
        const ov = overallLevel(nextResults);
        const payload = {
            section: sec,
            track,
            answers: qs.map((q) => ({ id: q.id, option: answers[q.id] == null ? null : answers[q.id] })),
            correct: g.correct,
            total: g.total,
            score: g.score,
            level: g.level,
            perLevel: g.perLevel,
            overall: ov,
            skills: sec === 'listening' ? { listening: g.level.toUpperCase() } : {},
        };

        // نتیجه را همین حالا نشان بده؛ ذخیره‌ی سمت سرور بعدش (خطا فقط با پیام اعلام می‌شود)
        results = nextResults;
        const snapshot = { section: sec, track, questions: qs, answers: Object.assign({}, answers), grade: g };
        session = null;
        if (LOCAL) {
            // شروع قفل ۱۵ روزه (دمو): بک‌اند باید data-next-at را بدهد
            const s = store.get();
            s.finished = Object.assign({}, s.finished || {}, { [sec]: rec.at });
            store.set(s);
        }
        persist();
        renderResult(snapshot, ov);
        show('result', true);

        const ev = $.Event('placement:finish');
        $(document).trigger(ev, payload);
        if (ev.isDefaultPrevented()) return; // بک‌اند با Teodel.placement.done جواب می‌دهد
        if (finishUrl) {
            try {
                const csrf = $('meta[name="csrf-token"]').attr('content') || '';
                const res = await fetch(finishUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error('http ' + res.status);
                let data = {};
                try {
                    data = await res.json();
                } catch (e) {
                    /* بدنه‌ی خالی هم موفق است */
                }
                done(true, data || {});
            } catch (e) {
                done(false);
            }
        } else {
            done(true); // دمو
        }
    }

    function done(ok, info) {
        if (!ok) return toast(t('toast.failed'), true);
        const ov = (info && info.overall) || overallLevel(results);
        if (ov) {
            syncLevel(ov);
            $(document).trigger('placement:level', { overall: ov, skills: {} });
        }
        toast(t('toast.saved'));
    }
    window.Teodel = window.Teodel || {};
    window.Teodel.placement = { done, levelSaved };

    /* ── نتیجه ─────────────────────────────────────────────────────── */
    let lastSnap = null;
    function renderResult(snap, ov) {
        lastSnap = snap;
        const g = snap.grade;
        $root.find('[data-pt-r-title]').text(t('result.title', { section: sectionTitle(snap.section) }));
        $root.find('[data-pt-r-score]').text(dig(g.score));
        $root.find('[data-pt-r-correct]').text(t('result.correct_of', { c: dig(g.correct), t: dig(g.total) }));
        $root.find('[data-pt-r-level]').text(g.level.toUpperCase());
        $root.find('[data-pt-r-level-name]').text(levelName(g.level));
        $root.find('[data-pt-r-msg]').text(t('result.msg.' + g.level));
        $root.find('[data-pt-r-ielts]').text(t('ielts.' + g.level));
        const low = snap.track === 'advanced' && LEVELS.indexOf(g.level) < LEVELS.indexOf('b1');
        const left = lockLeft(snap.section);
        $root.find('[data-pt-r-note]').prop('hidden', !low && !left).text(low ? t('result.adv_low') : left ? t('result.locked_retake', { n: dig(left) }) : '');
        $root.find('[data-pt-retake]').prop('hidden', !!left);
        // حلقه: از صفر شروع و بعد پر می‌شود (انیمیشن)
        const $ring = $root.find('[data-pt-ring]').css('--p', 0);
        requestAnimationFrame(() => requestAnimationFrame(() => $ring.css('--p', g.score)));

        const $ovBox = $root.find('[data-pt-r-overall]').prop('hidden', !ov);
        if (ov) {
            const n = Object.keys(results).length;
            $ovBox.find('[data-pt-r-overall-level]').text(ov.toUpperCase());
            $ovBox.find('[data-pt-r-overall-note]').text(n < 3 ? t('result.overall_partial', { n: dig(n) }) : '');
        }

        const $bl = $root.find('[data-pt-bylevel]').empty();
        LEVELS.forEach((l) => {
            const p = g.perLevel[l] || { correct: 0, total: 0 };
            const pct = p.total ? (p.correct / p.total) * 100 : 0;
            $('<li></li>')
                .toggleClass('is-low', p.total > 0 && p.correct / p.total < PASS)
                .append(
                    $('<b dir="ltr"></b>').text(l.toUpperCase()),
                    $('<div class="pt-meter" aria-hidden="true"><i></i></div>').find('i').css('width', pct + '%').end(),
                    $('<span dir="ltr"></span>').text(dig(p.correct) + '/' + dig(p.total))
                )
                .appendTo($bl);
        });

        renderReview(snap);
        $root.find('[data-pt-review]').prop('hidden', true);
        $root.find('[data-pt-review-toggle]').attr('aria-expanded', 'false').text(t('result.review'));
        $root.find('[data-pt-filter] [data-f]').each(function () {
            $(this).attr('aria-pressed', $(this).attr('data-f') === 'all' ? 'true' : 'false');
        });
        filterReview('all');
    }

    function renderReview(snap) {
        const $list = $root.find('[data-pt-review-list]').empty();
        snap.questions.forEach((q, qi) => {
            const mine = snap.answers[q.id];
            const state = mine == null ? 'skip' : mine === q.answer ? 'right' : 'wrong';
            const $c = $('<article class="pt-rv"></article>').addClass('is-' + state).attr('data-state', state);
            $('<div class="pt-rv__head"></div>')
                .append(
                    $('<span class="text-slate-400"></span>').text(t('exam.question_of', { n: dig(qi + 1), total: dig(snap.questions.length) })),
                    $('<span class="pt-rv__tag"></span>').text(state === 'right' ? t('result.right') : state === 'wrong' ? t('result.wrong') : t('result.skipped'))
                )
                .appendTo($c);
            $('<p class="pt-rv__q" dir="ltr" lang="en"></p>').text(q.q).appendTo($c);
            const $o = $('<div class="pt-rv__opts" dir="ltr"></div>');
            q.options.forEach((o, i) => {
                const $r = $('<div class="pt-rv__o"></div>');
                if (i === q.answer) $r.addClass('is-correct');
                else if (i === mine) $r.addClass('is-mine-wrong');
                $r.append($('<span class="pt-opt__k"></span>').text(LETTERS[i]), $('<span class="min-w-0"></span>').text(o));
                if (i === q.answer) $r.append($('<em></em>').text(t('result.correct_answer')));
                else if (i === mine) $r.append($('<em></em>').text(t('result.your_answer')));
                $o.append($r);
            });
            $c.append($o);
            if (q.transcript || q.explain) {
                const $ex = $('<p class="pt-rv__ex" dir="ltr" lang="en"></p>');
                if (q.transcript) $ex.append($('<b></b>').text(t('result.transcript') + ': '), document.createTextNode(q.transcript));
                if (q.transcript && q.explain) $ex.append('<br>');
                if (q.explain) $ex.append(document.createTextNode(q.explain));
                $c.append($ex);
            }
            $list.append($c);
        });
    }

    function filterReview(f) {
        const $cards = $root.find('.pt-rv');
        $cards.each(function () {
            $(this).prop('hidden', f === 'wrong' && $(this).attr('data-state') === 'right');
        });
        $root.find('[data-pt-review-empty]').prop('hidden', !(f === 'wrong' && $cards.filter('.is-wrong, .is-skip').length === 0));
    }
    $root.on('click', '[data-pt-filter] [data-f]', function () {
        $(this).attr('aria-pressed', 'true').siblings().attr('aria-pressed', 'false');
        filterReview($(this).attr('data-f'));
    });
    $root.on('click', '[data-pt-review-toggle]', function () {
        const $r = $root.find('[data-pt-review]');
        const open = $r.prop('hidden');
        $r.prop('hidden', !open);
        $(this).attr('aria-expanded', open ? 'true' : 'false').text(open ? $(this).attr('data-t-hide') : $(this).attr('data-t-show'));
        if (open) $r[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $root.on('click', '[data-pt-retake]', function () {
        if (lastSnap) begin(lastSnap.section, null).then(() => {});
    });

    /* ── پیام کوتاه ────────────────────────────────────────────────── */
    const $toast = $root.find('[data-pt-toast]');
    let toastTimer = 0;
    function toast(text, bad) {
        $toast.text(text).toggleClass('is-bad', !!bad).prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => $toast.removeClass('is-in').prop('hidden', true), 2800);
    }

    /* ── پخش‌کننده‌ی صوت (آزمون شنیداری) ───────────────────────────
     * اگر سؤال audio داشته باشد، با <audio> پخش می‌شود (صدا، سرعت، پیشروی)؛
     * وگرنه (دمو) transcript با صدای مرورگر (speechSynthesis) خوانده می‌شود: صدا و سرعت دارد، پیشروی ندارد. */
    const $au = $root.find('[data-pt-audio]');
    const $play = $au.find('[data-pt-play]');
    const $seek = $au.find('[data-pt-seek]');
    const $vol = $au.find('[data-pt-vol]');
    const $time = $au.find('[data-pt-time]');
    const $dur = $au.find('[data-pt-dur]');
    const fmt = (s) => {
        s = Math.max(0, Math.floor(s || 0));
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    };
    const audio = (function () {
        let el = null; // <audio>
        let q = null;
        let mode = 'none'; // file | tts | none
        let speed = 1;
        let volume = 1;
        let muted = false;
        let playing = false;
        // TTS
        let utter = null;
        let ttsStart = 0; // اندیسی از متن که از آنجا شروع شده
        let ttsAt = 0;
        let ticker = 0;
        let ttsT0 = 0;

        const synth = 'speechSynthesis' in window ? window.speechSynthesis : null;

        function ui() {
            $play.toggleClass('is-live', playing).attr('aria-label', playing ? t('audio.pause') : t('audio.play'));
            $play.find('[data-ic="play"]').prop('hidden', playing);
            $play.find('[data-ic="pause"]').prop('hidden', !playing);
            const m = muted || volume === 0;
            $au.find('[data-ic="wave"]').prop('hidden', m);
            $au.find('[data-ic="x"]').prop('hidden', !m);
        }
        function paintSpeed() {
            $au.find('[data-pt-speed]').each(function () {
                $(this).attr('aria-checked', parseFloat($(this).attr('data-pt-speed')) === speed ? 'true' : 'false');
            });
        }
        function tick() {
            if (mode === 'file' && el) {
                const d = el.duration || 0;
                $time.text(fmt(el.currentTime));
                $dur.text(isFinite(d) ? fmt(d) : '0:00');
                if (d && isFinite(d)) $seek.val(Math.round((el.currentTime / d) * 1000));
            } else if (mode === 'tts') {
                const len = (q.transcript || '').length || 1;
                $seek.val(Math.round((ttsAt / len) * 1000));
                $time.text(fmt((Date.now() - ttsT0) / 1000));
            }
        }
        function stopTts() {
            clearInterval(ticker);
            if (synth) {
                utter && (utter.onend = utter.onerror = utter.onboundary = null);
                synth.cancel();
            }
            utter = null;
        }
        function speak(from) {
            if (!synth) return;
            stopTts();
            const text = (q.transcript || '').slice(from);
            if (!text) return;
            ttsStart = from;
            ttsAt = from;
            ttsT0 = Date.now();
            utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'en-US';
            utter.rate = speed;
            utter.volume = muted ? 0 : volume;
            utter.onboundary = (e) => {
                ttsAt = ttsStart + (e.charIndex || 0);
            };
            utter.onend = () => {
                playing = false;
                ttsAt = (q.transcript || '').length;
                clearInterval(ticker);
                tick();
                ui();
            };
            utter.onerror = () => {
                playing = false;
                clearInterval(ticker);
                ui();
            };
            synth.speak(utter);
            playing = true;
            ticker = setInterval(tick, 200);
            ui();
        }

        const api = {
            load(question) {
                api.stop();
                q = question;
                const isL = question.section === 'listening' || !!question.audio || !!question.transcript;
                $au.prop('hidden', !isL);
                if (!isL) return (mode = 'none');
                $seek.val(0);
                $time.text('0:00');
                $dur.text('0:00');
                if (question.audio) {
                    mode = 'file';
                    el = new Audio();
                    el.preload = 'metadata';
                    el.src = question.audio;
                    el.playbackRate = speed;
                    el.volume = volume;
                    el.muted = muted;
                    el.addEventListener('timeupdate', tick);
                    el.addEventListener('loadedmetadata', tick);
                    el.addEventListener('ended', () => {
                        playing = false;
                        ui();
                    });
                    el.addEventListener('error', () => {
                        playing = false;
                        ui();
                        $au.find('[data-pt-audio-note]').text(t('audio.unavailable'));
                    });
                    $seek.prop('disabled', false);
                    $au.find('[data-pt-audio-note]').text(t('audio.hint'));
                } else if (synth && question.transcript) {
                    mode = 'tts';
                    $seek.prop('disabled', true);
                    $au.find('[data-pt-audio-note]').text(t('audio.hint'));
                } else {
                    mode = 'none';
                    $seek.prop('disabled', true);
                    $au.find('[data-pt-audio-note]').text(t('audio.unavailable'));
                }
                paintSpeed();
                ui();
            },
            toggle() {
                if (mode === 'file' && el) {
                    if (el.paused) {
                        if (el.ended) el.currentTime = 0;
                        el.play().then(() => {
                            playing = true;
                            ui();
                        }).catch(() => {
                            playing = false;
                            ui();
                            $au.find('[data-pt-audio-note]').text(t('audio.unavailable'));
                        });
                    } else {
                        el.pause();
                        playing = false;
                        ui();
                    }
                } else if (mode === 'tts') {
                    if (playing) {
                        stopTts();
                        playing = false;
                        ui();
                    } else {
                        speak(0);
                    }
                }
            },
            seek(frac) {
                if (mode === 'file' && el && isFinite(el.duration)) el.currentTime = frac * el.duration;
            },
            setSpeed(v) {
                speed = v;
                if (el) el.playbackRate = v;
                if (mode === 'tts' && playing) speak(ttsAt); // از جای فعلی دوباره، با سرعت تازه
                paintSpeed();
            },
            setVolume(v) {
                volume = v;
                if (v > 0) muted = false;
                if (el) {
                    el.volume = v;
                    el.muted = muted;
                }
                if (mode === 'tts' && playing) speak(ttsAt); // speechSynthesis صدا را وسط پخش عوض نمی‌کند
                ui();
            },
            toggleMute() {
                muted = !muted;
                if (!muted && volume === 0) {
                    volume = 0.6;
                    $vol.val(60);
                    if (el) el.volume = volume;
                }
                if (el) el.muted = muted;
                if (mode === 'tts' && playing) speak(ttsAt);
                ui();
            },
            stop() {
                if (el) {
                    el.pause();
                    el.removeAttribute('src');
                    el.load();
                    el = null;
                }
                stopTts();
                playing = false;
                ttsAt = 0;
                ui();
            },
            state: () => ({ mode, playing, speed, volume, muted }),
        };
        return api;
    })();
    window.Teodel.placement.audio = audio;

    $play.on('click', () => audio.toggle());
    $seek.on('input', function () {
        audio.seek(parseInt(this.value, 10) / 1000);
    });
    $vol.on('input', function () {
        audio.setVolume(parseInt(this.value, 10) / 100);
    });
    $au.on('click', '[data-pt-mute]', () => audio.toggleMute());
    $au.on('click', '[data-pt-speed]', function () {
        audio.setSpeed(parseFloat($(this).attr('data-pt-speed')));
    });
    $(document).on('visibilitychange', () => {
        if (document.hidden) audio.stop();
    });

    /* ── شروع ──────────────────────────────────────────────────────── */
    renderChoose();
    const ov0 = overallLevel(results);
    $root.find('[data-pt-scale]').each(function () {
        $(this).toggleClass('is-me', $(this).attr('data-pt-scale') === ov0);
    });
});
