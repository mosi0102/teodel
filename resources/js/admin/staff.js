import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast } from './common';

/**
 * افزودن / ویرایش ادمین (/admin/staff/create و /admin/staff/{id}/edit) — فقط مدیر اصلی.
 * قرارداد داده و بک‌اند: app/Support/AdminStaff.php
 *
 * - شماره‌ی موبایل: رقم فارسی/عربی، فاصله و +98 پذیرفته و به 09xxxxxxxxx تبدیل می‌شود.
 * - رمز پیش‌فرض: ساخت تصادفی امن (crypto.getRandomValues)، نمایش/پنهان، کپی، سنجش قدرت؛ حداقل ۸ نویسه.
 *   بعد از ساخت ادمین، اطلاعات ورود فقط یک بار در دیالوگ نشان داده می‌شود (برای کپی/ارسال).
 * - دسترسی: نقش آماده جدول را پر می‌کند؛ تغییر دستی هر سطر نقش را «سفارشی» می‌کند. حداقل یک بخش باید دسترسی داشته باشد.
 *
 * 🔌 بک‌اند (اولویت: رویداد ← Teodel.admin.staff.api ← آدرس روی ریشه ← حالت دمو):
 *   ذخیره:  data-save-url  POST/PUT { name, phone, password?, role, permissions, status, must_change_password, send_sms } ← { id } | 422 { message, errors }
 *           $(document).on('admin:staff-save', (e, { payload, done, fail }) => { e.preventDefault(); … })
 *   رمز:    data-reset-url POST { password, must_change_password: true, send_sms } | admin:staff-reset
 *   حذف:    data-delete-url DELETE | admin:staff-delete
 * LOCAL = true: دمو (localStorage). با اتصال بک‌اند false کنید.
 */
const LOCAL = true;
const LEVELS = ['none', 'view', 'edit', 'full'];
const API = (window.Teodel = window.Teodel || {});
API.admin = API.admin || {};
API.admin.staff = API.admin.staff || {};

/** ۰۹۱۲… یا null */
export function normalizePhone(raw) {
    let d = String(raw || '')
        .replace(/[۰-۹]/g, (c) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(c))
        .replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c))
        .replace(/\D/g, '');
    if (d.startsWith('0098')) d = '0' + d.slice(4);
    else if (d.startsWith('98')) d = '0' + d.slice(2);
    else if (d.startsWith('9') && d.length === 10) d = '0' + d;
    return /^09\d{9}$/.test(d) ? d : null;
}

/** رمز تصادفی خوانا (بدون نویسه‌های گیج‌کننده مثل O/0 و l/1) */
export function generatePassword(len = 10) {
    const sets = ['ABCDEFGHJKMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '@#$%*!'];
    const all = sets.join('');
    const rnd = (n) => {
        const a = new Uint32Array(1);
        (window.crypto || window.msCrypto).getRandomValues(a);
        return a[0] % n;
    };
    const out = sets.map((s) => s[rnd(s.length)]); // از هر دسته حداقل یکی
    while (out.length < len) out.push(all[rnd(all.length)]);
    for (let i = out.length - 1; i > 0; i--) {
        const j = rnd(i + 1);
        [out[i], out[j]] = [out[j], out[i]];
    }
    return out.join('');
}

/** ۰ تا ۴ */
export function strength(pw) {
    const s = String(pw || '');
    if (s.length < 8) return s ? 1 : 0;
    let n = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9]/].filter((r) => r.test(s)).length;
    if (s.length >= 12) n++;
    return Math.max(1, Math.min(4, n));
}

const fmtPhone = (p) => (p ? p.slice(0, 4) + ' ' + p.slice(4, 7) + ' ' + p.slice(7) : '');

async function call(evName, payload, fn, url, method) {
    const ev = $.Event(evName);
    const viaEvent = await new Promise((resolve) => {
        $(document).trigger(ev, [{ payload, done: (r) => resolve({ ok: r || {} }), fail: (m) => resolve({ err: m || '' }) }]);
        if (!ev.isDefaultPrevented()) resolve(null);
    });
    if (viaEvent) {
        if (viaEvent.err !== undefined) throw { message: viaEvent.err };
        return viaEvent.ok;
    }
    if (typeof fn === 'function') return fn(payload);
    if (url) return send(url, payload, method);
    return null;
}

$('[data-staff]').each(function () {
    const $root = $(this);
    const M = readJSON($root, 'data-model', {});
    const presets = readJSON($root, 'data-presets', {});
    const T = readJSON($root, 'data-t', {});
    const t = (k, r = {}) => String(T[k] || k).replace(/:(\w+)/g, (m, x) => (x in r ? r[x] : m));
    const urls = { save: $root.attr('data-save-url'), reset: $root.attr('data-reset-url'), del: $root.attr('data-delete-url'), list: $root.attr('data-list-url'), edit: $root.attr('data-edit-url') };
    const isOwner = M.role === 'owner';
    const isNew = !M.id;
    let dirty = false;

    /* ── فیلدها ── */
    $root.find('[data-st="name"]').val(M.name || '');
    $root.find('[data-st="phone"]').val(fmtPhone(M.phone));
    $root.find('[data-st="active"]').prop('checked', M.status !== 'disabled');
    $root.find('[data-st="must_change_password"]').prop('checked', !!M.must_change_password);

    const err = (k, msg) => {
        const $e = $root.find('[data-st-err="' + k + '"]');
        $e.text(msg || '').prop('hidden', !msg);
        $e.closest('.ad-field').toggleClass('has-error', !!msg);
    };
    $root.on('input change', '[data-st], [data-st-pw], [data-st-sms]', function () {
        dirty = true;
        const k = $(this).attr('data-st');
        if (k) err(k, '');
        if (k === 'name') $root.find('[data-st-title]').text(this.value.trim() || t('untitled'));
    });
    $root.on('blur', '[data-st="phone"]', function () {
        const p = normalizePhone(this.value);
        if (p) this.value = fmtPhone(p);
        else if (this.value.trim()) err('phone', t('phone_bad'));
    });

    /* ── رمز ── */
    const $pw = $root.find('[data-st-pw]');
    function paintMeter() {
        const s = strength($pw.val());
        $root.find('[data-st-meter]').attr('data-s', s);
        $root.find('[data-st-meter-t]').text(s ? t('pw_' + s) : t('pw_rule'));
        err('password', '');
    }
    $root.on('input', '[data-st-pw]', paintMeter);
    $root.on('click', '[data-st-pw-gen]', () => {
        $pw.val(generatePassword()).attr('type', 'text');
        $root.find('[data-st-pw-toggle]').attr('aria-pressed', 'true');
        paintMeter();
        dirty = true;
    });
    $root.on('click', '[data-st-pw-toggle]', function () {
        const show = $pw.attr('type') === 'password';
        $pw.attr('type', show ? 'text' : 'password');
        $(this).attr('aria-pressed', String(show));
    });
    const copy = (text) => {
        if (navigator.clipboard) navigator.clipboard.writeText(text).then(() => toast(t('copied')), () => {});
    };
    $root.on('click', '[data-st-pw-copy]', () => { if ($pw.val()) copy($pw.val()); });
    if (isNew && !$pw.val()) {
        $pw.val(generatePassword());
        paintMeter();
    }

    /* ── دسترسی ── */
    const perms = Object.assign({}, M.permissions || {});
    let role = M.role || 'custom';
    function paintMatrix() {
        $root.find('[data-st-sec]').each(function () {
            const s = $(this).attr('data-st-sec');
            const lv = perms[s] || 'none';
            $(this).attr('data-lv', lv).find('[data-st-lv]').each(function () { this.checked = this.value === lv; });
        });
        $root.find('[data-st-role]').each(function () { this.checked = this.value === role; });
        const n = Object.values(perms).filter((v) => v !== 'none').length;
        $root.find('[data-st-summary]').text(t('summary', { n: n.toLocaleString(document.documentElement.lang === 'fa' ? 'fa-IR' : 'en'), total: Object.keys(perms).length.toLocaleString(document.documentElement.lang === 'fa' ? 'fa-IR' : 'en') }));
        err('permissions', '');
    }
    /** اگر جدول دقیقاً با یک نقش آماده یکی باشد، همان نقش؛ وگرنه «سفارشی» */
    function detectRole() {
        const hit = Object.keys(presets).find((r) => Object.keys(perms).every((s) => (presets[r][s] || 'none') === perms[s]));
        role = hit || 'custom';
    }
    $root.on('change', '[data-st-role]', function () {
        role = this.value;
        if (presets[role]) Object.assign(perms, presets[role]);
        paintMatrix();
        dirty = true;
    });
    $root.on('change', '[data-st-lv]', function () {
        perms[$(this).closest('[data-st-sec]').attr('data-st-sec')] = this.value;
        detectRole();
        paintMatrix();
        dirty = true;
    });
    $root.on('click', '[data-st-all]', function () {
        const lv = $(this).attr('data-st-all');
        Object.keys(perms).forEach((s) => { perms[s] = lv; });
        detectRole();
        paintMatrix();
        dirty = true;
    });
    if (!isOwner) paintMatrix();

    /* ── ذخیره ── */
    function collect() {
        const name = String($root.find('[data-st="name"]').val() || '').trim();
        const phone = normalizePhone($root.find('[data-st="phone"]').val());
        let ok = true;
        if (!name) { err('name', t('name_req')); ok = false; }
        if (!phone) { err('phone', t('phone_bad')); ok = false; }
        const body = {
            name, phone, role: isOwner ? 'owner' : role,
            permissions: isOwner ? M.permissions : perms,
            status: isOwner || $root.find('[data-st="active"]').prop('checked') ? 'active' : 'disabled',
        };
        if (!isOwner && !Object.values(perms).some((v) => v !== 'none')) { err('permissions', t('perm_req')); ok = false; }
        if (isNew) {
            body.password = String($pw.val() || '');
            body.must_change_password = $root.find('[data-st="must_change_password"]').prop('checked');
            body.send_sms = $root.find('[data-st-sms]').prop('checked');
            if (body.password.length < 8) { err('password', t('pw_rule')); ok = false; }
        }
        if (!ok) {
            const $first = $root.find('.has-error').first();
            if ($first[0]) $first[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
            toast(t('check'), true);
        }
        return ok ? body : null;
    }

    async function save() {
        const body = collect();
        if (!body) return;
        const $b = $root.find('[data-st-save]').prop('disabled', true);
        $root.find('[data-st-msg]').text(t('saving'));
        try {
            let r = await call('admin:staff-save', Object.assign({ id: M.id }, body), API.admin.staff.api && API.admin.staff.api.save, urls.save, M.id ? 'PUT' : 'POST');
            if (r === null) {
                if (!LOCAL) throw { message: t('no_backend') };
                // دمو: رمز ذخیره نمی‌شود (فقط بک‌اند هش‌شده نگه می‌دارد)
                const id = M.id || Date.now() % 100000;
                const keep = Object.assign({}, body, { id });
                delete keep.password;
                try { localStorage.setItem('teodel:admin:staff:' + id, JSON.stringify(keep)); } catch (e) { /* */ }
                r = { id };
            }
            dirty = false;
            $root.find('[data-st-msg]').text(t('saved'));
            $(document).trigger('admin:staff-saved', [{ id: r.id, role: body.role }]);
            if (isNew) {
                M.id = r.id;
                if (urls.save && urls.edit) history.replaceState(null, '', urls.edit.replace(':id', r.id));
                $('#st-done [data-st-done-title]').text(t('done_title', { name: body.name }));
                $('#st-done [data-st-done-phone]').text(fmtPhone(body.phone));
                $('#st-done [data-st-done-pw]').text(body.password);
                $('#st-done [data-st-done-must]').prop('hidden', !body.must_change_password);
                $pw.val('');
                openDialog('#st-done');
            } else {
                toast(t('saved'));
                $root.find('[data-st-role-badge]').text(t('role_' + body.role));
            }
        } catch (e) {
            $root.find('[data-st-msg]').text('');
            toast((e && e.message) || t('save_error'), true);
            if (e && e.errors) Object.keys(e.errors).forEach((k) => err(k, [].concat(e.errors[k])[0]));
        } finally {
            $b.prop('disabled', false);
        }
    }
    $root.on('click', '[data-st-save]', save);
    $(document).on('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            save();
        }
    });
    window.addEventListener('beforeunload', (e) => {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // بعد از بستن دیالوگ «ساخته شد»، صفحه‌ی ویرایش همین ادمین باز شود (فرم «ساخت» دوباره ارسال نشود)
    $('#st-done').on('dialog:close', () => {
        // دمو: شناسه فقط در مرورگر است و سرور آن را نمی‌شناسد، پس به لیست برمی‌گردیم
        if (M.id) location.href = urls.save && urls.edit ? urls.edit.replace(':id', M.id) : urls.list;
    });

    $(document).on('click', '#st-done [data-st-done-copy]', () => {
        const $d = $('#st-done');
        copy(t('cred_text', { name: $d.find('[data-st-done-title]').text(), phone: $d.find('[data-st-done-phone]').text(), pw: $d.find('[data-st-done-pw]').text(), url: location.origin + '/login' }));
    });

    /* ── بازنشانی رمز ── */
    $root.on('click', '[data-st-reset-open]', () => {
        $('#st-reset [data-st-reset-pw]').val(generatePassword());
        openDialog('#st-reset');
    });
    $(document).on('click', '#st-reset [data-st-reset-gen]', () => $('#st-reset [data-st-reset-pw]').val(generatePassword()));
    $(document).on('click', '#st-reset [data-st-reset-copy]', () => copy($('#st-reset [data-st-reset-pw]').val()));
    $(document).on('click', '#st-reset [data-st-reset-go]', async function () {
        const payload = { id: M.id, password: $('#st-reset [data-st-reset-pw]').val(), must_change_password: true, send_sms: $('#st-reset [data-st-reset-sms]').prop('checked') };
        try {
            await call('admin:staff-reset', payload, API.admin.staff.api && API.admin.staff.api.reset, urls.reset, 'POST');
            closeDialog('#st-reset');
            toast(t('reset_done'));
        } catch (e) {
            toast((e && e.message) || t('save_error'), true);
        }
    });

    /* ── حذف ── */
    $(document).on('click', '#st-delete [data-st-delete]', async () => {
        try {
            const r = await call('admin:staff-delete', { id: M.id }, API.admin.staff.api && API.admin.staff.api.remove, urls.del, 'DELETE');
            if (r === null) { try { localStorage.removeItem('teodel:admin:staff:' + M.id); } catch (e) { /* */ } }
            dirty = false;
            location.href = urls.list || '/admin/staff';
        } catch (e) {
            toast((e && e.message) || t('save_error'), true);
        }
    });
});
