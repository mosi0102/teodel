import $ from 'jquery';

/**
 * داشبورد (/dashboard): سلام بر اساس ساعت، انیمیشن حلقه‌ها و نوارها، نمودار هفته، نام روزها و تغییر هدف روزانه.
 * قرارداد داده: app/Support/Dashboard.php
 *
 * 🔌 بک‌اند — هدف روزانه (گزینه‌ها: data-goal-options):
 *   • آدرس: data-goal-url روی ریشه ← POST JSON { minutes } + CSRF
 *   • یا رویداد:  $(document).on('dashboard:goal', (e, { minutes }) => { … })
 * LOCAL: در دمو هدف انتخاب‌شده در localStorage می‌ماند. با اتصال بک‌اند false کنید (هدف از سرور می‌آید).
 */
const LOCAL = true;
const KEY = 'teodel:dashboard:goal';

/** بازه‌ی ساعت → کلید سلام */
export function greetKey(hour) {
    if (hour >= 5 && hour < 12) return 'morning';
    if (hour >= 12 && hour < 16) return 'noon';
    if (hour >= 16 && hour < 20) return 'evening';
    return 'night';
}

/** درصد هدف (۰ تا ۱۰۰) */
export function goalPercent(today, goal) {
    return goal > 0 ? Math.min(100, Math.round((today / goal) * 100)) : 0;
}

$(function () {
    const $root = $('[data-db]');
    if (!$root.length) return;

    const fa = document.documentElement.lang === 'fa';
    const lc = fa ? 'fa-IR' : 'en-GB';
    const nf = new Intl.NumberFormat(lc, { useGrouping: false });
    const num = (n) => nf.format(n);
    const pct = (n) => num(n) + (fa ? '٪' : '%');
    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let TT = {};
    try {
        TT = JSON.parse($root.attr('data-t') || '{}');
    } catch (e) {
        TT = {};
    }
    const t = (path, rep) => {
        let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };

    /* سلام */
    const $hello = $root.find('[data-db-hello]');
    if ($hello.length) $hello.text(t('hello', { greet: t('greet.' + greetKey(new Date().getHours())), name: $hello.attr('data-name') || '' }));

    /* نام روزها از روی ts (روز هفته‌ی کوتاه) */
    const dayFmt = new Intl.DateTimeFormat(fa ? 'fa-IR-u-ca-persian' : 'en-GB', { weekday: 'short' });
    $root.find('[data-ts]').each(function () {
        const ts = parseInt($(this).attr('data-ts'), 10);
        if (!ts) return;
        const label = dayFmt.format(new Date(ts * 1000));
        $(this).find('[data-db-day]').text(label);
        if ($(this).is('.is-today')) $(this).find('[data-db-day]').text(t('week.today'));
        const min = $(this).attr('data-min');
        if (min != null) $(this).attr({ title: t('week.bar', { day: label, n: num(min) }) });
    });

    /* انیمیشن نوارها، حلقه‌ها، ستون‌های نمودار و شمارنده‌ها (بعد از دو فریم تا transition دیده شود) */
    function fill() {
        $root.find('[data-db-bar]').each(function () {
            $(this).css('width', Math.max(0, Math.min(100, parseFloat($(this).attr('data-db-bar')) || 0)) + '%');
        });
        $root.find('[data-db-h]').each(function () {
            $(this).css('height', parseFloat($(this).attr('data-db-h')) + '%');
        });
        const $ring = $root.find('[data-db-ring]');
        $ring.css('--p', $ring.attr('data-db-ring'));
    }
    requestAnimationFrame(() => requestAnimationFrame(fill));

    $root.find('[data-db-num]').each(function () {
        const to = parseFloat($(this).attr('data-db-num')) || 0;
        if (reduce || to < 2) return;
        const el = this;
        const t0 = performance.now();
        const dur = 700;
        (function step(now) {
            const k = Math.min(1, (now - t0) / dur);
            el.textContent = num(Math.round(to * (1 - Math.pow(1 - k, 3))));
            if (k < 1) requestAnimationFrame(step);
        })(t0);
    });

    /* هدف روزانه */
    const $goal = $root.find('.db-goal');
    const today = parseInt($goal.attr('data-today'), 10) || 0;
    let goal = parseInt($goal.attr('data-goal'), 10) || 0;
    const options = ($root.attr('data-goal-options') || '10,20,30,45').split(',').map((x) => parseInt(x, 10));

    if (LOCAL) {
        try {
            const saved = parseInt(localStorage.getItem(KEY) || '', 10);
            if (options.includes(saved)) goal = saved;
        } catch (e) {
            /* حالت خصوصی */
        }
    }

    function paintGoal(animate) {
        const p = goalPercent(today, goal);
        $root.find('[data-db-goal-of]').text(t('goal.of', { a: num(today), b: num(goal) }));
        $root.find('[data-db-goal-pct]').text(pct(p));
        $root.find('[data-db-goal-msg]').text(today >= goal ? t('goal.done') : t('goal.left', { n: num(goal - today) }));
        $root.find('[data-db-ring]').attr({ 'data-db-ring': p, 'aria-label': t('goal.of', { a: num(today), b: num(goal) }) });
        if (animate !== false) $root.find('[data-db-ring]').css('--p', p);
        $root.find('[data-db-goalopts] [data-m]').each(function () {
            const on = parseInt($(this).attr('data-m'), 10) === goal;
            $(this).attr({ 'aria-checked': on ? 'true' : 'false', tabindex: on ? 0 : -1 });
        });
    }
    paintGoal(false);

    const $opts = $root.find('[data-db-goalopts]');
    function choose(m) {
        if (m === goal) return;
        goal = m;
        paintGoal();
        if (LOCAL) {
            try {
                localStorage.setItem(KEY, String(m));
            } catch (e) {
                /* حالت خصوصی */
            }
        }
        $(document).trigger('dashboard:goal', { minutes: m });
        const url = $root.attr('data-goal-url');
        if (url) {
            const csrf = $('meta[name="csrf-token"]').attr('content') || '';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify({ minutes: m }),
            }).catch(() => {});
        }
    }
    $opts.on('click', '[data-m]', function () {
        choose(parseInt($(this).attr('data-m'), 10));
    });
    $opts.on('keydown', '[data-m]', function (e) {
        if (!/^Arrow/.test(e.key)) return;
        e.preventDefault();
        const $all = $opts.find('[data-m]');
        const rtl = document.documentElement.dir === 'rtl';
        const fwd = e.key === 'ArrowDown' || (e.key === 'ArrowRight') !== rtl;
        const n = $all.eq(($all.index(this) + (fwd ? 1 : -1) + $all.length) % $all.length);
        n.trigger('focus').trigger('click');
    });
});
