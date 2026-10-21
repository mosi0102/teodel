import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast } from './common';

/**
 * کاربران (/admin/users):
 *  • لیست: فیلتر با GET (بدون JS هم کار می‌کند)؛ اینجا فقط ارسال خودکار با تغییر مرتب‌سازی و تایپ جست‌وجو (debounce).
 *  • جزئیات: کپی کد معرف، مسدودسازی / رفع مسدودی (با تأیید)، هدیه‌ی روز اشتراک.
 *
 * 🔌 بک‌اند (آدرس روی ریشه‌ی صفحه — قرارداد کامل: app/Support/AdminUsers.php):
 *   data-block-url  ← POST { blocked: true|false, reason }      ← 200 { ok:true }
 *   data-grant-url  ← POST { days: 1..365, reason }             ← 200 { ok:true }
 *   یا رویداد لغوشدنی:  $(document).on('admin:user-action', (e, { action, user, payload, done, fail }) => { e.preventDefault(); … })
 *   بدون آدرس/رویداد: حالت دمو (فقط تغییر ظاهر صفحه).
 */

export function validDays(v) {
    const n = Number(v);
    return Number.isInteger(n) && n >= 1 && n <= 365;
}

$(function () {
    // ── لیست
    const $f = $('[data-ad-filter]');
    if ($f.length) {
        let timer = 0;
        $f.find('[data-ad-auto]').on('change', () => $f[0].submit());
        $f.find('[data-ad-q]').on('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => $f[0].submit(), 450);
        });
        // مکان‌نما بعد از بارگذاری دوباره به انتهای جست‌وجو برگردد
        const $q = $f.find('[data-ad-q]');
        if ($q.val() && new URLSearchParams(location.search).has('q') && document.referrer.includes(location.pathname)) {
            $q.trigger('focus');
            const v = $q.val();
            $q.val('').val(v);
        }
    }

    // ── جزئیات
    const $root = $('[data-ad-user]');
    if (!$root.length) return;
    const t = readJSON($root, 'data-t', {});
    const tr = (k, rep) => {
        let v = (t[k] != null ? t[k] : k) + '';
        if (rep) Object.keys(rep).forEach((r) => (v = v.split(':' + r).join(rep[r])));
        return v;
    };
    const user = $root.attr('data-user');

    $root.on('click', '[data-ad-copy]', async function () {
        const code = $root.find('[data-ad-code]').text().trim();
        try {
            await navigator.clipboard.writeText(code);
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = code; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); } catch (err) { /* ignore */ }
            ta.remove();
        }
        toast($root.attr('data-copied') || tr('copied') || code);
    });

    async function run(action, url, payload, $btn, $err) {
        $btn.prop('disabled', true);
        $err.prop('hidden', true);
        const ev = $.Event('admin:user-action');
        let handled = false;
        const result = await new Promise((resolve) => {
            $(document).trigger(ev, { action, user, payload, done: () => resolve({ ok: true }), fail: (m) => resolve({ err: m || tr('error') }) });
            handled = ev.isDefaultPrevented();
            if (!handled) resolve(null);
        });
        try {
            if (result) {
                if (result.err) throw { message: result.err };
            } else if (url) {
                await send(url, payload);
            } // بدون آدرس: دمو
            return true;
        } catch (e) {
            $err.text((e && e.message) || tr('error')).prop('hidden', false);
            return false;
        } finally {
            $btn.prop('disabled', false);
        }
    }

    // مسدودسازی
    const $blk = $('#ad-block');
    let blocking = true;
    $root.on('click', '[data-ad-act=block]', function () {
        blocking = $(this).attr('data-blocked') !== '1';
        $blk.find('[data-ad-block-title]').text(blocking ? tr('block_t') : tr('unblock_t'));
        $blk.find('[data-ad-block-desc]').text(blocking ? tr('block_d') : tr('unblock_d'));
        $blk.find('[data-ad-block-ok]').text(blocking ? tr('confirm_block') : tr('confirm_unblock')).toggleClass('!bg-rose-600 hover:!bg-rose-700', blocking);
        $blk.find('[data-ad-reason]').val('');
        $blk.find('[data-ad-block-err]').prop('hidden', true);
        openDialog('#ad-block');
    });
    $blk.find('[data-ad-block-ok]').on('click', async function () {
        const ok = await run('block', $root.attr('data-block-url'), { blocked: blocking, reason: $blk.find('[data-ad-reason]').val().toString().trim() }, $(this), $blk.find('[data-ad-block-err]'));
        if (!ok) return;
        $root.find('[data-ad-act=block]').attr('data-blocked', blocking ? '1' : '0').find('[data-ad-block-t]').text(blocking ? tr('unblock') : tr('block'));
        $root.find('[data-ad-blocked-badge]').prop('hidden', !blocking);
        closeDialog('#ad-block');
        toast(blocking ? tr('done_block') : tr('done_unblock'));
    });

    // هدیه‌ی روز اشتراک
    const $gr = $('#ad-grant');
    $root.on('click', '[data-ad-act=grant]', function () {
        $gr.find('[data-ad-grant-err]').prop('hidden', true);
        openDialog('#ad-grant');
        setTimeout(() => $gr.find('[data-ad-days]').trigger('focus').trigger('select'), 80);
    });
    $gr.find('[data-ad-grant-ok]').on('click', async function () {
        const days = $gr.find('[data-ad-days]').val();
        const $err = $gr.find('[data-ad-grant-err]');
        if (!validDays(days)) { $err.text(tr('bad_days')).prop('hidden', false); return; }
        const ok = await run('grant', $root.attr('data-grant-url'), { days: Number(days), reason: $gr.find('[data-ad-reason2]').val().toString().trim() }, $(this), $err);
        if (!ok) return;
        closeDialog('#ad-grant');
        toast(tr('done_grant', { n: new Intl.NumberFormat(document.documentElement.lang === 'fa' ? 'fa-IR' : 'en-GB').format(Number(days)) }));
    });
});
