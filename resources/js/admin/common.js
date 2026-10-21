import $ from 'jquery';

export const fa = document.documentElement.lang === 'fa';
const loc = fa ? 'fa-IR' : 'en-GB';
const nf = new Intl.NumberFormat(loc);

export const num = (n) => nf.format(Math.round(n));
export const fmtDate = (ts) => new Intl.DateTimeFormat(fa ? 'fa-IR-u-ca-persian' : 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(ts * 1000));
/** تاریخ کوتاه عددی برای جدول‌ها: ۱۴۰۵/۰۷/۰۷ */
export const fmtShort = (ts) => new Intl.DateTimeFormat(fa ? 'fa-IR-u-ca-persian' : 'en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(ts * 1000));
export const fmtDay = (ts) => new Intl.DateTimeFormat(fa ? 'fa-IR-u-ca-persian' : 'en-GB', { month: 'short', day: 'numeric' }).format(new Date(ts * 1000));

/** «۵ دقیقه پیش» با Intl.RelativeTimeFormat */
export function ago(ts, now = Date.now() / 1000) {
    const rtf = new Intl.RelativeTimeFormat(loc, { numeric: 'auto' });
    const s = Math.round(ts - now);
    const a = Math.abs(s);
    if (a < 60) return rtf.format(0, 'second');
    if (a < 3600) return rtf.format(Math.round(s / 60), 'minute');
    if (a < 86400) return rtf.format(Math.round(s / 3600), 'hour');
    if (a < 86400 * 30) return rtf.format(Math.round(s / 86400), 'day');
    return fmtDate(ts);
}

export function readJSON($el, attr, fallback) {
    try {
        return JSON.parse($el.attr(attr) || '') || fallback;
    } catch (e) {
        return fallback;
    }
}

/** پیام کوتاه پایین صفحه (.pf-toast) */
let timer = 0;
export function toast(text, bad = false) {
    const $t = $('[data-ad-toast]');
    if (!$t.length) return;
    $t.text(text).toggleClass('is-bad', bad).removeClass('is-in');
    void $t[0].offsetWidth;
    $t.addClass('is-in').show();
    clearTimeout(timer);
    timer = setTimeout(() => $t.hide().removeClass('is-in'), 2600);
}

export function csrf() {
    return $('meta[name="csrf-token"]').attr('content') || '';
}

/** POST/PATCH JSON با CSRF؛ خطا: throw { message, errors } */
export async function send(url, body, method = 'POST') {
    let res;
    try {
        res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
    } catch (e) {
        throw { message: '' };
    }
    let data = {};
    try {
        data = await res.json();
    } catch (e) {
        /* بدنه‌ی خالی */
    }
    if (!res.ok) throw { message: data.message || '', errors: data.errors || null };
    return data;
}
