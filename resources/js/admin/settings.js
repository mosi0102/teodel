import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast, num } from './common';
import { tFrom } from './bank';

/**
 * تنظیمات (/admin/settings). قرارداد داده و ذخیره: app/Support/AdminSettings.php
 *
 * 🔌 بک‌اند (یکی از دو راه):
 *   • PUT data-save-url { settings:{ 'general.site_name_fa':…, … }, changed:[کلیدهای تغییرکرده] } ← 200 { ok:true } | 422 { message, errors:{ 'کلید':['…'] } }
 *   • رویداد لغوشدنی admin:settings-save { settings, changed, done(), fail(msg, errors) }
 *   بدون هر دو: حالت دمو (فقط ظاهر؛ ذخیره نمی‌شود).
 *   رازها (ai.key، payments.merchant) فقط وقتی مدیر مقدار تازه نوشته در settings می‌آیند.
 */

/* ── توابع خالص (تست‌شده؛ همان قانون سمت سرور) ───────────────── */

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** بررسی مقدارها (کلید «گروه.کلید») → { کلید: { code, a?, b?, n? } } */
export function validateSettings(v) {
    const e = {};
    const str = (k) => String(v[k] == null ? '' : v[k]).trim();
    ['general.site_name_fa', 'general.site_name_en'].forEach((k) => {
        if (!str(k)) e[k] = { code: 'required' };
        else if (str(k).length > 80) e[k] = { code: 'max', n: 80 };
    });
    if (!str('general.support_email')) e['general.support_email'] = { code: 'required' };
    else if (!EMAIL.test(str('general.support_email'))) e['general.support_email'] = { code: 'email' };
    if (str('general.maintenance_msg').length > 300) e['general.maintenance_msg'] = { code: 'max', n: 300 };
    if (v['ai.enabled'] && !str('ai.model')) e['ai.model'] = { code: 'required' };
    const anyNotify = ['notify.new_ticket', 'notify.new_report', 'notify.failed_payment'].some((k) => v[k]);
    if (str('notify.email') ? !EMAIL.test(str('notify.email')) : anyNotify) e['notify.email'] = { code: str('notify.email') ? 'email' : 'required' };
    const nums = {
        'access.free_songs': [0, 20, 1], 'access.free_steps': [1, 8, 1],
        'level_test.questions_per_section': [5, 50, 5], 'level_test.time_limit_min': [0, 120, 1], 'level_test.retake_days': [0, 90, 1],
        'ai.batch_max': [5, 30, 1], 'referral.inviter_points': [0, 1000, 1], 'referral.invitee_days': [0, 30, 1],
    };
    Object.keys(nums).forEach((k) => {
        const [min, max, step] = nums[k];
        const raw = v[k];
        const n = typeof raw === 'number' ? raw : /^-?\d+$/.test(String(raw).trim()) ? parseInt(raw, 10) : NaN;
        if (!Number.isFinite(n) || n < min || n > max) e[k] = { code: 'range', a: min, b: max };
        else if (step > 1 && n % step !== 0) e[k] = { code: 'step' };
    });
    return e;
}

/** کلیدهایی که با مقدار اولیه فرق دارند (راز: فقط اگر چیزی نوشته شده) */
export function diffKeys(initial, now, secrets = []) {
    const out = [];
    Object.keys(now).forEach((k) => {
        if (secrets.includes(k)) {
            if (String(now[k] || '').trim() !== '') out.push(k);
        } else if (String(now[k]) !== String(initial[k])) out.push(k);
    });
    return out;
}

/** آیا بانک سؤال برای «سؤال هر بخش» کافی است؟ cover = { بخش: کمترین تعداد منتشرشده در هر سطح } → بخش‌های کم‌مایه */
export function coverShort(perSection, cover) {
    const need = Math.floor(Number(perSection) / 5);
    const short = [];
    Object.keys(cover || {}).forEach((s) => {
        if (cover[s] < need) short.push({ section: s, have: cover[s], need });
    });
    return { need, short };
}

/** آبجکت تودرتوی current() → کلیدهای تخت 'گروه.کلید' */
export function flatten(s) {
    const o = {};
    Object.keys(s || {}).forEach((g) => Object.keys(s[g] || {}).forEach((k) => (o[g + '.' + k] = s[g][k])));
    return o;
}

/* ═════ صفحه ═════ */
$(function () {
    const $root = $('[data-st]');
    if (!$root.length) return;
    const t = tFrom(readJSON($root, 'data-t', {}));
    const cover = readJSON($root, 'data-cover', {});
    let base = flatten(readJSON($root, 'data-s', {}));
    const $f = (k) => $root.find('[data-k="' + k + '"]');
    const keys = $root.find('[data-k]').map(function () { return $(this).attr('data-k'); }).get();
    const secrets = $root.find('[data-secret]').map(function () { return $(this).attr('data-k'); }).get();
    let busy = false;

    const read = (k) => {
        const $e = $f(k);
        if ($e.is(':checkbox')) return $e.prop('checked');
        if ($e.attr('type') === 'number') return $e.val() === '' ? '' : parseInt($e.val(), 10);
        return $e.val();
    };
    const values = () => {
        const o = {};
        keys.forEach((k) => (o[k] = read(k)));
        return o;
    };
    const write = (k, v) => {
        const $e = $f(k);
        if ($e.is(':checkbox')) $e.prop('checked', !!v);
        else $e.val(v == null ? '' : v);
    };

    function secretUi() {
        $root.find('[data-secret]').each(function () {
            const flag = $(this).attr('data-secret');
            const isSet = !!base[flag];
            $(this).attr('placeholder', isSet ? t('secret.ph_set') : t('secret.ph_new'));
            $root.find('[data-st-secret-badge="' + flag + '"]').text(isSet ? t('secret.set') : t('secret.unset')).attr('data-tone', isSet ? 'ok' : 'warn');
        });
    }

    function fill() {
        keys.forEach((k) => (secrets.includes(k) ? write(k, '') : write(k, base[k])));
        secretUi();
        ui();
    }

    function clearErrors() {
        $root.find('.has-error').removeClass('has-error').find('.ad-error').prop('hidden', true).text('');
    }
    function showError(k, msg) {
        const $w = $f(k).closest('.ad-field');
        $w.addClass('has-error').find('.ad-error').first().text(msg).prop('hidden', false);
    }
    const msgOf = (e) => t('v.' + e.code, { a: num(e.a || 0), b: num(e.b || 0), n: num(e.n || 0) });

    /* وابسته‌ها: پیام نگهداری، AI، پوشش بانک، نوار ذخیره */
    function coverUi() {
        const per = read('level_test.questions_per_section');
        const $c = $root.find('[data-st-cover]');
        if (typeof per !== 'number' || !Number.isFinite(per)) return $c.prop('hidden', true);
        const r = coverShort(per, cover);
        $c.prop('hidden', false).attr('data-tone', r.short.length ? 'warn' : 'ok');
        if (!r.short.length) {
            const min = Object.values(cover).length ? Math.min(...Object.values(cover)) : 0;
            $c.text(t('cover.ok', { m: num(min) }));
        } else {
            const w = r.short[0];
            $c.text(t('cover.short', { n: num(r.need), section: t('sections.' + w.section), m: num(w.have) }));
        }
        $c.append(document.createTextNode(' '));
        $c.append($('<a>').attr('href', $root.attr('data-bank-url') || '/admin/placement').text(t('cover.link')));
    }
    function ui() {
        $root.find('[data-st-maint-msg]').prop('hidden', !read('general.maintenance'));
        const aiOn = read('ai.enabled');
        $f('ai.model').add($f('ai.batch_max')).prop('disabled', !aiOn).closest('.ad-field').toggleClass('is-off', !aiOn);
        coverUi();
        const ch = diffKeys(base, values(), secrets);
        $root.find('[data-st-bar]').prop('hidden', ch.length === 0);
        $root.find('[data-st-msg]').text(t('bar.unsaved') + ' (' + num(ch.length) + ')');
    }

    let maintPrev = false;
    $root.on('input change', '[data-k]', function () {
        const k = $(this).attr('data-k');
        $(this).closest('.ad-field').removeClass('has-error').find('.ad-error').prop('hidden', true);
        if (k === 'general.maintenance' && $(this).prop('checked') && !base['general.maintenance']) {
            maintPrev = true;
            openDialog('#st-maint');
        }
        ui();
    });
    // تأیید روشن‌کردن نگهداری
    $(document).on('click', '[data-st-maint-ok]', () => { maintPrev = false; closeDialog('#st-maint'); ui(); });
    $(document).on('click', '[data-st-maint-cancel], #st-maint [data-dialog-close]', () => {
        if (maintPrev) { $f('general.maintenance').prop('checked', false); maintPrev = false; ui(); }
    });

    $root.on('click', '[data-st-discard]', () => { clearErrors(); fill(); });

    async function persist(settings, changed) {
        const ev = $.Event('admin:settings-save');
        const res = await new Promise((resolve) => {
            $(document).trigger(ev, { settings, changed, done: (r) => resolve({ ok: r || {} }), fail: (message, errors) => resolve({ err: { message, errors } }) });
            if (!ev.isDefaultPrevented()) resolve(null);
        });
        if (res) {
            if (res.err) throw res.err;
            return res.ok;
        }
        const url = $root.attr('data-save-url');
        if (url) return send(url, { settings, changed }, 'PUT');
        await new Promise((r) => setTimeout(r, 450)); // حالت دمو
        return { ok: true, demo: true };
    }

    $root.on('click', '[data-st-save]', async function () {
        if (busy) return;
        clearErrors();
        const now = values();
        const errs = validateSettings(now);
        const ek = Object.keys(errs);
        if (ek.length) {
            ek.forEach((k) => showError(k, msgOf(errs[k])));
            $root.find('[data-st-msg]').text(t('bar.fix', { n: num(ek.length) }));
            const $first = $f(ek[0]).first();
            $first.closest('.st-sec')[0] && $first.closest('.st-sec')[0].scrollIntoView({ block: 'start' });
            $first.trigger('focus');
            return;
        }
        const changed = diffKeys(base, now, secrets);
        if (!changed.length) return;
        const settings = {};
        keys.forEach((k) => {
            if (secrets.includes(k)) { if (String(now[k] || '').trim()) settings[k] = String(now[k]).trim(); } else settings[k] = now[k];
        });
        busy = true;
        const $b = $(this).prop('disabled', true);
        $root.find('[data-st-save-t]').text(t('bar.saving'));
        try {
            await persist(settings, changed);
            keys.forEach((k) => {
                if (secrets.includes(k)) { if (changed.includes(k)) base[$f(k).attr('data-secret')] = true; } else base[k] = now[k];
            });
            fill();
            toast(t('bar.saved'));
        } catch (e) {
            toast((e && e.message) || t('errors.save'), true);
            Object.keys((e && e.errors) || {}).forEach((k) => showError(k, [].concat(e.errors[k])[0]));
        } finally {
            busy = false;
            $b.prop('disabled', false);
            $root.find('[data-st-save-t]').text(t('bar.save'));
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (diffKeys(base, values(), secrets).length) { e.preventDefault(); e.returnValue = ''; }
    });

    /* ناوبری کناری: بخش فعال هنگام اسکرول */
    const $links = $root.find('[data-st-nav] a');
    const secs = $root.find('.st-sec').toArray();
    function spy() {
        let cur = secs[0];
        secs.forEach((s) => { if (s.getBoundingClientRect().top <= 120) cur = s; });
        $links.each(function () { $(this).attr('aria-current', $(this).attr('href') === '#' + cur.id ? 'true' : 'false'); });
    }
    $(window).on('scroll', spy);
    $links.on('click', function () { setTimeout(spy, 50); });

    fill();
    spy();
    $root.trigger('admin:settings-ready');
});
