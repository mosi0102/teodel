import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, ago, num } from './common';
import { API, overlay, createApi, paginate, tFrom, el } from './bank';

/**
 * بانک سؤال آزمون تعیین سطح — لیست (/admin/placement) و ویرایشگر (/admin/placement/{id}/edit).
 * قرارداد داده و آدرس‌ها: app/Support/AdminPlacement.php
 *
 * 🔌 بک‌اند — همه‌ی عملیات از resources/js/admin/bank.js (createApi) می‌گذرد؛ سه راه اتصال:
 *   رویداد لغوشدنی admin:question-{save|upload|delete|bulk} ← Teodel.admin.question.api.{save|upload|remove|bulk} ← data-*-url
 * AI: kind 'question' (فیلدهای فرم) و 'question_batch' (ساخت گروهی؛ پاسخ: { fields:{ items:[مدل سؤال…] }, missing:[] })
 *
 * ⚠️ LOCAL = true: ذخیره‌ی دمو در localStorage مرورگر (سؤال‌های ساخته/ویرایش‌شده در لیست دیده می‌شوند).
 *    وقتی بک‌اند وصل شد، آن را false کنید.
 */
const LOCAL = true;
const LS = 'teodel:admin:placement';
export const SECTIONS = ['vocab', 'grammar', 'listening'];
export const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1'];
const LETTERS = ['A', 'B', 'C', 'D'];

/* ── توابع خالص (تست‌شده؛ همان قانون AdminPlacement::validate در PHP) ───── */

/** شماره‌ی گزینه (۰ تا ۳) از 'A'..'D' یا '0'..'3' یا عدد؛ نامعتبر = null */
export function parseAnswer(v) {
    if (typeof v === 'number') return v >= 0 && v <= 3 && Number.isInteger(v) ? v : null;
    const s = String(v == null ? '' : v).trim().toUpperCase();
    if (/^[A-D]$/.test(s)) return s.charCodeAt(0) - 65;
    if (/^[0-3]$/.test(s)) return parseInt(s, 10);
    return null;
}

/** بررسی یک سؤال → [{level:'error'|'warn', code, field}] */
export function validate(q) {
    const out = [];
    const add = (level, code, field) => out.push({ level, code, field });
    if (!SECTIONS.includes(q.section)) add('error', 'section', 'section');
    if (!LEVELS.includes(q.level)) add('error', 'level', 'level');
    const text = String(q.q || '').trim();
    if (!text) add('error', 'q_required', 'q');
    else if ((q.section === 'vocab' || q.section === 'grammar') && !text.includes('____')) add('warn', 'blank_missing', 'q');
    const opts = (q.options || []).map((o) => String(o || '').trim());
    if (opts.length !== 4 || opts.some((o) => !o)) add('error', 'options_required', 'options');
    else if (new Set(opts.map((o) => o.toLowerCase())).size !== 4) add('error', 'options_distinct', 'options');
    if (parseAnswer(q.answer) === null) add('error', 'answer', 'answer');
    if (q.section === 'listening') {
        if (!String(q.transcript || '').trim()) add('error', 'transcript_required', 'transcript');
        if (!String(q.audio || '').trim()) add('warn', 'audio_missing', 'audio');
    }
    return out;
}
export const isReady = (q) => !validate(q).some((i) => i.level === 'error');

/** ماتریس پوشش: فقط سؤال‌های منتشرشده */
export function coverage(rows) {
    const cov = {};
    SECTIONS.forEach((s) => {
        cov[s] = {};
        LEVELS.forEach((l) => (cov[s][l] = 0));
    });
    rows.forEach((r) => {
        if (r.status === 'published' && cov[r.section] && cov[r.section][r.level] != null) cov[r.section][r.level]++;
    });
    return cov;
}
export const covTone = (n, min, good) => (n < min ? 'bad' : n < good ? 'warn' : 'ok');

/** فیلتر + مرتب‌سازی لیست */
export function filterRows(rows, f) {
    const q = String(f.q || '').trim().toLowerCase();
    let out = rows.filter((r) => {
        if (q && !((r.q || '') + ' ' + (r.options || []).join(' ') + ' ' + (r.transcript || '')).toLowerCase().includes(q)) return false;
        return (f.section === 'all' || r.section === f.section) && (f.level === 'all' || r.level === f.level) && (f.status === 'all' || r.status === f.status);
    });
    const li = (r) => LEVELS.indexOf(r.level);
    out = out.slice().sort((a, b) => {
        if (f.sort === 'level') return li(a) - li(b) || a.id - b.id;
        if (f.sort === 'hard') return ((a.stats && a.stats.correct_pct) == null ? 999 : a.stats.correct_pct) - ((b.stats && b.stats.correct_pct) == null ? 999 : b.stats.correct_pct);
        return (b.updated_at || 0) - (a.updated_at || 0);
    });
    return out;
}

/** مدل تمیز برای ارسال */
export function cleanQuestion(Q) {
    const q = {
        id: Q.id || null,
        status: Q.status,
        section: Q.section,
        level: Q.level,
        title: Q.title,
        fa: String(Q.fa || '').trim(),
        q: String(Q.q || '').trim(),
        options: (Q.options || []).map((o) => String(o || '').trim()),
        answer: parseAnswer(Q.answer) == null ? 0 : parseAnswer(Q.answer),
        explain: String(Q.explain || '').trim(),
        audio: '',
        transcript: '',
    };
    if (Q.section === 'listening') {
        q.audio = String(Q.audio || '').trim();
        q.transcript = String(Q.transcript || '').trim();
    }
    return q;
}

/* ── نمونه‌های AI در «حالت دمو» (بدون بک‌اند) ───────────────────── */
const POOL = {
    vocab: {
        a1: [['My brother is very ____; he is only five years old.', ['young', 'heavy', 'late', 'dark'], 0], ['I wash my hands with ____ and water.', ['soap', 'salt', 'sugar', 'stone'], 0]],
        a2: [['We stayed in a small ____ near the beach.', ['hotel', 'hole', 'hall', 'hill'], 0], ['Please ____ your phone before the film starts.', ['switch off', 'switch on', 'take in', 'put on'], 0]],
        b1: [['The weather forecast says it will be ____ tomorrow, so take an umbrella.', ['wet', 'wide', 'weak', 'worn'], 0], ['He was ____ to hear that his friend had won the prize.', ['delighted', 'divided', 'defeated', 'delayed'], 0]],
        b2: [['The new law will ____ in January, not before.', ['come into effect', 'come across', 'come round', 'come up with'], 0], ['She is very ____ about her work and never misses a deadline.', ['conscientious', 'conscious', 'conscience', 'consequent'], 0]],
        c1: [['The negotiations reached a ____ when neither side would compromise.', ['deadlock', 'deadline', 'deadweight', 'deadpan'], 0], ['His ____ comments made everyone in the room uncomfortable.', ['tactless', 'tasteless', 'tireless', 'tearless'], 0]],
    },
    grammar: {
        a1: [['My sister ____ two cats.', ['has', 'have', 'having', 'haves'], 0], ['____ you like tea?', ['Do', 'Does', 'Are', 'Is'], 0]],
        a2: [['I ____ to the cinema last night.', ['went', 'go', 'gone', 'going'], 0], ['She is ____ student in our class.', ['the best', 'good', 'more good', 'the most good'], 0]],
        b1: [['If I ____ you, I would ask for help.', ['were', 'am', 'will be', 'was being'], 0], ['I’ve been waiting here ____ two hours.', ['for', 'since', 'during', 'from'], 0]],
        b2: [['I wish I ____ more time to prepare.', ['had had', 'have', 'will have', 'would have'], 0], ['The house ____ while we were away on holiday.', ['was broken into', 'broke into', 'has broken into', 'was breaking'], 0]],
        c1: [['Seldom ____ such a talented young player.', ['have I seen', 'I have seen', 'I saw', 'did I saw'], 0], ['She insisted that he ____ the truth.', ['tell', 'tells', 'told', 'would tell'], 0]],
    },
    listening: {
        a1: [['My name is Ali. I live in Tehran. I have two brothers.', 'Where does Ali live?', ['Tehran', 'Shiraz', 'Tabriz', 'Isfahan'], 0], ['It is Monday today. The shop is open until eight.', 'What day is it?', ['Monday', 'Sunday', 'Friday', 'Tuesday'], 0]],
        a2: [['The bus to the museum leaves at ten past two from stop number four.', 'Which stop does the bus leave from?', ['Stop four', 'Stop two', 'Stop ten', 'Stop five'], 0], ['I wanted to go swimming, but the pool is closed on Sundays.', 'Why can’t the speaker swim?', ['The pool is closed', 'It is too cold', 'There is no time', 'They are ill'], 0]],
        b1: [['Could you tell me whether the conference starts at nine or at ten? The invitation says nine, but the website says ten.', 'What is the speaker confused about?', ['The start time', 'The place', 'The price', 'The speaker'], 0], ['I’d recommend booking early because the tickets for the festival sell out within hours.', 'Why should people book early?', ['Tickets sell out quickly', 'Prices will rise', 'The festival is far', 'The weather is bad'], 0]],
        b2: [['Although the experiment was repeated several times, the results were inconsistent, which suggested a problem with the equipment.', 'What did the inconsistent results suggest?', ['Faulty equipment', 'A wrong theory', 'Too few people', 'Bad weather'], 0], ['Instead of raising ticket prices, the theatre introduced a membership scheme to attract regular visitors.', 'What did the theatre do?', ['Started a membership scheme', 'Raised prices', 'Closed early', 'Hired new actors'], 0]],
        c1: [['What the report fails to acknowledge is that the policy, however well intended, disproportionately affected small businesses.', 'What is the speaker’s main criticism?', ['The report ignores an effect on small firms', 'The policy was badly intended', 'The report is too long', 'The data is outdated'], 0], ['Having weighed the evidence, the committee concluded that the proposal, though ambitious, was financially unrealistic.', 'What did the committee conclude?', ['It was ambitious but unaffordable', 'It was cheap and simple', 'It was illegal', 'It needed more time'], 0]],
    },
};
const EXPL = 'گزینه‌ی درست با ساختار و معنی جمله هماهنگ است؛ بقیه‌ی گزینه‌ها از نظر معنی یا دستور جا نمی‌افتند.';

function shuffleWithAnswer(opts, ans, seed) {
    const idx = [0, 1, 2, 3];
    for (let i = 3; i > 0; i--) {
        const j = (seed * 7 + i * 3) % (i + 1);
        [idx[i], idx[j]] = [idx[j], idx[i]];
    }
    return { options: idx.map((k) => opts[k]), answer: idx.indexOf(ans) };
}

/** یک سؤال نمونه برای (بخش، سطح) — n امین نمونه */
export function demoQuestion(section, level, n) {
    const list = POOL[section][level];
    const it = list[n % list.length];
    const seed = n + LEVELS.indexOf(level) + 1;
    if (section === 'listening') {
        const [tr, q, opts, ans] = it;
        const s = shuffleWithAnswer(opts, ans, seed);
        return { section, level, title: 'Listening', q, transcript: tr, options: s.options, answer: s.answer, explain: EXPL, audio: '' };
    }
    const [q, opts, ans] = it;
    const s = shuffleWithAnswer(opts, ans, seed);
    return { section, level, title: section === 'vocab' ? 'Vocabulary' : 'Grammar', q, options: s.options, answer: s.answer, explain: EXPL, audio: '', transcript: '' };
}

const FA = {
    vocab: 'کلمه‌ی مناسب را برای جای خالی انتخاب کنید.',
    grammar: 'گزینه‌ی درست را برای تکمیل جمله انتخاب کنید.',
    listening: 'به صدا گوش دهید و به سؤال پاسخ دهید.',
};
const TITLES = { vocab: 'Vocabulary', grammar: 'Grammar', listening: 'Listening' };

/** نمونه‌ی دموی پاسخ AI برای kind='question' (فیلدهای فرم) */
function demoQuestionFields(payload) {
    const section = SECTIONS.includes(payload.seeds.section) ? payload.seeds.section : 'vocab';
    const level = LEVELS.includes(payload.seeds.level) ? payload.seeds.level : 'a2';
    const d = demoQuestion(section, level, Math.floor(Math.random() * 6));
    const all = { q: d.q, o1: d.options[0], o2: d.options[1], o3: d.options[2], o4: d.options[3], answer: LETTERS[d.answer], explain: d.explain, transcript: d.transcript };
    const out = {};
    const missing = [];
    payload.fields.forEach((f) => {
        if (/^audio$/.test(f)) return missing.push(f);
        if (all[f] != null && all[f] !== '') out[f] = all[f];
        else missing.push(f);
    });
    return { fields: out, missing };
}

API.admin.ai = API.admin.ai || {};
API.admin.ai.demos = API.admin.ai.demos || {};
API.admin.ai.demos.question = demoQuestionFields;

/* ═════════ لیست ═════════ */
$(function () {
    const $root = $('[data-bk-list]');
    if (!$root.length) return;
    const T = readJSON($root, 'data-t', {});
    const t = tFrom(T);
    const ov = overlay(LS);
    const base = readJSON($root, 'data-rows', []);
    const rows = LOCAL ? ov.merge(base) : base;
    const MIN = parseInt($root.attr('data-min'), 10) || 3;
    const GOOD = parseInt($root.attr('data-good'), 10) || 8;
    const editUrl = (id) => $root.attr('data-edit-url').replace('__ID__', id);
    const PER = 15;
    const F = { q: '', section: 'all', level: 'all', status: 'all', sort: 'newest', page: 1 };
    const range = { a1: [80, 95], a2: [65, 85], b1: [50, 72], b2: [38, 60], c1: [25, 48] };

    // فیلترها از آدرس
    const sp = new URLSearchParams(location.search);
    ['q', 'section', 'level', 'status', 'sort'].forEach((k) => sp.get(k) && (F[k] = sp.get(k)));
    F.page = parseInt(sp.get('page'), 10) || 1;

    const $rows = $root.find('[data-bk-rows]');
    const sync = () => {
        const p = new URLSearchParams();
        ['q', 'section', 'level', 'status', 'sort'].forEach((k) => F[k] && F[k] !== 'all' && !(k === 'sort' && F[k] === 'newest') && p.set(k, F[k]));
        if (F.page > 1) p.set('page', F.page);
        const s = p.toString();
        try {
            history.replaceState(null, '', location.pathname + (s ? '?' + s : ''));
        } catch (e) {
            /* ignore */
        }
    };

    function renderCov() {
        const cov = coverage(rows);
        SECTIONS.forEach((s) => {
            let tot = 0;
            LEVELS.forEach((l) => {
                const n = cov[s][l];
                tot += n;
                const tone = covTone(n, MIN, GOOD);
                $root
                    .find(`.bk-cov__c[data-sec="${s}"][data-lv="${l}"]`)
                    .attr('data-tone', tone)
                    .attr('aria-pressed', F.section === s && F.level === l ? 'true' : 'false')
                    .attr('title', tone === 'bad' ? t('cov.bad', { n: num(MIN) }) : tone === 'warn' ? t('cov.warn', { n: num(GOOD) }) : t('cov.ok'))
                    .text(num(n));
            });
            $root.find(`tr[data-sec="${s}"] [data-tot]`).text(num(tot));
        });
    }

    function renderChips() {
        const cnt = { all: rows.length };
        SECTIONS.forEach((s) => (cnt[s] = rows.filter((r) => r.section === s).length));
        $root.find('[data-bk-secs] button').each(function () {
            const v = $(this).attr('data-v');
            $(this).attr('aria-current', F.section === v ? 'true' : 'false').find('[data-n]').text(num(cnt[v]));
        });
        $root.find('[data-bk-lvs] button').each(function () {
            $(this).attr('aria-current', F.level === $(this).attr('data-v') ? 'true' : 'false');
        });
    }

    function blankQ(text) {
        const frag = document.createDocumentFragment();
        String(text).split('____').forEach((part, i, a) => {
            frag.appendChild(document.createTextNode(part));
            if (i < a.length - 1) frag.appendChild(el('span', 'bk-blank', '____'));
        });
        return frag;
    }

    function statsCell(r) {
        const td = el('td');
        td.setAttribute('data-label', t('col.stats'));
        const s = r.stats || {};
        if (!s.asked) {
            td.appendChild(el('span', 'text-xs text-slate-400', t('stats_none')));
            return td;
        }
        const [lo, hi] = range[r.level] || [0, 100];
        const tone = s.correct_pct > hi ? 'easy' : s.correct_pct < lo ? 'hard' : 'fit';
        const wrap = el('div', 'bk-rate');
        wrap.setAttribute('data-tone', tone);
        const bar = el('i');
        bar.style.setProperty('--p', s.correct_pct + '%');
        wrap.appendChild(bar);
        wrap.appendChild(el('b', null, num(s.correct_pct) + '٪'));
        td.appendChild(wrap);
        td.appendChild(el('small', 'block text-[0.7rem] text-slate-400', t('stats_fmt', { p: num(s.correct_pct), n: num(s.asked) }).replace(/^[^ ]+ /, '')));
        return td;
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
            const sp1 = el('span', 'min-w-0');
            const b = el('b', 'block truncate text-start');
            b.setAttribute('dir', 'ltr');
            b.appendChild(blankQ(r.q));
            const sm = el('small', 'block truncate text-start');
            sm.setAttribute('dir', 'ltr');
            sm.textContent = '#' + r.id + (r.section === 'listening' ? ' · ' + (r.audio ? t('has_audio') : t('no_audio')) : '');
            sp1.appendChild(b);
            sp1.appendChild(sm);
            a.appendChild(sp1);
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
            tr.appendChild(cell(t('col.section'), badge(t('section.' + r.section))));
            tr.appendChild(cell(t('col.level'), badge(t('level.' + r.level), 'info')));
            const ans = el('span', 'block max-w-[14rem] truncate text-xs font-bold text-slate-600', (r.options || [])[parseAnswer(r.answer)] || '—');
            ans.setAttribute('dir', 'ltr');
            tr.appendChild(cell(t('col.answer'), ans));
            tr.appendChild(statsCell(r));
            tr.appendChild(cell(t('col.status'), badge(t('status_label.' + r.status), r.status === 'published' ? 'ok' : '')));
            const upd = el('td');
            upd.setAttribute('data-label', t('col.updated'));
            if (r.updated_at) {
                const tm = el('time', null, ago(r.updated_at));
                upd.appendChild(tm);
            } else upd.textContent = '—';
            tr.appendChild(upd);
            $rows.append(tr);
        });
        const none = list.length === 0;
        $root.find('[data-bk-table]').prop('hidden', none);
        $root.find('[data-bk-empty]').prop('hidden', !none);
        $root.find('[data-bk-pager]').prop('hidden', none);
        $root.find('[data-bk-count]').text(t('result', { n: num(list.length) }) + ' · ' + t('page_of', { a: num(pg.page), b: num(pg.pages) }));
        $root.find('[data-bk-prev]').prop('disabled', pg.page <= 1);
        $root.find('[data-bk-next]').prop('disabled', pg.page >= pg.pages);
    }

    function render() {
        renderCov();
        renderChips();
        renderRows();
        sync();
    }
    $root.find('[data-bk-q]').val(F.q);
    $root.find('[data-bk-status]').val(F.status);
    $root.find('[data-bk-sort]').val(F.sort);

    let tm = 0;
    $root.on('input', '[data-bk-q]', function () {
        clearTimeout(tm);
        const v = $(this).val();
        tm = setTimeout(() => ((F.q = String(v)), (F.page = 1), render()), 150);
    });
    $root.on('change', '[data-bk-status]', function () { F.status = $(this).val(); F.page = 1; render(); });
    $root.on('change', '[data-bk-sort]', function () { F.sort = $(this).val(); F.page = 1; render(); });
    $root.on('click', '[data-bk-secs] button', function () { F.section = $(this).attr('data-v'); F.page = 1; render(); });
    $root.on('click', '[data-bk-lvs] button', function () { F.level = $(this).attr('data-v'); F.page = 1; render(); });
    $root.on('click', '.bk-cov__c', function () {
        const s = $(this).attr('data-sec');
        const l = $(this).attr('data-lv');
        const same = F.section === s && F.level === l;
        F.section = same ? 'all' : s;
        F.level = same ? 'all' : l;
        F.page = 1;
        render();
    });
    $root.on('click', '[data-bk-prev]', () => { F.page--; render(); window.scrollTo({ top: 0 }); });
    $root.on('click', '[data-bk-next]', () => { F.page++; render(); window.scrollTo({ top: 0 }); });
    $root.on('click', '[data-bk-clear]', () => {
        Object.assign(F, { q: '', section: 'all', level: 'all', status: 'all', sort: 'newest', page: 1 });
        $root.find('[data-bk-q]').val('');
        $root.find('[data-bk-status]').val('all');
        $root.find('[data-bk-sort]').val('newest');
        render();
    });
    render();

    /* ── ساخت گروهی با AI ── */
    const $dlg = $('#bk-batch');
    const api = createApi({ $root, kind: 'question', ns: 'question', local: LOCAL, ov, t, send });
    let items = [];
    let busy = false;
    const $err = $dlg.find('[data-bt-err]');
    const $list = $dlg.find('[data-bt-list]');
    const $add = $dlg.find('[data-bt-add]');
    const $genT = $dlg.find('[data-bt-gen-t]');

    $root.on('click', '[data-bk-batch-open]', function () {
        items = [];
        $list.empty().prop('hidden', true);
        $dlg.find('[data-bt-bar], [data-bt-note]').prop('hidden', true);
        $add.prop('hidden', true);
        $err.prop('hidden', true);
        $genT.text(t('bt.go'));
        openDialog('#bk-batch');
    });

    const bv = (k) => $dlg.find('[data-bt="' + k + '"]').val();
    function normalizeItem(it) {
        const options = (it.options || []).map(String);
        const ans = parseAnswer(it.answer);
        const section = SECTIONS.includes(it.section) ? it.section : bv('section');
        return {
            id: null, status: 'draft', section, level: LEVELS.includes(it.level) ? it.level : 'a1', title: TITLES[section], fa: FA[section],
            q: String(it.q || '').trim(), options, answer: ans == null ? 0 : ans, explain: String(it.explain || '').trim(), audio: '', transcript: String(it.transcript || '').trim(),
        };
    }
    function updateSum() {
        const n = $list.find('input[type=checkbox]:checked').length;
        $add.text(t('bt.add', { n: num(n) })).prop('disabled', n === 0 || busy);
        $dlg.find('[data-bt-sum]').text(num(n) + ' / ' + num(items.length));
        $dlg.find('[data-bt-all]').prop('checked', n === items.length && n > 0);
    }
    function showItems() {
        $list.empty();
        items.forEach((q, i) => {
            const bad = !isReady(q);
            const li = el('li', 'bk-bt-item' + (bad ? ' is-bad' : ''));
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = !bad;
            cb.disabled = bad;
            cb.setAttribute('data-i', i);
            cb.className = 'size-4 shrink-0 accent-brand-700';
            const box = el('div', 'min-w-0 flex-1');
            const head = el('div', 'flex flex-wrap items-center gap-1.5');
            const b1 = el('span', 'ad-badge', t('section.' + q.section));
            const b2 = el('span', 'ad-badge', t('level.' + q.level));
            b2.setAttribute('data-tone', 'info');
            head.appendChild(b1);
            head.appendChild(b2);
            if (bad) {
                const b3 = el('span', 'ad-badge', t('bt.invalid'));
                b3.setAttribute('data-tone', 'bad');
                head.appendChild(b3);
            }
            const p = el('p', 'bk-bt-q');
            p.setAttribute('dir', 'ltr');
            p.appendChild(blankQ(q.q));
            const ul = el('ul', 'bk-bt-opts');
            ul.setAttribute('dir', 'ltr');
            q.options.forEach((o, k) => {
                const o1 = el('li', k === q.answer ? 'is-ok' : '', LETTERS[k] + '. ' + o);
                ul.appendChild(o1);
            });
            box.appendChild(head);
            if (q.transcript) {
                const tr = el('p', 'bk-bt-tr', q.transcript);
                tr.setAttribute('dir', 'ltr');
                box.appendChild(tr);
            }
            box.appendChild(p);
            box.appendChild(ul);
            li.appendChild(cb);
            li.appendChild(box);
            $list.append(li);
        });
        const has = items.length > 0;
        $list.prop('hidden', !has);
        $dlg.find('[data-bt-bar]').prop('hidden', !has);
        $dlg.find('[data-bt-note]').prop('hidden', !(has && items.some((q) => q.section === 'listening')));
        $add.prop('hidden', !has);
        updateSum();
    }
    $dlg.on('change', '.bk-bt-item input', updateSum);
    $dlg.on('change', '[data-bt-all]', function () {
        const on = $(this).prop('checked');
        $list.find('input[type=checkbox]:not(:disabled)').prop('checked', on);
        updateSum();
    });

    $dlg.on('click', '[data-bt-gen]', async function () {
        if (busy) return;
        const count = Math.max(1, Math.min(20, parseInt(bv('count'), 10) || 10));
        $dlg.find('[data-bt="count"]').val(count);
        const seeds = { section: bv('section'), level: bv('level'), count: String(count), topic: String(bv('topic') || '').trim() };
        busy = true;
        $dlg.addClass('is-busy');
        $dlg.find('[data-bt-gen]').prop('disabled', true);
        $genT.text(t('bt.busy'));
        $err.prop('hidden', true);
        try {
            const payload = { kind: 'question_batch', seeds, fields: ['items'], note: '', current: {}, locale: document.documentElement.lang };
            const r = await API.admin.ai.request(payload, { url: $root.attr('data-ai-url'), demo: () => demoBatch(seeds) });
            const got = (r && r.fields && r.fields.items) || [];
            items = got.map(normalizeItem);
            if (!items.length) $err.text(t('bt.none')).prop('hidden', false);
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

    function demoBatch(s) {
        const count = parseInt(s.count, 10) || 10;
        const out = [];
        for (let i = 0; i < count; i++) {
            const level = s.level === 'all' ? LEVELS[i % 5] : s.level;
            out.push(Object.assign(demoQuestion(s.section, level, Math.floor(i / (s.level === 'all' ? 5 : 1)) + (i % 2)), { level }));
        }
        return { fields: { items: out }, missing: [] };
    }

    $add.on('click', async function () {
        if (busy) return;
        const chosen = $list.find('input[type=checkbox]:checked').map(function () { return items[parseInt($(this).attr('data-i'), 10)]; }).get();
        if (!chosen.length) return;
        busy = true;
        $add.prop('disabled', true).text(t('bt.adding'));
        try {
            const r = await api.bulk(chosen);
            toast(t('bt.added', { n: num((r && r.saved) || chosen.length) }));
            closeDialog('#bk-batch');
            if (LOCAL && !$root.attr('data-bulk-url')) {
                const merged = ov.merge(base);
                rows.length = 0;
                merged.forEach((x) => rows.push(x));
                F.status = 'draft';
                F.sort = 'newest';
                F.page = 1;
                $root.find('[data-bk-status]').val('draft');
                $root.find('[data-bk-sort]').val('newest');
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
    const $root = $('[data-bk-edit]');
    if (!$root.length) return;
    const T = readJSON($root, 'data-t', {});
    const t = tFrom(T);
    const defaults = readJSON($root, 'data-defaults', { fa: FA, title: TITLES });
    const range = readJSON($root, 'data-range', {});
    const ov = overlay(LS);
    let Q = readJSON($root, 'data-q', {});
    if (LOCAL && Q.id) {
        const saved = ov.get(Q.id);
        if (saved) Q = Object.assign({}, Q, saved);
    }
    Q.options = (Q.options && Q.options.length === 4 ? Q.options : ['', '', '', '']).slice();
    if (parseAnswer(Q.answer) == null) Q.answer = 0;
    Q.answer = parseAnswer(Q.answer);
    const api = createApi({ $root, kind: 'question', ns: 'question', local: LOCAL, ov, t, send });
    const $form = $('#bk-form');
    const f = (n) => $form.find('[data-f="' + n + '"]');
    let dirty = false;
    let busy = false;

    const markDirty = () => {
        dirty = true;
        $root.find('[data-bk-dirty]').prop('hidden', false);
    };
    const say = (m) => $root.find('[data-bk-msg], [data-bk-msg2]').text(m || '');

    /* ← مدل به فرم */
    function fill() {
        f('section').val(Q.section);
        f('level').val(Q.level);
        f('fa').val(Q.fa);
        f('q').val(Q.q);
        f('transcript').val(Q.transcript || '');
        f('explain').val(Q.explain || '');
        f('audio').val(Q.audio || '');
        for (let i = 0; i < 4; i++) f('o' + (i + 1)).val(Q.options[i]);
        setAnswer(Q.answer, false);
        applySection();
    }
    function setAnswer(i, dirtyIt = true) {
        Q.answer = i;
        $form.find('input[name=answer_pick]').each(function () {
            $(this).prop('checked', parseInt($(this).val(), 10) === i);
        });
        $form.find('.bk-opt').each(function () { $(this).toggleClass('is-correct', parseInt($(this).attr('data-i'), 10) === i); });
        f('answer').val(LETTERS[i]);
        if (dirtyIt) markDirty();
    }
    function applySection() {
        const listening = Q.section === 'listening';
        $root.find('[data-bk-only="listening"]').prop('hidden', !listening);
        $root.find('[data-bk-qhint]').text(t(listening ? 'f.q_hint_listening' : 'f.q_hint_blank'));
        const fields = ['q', 'o1', 'o2', 'o3', 'o4', 'answer', 'explain'].concat(listening ? ['transcript', 'audio'] : []);
        $root.find('[data-bk-ai-all]').attr('data-ai-fields', fields.join(','));
        renderAudio();
    }

    /* → فرم به مدل */
    $form.on('input change', '[data-f]', function () {
        const n = $(this).attr('data-f');
        const v = $(this).val();
        if (n === 'section') {
            const prev = Q.section;
            Q.section = v;
            // دستور فارسی/عنوان پیش‌فرض را فقط اگر کاربر عوضشان نکرده بود تغییر بده
            if (!Q.fa || Q.fa === defaults.fa[prev]) { Q.fa = defaults.fa[v]; f('fa').val(Q.fa); }
            Q.title = defaults.title[v];
            applySection();
        } else if (/^o[1-4]$/.test(n)) Q.options[parseInt(n.slice(1), 10) - 1] = v;
        else if (n === 'answer') {
            const idx = parseAnswer(v);
            if (idx != null && idx !== Q.answer) setAnswer(idx, false);
        } else Q[n] = v;
        markDirty();
        refresh();
    });
    $form.on('change', 'input[name=answer_pick]', function () {
        setAnswer(parseInt($(this).val(), 10));
        refresh();
    });
    // نتیجه‌ی AI فیلدها را با trigger('input') می‌نویسد؛ پس از اعمال، همه را یک‌بار از DOM بخوان
    $(document).on('admin:ai-applied', () => { syncFromDom(); refresh(); });
    function syncFromDom() {
        ['q', 'fa', 'transcript', 'explain', 'audio'].forEach((n) => (Q[n] = f(n).val() || ''));
        for (let i = 0; i < 4; i++) Q.options[i] = f('o' + (i + 1)).val() || '';
        const idx = parseAnswer(f('answer').val());
        if (idx != null) setAnswer(idx, false);
        markDirty();
    }

    /* ── پیش‌نمایش ── */
    function blankNode(text) {
        const frag = document.createDocumentFragment();
        String(text).split('____').forEach((part, i, a) => {
            frag.appendChild(document.createTextNode(part));
            if (i < a.length - 1) frag.appendChild(el('span', 'bk-blank', '____'));
        });
        return frag;
    }
    function renderPreview() {
        const $pv = $root.find('[data-bk-pv]');
        $pv.find('[data-pv=title]').text(Q.title || defaults.title[Q.section] || '');
        $pv.find('[data-pv=level]').text(t('level.' + Q.level));
        $pv.find('[data-pv=fa]').text(Q.fa || '');
        const $q = $pv.find('[data-pv=q]').empty();
        if (Q.q.trim()) $q.append(blankNode(Q.q));
        else $q.append(el('span', 'text-slate-300', t('pv.ph_q')));
        const $ul = $pv.find('[data-pv=opts]').empty();
        Q.options.forEach((o, i) => {
            const li = el('li', i === Q.answer ? 'is-ok' : '');
            li.appendChild(el('i', null, LETTERS[i]));
            const s = el('span', o.trim() ? '' : 'text-slate-300', o.trim() || t('pv.ph_opt') + ' ' + LETTERS[i]);
            li.appendChild(s);
            $ul.append(li);
        });
        $root.find('[data-pv=ans]').text(LETTERS[Q.answer] + (Q.options[Q.answer] ? ' · ' + Q.options[Q.answer] : ''));
        $root.find('[data-bk-play]').prop('hidden', Q.section !== 'listening');
    }

    /* ── بررسی (چک‌لیست زنده) ── */
    function renderCheck() {
        const v = validate(Q);
        const $ul = $root.find('[data-bk-ck]').empty();
        const errs = v.filter((x) => x.level === 'error');
        const warns = v.filter((x) => x.level === 'warn');
        if (!v.length) {
            const li = el('li', null, t('ck.ok'));
            li.setAttribute('data-s', 'ok');
            $ul.append(li);
        }
        const group = (arr, s, title) => {
            if (!arr.length) return;
            const h = el('li', 'bk-ck__h', title);
            $ul.append(h);
            arr.forEach((x) => {
                const li = el('li', null, t('v.' + x.code));
                li.setAttribute('data-s', s);
                $ul.append(li);
            });
        };
        group(errs, 'bad', t('ck.errors'));
        group(warns, 'warn', t('ck.warns'));
        $root.find('[data-bk-save=published]').prop('disabled', errs.length > 0);
    }

    /* ── آمار پاسخ‌دهی ── */
    function renderStats() {
        const $b = $root.find('[data-bk-stats-body]');
        if (!$b.length) return;
        const s = Q.stats || {};
        $b.empty();
        if (!s.asked) {
            $b.append(el('p', 'text-sm text-slate-400', t('st.none')));
            return;
        }
        const [lo, hi] = range[Q.level] || [0, 100];
        const tone = s.correct_pct > hi ? 'easy' : s.correct_pct < lo ? 'hard' : 'fit';
        const grid = el('div', 'grid grid-cols-2 gap-2');
        const mk = (l, v) => {
            const d = el('div', 'ad-sub-box');
            d.appendChild(el('span', 'ad-sub-box__l', l));
            d.appendChild(el('b', 'ad-sub-box__v', v));
            return d;
        };
        grid.appendChild(mk(t('st.asked'), num(s.asked)));
        grid.appendChild(mk(t('st.correct'), num(s.correct_pct) + '٪'));
        $b.append(grid);
        $b.append(el('p', 'ad-hint mt-2', t('st.range', { a: num(lo), b: num(hi) })));
        const m = el('p', 'bk-stmsg', t('st.' + tone));
        m.setAttribute('data-tone', tone);
        $b.append(m);
    }

    /* ── صدا: فایل یا خواندن متن با صدای مرورگر ── */
    const audioEl = $root.find('[data-bk-audio]')[0];
    function renderAudio() {
        const url = (Q.audio || '').trim();
        $root.find('[data-bk-up]').toggleClass('has-file', !!url);
        $root.find('[data-bk-up-clear]').prop('hidden', !url);
        const $a = $(audioEl);
        if (url) {
            if ($a.attr('src') !== url) $a.attr('src', url);
            $a.removeClass('hidden');
        } else {
            $a.removeAttr('src').addClass('hidden');
        }
    }
    let speaking = false;
    function stopSpeak() {
        speaking = false;
        try { window.speechSynthesis && window.speechSynthesis.cancel(); } catch (e) { /* ignore */ }
        try { audioEl.pause(); } catch (e) { /* ignore */ }
        $root.find('[data-bk-play]').removeClass('is-on').find('[data-bk-play-t]').text(t('pv.play'));
    }
    $root.on('click', '[data-bk-play]', function () {
        if (speaking) return stopSpeak();
        if ((Q.audio || '').trim()) {
            audioEl.currentTime = 0;
            const p = audioEl.play();
            if (p && p.catch) p.catch(() => {});
            speaking = true;
            audioEl.onended = stopSpeak;
        } else if ((Q.transcript || '').trim() && 'speechSynthesis' in window) {
            const u = new SpeechSynthesisUtterance(Q.transcript);
            u.lang = 'en-US';
            u.onend = stopSpeak;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
            speaking = true;
        } else { toast(t('pv.no_sound'), true); return; }
        $(this).addClass('is-on').find('[data-bk-play-t]').text(t('pv.stop'));
    });
    $root.on('change', '[data-bk-file]', async function () {
        const file = this.files && this.files[0];
        this.value = '';
        if (!file) return;
        const $err = $root.find('[data-bk-up-err]').prop('hidden', true);
        if (!/^audio\//.test(file.type)) { $err.text(t('errors.type')).prop('hidden', false); return; }
        if (file.size > 5 * 1048576) { $err.text(t('errors.size', { n: num(5) })).prop('hidden', false); return; }
        $root.find('[data-bk-up]').addClass('is-busy');
        try {
            const r = await api.upload(file, 'audio');
            Q.audio = r.url;
            f('audio').val(Q.audio);
            markDirty();
            renderAudio();
            refresh();
        } catch (e) {
            $err.text((e && e.message) || t('errors.upload')).prop('hidden', false);
        } finally { $root.find('[data-bk-up]').removeClass('is-busy'); }
    });
    $root.on('click', '[data-bk-up-clear]', () => { Q.audio = ''; f('audio').val(''); markDirty(); renderAudio(); refresh(); });
    $form.on('input', '[data-f=audio]', renderAudio);

    function refresh() {
        renderPreview();
        renderCheck();
    }

    /* ── ذخیره / انتشار / حذف ── */
    function showErrors(errors) {
        Object.keys(errors || {}).forEach((k) => {
            const $f = f(k).first();
            if ($f.length) $f.closest('.ad-field').addClass('has-error').find('.ad-error').first().text([].concat(errors[k])[0]).prop('hidden', false);
        });
    }
    async function save(mode) {
        if (busy) return;
        const status = mode === 'published' ? 'published' : mode === 'draft' ? 'draft' : Q.status || 'draft';
        const v = validate(Q).filter((x) => x.level === 'error');
        const blocking = status === 'published' ? v : v.filter((x) => x.code === 'q_required');
        $form.find('.has-error').removeClass('has-error').find('.ad-error').prop('hidden', true);
        if (blocking.length) {
            const first = blocking[0];
            const $f = f(first.field).first();
            if ($f.length) $f.closest('.ad-field').addClass('has-error').find('.ad-error').first().text(t('v.' + first.code)).prop('hidden', false);
            else if (first.field === 'options') $root.find('[data-bk-opts-err]').text(t('v.' + first.code)).prop('hidden', false);
            toast(t('v.' + first.code), true);
            say(t('fix', { n: num(blocking.length) }));
            (f(first.field).length ? f(first.field) : $form.find('[data-f=o1]')).first().trigger('focus');
            return;
        }
        $root.find('[data-bk-opts-err]').prop('hidden', true);
        busy = true;
        $root.find('[data-bk-save]').prop('disabled', true);
        say(t('saving'));
        const payload = Object.assign(cleanQuestion(Q), { status });
        try {
            const wasNew = !Q.id;
            const r = await api.save(payload, wasNew);
            Q.id = r.id || Q.id;
            Q.status = r.status || status;
            Q.updated_at = Math.floor(Date.now() / 1000);
            dirty = false;
            $root.find('[data-bk-dirty]').prop('hidden', true);
            $root.find('[data-bk-status]').text(t('status_label.' + Q.status)).attr('data-tone', Q.status === 'published' ? 'ok' : '');
            say(t('saved'));
            toast(t(status === 'published' ? 'published_ok' : 'saved'));
            if (mode === 'next') {
                dirty = false;
                location.assign($root.attr('data-new-url') + '?section=' + encodeURIComponent(Q.section) + '&level=' + encodeURIComponent(Q.level));
                return;
            }
            if (wasNew && Q.id) {
                try { history.replaceState(null, '', r.url || $root.attr('data-edit-url').replace('__ID__', Q.id)); } catch (e) { /* ignore */ }
                $root.find('[data-bk-save=next]').remove();
            }
            $root.trigger('admin:question-saved', { id: Q.id, status: Q.status });
        } catch (e) {
            say('');
            toast((e && e.message) || t('errors.save'), true);
            if (e && e.errors) showErrors(e.errors);
        } finally {
            busy = false;
            $root.find('[data-bk-save]').prop('disabled', false);
            renderCheck();
        }
    }
    $root.on('click', '[data-bk-save]', function () { save($(this).attr('data-bk-save')); });

    $root.on('click', '[data-bk-delete-open]', () => openDialog('#bk-delete'));
    $(document).on('click', '[data-bk-delete]', async function () {
        const $b = $(this).prop('disabled', true);
        try {
            await api.remove(Q.id);
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
    renderStats();
    dirty = false;
    $root.find('[data-bk-dirty]').prop('hidden', true);

    // برای تست و بک‌اند
    API.admin.question = API.admin.question || {};
    API.admin.question.model = () => cleanQuestion(Q);
});
