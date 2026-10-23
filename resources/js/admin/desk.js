import $ from 'jquery';
import { readJSON, send, toast, csrf, ago } from './common';
import { API, viaEvent, tFrom, el } from './bank';

/**
 * تیکت‌ها (/admin/tickets/{id}): ارسال پاسخ و یادداشت داخلی، پاسخ آماده، AI، پیوست، بستن/بازکردن.
 * لیست تیکت‌ها GET است و با users.js (فرم [data-ad-filter]) کار می‌کند. قرارداد: app/Support/AdminTickets.php
 *
 * 🔌 بک‌اند (آدرس‌ها روی ریشه‌ی صفحه؛ __ID__ = شناسه‌ی تیکت):
 *   data-reply-url  POST multipart { message, files[], internal, close } ← { message, status }
 *   data-close-url / data-reopen-url  POST ← { status }
 *   یا رویدادهای لغوشدنی admin:ticket-reply { ticket, payload, done(message?, status?), fail(msg) } و admin:ticket-status { ticket, to, done(), fail(msg) }.
 *   بدون آدرس/رویداد: حالت دمو (فقط ظاهر).
 */

export const MAX_FILES = 3;
export const MAX_MB = 2;
export const MAX_LEN = 3000;

/* ── توابع خالص (تست‌شده) ───────────────────────────────────── */

/** کد خطای پاسخ یا null (کلید lang: errors.*) */
export function validateReply(text, files) {
    const s = String(text || '').trim();
    if (!s) return 'empty';
    if (s.length > MAX_LEN) return 'long';
    if ((files || []).length > MAX_FILES) return 'files';
    if ((files || []).some((f) => f.size > MAX_MB * 1048576)) return 'size';
    return null;
}

/** متن پاسخ آماده: {name} با نام کاربر جایگزین می‌شود */
export const fillCanned = (body, name) => String(body || '').split('{name}').join(String(name || '').trim());

/** پاسخ آماده را به متن فعلی اضافه می‌کند (جدا با یک خط خالی) */
export const appendCanned = (cur, body) => (String(cur || '').trim() ? String(cur).replace(/\s+$/, '') + '\n\n' + body : body);

export function fmtSize(b) {
    if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
    return Math.max(1, Math.round(b / 1024)) + ' KB';
}

/* ── نمونه‌ی AI در حالت دمو ─────────────────────────────────── */
API.admin = API.admin || {};
API.admin.ai = API.admin.ai || {};
API.admin.ai.demos = API.admin.ai.demos || {};
API.admin.ai.demos.ticket_reply = (payload) => {
    const fa = (payload.locale || document.documentElement.lang) === 'fa';
    const sub = (payload.seeds && payload.seeds.subject) || '';
    return {
        fields: {
            reply: fa
                ? `سلام {name} عزیز، وقت بخیر.\nممنون که درباره‌ی «${sub}» پیام دادید. موضوع را بررسی کردیم؛ لطفاً اگر مشکل ادامه داشت یک اسکرین‌شات و نوع دستگاهتان را برایمان بفرستید تا سریع‌تر پیگیری کنیم.`
                : `Hi {name},\nThanks for writing to us about “${sub}”. We have looked into it; if the problem continues, please send a screenshot and your device type so we can follow up faster.`,
        },
        missing: [],
    };
};

$(function () {
    const $root = $('[data-td-show]');
    if (!$root.length) return;
    const t = tFrom(readJSON($root, 'data-t', {}));
    const canned = readJSON($root, 'data-canned', []);
    const id = $root.attr('data-id');
    const userName = $root.attr('data-user-name') || '';
    const $thread = $root.find('[data-td-thread]');
    const $text = $root.find('[data-td-text]');
    const $err = $root.find('[data-td-err]');
    const $chips = $root.find('[data-td-chips]');
    let status = $root.attr('data-status');
    let files = [];
    let busy = false;
    const KEY = 'teodel:admin:tdraft:' + id;
    const tone = { open: 'warn', answered: 'ok', closed: '' };
    const url = (a) => ($root.attr('data-' + a + '-url') || '').replace('__ID__', id);

    const scrollEnd = () => { const last = $thread.children().last()[0]; if (last && last.scrollIntoView) last.scrollIntoView({ block: 'nearest' }); };

    /* ← پیش‌نویس ذخیره‌شده */
    try {
        const d = localStorage.getItem(KEY);
        if (d && !$text.val()) { $text.val(d); toast(t('s.draft_restored')); }
    } catch (e) { /* ignore */ }
    let dt = 0;
    const saveDraft = () => {
        clearTimeout(dt);
        dt = setTimeout(() => { try { const v = String($text.val() || ''); v ? localStorage.setItem(KEY, v) : localStorage.removeItem(KEY); } catch (e) { /* ignore */ } }, 400);
    };
    $text.on('input', function () {
        $err.prop('hidden', true).closest('.ad-field').removeClass('has-error');
        saveDraft();
    });
    // AI و پاسخ آماده ممکن است {name} داشته باشند → نام کاربر
    $(document).on('admin:ai-applied', () => {
        $text.val(fillCanned($text.val(), userName)); // بدون trigger('input') تا علامت «ساخته‌ی AI» برنگردد
        saveDraft();
    });

    /* ── حالت یادداشت داخلی ── */
    $root.on('change', '[data-td-internal]', function () {
        const on = $(this).prop('checked');
        $root.find('[data-td-composer]').toggleClass('is-note', on);
        $root.find('[data-td-label]').text(t(on ? 's.internal' : 's.reply'));
        $text.attr('placeholder', t(on ? 's.note_ph' : 's.reply_ph'));
        $root.find('[data-td-send-t]').text(t(on ? 's.send_note' : 's.send'));
        $root.find('[data-td-send=close]').prop('hidden', on);
    });

    /* ── پاسخ آماده ── */
    $root.on('change', '[data-td-canned]', function () {
        const c = canned.find((x) => x.id === $(this).val());
        $(this).val('');
        if (!c) return;
        $text.val(appendCanned($text.val(), fillCanned(c.body, userName))).trigger('input').trigger('focus');
    });

    /* ── پیوست ── */
    function renderChips() {
        $chips.empty();
        files.forEach((f, i) => {
            const li = el('li', 'td-chip');
            li.appendChild(el('span', 'truncate', f.name));
            const sz = el('small', null, fmtSize(f.size));
            sz.setAttribute('dir', 'ltr');
            li.appendChild(sz);
            const b = el('button', null, '×');
            b.type = 'button';
            b.setAttribute('data-i', i);
            b.setAttribute('aria-label', t('s.remove') + ': ' + f.name);
            li.appendChild(b);
            $chips.append(li);
        });
    }
    $root.on('change', '[data-td-file]', function () {
        const picked = [...(this.files || [])];
        this.value = '';
        let msg = null;
        picked.forEach((f) => {
            if (!/^image\//.test(f.type)) return;
            if (files.length >= MAX_FILES) { msg = 'files'; return; }
            if (f.size > MAX_MB * 1048576) { msg = 'size'; return; }
            files.push(f);
        });
        renderChips();
        if (msg) toast(t('errors.' + msg), true);
    });
    $chips.on('click', 'button', function () { files.splice(parseInt($(this).attr('data-i'), 10), 1); renderChips(); });

    /* ── نمایش پیام ── */
    function addMessage(m) {
        const li = el('li', 'td-msg');
        li.setAttribute('data-from', m.from);
        const h = el('div', 'td-msg__h');
        h.appendChild(el('b', null, m.name || t('s.you')));
        if (m.from === 'note') { const tag = el('span', 'ad-badge', t('s.note_tag')); tag.setAttribute('data-tone', 'warn'); h.appendChild(tag); }
        const tm = el('time', null, ago(m.at));
        h.appendChild(tm);
        li.appendChild(h);
        const p = el('p', 'td-msg__t', m.text);
        p.setAttribute('dir', 'auto');
        li.appendChild(p);
        if (m.files && m.files.length) {
            const ul = el('ul', 'td-files');
            m.files.forEach((f) => { const l = el('li'); const a = el('a'); a.href = f.url || '#'; a.target = '_blank'; a.rel = 'noopener'; a.appendChild(el('span', null, f.name)); l.appendChild(a); ul.appendChild(l); });
            li.appendChild(ul);
        }
        $thread.append(li);
        scrollEnd();
    }
    function setStatus(s) {
        status = s;
        $root.attr('data-status', s);
        $root.find('[data-td-status]').text(t('status.' + s)).attr('data-tone', tone[s] || '');
        const closed = s === 'closed';
        $root.find('[data-td-composer]').prop('hidden', closed);
        $root.find('[data-td-closed]').prop('hidden', !closed);
        $root.find('[data-td-close]').prop('hidden', closed);
        $root.find('[data-td-reopen]').prop('hidden', !closed);
        $root.find('[data-td-updated]').text(ago(Math.floor(Date.now() / 1000)));
    }

    /* ── ارسال ── */
    async function sendReply(close) {
        if (busy) return;
        const internal = $root.find('[data-td-internal]').prop('checked');
        const code = validateReply($text.val(), files);
        if (code) {
            $err.text(t('errors.' + code)).prop('hidden', false).closest('.ad-field').addClass('has-error');
            if (code === 'empty' || code === 'long') $text.trigger('focus');
            return;
        }
        const message = String($text.val()).trim();
        const payload = { message, files: files.slice(), internal, close: !!close && !internal };
        busy = true;
        const $btns = $root.find('[data-td-send]').prop('disabled', true);
        $root.find('[data-td-send-t]').text(t('s.sending'));
        try {
            let res = null;
            const ev = await viaEvent('admin:ticket-reply', { ticket: id, payload });
            if (ev) {
                if (ev.err !== undefined) throw { message: ev.err };
                res = ev.ok || {};
            } else if (url('reply')) {
                const fd = new FormData();
                fd.append('message', message);
                fd.append('internal', internal ? '1' : '0');
                fd.append('close', payload.close ? '1' : '0');
                files.forEach((f) => fd.append('files[]', f));
                const r = await fetch(url('reply'), { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' });
                let data = {};
                try { data = await r.json(); } catch (e) { /* ignore */ }
                if (!r.ok) throw { message: data.message || '', errors: data.errors };
                res = data;
            }
            const m = (res && res.message) || {
                id: Date.now(), from: internal ? 'note' : 'support', name: t('s.you'), text: message, at: Math.floor(Date.now() / 1000),
                files: files.map((f) => ({ name: f.name, url: URL.createObjectURL(f), size: f.size })),
            };
            addMessage(m);
            if (!internal) setStatus((res && res.status) || (payload.close ? 'closed' : 'answered'));
            $text.val('');
            files = [];
            renderChips();
            try { localStorage.removeItem(KEY); } catch (e) { /* ignore */ }
            toast(t(internal ? 's.noted' : 's.sent'));
        } catch (e) {
            const msg = (e && e.errors && Object.values(e.errors)[0]) || (e && e.message) || t('errors.send');
            $err.text([].concat(msg)[0]).prop('hidden', false).closest('.ad-field').addClass('has-error');
        } finally {
            busy = false;
            $btns.prop('disabled', false);
            $root.find('[data-td-send-t]').text(t($root.find('[data-td-internal]').prop('checked') ? 's.send_note' : 's.send'));
        }
    }
    $root.on('click', '[data-td-send]', function () { sendReply($(this).attr('data-td-send') === 'close'); });
    $text.on('keydown', (e) => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); sendReply(false); } });

    /* ── بستن / بازکردن ── */
    async function change(to, endpoint) {
        const $b = $root.find(to === 'closed' ? '[data-td-close]' : '[data-td-reopen]').prop('disabled', true);
        try {
            const ev = await viaEvent('admin:ticket-status', { ticket: id, to });
            if (ev) { if (ev.err !== undefined) throw { message: ev.err }; }
            else if (url(endpoint)) await send(url(endpoint), {}, 'POST');
            setStatus(to === 'closed' ? 'closed' : 'open');
            toast(t(to === 'closed' ? 's.closed_ok' : 's.reopened_ok'));
        } catch (e) {
            toast((e && e.message) || t('errors.status'), true);
        } finally { $b.prop('disabled', false); }
    }
    $root.on('click', '[data-td-close]', () => change('closed', 'close'));
    $root.on('click', '[data-td-reopen]', () => change('open', 'reopen'));

    scrollEnd();
    API.admin.ticket = { status: () => status, files: () => files.length };
});
