import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, num, fmtDate, ago, fa } from './common';
import { viaEvent, tFrom } from './bank';

/**
 * اشتراک و پرداخت‌ها (/admin/billing و /admin/billing/plans). قرارداد: app/Support/AdminBilling.php
 *
 * 🔌 بک‌اند:
 *   • اقدام روی پرداخت: POST data-action-url (با __ID__) { action:'verify'|'refund', reason } ← { ok:true, status }
 *     یا رویداد لغوشدنی admin:payment-action { action, payment, payload, done(status), fail(msg) }. بدون هر دو: حالت دمو (فقط ظاهر).
 *   • ذخیره‌ی قیمت پلن‌ها: PUT data-save-url { plans:[{id,price,old_price,recommended,active}] }
 *     یا رویداد لغوشدنی admin:plans-save { plans, done(), fail(msg) }.
 *   • خروجی CSV: data-export-url (با همان query فعلی)؛ بدون آن، ردیف‌های همین صفحه CSV می‌شوند.
 */

/* ── توابع خالص (تست‌شده) ───────────────────────────────────── */

/** عدد از متن (ارقام فارسی/عربی و جداکننده‌ها مجازند)؛ خالی/نامعتبر = null */
export function parseNum(v) {
    const s = String(v == null ? '' : v)
        .replace(/[۰-۹]/g, (d) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
        .replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)))
        .replace(/[,٬،\s]/g, '');
    if (!/^\d+$/.test(s)) return null;
    return parseInt(s, 10);
}

/** درصد تخفیف (۰ اگر تخفیف ندارد) */
export const discountPct = (price, old) => (price > 0 && old > price ? Math.round(((old - price) / old) * 100) : 0);
export const perMonth = (price, months) => (months > 0 ? Math.round(price / months / 1000) * 1000 : price);

/**
 * بررسی پلن‌ها؛ ورودی [{id, months, price, old_price, recommended, active}]
 * خروجی { fields:{ id:{price?:code, old?:code} }, global:[code] } — کد = کلید lang: p.errors.*
 */
export function validatePlans(list) {
    const fields = {};
    const global = [];
    list.forEach((p) => {
        const e = {};
        if (!(Number.isInteger(p.price) && p.price > 0)) e.price = 'price';
        else if (p.old_price != null && !(p.old_price > p.price)) e.old = 'old';
        if (Object.keys(e).length) fields[p.id] = e;
    });
    if (!list.some((p) => p.active)) global.push('none_active');
    const rec = list.find((p) => p.recommended);
    if (rec && !rec.active) global.push('rec_inactive');
    return { fields, global, ok: !Object.keys(fields).length && !global.length };
}

/** متن CSV (با BOM اختیاری توسط فراخواننده) */
export function toCsv(rows, head) {
    const esc = (v) => {
        const s = String(v == null ? '' : v);
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    };
    return [head.map(esc).join(',')].concat(rows.map((r) => r.map(esc).join(','))).join('\n');
}

const dt = new Intl.DateTimeFormat(fa ? 'fa-IR-u-ca-persian' : 'en-GB', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const stTone = { success: 'ok', failed: 'bad', cancelled: '', pending: 'warn', refunded: 'info' };

/* ═════════ لیست پرداخت‌ها ═════════ */
$(function () {
    const $root = $('[data-bl-list]');
    if (!$root.length) return;
    const t = tFrom(readJSON($root, 'data-t', {}));
    const $dlg = $('#bl-detail');
    let cur = null; // پرداخت باز
    let $row = null;
    let act = null;

    const money = (n) => num(n) + ' ' + t('toman');
    const set = (k, v) => $dlg.find('[data-bl-d="' + k + '"]').text(v);

    function renderActs() {
        $dlg.find('[data-bl-act=verify]').prop('hidden', !(cur.status === 'pending' || cur.status === 'failed'));
        $dlg.find('[data-bl-act=refund]').prop('hidden', cur.status !== 'success');
        $dlg.find('[data-bl-acts]').prop('hidden', !(cur.status === 'pending' || cur.status === 'failed' || cur.status === 'success'));
    }
    function renderEvents() {
        const $ol = $dlg.find('[data-bl-events]').empty();
        (cur.events || []).forEach((e) => {
            const li = document.createElement('li');
            li.setAttribute('data-k', e.key);
            const b = document.createElement('b');
            b.textContent = t('ev.' + e.key);
            const s = document.createElement('small');
            s.textContent = dt.format(new Date(e.at * 1000)) + (e.note ? ' — ' + e.note : '');
            li.appendChild(b);
            li.appendChild(s);
            $ol.append(li);
        });
    }
    function renderStatus() {
        $dlg.find('[data-bl-d=status]').text(t('status.' + cur.status)).attr('data-tone', stTone[cur.status] || '');
        renderActs();
    }
    function openDetail(p) {
        cur = p;
        set('ref', p.ref);
        set('plan', t('plan.' + p.plan));
        set('amount', money(p.amount));
        set('gateway', p.gateway || '—');
        set('bank_ref', p.bank_ref || '—');
        set('at', dt.format(new Date(p.at * 1000)));
        $dlg.find('[data-bl-row=ends_at]').prop('hidden', !p.ends_at);
        set('ends_at', p.ends_at ? fmtDate(p.ends_at) : '');
        $dlg.find('[data-bl-row=message]').prop('hidden', !p.message);
        set('message', p.message || '');
        $dlg.find('[data-bl-d=user]').text(p.user.name).attr('href', ($root.attr('data-user-url') || '/admin/users/__ID__').replace('__ID__', p.user.id));
        renderStatus();
        renderEvents();
        cancelAct();
        openDialog('#bl-detail');
    }
    $root.on('click', '[data-bl-open]', function () {
        $row = $(this).closest('tr');
        openDetail(readJSON($row, 'data-p', null));
    });

    $dlg.on('click', '[data-bl-copy]', async function () {
        try { await navigator.clipboard.writeText(cur.ref); } catch (e) { /* ignore */ }
        toast(t('detail.copied'));
    });

    function cancelAct() {
        act = null;
        $dlg.find('[data-bl-confirm]').prop('hidden', true);
        $dlg.find('[data-bl-err]').prop('hidden', true);
        $dlg.find('[data-bl-reason]').val('');
        $dlg.find('[data-bl-acts], [data-bl-close-row]').prop('hidden', false);
        if (cur) renderActs();
    }
    $dlg.on('click', '[data-bl-act]', function () {
        act = $(this).attr('data-bl-act');
        $dlg.find('[data-bl-acts], [data-bl-close-row]').prop('hidden', true);
        $dlg.find('[data-bl-lead]').text(t('act.' + act + '_lead'));
        $dlg.find('[data-bl-ok]').text(t('act.' + act)).toggleClass('!bg-rose-600 hover:!bg-rose-700', act === 'refund');
        $dlg.find('[data-bl-confirm]').prop('hidden', false);
        $dlg.find('[data-bl-reason]').trigger('focus');
    });
    $dlg.on('click', '[data-bl-cancel]', cancelAct);

    $dlg.on('click', '[data-bl-ok]', async function () {
        const $btn = $(this);
        const $err = $dlg.find('[data-bl-err]');
        const reason = String($dlg.find('[data-bl-reason]').val() || '').trim();
        if (!reason) { $err.text(t('act.reason_required')).prop('hidden', false); return; }
        const payload = { action: act, reason };
        $btn.prop('disabled', true).text(t('act.busy'));
        $err.prop('hidden', true);
        try {
            const url = $root.attr('data-action-url');
            const ev = await viaEvent('admin:payment-action', { action: act, payment: cur.id, payload });
            let status = act === 'verify' ? 'success' : 'refunded';
            if (ev) {
                if (ev.err !== undefined) throw { message: ev.err };
                if (ev.ok && typeof ev.ok === 'string') status = ev.ok;
            } else if (url) {
                const r = await send(url.replace('__ID__', cur.id), payload);
                if (r && r.status) status = r.status;
            } // بدون آدرس/رویداد: دمو
            const now = Math.floor(Date.now() / 1000);
            cur.status = status;
            cur.events = (cur.events || []).concat([{ at: now, key: act === 'verify' ? 'verified' : 'refunded', note: reason }]);
            if (act === 'verify') cur.message = null;
            if ($row && $row.length) {
                $row.attr('data-p', JSON.stringify(cur));
                $row.find('[data-bl-status]').text(t('status.' + status)).attr('data-tone', stTone[status] || '');
            }
            renderStatus();
            renderEvents();
            cancelAct();
            toast(t(act === 'verify' ? 'act.done_verify' : 'act.done_refund'));
        } catch (e) {
            $err.text((e && e.message) || t('act.error')).prop('hidden', false);
        } finally {
            $btn.prop('disabled', false).text(act ? t('act.' + act) : '');
        }
    });

    /* ── CSV ── */
    $root.on('click', '[data-bl-export]', function () {
        const url = $root.attr('data-export-url');
        if (url) { location.assign(url + location.search); return; }
        const c = (k) => t('col.' + k);
        const rows = $root.find('[data-bl-rows] tr').map(function () { return readJSON($(this), 'data-p', null); }).get().filter(Boolean)
            .map((p) => [p.ref, p.bank_ref || '', p.user.name, p.user.contact, t('plan.' + p.plan), p.amount, t('status.' + p.status), new Date(p.at * 1000).toISOString()]);
        const csv = '﻿' + toCsv(rows, [c('ref'), t('detail.bank_ref'), c('user'), t('detail.user'), c('plan'), c('amount'), c('status'), c('at')]);
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'payments.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500);
    });
});

/* ═════════ قیمت پلن‌ها ═════════ */
$(function () {
    const $root = $('[data-bl-plans]');
    if (!$root.length) return;
    const t = tFrom(readJSON($root, 'data-t', {}));
    const plans = readJSON($root, 'data-plans', []);
    let dirty = false;
    let busy = false;

    const $card = (id) => $root.find('.bl-plan[data-id="' + id + '"]');
    const read = () =>
        plans.map((p) => {
            const $c = $card(p.id);
            const old = $c.find('[data-bl-f=old_price]').val();
            return {
                id: p.id, months: p.months, price: parseNum($c.find('[data-bl-f=price]').val()),
                old_price: String(old).trim() === '' ? null : parseNum(old) == null ? -1 : parseNum(old),
                recommended: $c.find('[data-bl-f=recommended]').prop('checked'), active: $c.find('[data-bl-f=active]').prop('checked'),
            };
        });

    function calc() {
        read().forEach((p) => {
            const $c = $card(p.id);
            const d = discountPct(p.price, p.old_price);
            const bits = [];
            if (p.price > 0) bits.push(t('p.per_month', { n: num(perMonth(p.price, p.months)) }));
            if (d) bits.push(t('p.discount', { p: num(d) }));
            $c.find('[data-bl-calc]').text(bits.join(' · '));
            $c.toggleClass('is-off', !p.active);
        });
    }
    function check(show) {
        const v = validatePlans(read());
        $root.find('.ad-field').removeClass('has-error').find('.ad-error').prop('hidden', true);
        if (show) {
            Object.keys(v.fields).forEach((id) => {
                const e = v.fields[id];
                const mark = (f, code) => $card(id).find('[data-bl-f=' + f + ']').closest('.ad-field').addClass('has-error').find('.ad-error').first().text(t('p.errors.' + code)).prop('hidden', false);
                if (e.price) mark('price', e.price);
                if (e.old) mark('old_price', e.old);
            });
            $root.find('[data-bl-plans-err]').text(v.global.map((c) => t('p.errors.' + c)).join(' ')).prop('hidden', !v.global.length);
        }
        return v;
    }
    const markDirty = () => { dirty = true; $root.find('[data-bl-dirty]').prop('hidden', false); };
    $root.on('input change', '[data-bl-f]', function () {
        calc();
        markDirty();
        if ($root.find('.has-error').length || !$root.find('[data-bl-plans-err]').prop('hidden')) check(true);
    });

    $root.on('click', '[data-bl-save]', async function () {
        if (busy) return;
        const v = check(true);
        if (!v.ok) {
            toast(Object.keys(v.fields).length ? t('p.errors.' + Object.values(v.fields[Object.keys(v.fields)[0]])[0]) : t('p.errors.' + v.global[0]), true);
            return;
        }
        const body = { plans: read().map((p) => ({ id: p.id, price: p.price, old_price: p.old_price, recommended: p.recommended, active: p.active })) };
        busy = true;
        const $b = $(this).prop('disabled', true).text(t('p.saving'));
        try {
            const ev = await viaEvent('admin:plans-save', { plans: body.plans });
            const url = $root.attr('data-save-url');
            if (ev) { if (ev.err !== undefined) throw { message: ev.err }; } else if (url) await send(url, body, 'PUT'); // بدون آدرس/رویداد: دمو
            dirty = false;
            $root.find('[data-bl-dirty]').prop('hidden', true);
            toast(t('p.saved'));
        } catch (e) {
            if (e && e.errors) toast(Object.values(e.errors)[0] + '', true);
            else toast((e && e.message) || t('p.errors.save'), true);
        } finally {
            busy = false;
            $b.prop('disabled', false).text(t('p.save'));
        }
    });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    calc();

    window.Teodel = window.Teodel || {};
    window.Teodel.admin = window.Teodel.admin || {};
    window.Teodel.admin.plans = { read };
});
