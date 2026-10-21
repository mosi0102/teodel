import $ from 'jquery';
import { num, fmtDay, readJSON } from './common';

/**
 * داشبورد مدیر: سلام بر اساس ساعت، نمودار خطی ۳۰ روز (فروش / ثبت‌نام) با راهنمای شناور،
 * پر شدن نوارها. داده: data-series روی ریشه (قرارداد: app/Support/AdminDashboard.php).
 */
export function greetKey(h) {
    return h < 5 ? 'night' : h < 12 ? 'morning' : h < 17 ? 'noon' : h < 21 ? 'evening' : 'night';
}

/** نقاط نمودار در یک ویوباکس ۱۰۰×۴۰ ( y معکوس ) */
export function plot(values) {
    const max = Math.max(1, ...values);
    const step = values.length > 1 ? 100 / (values.length - 1) : 0;
    return values.map((v, i) => [i * step, 38 - (v / max) * 34]);
}

$(function () {
    const $root = $('[data-ad-dash]');
    if (!$root.length) return;

    const greets = readJSON($root, 'data-greets', {});
    const $h = $root.find('[data-ad-hello]');
    const tpl = readJSON($root, 'data-hour-greet', ':greet، :name');
    const g = greets[greetKey(new Date().getHours())];
    if (g) $h.text(tpl.replace(':greet', g).replace(':name', $root.attr('data-name') || ''));

    const series = readJSON($root, 'data-series', { revenue: [], signups: [] });
    const t = readJSON($root, 'data-t', {});
    const $chart = $root.find('[data-ad-chart]');
    const $dot = $chart.find('[data-ad-dot]');
    const $tip = $chart.find('[data-ad-tip]');
    const SVG = 'http://www.w3.org/2000/svg';
    let kind = 'revenue';
    let pts = [];
    let rows = [];

    const val = (r) => (kind === 'revenue' ? r.amount : r.count);
    const label = (n) => (kind === 'revenue' ? t.label_revenue : t.label_signups).replace(':n', num(n));
    const short = (n) => (kind === 'revenue' && n >= 1e6 ? num(n / 1e6) + 'M' : num(n));

    function draw() {
        rows = series[kind] || [];
        const vals = rows.map(val);
        pts = plot(vals);
        $chart.find('svg').remove();
        if (!pts.length) return;
        const rtl = document.documentElement.dir === 'rtl';
        // در RTL قدیمی‌ترین روز سمت راست است
        const px = (x) => (rtl ? 100 - x : x);
        const line = pts.map(([x, y], i) => (i ? 'L' : 'M') + px(x).toFixed(2) + ' ' + y.toFixed(2)).join(' ');
        const area = line + ` L${px(100)} 40 L${px(0)} 40 Z`;
        const svg = document.createElementNS(SVG, 'svg');
        svg.setAttribute('viewBox', '0 0 100 40');
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('aria-hidden', 'true');
        [10, 20, 30].forEach((y) => {
            const l = document.createElementNS(SVG, 'line');
            l.setAttribute('x1', 0); l.setAttribute('x2', 100); l.setAttribute('y1', y); l.setAttribute('y2', y);
            l.setAttribute('class', 'ad-chart__grid');
            svg.appendChild(l);
        });
        const a = document.createElementNS(SVG, 'path');
        a.setAttribute('d', area); a.setAttribute('class', 'ad-chart__area');
        const p = document.createElementNS(SVG, 'path');
        p.setAttribute('d', line); p.setAttribute('class', 'ad-chart__line');
        svg.appendChild(a); svg.appendChild(p);
        $chart.prepend(svg);

        const total = vals.reduce((s, v) => s + v, 0);
        const best = rows[vals.indexOf(Math.max(...vals))];
        $root.find('[data-ad-sum=total]').text(short(total));
        $root.find('[data-ad-sum=avg]').text(short(total / vals.length));
        $root.find('[data-ad-sum=best]').text(best ? fmtDay(best.at) : '—');
        $root.find('[data-ad-x]').html(`<span>${fmtDay(rows[0].at)}</span><span>${fmtDay(rows[Math.floor(rows.length / 2)].at)}</span><span>${fmtDay(rows[rows.length - 1].at)}</span>`);
        $chart.attr('aria-label', t.title + ': ' + label(total));
    }

    function hover(clientX) {
        if (!pts.length) return;
        const box = $chart[0].getBoundingClientRect();
        const rtl = document.documentElement.dir === 'rtl';
        let f = (clientX - box.left) / box.width;
        if (rtl) f = 1 - f;
        const i = Math.max(0, Math.min(pts.length - 1, Math.round(f * (pts.length - 1))));
        const [x, y] = pts[i];
        const left = ((rtl ? 100 - x : x) / 100) * box.width;
        const top = (y / 40) * box.height;
        $dot.css({ left, top });
        $tip.css({ left: Math.max(70, Math.min(box.width - 70, left)), top }).html(`${label(val(rows[i]))}<small>${fmtDay(rows[i].at)}</small>`);
        $chart.addClass('is-hover');
    }
    $chart.on('pointermove pointerdown', (e) => hover(e.clientX));
    $chart.on('pointerleave pointerup pointercancel', () => $chart.removeClass('is-hover'));

    $root.on('click', '[data-ad-series]', function () {
        kind = $(this).attr('data-ad-series');
        $root.find('[data-ad-series]').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');
        draw();
    });
    draw();

    // پر شدن نوارها
    requestAnimationFrame(() => requestAnimationFrame(() => {
        $root.find('.ad-mix i, .ad-meter i').each(function () {
            $(this).css('width', $(this).attr('data-w') + '%');
        });
    }));
});
