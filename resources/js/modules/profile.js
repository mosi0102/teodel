import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';
import { digits } from './digits';

/**
 * پروفایل کاربر (/profile): دیدن، تکمیل و ویرایش اطلاعات + انتخاب آواتار از لیست پیشنهادی سایت.
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 * ذخیره‌ی فرم، یکی از این دو راه:
 *  1) آدرس: مقدار data-endpoint روی <form id="pf-form"> (POST + JSON + CSRF). خطای 422 با { errors: { field: ['…'] } } زیر همان فیلد نشان داده می‌شود.
 *  2) رویداد:
 *       $(document).on('profile:submit', (e, payload) => {
 *           e.preventDefault();                       // یعنی «خودم ذخیره می‌کنم»
 *           save(payload).then(() => Teodel.profile.done(true))
 *                        .catch((errs) => Teodel.profile.done(false, errs)); // errs = { mobile: 'پیام' }
 *       });
 *     بدون preventDefault و بدون endpoint، حالت دمو اجرا می‌شود (موفقیتِ فوری).
 *  payload = { first_name, last_name, mobile, email, gender, birthday('YYYY-MM-DD' میلادی | ''), avatar, password?, password_confirmation? }
 *
 * آواتار (همان لحظه‌ی «تایید و بستن»):  $(document).on('profile:avatar', (e, { id, src }) => {...})
 *   هر <img data-user-avatar> در صفحه (مثلاً سایدبار و هدر) هم خودکار عوض می‌شود.
 *
 * LOCAL: در دمو اطلاعات در localStorage نگه داشته می‌شود (بدون رمز). با اتصال بک‌اند false کنید.
 */
const LOCAL = true;
const KEY = 'teodel:profile';
const MOBILE_RE = /^09\d{9}$/;
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

const store = {
    get() {
        try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
    },
    set(v) {
        try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) { /* ignore */ }
    },
};

/* ---------- تقویم شمسی ↔ میلادی (الگوریتم jalaali-js) ---------- */
const div = (a, b) => Math.trunc(a / b);
const mod = (a, b) => a - Math.trunc(a / b) * b;
const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

function jalCal(jy) {
    const gy = jy + 621;
    let leapJ = -14;
    let jp = BREAKS[0];
    let jump = 0;
    for (let i = 1; i < BREAKS.length; i++) {
        const jm = BREAKS[i];
        jump = jm - jp;
        if (jy < jm) break;
        leapJ += div(jump, 33) * 8 + div(mod(jump, 33), 4);
        jp = jm;
    }
    let n = jy - jp;
    leapJ += div(n, 33) * 8 + div(mod(n, 33) + 3, 4);
    if (mod(jump, 33) === 4 && jump - n === 4) leapJ += 1;
    const leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
    const march = 20 + leapJ - leapG;
    if (jump - n < 6) n = n - jump + div(jump + 4, 33) * 33;
    let leap = mod(mod(n + 1, 33) - 1, 4);
    if (leap === -1) leap = 4;
    return { leap, gy, march };
}
const g2d = (gy, gm, gd) => {
    let d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4) + div(153 * mod(gm + 9, 12) + 2, 5) + gd - 34840408;
    d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
    return d;
};
const d2g = (jdn) => {
    let j = 4 * jdn + 139361631;
    j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    const i = div(mod(j, 1461), 4) * 5 + 308;
    const gd = div(mod(i, 153), 5) + 1;
    const gm = mod(div(i, 153), 12) + 1;
    const gy = div(j, 1461) - 100100 + div(8 - gm, 6);
    return { gy, gm, gd };
};
const j2d = (jy, jm, jd) => {
    const r = jalCal(jy);
    return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
};
const d2j = (jdn) => {
    const gy = d2g(jdn).gy;
    let jy = gy - 621;
    const r = jalCal(jy);
    const jdn1f = g2d(gy, 3, r.march);
    let k = jdn - jdn1f;
    if (k >= 0) {
        if (k <= 185) return { jy, jm: 1 + div(k, 31), jd: mod(k, 31) + 1 };
        k -= 186;
    } else {
        jy -= 1;
        k += 179;
        if (r.leap === 1) k += 1;
    }
    return { jy, jm: 7 + div(k, 30), jd: mod(k, 30) + 1 };
};
export const toJalali = (gy, gm, gd) => d2j(g2d(gy, gm, gd));
export const toGregorian = (jy, jm, jd) => d2g(j2d(jy, jm, jd));
const jalaliMonthLen = (jy, jm) => (jm <= 6 ? 31 : jm <= 11 ? 30 : jalCal(jy).leap === 0 ? 30 : 29);
const gregorianMonthLen = (y, m) => new Date(y, m, 0).getDate();
const pad = (n) => String(n).padStart(2, '0');

const toLatin = (s) => String(s)
    .replace(/[۰-۹]/g, (c) => String(c.charCodeAt(0) - 1776))
    .replace(/[٠-٩]/g, (c) => String(c.charCodeAt(0) - 1632));

function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
    return new Promise((resolve, reject) => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;opacity:0;top:0';
        document.body.appendChild(ta);
        ta.select();
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        ta.remove();
        if (ok) resolve(); else reject(new Error('copy'));
    });
}

function init(root) {
    const $root = $(root);
    const T = (n) => $root.attr('data-t-' + n) || '';
    const E = (n) => $root.attr('data-e-' + n) || '';
    const $form = $root.find('[data-pf-form]');
    const form = $form[0];
    const $dlg = $('#avatar-dialog');
    const fa = document.documentElement.lang === 'fa';
    let user = {};
    try { user = JSON.parse($root.attr('data-user') || '{}'); } catch (e) { user = {}; }
    let months = [];
    try { months = JSON.parse($root.attr('data-months') || '[]'); } catch (e) { months = []; }
    const dig = (n) => (fa ? digits(n) : String(n));

    if (LOCAL) {
        const s = store.get();
        ['first_name', 'last_name', 'mobile', 'email', 'gender', 'birthday'].forEach((k) => {
            if (s[k] !== undefined) user[k] = s[k];
        });
        if (s.avatar) user.avatar = s.avatar;
    }

    const $f = (name) => $form.find('[name="' + name + '"]');
    ['first_name', 'last_name', 'mobile', 'email', 'gender'].forEach((k) => {
        if (user[k] != null) $f(k).val(user[k]);
    });

    /* ---------- تاریخ تولد ---------- */
    const $date = $form.find('[data-pf-date]');
    const $dd = $date.find('[data-pf-d="d"]');
    const $dm = $date.find('[data-pf-d="m"]');
    const $dy = $date.find('[data-pf-d="y"]');
    const $bd = $form.find('[data-pf-birthday]');
    const nowG = new Date();
    const nowY = fa ? toJalali(nowG.getFullYear(), nowG.getMonth() + 1, nowG.getDate()).jy : nowG.getFullYear();

    const opt = (v, t) => $('<option>').val(v).text(t);
    $dd.append(opt('', $date.attr('data-ph-day')));
    $dm.append(opt('', $date.attr('data-ph-month')));
    $dy.append(opt('', $date.attr('data-ph-year')));
    for (let m = 1; m <= 12; m++) $dm.append(opt(m, months[m - 1] || m));
    for (let y = nowY - 5; y >= nowY - 100; y--) $dy.append(opt(y, dig(y)));

    function fillDays() {
        const y = +$dy.val() || nowY - 20; // سال انتخاب‌نشده: ۲۹/۳۰ اسفند را آزاد بگذار
        const m = +$dm.val() || 1;
        const len = fa ? jalaliMonthLen(y, m) : gregorianMonthLen(y, m);
        const keep = +$dd.val();
        $dd.find('option:gt(0)').remove();
        for (let d = 1; d <= len; d++) $dd.append(opt(d, dig(d)));
        $dd.val(keep && keep <= len ? keep : '');
    }
    function readDate() {
        const y = +$dy.val();
        const m = +$dm.val();
        const d = +$dd.val();
        if (!y && !m && !d) return { empty: true, value: '' };
        if (!y || !m || !d) return { empty: false, value: '', invalid: true };
        const g = fa ? toGregorian(y, m, d) : { gy: y, gm: m, gd: d };
        return { empty: false, value: g.gy + '-' + pad(g.gm) + '-' + pad(g.gd) };
    }
    function writeDate(iso) {
        $dy.val(''); $dm.val(''); fillDays();
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
        if (!m) return;
        const gy = +m[1];
        const gm = +m[2];
        const gd = +m[3];
        const p = fa ? toJalali(gy, gm, gd) : { jy: gy, jm: gm, jd: gd };
        $dy.val(p.jy); $dm.val(p.jm); fillDays(); $dd.val(p.jd);
    }
    fillDays();
    writeDate(user.birthday);
    $bd.val(readDate().value);
    $date.on('change', 'select', function () {
        if (this !== $dd[0]) fillDays();
        $bd.val(readDate().value);
        touch('birthday');
        refresh();
    });

    /* ---------- خطاها ---------- */
    const $err = (name) => $form.find('[data-pf-err="' + name + '"]');
    function setErr(name, msg) {
        const $e = $err(name);
        $e.text(msg || '');
        const id = 'pf-err-' + name;
        const $inp = name === 'birthday' ? $date.find('select') : $f(name);
        if (msg) {
            $e.attr('id', id);
            $inp.attr({ 'aria-invalid': 'true', 'aria-describedby': id });
        } else {
            $inp.removeAttr('aria-invalid aria-describedby');
        }
    }
    const touched = {};
    const touch = (n) => { touched[n] = true; };

    const rules = {
        first_name: (v) => (v.trim() ? '' : E('required')),
        last_name: (v) => (v.trim() ? '' : E('required')),
        mobile: (v) => (!v ? E('required') : MOBILE_RE.test(v) ? '' : E('mobile')),
        email: (v) => (!v || EMAIL_RE.test(v) ? '' : E('email')),
        birthday: () => (readDate().invalid ? E('birthday') : ''),
        password: (v) => {
            if (!v) return $f('password').prop('required') ? E('required') : '';
            return v.length >= 8 ? '' : E('password');
        },
        password_confirmation: (v) => {
            const p = $f('password').val();
            if (!p && !v) return $f('password_confirmation').prop('required') ? E('required') : '';
            return v === p ? '' : E('confirm');
        },
    };
    const value = (n) => (n === 'birthday' ? $bd.val() : String($f(n).val() || ''));
    function check(n) {
        const msg = rules[n](value(n));
        setErr(n, msg);
        return !msg;
    }

    $form.on('input', '[name="mobile"]', function () {
        this.value = toLatin(this.value).replace(/\D/g, '').slice(0, 11);
    });
    $form.on('input', '[data-pf-f]', function () {
        const n = this.name;
        if (n === 'password' && $err('password_confirmation').text()) check('password_confirmation');
        if (touched[n] || $err(n).text()) check(n);
        dirty(true);
        refresh();
    });
    $form.on('change', 'select[data-pf-f]', () => { dirty(true); refresh(); });
    // با فشردن «ثبت»، خطای blur نباید نشان داده شود: چیدمان جابه‌جا می‌شود و کلیک از دست می‌رود (ثبت خودش همه را بررسی می‌کند)
    let pressing = false;
    $form.on('pointerdown mousedown touchstart', '[data-pf-submit]', () => {
        pressing = true;
        setTimeout(() => { pressing = false; }, 400);
    });
    $form.on('blur', '[data-pf-f]', function () {
        if (pressing) return;
        touch(this.name);
        if (this.name && rules[this.name]) check(this.name);
    });

    /* ---------- نام کنار آواتار + میزان تکمیل ---------- */
    const meterFields = ['first_name', 'last_name', 'mobile', 'email', 'gender', 'birthday'];
    function refresh() {
        const nm = (value('first_name') + ' ' + value('last_name')).trim();
        $root.find('[data-pf-name]').text(nm);
        const filled = meterFields.filter((n) => value(n).trim() !== '').length;
        const pct = Math.round((filled / meterFields.length) * 100);
        const done = pct === 100;
        $root.find('[data-pf-meter]').toggleClass('is-done', done);
        $root.find('[data-pf-meter-bar]').css('width', pct + '%');
        $root.find('[data-pf-meter-text]').text(done ? T('complete-done') : T('complete').replace(':n', dig(pct)));
    }

    /* ---------- تغییرات ذخیره‌نشده ---------- */
    let isDirty = false;
    function dirty(v) { isDirty = v; }
    window.addEventListener('beforeunload', (e) => {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = T('unsaved');
    });

    /* ---------- نمایش/پنهان رمز ---------- */
    $form.on('click', '[data-pf-eye]', function () {
        const $i = $($(this).attr('data-pf-eye'));
        const show = $i.attr('type') === 'password';
        $i.attr('type', show ? 'text' : 'password');
        this.setAttribute('aria-pressed', show ? 'true' : 'false');
        this.setAttribute('aria-label', show ? T('hide-pass') : T('show-pass'));
    });

    /* ---------- پیام کوتاه ---------- */
    const $toast = $root.find('[data-pf-toast]');
    let toastTimer = 0;
    function toast(text, bad) {
        $toast.text(text).toggleClass('is-bad', !!bad).prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => $toast.removeClass('is-in').prop('hidden', true), 2800);
    }

    /* ---------- کپی کد معرف ---------- */
    let copyTimer = 0;
    $root.on('click', '[data-pf-copy], [data-pf-copy-ticket]', () => {
        const code = $root.find('[data-pf-code]').text().trim();
        const $l = $root.find('[data-pf-copy-label]');
        copyText(code).then(() => {
            $l.text(T('copied'));
            $root.find('[data-pf-copy]').addClass('is-ok');
            clearTimeout(copyTimer);
            copyTimer = setTimeout(() => { $l.text(T('copy')); $root.find('[data-pf-copy]').removeClass('is-ok'); }, 1800);
            toast(T('copied'));
        }).catch(() => toast(T('copy-failed'), true));
    });

    /* ---------- ارسال فرم ---------- */
    const $submit = $form.find('[data-pf-submit]');
    const order = ['first_name', 'last_name', 'mobile', 'email', 'birthday', 'password', 'password_confirmation'];
    const focusFor = (n) => (n === 'birthday' ? $dd[0] : $f(n)[0]);

    function payload() {
        const p = {
            first_name: value('first_name').trim(),
            last_name: value('last_name').trim(),
            mobile: value('mobile'),
            email: value('email').trim(),
            gender: value('gender'),
            birthday: value('birthday'),
            avatar: +$form.find('[data-pf-avatar-input]').val() || null,
        };
        if (value('password')) {
            p.password = value('password');
            p.password_confirmation = value('password_confirmation');
        }
        return p;
    }

    function setErrors(map) {
        let first = null;
        Object.keys(map || {}).forEach((k) => {
            const m = Array.isArray(map[k]) ? map[k][0] : map[k];
            const name = $err(k).length ? k : '_form';
            setErr(name, m);
            if (!first && name !== '_form') first = name;
        });
        if (first && focusFor(first)) focusFor(first).focus();
    }

    function done(ok, errors) {
        $submit.prop('disabled', false);
        $form.removeClass('busy');
        if (!ok) {
            setErrors(errors && Object.keys(errors).length ? errors : { _form: T('failed') });
            return;
        }
        if (LOCAL) {
            const p = payload();
            store.set({ first_name: p.first_name, last_name: p.last_name, mobile: p.mobile, email: p.email, gender: p.gender, birthday: p.birthday, avatar: p.avatar });
        }
        $f('password').val('').prop('required', false);
        $f('password_confirmation').val('').prop('required', false);
        $form.find('[data-pf-eye]').each(function () {
            if (this.getAttribute('aria-pressed') === 'true') $(this).trigger('click');
        });
        dirty(false);
        toast(T('saved'));
    }

    $form.on('submit', async (e) => {
        e.preventDefault();
        setErr('_form', '');
        let firstBad = null;
        order.forEach((n) => {
            touch(n);
            if (!check(n) && !firstBad) firstBad = n;
        });
        if (firstBad) {
            focusFor(firstBad).focus();
            return;
        }

        const data = payload();
        $submit.prop('disabled', true);
        $form.addClass('busy');

        const ev = $.Event('profile:submit');
        $(document).trigger(ev, [data]);
        if (ev.isDefaultPrevented()) return; // بک‌اند خودش ذخیره می‌کند و Teodel.profile.done را صدا می‌زند

        const url = $form.attr('data-endpoint');
        if (!url) { done(true); return; }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $form.find('input[name="_token"]').val() || '',
                },
                body: JSON.stringify(data),
                credentials: 'same-origin',
            });
            if (res.ok) { done(true); return; }
            let errs = null;
            if (res.status === 422) {
                try { errs = (await res.json()).errors; } catch (x) { errs = null; }
            }
            done(false, errs);
        } catch (x) {
            done(false);
        }
    });

    /* ---------- آواتار ---------- */
    const $radios = $dlg.find('input[name="pf-avatar"]');
    const $avImg = $root.find('[data-pf-avatar-img]');

    function applyAvatar(id, src) {
        $form.find('[data-pf-avatar-input]').val(id);
        $('img[data-user-avatar]').attr('src', src);
    }

    $root.on('click', '[data-pf-avatar-open]', () => {
        const cur = String($form.find('[data-pf-avatar-input]').val());
        $radios.each(function () { this.checked = this.value === cur; });
        openDialog('#avatar-dialog');
        // فوکوس روی آواتار فعلی (کمی بعد، چون دیالوگ تازه دیده‌شدنی می‌شود)
        setTimeout(() => {
            const $c = $radios.filter(':checked');
            ($c.length ? $c : $radios.not(':disabled').first()).trigger('focus');
        }, 120);
    });

    $dlg.on('click', '[data-pf-avatar-confirm]', () => {
        const $c = $radios.filter(':checked');
        if ($c.length) {
            const id = +$c.val();
            const src = $c.attr('data-src');
            const changed = String(id) !== String($form.find('[data-pf-avatar-input]').val());
            applyAvatar(id, src);
            if (changed) {
                if (LOCAL) store.set(Object.assign(store.get(), { avatar: id }));
                $(document).trigger('profile:avatar', [{ id, src }]);
            }
        }
        closeDialog($dlg);
    });

    // آواتار ذخیره‌شده‌ی دمو
    if (LOCAL && user.avatar) {
        const $r = $radios.filter('[value="' + user.avatar + '"]');
        if ($r.length && !$r.prop('disabled')) applyAvatar(user.avatar, $r.attr('data-src'));
    }
    if (!$avImg.attr('src')) { /* بدون آواتار: چیزی نمایش داده نمی‌شود */ }

    refresh();

    window.Teodel = window.Teodel || {};
    window.Teodel.profile = { setErrors, done };
}

$(function () {
    $('[data-pf]').each(function () { init(this); });
});
