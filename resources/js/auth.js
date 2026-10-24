import $ from 'jquery';

/**
 * ورود و ثبت‌نام (/login، /register) — یک صفحه‌ی چندمرحله‌ای. قرارداد بک‌اند: app/Support/AuthFlow.php
 *
 * مراحل: phone → code (پیش‌فرض) | password → profile (فقط شماره‌ی جدید) → done
 * 🔌 هر عملیات (send | verify | password | register) با این اولویت اجرا می‌شود:
 *   ۱) رویداد لغوشدنی  auth:{op}  با { payload, done(result), fail(message, errors, extra) }   (e.preventDefault())
 *   ۲) Teodel.auth.api.{op}(payload)  (async؛ خطا: throw { message, errors, retry_in })
 *   ۳) آدرس روی ریشه‌ی صفحه: data-send-url / data-verify-url / data-password-url / data-register-url  (POST JSON + CSRF)
 *   ۴) حالت دمو (وقتی هیچ آدرسی نیست): شماره‌ی پایان ۰–۴ = حساب موجود، ۵–۹ = جدید، کد 12345، رمز demo1234
 */

const API = (window.Teodel = window.Teodel || {});
API.auth = API.auth || {};
API.auth.api = API.auth.api || {};

/* ── توابع خالص (تست‌شده؛ همان قانون AuthFlow در PHP) ─────────── */

const P = '۰۱۲۳۴۵۶۷۸۹';
const A = '٠١٢٣٤٥٦٧٨٩';

/** ارقام فارسی/عربی → لاتین */
export const toLatin = (s) => String(s == null ? '' : s).replace(/[۰-۹]/g, (d) => P.indexOf(d)).replace(/[٠-٩]/g, (d) => A.indexOf(d));
/** ارقام لاتین → فارسی */
export const toFa = (s) => String(s == null ? '' : s).replace(/\d/g, (d) => P[d]);

/** شماره‌ی موبایل ایران → 09123456789 یا null */
export function normalizePhone(v) {
    let s = toLatin(v).replace(/[\s\-().‌‏‎]/g, '');
    s = s.replace(/^(\+98|0098|98)/, '0');
    if (/^9\d{9}$/.test(s)) s = '0' + s;
    return /^09\d{9}$/.test(s) ? s : null;
}

/** رمز: ≥۸ نویسه با حرف و عدد → null | 'password_short' | 'password_weak' */
export function passwordIssue(p) {
    const s = String(p || '');
    if ([...s].length < 8) return 'password_short';
    if (!/\p{L}/u.test(s) || !/\d/.test(s)) return 'password_weak';
    return null;
}

/** قدرت رمز ۰ تا ۴ (۰ = خالی) */
export function passwordLevel(p) {
    const s = String(p || '');
    if (!s) return 0;
    let n = 1;
    if (!passwordIssue(s)) n++;
    if ([...s].length >= 12) n++;
    if ((/[a-z]/.test(s) && /[A-Z]/.test(s)) || /[^\p{L}\d\s]/u.test(s)) n++;
    return Math.min(4, passwordIssue(s) ? Math.min(n, 1) : n);
}

/** فقط مسیر داخلی (هم‌مبدأ) را قبول می‌کند */
export function safePath(to, fallback = '/dashboard') {
    const s = String(to || '');
    try {
        if (/^https?:\/\//i.test(s)) {
            const u = new URL(s);
            if (u.origin === location.origin) return u.pathname + u.search + u.hash;
            return fallback;
        }
    } catch (e) {
        return fallback;
    }
    return s.startsWith('/') && !s.startsWith('//') && !s.includes('\\') ? s : fallback;
}

/** ۶۵ ثانیه → 1:05 */
export const fmtTimer = (sec) => {
    const s = Math.max(0, Math.ceil(sec));
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
};

/** رقم‌های چسبانده‌شده → فقط ارقام (حداکثر n) */
export const digitsOf = (s, n) => toLatin(s).replace(/\D/g, '').slice(0, n);

/* ═════ صفحه ═════ */
$(function () {
    const $root = $('[data-auth]');
    if (!$root.length) return;
    let TT = {};
    try { TT = JSON.parse($root.attr('data-t') || '{}'); } catch (e) { /* ignore */ }
    const fa = document.documentElement.lang === 'fa';
    const t = (path, rep) => {
        let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };
    const num = (s) => (fa ? toFa(s) : String(s));
    const N = parseInt($root.attr('data-code-length'), 10) || 5;
    const RESEND = parseInt($root.attr('data-resend'), 10) || 60;
    const OPS = ['send', 'verify', 'password', 'register'];
    const demo = !OPS.some((o) => $root.attr('data-' + o + '-url'));
    const reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $$ = (s) => $root.find(s);
    const S = { step: 'phone', mode: $root.attr('data-mode') === 'register' ? 'register' : 'login', phone: null, exists: null, ticket: '', purpose: 'login', busy: false, end: 0 };

    /* ── اتصال به بک‌اند ── */
    async function post(url, body) {
        let res;
        try {
            res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
        } catch (e) {
            throw { message: t('errors.network'), network: true };
        }
        let data = {};
        try { data = await res.json(); } catch (e) { /* بدنه‌ی خالی */ }
        if (!res.ok) throw { message: data.message || '', errors: data.errors || null, retry_in: data.retry_in || 0, status: res.status };
        return data;
    }
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));
    const DEMO = {
        send: async (b) => { await wait(550); const ex = /[0-4]$/.test(b.phone); return { exists: ex, has_password: ex, sent: true, cooldown: RESEND }; },
        verify: async (b) => {
            await wait(450);
            if (b.code !== '12345') throw { errors: { code: [t('v.code_invalid')] } };
            return S.exists ? { status: 'logged_in', redirect: '/dashboard' } : { status: 'needs_profile', ticket: 'demo-ticket' };
        },
        password: async (b) => {
            await wait(450);
            if (!/[0-4]$/.test(b.phone) || b.password !== 'demo1234') throw { errors: { password: [t('v.password_wrong')] } };
            return { redirect: '/dashboard' };
        },
        register: async () => { await wait(600); return { redirect: '/dashboard' }; },
    };
    async function call(op, body) {
        const ev = $.Event('auth:' + op);
        const r = await new Promise((resolve) => {
            $(document).trigger(ev, { payload: body, done: (x) => resolve({ ok: x || {} }), fail: (message, errors, extra) => resolve({ err: Object.assign({ message, errors }, extra) }) });
            if (!ev.isDefaultPrevented()) resolve(null);
        });
        if (r) { if (r.err) throw r.err; return r.ok; }
        if (typeof API.auth.api[op] === 'function') return API.auth.api[op](body);
        const url = $root.attr('data-' + op + '-url');
        if (url) return post(url, body);
        return DEMO[op](body);
    }

    /* ── نمایش: مرحله، خطا، پیام ── */
    const err = ($el, msg) => {
        $el.text(msg || '').prop('hidden', !msg);
        $el.closest('.au-field').toggleClass('has-error', !!msg).find('input').first().attr('aria-invalid', msg ? 'true' : null);
    };
    const clearErrors = ($scope) => { $scope.find('.au-err').prop('hidden', true).text('').closest('.au-field').removeClass('has-error'); $scope.find('[aria-invalid]').removeAttr('aria-invalid'); };
    function notice(text, tone) {
        $$('[data-au-notice]').text(text || '').attr('data-tone', tone || '').prop('hidden', !text);
    }
    function setBusy(on, label) {
        S.busy = on;
        const $b = $$('[data-au-submit]'); // همه‌ی مرحله‌ها: بعد از رفتن به مرحله‌ی بعد، دکمه‌ی مرحله‌ی قبل گیر نکند
        $b.prop('disabled', on).toggleClass('is-busy', on);
    }
    function mapErrors(e, map) {
        const errs = (e && e.errors) || {};
        let shown = false;
        Object.keys(map).forEach((k) => {
            if (errs[k]) { err(map[k], [].concat(errs[k])[0]); shown = true; }
        });
        return shown;
    }
    const phoneText = (p) => '⁦' + num(p) + '⁩';

    function dots() {
        const $d = $$('[data-au-dots]');
        const order = ['phone', 'code', 'profile'];
        const cur = S.step === 'password' ? 'code' : S.step;
        const idx = order.indexOf(cur);
        $d.prop('hidden', idx < 0 && S.step !== 'done');
        $d.find('li').each(function (i) {
            const s = $(this).attr('data-s');
            $(this).attr('data-state', i < idx ? 'done' : i === idx ? 'active' : 'todo').prop('hidden', s === 'profile' && S.exists === true);
        });
    }
    function title() {
        $$('[data-au-title]').text(t('phone.title_' + S.mode));
        $$('[data-au-tabs] button').each(function () { $(this).attr('aria-selected', $(this).attr('data-v') === S.mode ? 'true' : 'false'); });
        document.title = t('title_' + S.mode) + ' · ' + t('brand');
    }
    let otpAbort = null;
    function go(step, focus = true) {
        S.step = step;
        if (otpAbort) { try { otpAbort.abort(); } catch (e) { /* ignore */ } otpAbort = null; }
        $$('[data-au-step]').each(function () {
            const on = $(this).attr('data-au-step') === step;
            $(this).prop('hidden', !on).removeClass('is-in');
            if (on && !reduce) { void this.offsetWidth; $(this).addClass('is-in'); }
        });
        $$('[data-au-tabs]').prop('hidden', step !== 'phone');
        dots();
        clearErrors($$('[data-au-step]'));
        if (step === 'code') {
            $$('[data-au-code-lead]').text(t('code.lead', { n: num(N), phone: phoneText(S.phone) }));
            $$('[data-au-pw-link]').closest('.au-alt').prop('hidden', S.exists === false);
            startOtp();
        }
        if (step === 'password') {
            $$('[data-au-pw-lead]').text(S.phone ? t('password.lead', { phone: phoneText(S.phone) }) : '');
            $$('[data-au-phone-wrap]').prop('hidden', !!S.phone);
            $$('[data-au-forgot-hint]').prop('hidden', true);
        }
        if (step === 'profile') $$('[data-au-profile-lead]').text(t('profile.lead', { phone: phoneText(S.phone) }));
        if (!focus) return;
        setTimeout(() => {
            const $s = $$('[data-au-step="' + step + '"]');
            const $f = step === 'password' && !S.phone ? $s.find('[data-au-phone2]') : $s.find('input:not([type=checkbox]):not([type=hidden])').first();
            if ($f.length && !$f.is(':hidden')) $f.trigger('focus');
        }, reduce ? 0 : 60);
    }

    /* ── مرحله‌ی شماره ── */
    const $phone = $$('[data-au-phone]');
    $phone.on('input', function () {
        const v = toLatin($(this).val());
        if (v !== $(this).val()) $(this).val(v);
        err($$('#au-phone-err'), '');
    });
    $$('[data-au-tabs]').on('click', 'button', function () {
        S.mode = $(this).attr('data-v');
        title();
        try { history.replaceState(null, '', location.pathname.replace(/\/(login|register)\/?$/, '/' + S.mode) + location.search); } catch (e) { /* ignore */ }
    });

    function fail(e, map, $general) {
        if (e && e.retry_in) { startTimer(e.retry_in); }
        if (mapErrors(e, map)) return;
        const m = (e && e.retry_in && !e.message) ? t('errors.too_many', { t: num(fmtTimer(e.retry_in)) }) : (e && e.message) || t('errors.generic');
        err($general, m);
    }

    async function sendCode(opts = {}) {
        if (S.busy) return;
        const phone = S.phone;
        setBusy(true);
        try {
            const res = await call('send', Object.assign({ phone }, opts.resend ? { resend: true } : {}, S.purpose === 'reset' ? { purpose: 'reset' } : {}));
            S.exists = !!res.exists;
            S.hasPassword = res.has_password == null ? S.exists : !!res.has_password;
            startTimer(res.cooldown || RESEND);
            if (S.step !== 'code') {
                // نکته: حالت انتخابی (ورود/ثبت‌نام) فقط پیام می‌دهد؛ سرور تعیین می‌کند
                if (S.mode === 'register' && S.exists) notice(t('notice.exists'), 'info');
                else if (S.mode === 'login' && !S.exists) notice(t('notice.new'), 'info');
                else notice(S.purpose === 'reset' ? t('password.forgot_hint') : '', 'info');
                S.mode = S.exists ? 'login' : 'register';
                title();
                go('code');
            } else notice(opts.resend ? t('code.sent_again') : '', 'info');
            $$('[data-au-notsent]').prop('hidden', res.sent !== false);
            if (res.sent === false && S.exists) $$('[data-au-pw-link]').addClass('is-hot');
            $$('[data-au-pw-link]').closest('.au-alt').prop('hidden', S.exists === false);
            dots();
            return res;
        } catch (e) {
            if (S.step === 'phone') fail(e, { phone: $$('#au-phone-err') }, $$('#au-phone-err'));
            else fail(e, {}, $$('[data-au-code-err]'));
        } finally { setBusy(false); }
    }

    $$('[data-au-form="phone"]').on('submit', function () {
        const p = normalizePhone($phone.val());
        if (!p) { err($$('#au-phone-err'), t($phone.val().trim() ? 'v.phone_invalid' : 'v.phone_required')); $phone.trigger('focus'); return; }
        $phone.val(p);
        S.phone = p;
        S.purpose = 'login';
        notice('');
        sendCode();
    });

    /* ── مرحله‌ی کد ── */
    const $digits = $$('[data-au-digit]');
    const codeVal = () => $digits.map(function () { return $(this).val(); }).get().join('');
    function fillCode(str, from = 0) {
        const d = digitsOf(str, N - from);
        d.split('').forEach((c, i) => $digits.eq(from + i).val(c));
        const next = Math.min(from + d.length, N - 1);
        $digits.eq(next).trigger('focus');
        err($$('[data-au-code-err]'), '');
        if (codeVal().length === N) submitCode();
    }
    $digits.on('input', function () {
        const i = $digits.index(this);
        const raw = $(this).val();
        const d = digitsOf(raw, N);
        if (d.length > 1) { $(this).val(''); fillCode(d, i); return; }
        $(this).val(d);
        err($$('[data-au-code-err]'), '');
        if (d && i < N - 1) $digits.eq(i + 1).trigger('focus');
        if (codeVal().length === N) submitCode();
    });
    $digits.on('keydown', function (e) {
        const i = $digits.index(this);
        if (e.key === 'Backspace' && !$(this).val() && i > 0) { e.preventDefault(); $digits.eq(i - 1).val('').trigger('focus'); }
        else if (e.key === 'ArrowLeft' && i > 0) { e.preventDefault(); $digits.eq(i - 1).trigger('focus'); }
        else if (e.key === 'ArrowRight' && i < N - 1) { e.preventDefault(); $digits.eq(i + 1).trigger('focus'); }
    });
    $digits.on('paste', function (e) {
        const txt = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        e.preventDefault();
        fillCode(txt, 0);
    });
    $digits.on('focus', function () { this.select(); });

    function startOtp() {
        if (!('OTPCredential' in window) || !navigator.credentials) return;
        try {
            otpAbort = new AbortController();
            navigator.credentials.get({ otp: { transport: ['sms'] }, signal: otpAbort.signal }).then((o) => { if (o && o.code) fillCode(o.code, 0); }).catch(() => {});
        } catch (e) { /* ignore */ }
    }

    async function submitCode() {
        if (S.busy) return;
        const code = codeVal();
        if (code.length < N) { err($$('[data-au-code-err]'), t('v.code_incomplete')); $digits.eq(code.length).trigger('focus'); return; }
        setBusy(true);
        try {
            const res = await call('verify', Object.assign({ phone: S.phone, code }, S.purpose === 'reset' ? { purpose: 'reset' } : {}));
            if (res.status === 'needs_profile') {
                S.ticket = res.ticket || '';
                S.exists = false;
                notice('');
                go('profile');
            } else finish(res.redirect);
        } catch (e) {
            $digits.val('');
            const $box = $$('[data-au-code]');
            if (!reduce) { $box.removeClass('is-shake'); void $box[0].offsetWidth; $box.addClass('is-shake'); }
            fail(e, { code: $$('[data-au-code-err]') }, $$('[data-au-code-err]'));
            $digits.eq(0).trigger('focus');
        } finally { setBusy(false); }
    }
    $$('[data-au-form="code"]').on('submit', submitCode);

    let timer = 0;
    function startTimer(sec) {
        clearInterval(timer);
        S.end = Date.now() + sec * 1000;
        const $b = $$('[data-au-resend]');
        const tick = () => {
            const left = (S.end - Date.now()) / 1000;
            if (left <= 0) { clearInterval(timer); $b.prop('disabled', false).text(t('code.resend')); return; }
            $b.prop('disabled', true).text(t('code.resend_in', { t: num(fmtTimer(left)) }));
        };
        tick();
        timer = setInterval(tick, 1000);
    }
    $$('[data-au-resend]').on('click', () => { if (Date.now() >= S.end) { $digits.val(''); sendCode({ resend: true }); } });
    $$('[data-au-edit], [data-au-edit2]').on('click', () => { notice(''); go('phone'); });

    /* ── مرحله‌ی رمز عبور ── */
    $$('[data-au-go]').on('click', function () {
        const to = $(this).attr('data-au-go');
        if (to === 'password') {
            const p = S.phone || normalizePhone($phone.val());
            if (p) { S.phone = p; $$('[data-au-phone2]').val(p); }
            notice('');
            go('password');
        } else if (to === 'code') {
            if (!S.phone) { go('phone'); return; }
            S.purpose = 'login';
            S.step = 'password';
            sendCode();
        }
    });
    $$('[data-au-forgot]').on('click', function () {
        const p = S.phone || normalizePhone($$('[data-au-phone2]').val());
        if (!p) { err($$('[data-au-phone2-err]'), t('v.phone_required')); $$('[data-au-phone-wrap]').prop('hidden', false); $$('[data-au-phone2]').trigger('focus'); return; }
        S.phone = p;
        S.purpose = 'reset';
        sendCode();
    });
    $$('[data-au-eye]').on('click', function () {
        const $i = $(this).closest('.au-input').find('input').first();
        const show = $i.attr('type') === 'password';
        $i.attr('type', show ? 'text' : 'password');
        $(this).attr({ 'aria-pressed': show ? 'true' : 'false', 'aria-label': $(this).attr(show ? 'data-hide' : 'data-show') });
        $(this).find('[data-e=on]').prop('hidden', show);
        $(this).find('[data-e=off]').prop('hidden', !show);
    });
    $$('[data-au-phone2]').on('input', function () { const v = toLatin($(this).val()); if (v !== $(this).val()) $(this).val(v); err($$('[data-au-phone2-err]'), ''); });
    $$('[data-au-pw]').on('input', () => err($$('[data-au-pw-err]'), ''));
    $$('[data-au-form="password"]').on('submit', async function () {
        if (S.busy) return;
        clearErrors($(this));
        const phone = S.phone || normalizePhone($$('[data-au-phone2]').val());
        if (!phone) { err($$('[data-au-phone2-err]'), t($$('[data-au-phone2]').val().trim() ? 'v.phone_invalid' : 'v.phone_required')); $$('[data-au-phone2]').trigger('focus'); return; }
        const password = String($$('[data-au-pw]').val() || '');
        if (!password) { err($$('[data-au-pw-err]'), t('v.password_required')); $$('[data-au-pw]').trigger('focus'); return; }
        S.phone = phone;
        setBusy(true);
        try {
            const res = await call('password', { phone, password, remember: $$('[data-au-remember]').prop('checked') });
            finish(res.redirect);
        } catch (e) {
            fail(e, { password: $$('[data-au-pw-err]'), phone: $$('[data-au-phone2-err]') }, $$('[data-au-pw-err]'));
            $$('[data-au-pw]').trigger('focus').trigger('select');
        } finally { setBusy(false); }
    });

    /* ── مرحله‌ی تکمیل ثبت‌نام ── */
    const $npw = $$('[data-au-npw]');
    $npw.on('input', function () {
        const lv = passwordLevel($(this).val());
        const $m = $$('[data-au-meter]').attr('data-level', lv);
        $m.find('[data-au-meter-t]').text(lv ? t('profile.strength.' + ['weak', 'fair', 'good', 'strong'][lv - 1]) : '');
        err($$('[data-au-npw-err]'), '');
    });
    $$('[data-au-fn]').on('input', () => err($$('[data-au-fn-err]'), ''));
    $$('[data-au-ln]').on('input', () => err($$('[data-au-ln-err]'), ''));
    $$('[data-au-terms]').on('change', () => err($$('[data-au-terms-err]'), ''));
    $$('[data-au-ref-open]').on('click', function () {
        const open = $$('[data-au-ref-box]').prop('hidden');
        $$('[data-au-ref-box]').prop('hidden', !open);
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) $$('[data-au-ref]').trigger('focus');
    });
    const refInit = String($root.attr('data-ref') || '');
    if (refInit) { $$('[data-au-ref]').val(refInit); $$('[data-au-ref-box]').prop('hidden', false); $$('[data-au-ref-open]').attr('aria-expanded', 'true'); }

    $$('[data-au-form="profile"]').on('submit', async function () {
        if (S.busy) return;
        clearErrors($(this));
        const fn = String($$('[data-au-fn]').val() || '').trim();
        const ln = String($$('[data-au-ln]').val() || '').trim();
        const pw = String($npw.val() || '');
        const issue = passwordIssue(pw);
        const checks = [
            [!fn, $$('[data-au-fn-err]'), 'v.first_required', '[data-au-fn]'],
            [!ln, $$('[data-au-ln-err]'), 'v.last_required', '[data-au-ln]'],
            [!!issue, $$('[data-au-npw-err]'), 'v.' + issue, '[data-au-npw]'],
            [!$$('[data-au-terms]').prop('checked'), $$('[data-au-terms-err]'), 'v.terms_required', '[data-au-terms]'],
        ];
        let first = null;
        checks.forEach(([bad, $e, key, sel]) => { if (bad) { err($e, t(key)); if (!first) first = sel; } });
        if (first) { $$(first).trigger('focus'); return; }
        setBusy(true);
        try {
            const body = { phone: S.phone, ticket: S.ticket, first_name: fn, last_name: ln, password: pw, terms: true };
            const ref = String($$('[data-au-ref]').val() || '').trim();
            if (ref) body.ref = ref;
            const res = await call('register', body);
            finish(res.redirect);
        } catch (e) {
            fail(e, { first_name: $$('[data-au-fn-err]'), last_name: $$('[data-au-ln-err]'), password: $$('[data-au-npw-err]'), terms: $$('[data-au-terms-err]'), ref: $$('[data-au-ref]').closest('.au-field').find('.au-hint').first() }, $$('[data-au-form-err]'));
        } finally { setBusy(false); }
    });

    /* ── پایان ── */
    function finish(redirect) {
        const to = safePath(redirect, safePath($root.attr('data-redirect')));
        go('done', false);
        $$('[data-au-step="done"]').trigger('focus');
        $root.trigger('auth:success', { redirect: to });
        if (demo) { $$('[data-au-done-l]').text(t('done.demo')); return; }
        setTimeout(() => location.assign(to), reduce ? 0 : 700);
    }

    /* ── شروع ── */
    $$('[data-au-demo]').prop('hidden', !demo);
    title();
    dots();
    $$('[data-au-notsent]').prop('hidden', true);
    $$('[data-au-step]').not('[data-au-step=phone]').prop('hidden', true);
    $phone.trigger('focus');
    API.auth.state = () => Object.assign({}, S);
});
