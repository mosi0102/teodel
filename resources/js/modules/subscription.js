import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/**
 * خرید اشتراک (/subscription): انتخاب پلن ← مودال تأیید ← شروع پرداخت ← (درگاه) ← /subscription/result.
 * نتیجه‌ی پرداخت (/subscription/result): تاریخ شمسی و کپی کد پیگیری.
 * قرارداد داده: app/Support/Subscription.php
 *
 * 🔌 بک‌اند — شروع پرداخت (بعد از زدن «پرداخت» در مودال)، یکی از این‌ها:
 *   ۱) آدرس: data-checkout-url روی ریشه ← POST JSON { plan: '6m' } + CSRF
 *        ← 200 { redirect: 'https://درگاه…' }   ← 4xx { message }
 *   ۲) رویداد لغوشدنی:
 *        $(document).on('subscription:checkout', (e, { plan, done, fail }) => { e.preventDefault(); … done(urlدرگاه) | fail('پیام') })
 *   ۳) جایگزین کردن:  Teodel.subscription.api.checkout = async (planId) => ({ redirect: '…' })   (خطا: throw { message })
 *   اگر هیچ‌کدام نبود، حالت دمو مستقیم به صفحه‌ی نتیجه‌ی «موفق» می‌رود.
 *   مبلغ را سرور از روی planId حساب کند؛ فرانت فقط شناسه‌ی پلن می‌فرستد.
 */

/** درصد تخفیف (۰ اگر قیمت قبلی نبود) */
export function discountPercent(price, oldPrice) {
    return oldPrice && oldPrice > price ? Math.round((1 - price / oldPrice) * 100) : 0;
}

/** افزودن ماه به یک زمان (ثانیه‌ی یونیکس) — پایان ماه را حفظ می‌کند (۳۱ مرداد + ۱ ماه ≠ پرش به ماه بعدی) */
export function addMonths(ts, months) {
    const d = new Date(ts * 1000);
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + months);
    const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, last));
    return Math.floor(d.getTime() / 1000);
}

const fa = document.documentElement.lang === 'fa';
const lc = fa ? 'fa-IR-u-ca-persian' : 'en-GB';
const nf = new Intl.NumberFormat(fa ? 'fa-IR' : 'en-GB', { useGrouping: true });
const money = (n) => nf.format(n);
const fmtD = (ts) => new Intl.DateTimeFormat(lc, { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(ts * 1000));
const fmtDT = (ts) =>
    new Intl.DateTimeFormat(lc, { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(ts * 1000)) +
    (fa ? '، ' : ', ') +
    new Intl.DateTimeFormat(fa ? 'fa-IR' : 'en-GB', { hour: '2-digit', minute: '2-digit' }).format(new Date(ts * 1000));

const readJSON = ($el, attr, fallback) => {
    try {
        return JSON.parse($el.attr(attr) || '') || fallback;
    } catch (e) {
        return fallback;
    }
};
const tFrom = (TT) => (path, rep) => {
    let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
    if (typeof v !== 'string') return path;
    if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
    return v;
};

/* ── صفحه‌ی لیست پلن‌ها ────────────────────────────────────────── */
$(function () {
    const $root = $('[data-sb]');
    if (!$root.length) return;

    const plans = readJSON($root, 'data-plans', []);
    const t = tFrom(readJSON($root, 'data-t', {}));
    const $dlg = $('#sb-confirm');
    const $pay = $dlg.find('[data-sb-pay]');
    const $err = $dlg.find('[data-sb-err]');
    let picked = null;
    let busy = false;

    const API = (window.Teodel = window.Teodel || {});
    API.subscription = API.subscription || {};
    API.subscription.api = API.subscription.api || {};

    // پیش‌فرض: آدرس چک‌اوت یا دمو
    if (!API.subscription.api.checkout) {
        API.subscription.api.checkout = async (planId) => {
            const url = $root.attr('data-checkout-url');
            if (!url) return { redirect: `${$root.attr('data-result-url')}?status=success&plan=${encodeURIComponent(planId)}` };
            const csrf = $('meta[name="csrf-token"]').attr('content') || '';
            let res;
            try {
                res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ plan: planId }),
                });
            } catch (e) {
                throw { message: t('errors.network') };
            }
            let data = {};
            try {
                data = await res.json();
            } catch (e) {
                /* بدنه‌ی خالی */
            }
            if (!res.ok || !data.redirect) throw { message: data.message || t('errors.failed') };
            return data;
        };
    }

    function setBusy(on) {
        busy = on;
        $pay.prop('disabled', on).attr('aria-busy', on ? 'true' : 'false');
        $dlg.find('[data-sb-close]').prop('disabled', on);
        $pay.find('[data-sb-pay-t]').text(on ? t('confirm.busy') : t('confirm.pay', { price: money(picked.price) }));
        $dlg.toggleClass('is-busy', on);
    }

    function showErr(msg) {
        $err.text(msg).prop('hidden', !msg);
    }

    function fill(p) {
        const now = Math.floor(Date.now() / 1000);
        const off = discountPercent(p.price, p.old_price);
        $dlg.find('[data-sb-s=name]').text(t('plans.' + p.id + '.name'));
        $dlg.find('[data-sb-s=period]').text(t('confirm.from_to', { from: fmtD(now), to: fmtD(addMonths(now, p.months)) }));
        $dlg.find('[data-sb-row=old]').prop('hidden', !p.old_price);
        $dlg.find('[data-sb-row=disc]').prop('hidden', !off);
        $dlg.find('[data-sb-s=old]').text(p.old_price ? money(p.old_price) + ' ' + t('currency') : '');
        $dlg.find('[data-sb-s=disc]').text(off ? '−' + money(p.old_price - p.price) + ' ' + t('currency') + ' (' + t('off', { n: money(off) }) + ')' : '');
        $dlg.find('[data-sb-s=total]').text(money(p.price));
        showErr('');
        setBusy(false);
    }

    $root.on('click', '[data-sb-buy]', function () {
        picked = plans.find((p) => p.id === $(this).attr('data-sb-buy'));
        if (!picked) return;
        fill(picked);
        openDialog('#sb-confirm');
        $(document).trigger('subscription:select', { plan: picked.id });
        setTimeout(() => $pay.trigger('focus'), 80);
    });

    $dlg.on('click', '[data-sb-close]', function () {
        if (!busy) closeDialog('#sb-confirm');
    });
    // Esc / کلیک روی پس‌زمینه در حین انتقال، مودال را نبندد
    document.addEventListener(
        'keydown',
        (e) => {
            if (busy && e.key === 'Escape' && $dlg.hasClass('is-open')) e.stopImmediatePropagation();
        },
        true
    );

    $pay.on('click', async function () {
        if (busy || !picked) return;
        showErr('');
        setBusy(true);
        let settled = false;
        const go = (url) => {
            settled = true;
            window.location.assign(url);
        };
        const fail = (msg) => {
            settled = true;
            showErr(msg || t('errors.failed'));
            setBusy(false);
        };
        const ev = $.Event('subscription:checkout');
        $(document).trigger(ev, { plan: picked.id, done: go, fail });
        if (ev.isDefaultPrevented()) return; // بک‌اند خودش done/fail را صدا می‌زند
        try {
            const r = await API.subscription.api.checkout(picked.id);
            if (!settled && r && r.redirect) go(r.redirect);
            else if (!settled) fail();
        } catch (e) {
            fail(e && e.message);
        }
    });

    // برگشت با دکمه‌ی back از درگاه (bfcache): دکمه از حالت «در حال انتقال» دربیاید
    window.addEventListener('pageshow', (e) => {
        if (e.persisted && busy) setBusy(false);
    });
});

/* ── صفحه‌ی نتیجه‌ی پرداخت ───────────────────────────────────── */
$(function () {
    const $r = $('[data-sb-result]');
    if (!$r.length) return;
    const t = tFrom(readJSON($r, 'data-t', {}));

    $r.find('[data-sr-ts]').each(function () {
        const ts = parseInt($(this).attr('data-sr-ts'), 10);
        if (!ts) return;
        $(this).text($(this).attr('data-sr-fmt') === 'd' ? fmtD(ts) : fmtDT(ts));
    });

    const $toast = $('[data-sr-toast]');
    let timer = 0;
    function toast(text) {
        $toast.text(text).prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth; // ری‌استارت انیمیشن
        $toast.addClass('is-in');
        clearTimeout(timer);
        timer = setTimeout(() => $toast.prop('hidden', true), 2200);
    }
    $r.on('click', '[data-sr-copy]', async function () {
        const code = $r.find('[data-sr-ref]').text().trim();
        try {
            await navigator.clipboard.writeText(code);
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = code;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                /* ignore */
            }
            ta.remove();
        }
        toast(t('result.copied'));
    });
});
