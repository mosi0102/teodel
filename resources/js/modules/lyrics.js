import $ from 'jquery';

/**
 * لیریک هم‌زمان با موزیک (مرحله‌ی ۲)
 *
 * - هر خط: data-t (شروع) و data-end (پایان خواندن) به ثانیه. زمان هر کلمه به نسبت طولش بین این دو پخش می‌شود.
 * - خط جاری برجسته می‌شود و کلمه‌ها یکی‌یکی رنگ می‌گیرند؛ خط‌های قبلی کم‌رنگ می‌شوند.
 *   بین دو خط (بخش بی‌کلام) هیچ خطی برجسته نیست.
 * - کلیک روی هر خط: موزیک از همان‌جا پخش می‌شود.
 * - اسکرول خودکار روی خط جاری؛ اگر کاربر خودش اسکرول کند، دنبال‌کردن خاموش می‌شود و دکمه‌ی «خط جاری» می‌آید.
 *
 * وابسته به پخش‌کننده‌ی همان مرحله (player.js): $(el).data('player') → time() / playing() / seek() / play()
 */
const GAP = 0.8; // چند ثانیه بعد از پایان خواندن، خط «تمام‌شده» حساب می‌شود
const DONE_AT = 0.9; // بعد از شنیدن این مقدار از موزیک، مرحله تمام می‌شود

const reduceMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function init(root) {
    const $root = $(root);
    const $panel = $root.closest('[data-step-panel]');
    const playerEl = $panel.find('[data-player]')[0];
    const scroller = $root.find('[data-l-scroll]')[0];
    if (!playerEl || !scroller) return;

    const api = () => $(playerEl).data('player');

    // --- خط‌ها و کلمه‌ها ---
    const lines = $root.find('[data-l-line]').toArray().map((el) => {
        const t = Number(el.dataset.t);
        const end = Number(el.dataset.end) || t + 2;
        const ws = $(el).find('.lw').toArray();
        const total = ws.reduce((n, w) => n + (Number(w.dataset.c) || 1), 0) || 1;
        let acc = 0;
        const words = ws.map((w) => {
            const s = t + ((end - t) * acc) / total;
            acc += Number(w.dataset.c) || 1;
            return { el: w, s };
        });
        return { el, t, end, words };
    });
    if (!lines.length) return;

    let idx = -2; // -1 = هنوز به اولین خط نرسیده
    let phase = '';
    let follow = true;
    let progUntil = 0; // تا این زمان، اسکرول برنامه‌ای است (نه کاربر)
    let done = false;
    let raf = 0;

    const lineAt = (time) => {
        let lo = 0;
        let hi = lines.length - 1;
        let ans = -1;
        while (lo <= hi) {
            const mid = (lo + hi) >> 1;
            if (lines[mid].t <= time) {
                ans = mid;
                lo = mid + 1;
            } else hi = mid - 1;
        }
        return ans;
    };

    function setFollow(on) {
        follow = on;
        $root.attr('data-follow', on ? 'on' : 'off');
        $root.find('[data-l-follow]').attr('aria-pressed', on ? 'true' : 'false');
        $root.find('[data-l-now]').prop('hidden', on);
    }

    function scrollTo(i) {
        const l = lines[i];
        if (!l) return;
        const top = l.el.offsetTop - scroller.clientHeight / 2 + l.el.offsetHeight / 2;
        progUntil = Date.now() + 900;
        scroller.scrollTo({ top: Math.max(top, 0), behavior: reduceMotion() ? 'auto' : 'smooth' });
    }

    function update(time) {
        const i = lineAt(time);
        const cur = lines[i];
        const ph = cur ? (time > cur.end + GAP ? 'gap' : 'now') : '';

        if (i !== idx || ph !== phase) {
            lines.forEach((l, n) => {
                const st = n < i || (n === i && ph === 'gap') ? 'past' : n === i ? 'now' : 'next';
                if (l.el.dataset.state !== st) l.el.dataset.state = st;
                if (st !== 'now') l.words.forEach((w) => w.el.classList.remove('is-sung'));
            });
            if (i !== idx && i >= 0 && follow) scrollTo(i);
            idx = i;
            phase = ph;
        }

        // کلمه‌های خط جاری
        if (cur && ph === 'now') {
            cur.words.forEach((w) => w.el.classList.toggle('is-sung', time >= w.s));
        }
    }

    // --- حلقه‌ی نرم فقط وقتی موزیک پخش می‌شود ---
    function loop() {
        const a = api();
        if (a) update(a.time());
        raf = a && a.playing() && !$panel.prop('hidden') ? requestAnimationFrame(loop) : 0;
    }
    const startLoop = () => { if (!raf) raf = requestAnimationFrame(loop); };

    new MutationObserver(() => {
        if (playerEl.dataset.state === 'playing') startLoop();
    }).observe(playerEl, { attributes: true, attributeFilter: ['data-state'] });

    // پرش/توقف: یک‌بار به‌روز کن
    $(document).on('player:time', (e, d) => {
        if (d.el === playerEl && !raf) update(d.time);
    });

    // --- شنیدن و تمام‌شدن مرحله ---
    $(document).on('player:listened', (e, d) => {
        if (d.el !== playerEl || done) return;
        const f = Math.min(d.ratio / DONE_AT, 1);
        if (window.Teodel && window.Teodel.lesson) {
            window.Teodel.lesson.setStepProgress(f * 0.95);
            if (f >= 1) {
                done = true;
                window.Teodel.lesson.completeStep();
            }
        }
    });

    // --- کلیک روی خط: از همان‌جا پخش شود ---
    $root.on('click', '[data-l-line]', function () {
        const a = api();
        if (!a) return;
        setFollow(true);
        a.seek(Number(this.dataset.t));
        a.play();
        update(Number(this.dataset.t));
        startLoop();
    });

    // --- دنبال‌کردن خودکار ---
    $root.on('click', '[data-l-follow]', () => {
        setFollow(!follow);
        if (follow && idx >= 0) scrollTo(idx);
    });
    $root.on('click', '[data-l-now]', () => {
        setFollow(true);
        if (idx >= 0) scrollTo(idx);
    });
    // اسکرول دستی کاربر → دنبال‌کردن خاموش
    $(scroller).on('wheel touchmove', () => { if (follow) setFollow(false); });
    $(scroller).on('scroll', () => {
        if (Date.now() <= progUntil) progUntil = Date.now() + 150; // اسکرول نرمِ برنامه هنوز ادامه دارد
        else if (follow) setFollow(false);
    });
    $(scroller).on('keydown', (e) => {
        if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(e.key) && follow) setFollow(false);
    });

    // --- ترجمه (فقط اگر ترجمه داده شده باشد) ---
    $root.on('click', '[data-l-tr]', function () {
        const on = $root.attr('data-tr') !== 'on';
        $root.attr('data-tr', on ? 'on' : 'off');
        $(this).attr('aria-pressed', on ? 'true' : 'false');
    });

    // --- وقتی به این مرحله می‌آییم: روی خط جاری بایست ---
    $(document).on('lesson:step-change', (e, d) => {
        if (!$panel.prop('hidden') && api()) {
            update(api().time());
            if (idx >= 0 && follow) setTimeout(() => scrollTo(idx), 60);
        }
    });

    update(0);
}

$(function () {
    $('[data-lyrics]').each(function () { init(this); });
});
