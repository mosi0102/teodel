import $ from 'jquery';
import { openDialog } from '../modules/dialogs';
import { readJSON, send, toast, ago } from './common';
import { API, viaEvent, tFrom, el } from './bank';

/**
 * گزارش خطا (/admin/reports/{id}): تغییر وضعیت/اولویت، یادداشت داخلی، بزرگ‌نمایی اسکرین‌شات، کپی اطلاعات.
 * لیست GET است و با users.js (فرم [data-ad-filter]) کار می‌کند. قرارداد: app/Support/AdminReports.php
 *
 * 🔌 بک‌اند (آدرس‌ها روی ریشه‌ی صفحه؛ __ID__ = شناسه‌ی گزارش):
 *   data-update-url  POST { status?, priority?, notify? } ← { status, priority }
 *   data-note-url    POST { text } ← { note:{ id, by, text, at } }
 *   یا رویدادهای لغوشدنی admin:report-update { report, patch, notify, done(res), fail(msg) } و admin:report-note { report, text, done(note), fail(msg) }.
 *   بدون آدرس/رویداد: حالت دمو (فقط ظاهر).
 */

export const MAX_NOTE = 1000;

/* ── توابع خالص (تست‌شده) ───────────────────────────────────── */

/** کد خطای یادداشت یا null (کلید lang: errors.*) */
export function validateNote(text) {
    const s = String(text || '').trim();
    if (!s) return 'note_empty';
    if (s.length > MAX_NOTE) return 'note_long';
    return null;
}

/** آیا این تغییر وضعیت باید به کاربر اعلان بدهد؟ (فقط رفتن به «برطرف شد» یا «ردشده» با تیک اعلان) */
export const shouldNotify = (from, to, checked) => !!checked && from !== to && (to === 'resolved' || to === 'rejected');

$(function () {
    const $root = $('[data-rp-show]');
    if (!$root.length) return;
    const t = tFrom(readJSON($root, 'data-t', {}));
    const id = $root.attr('data-id');
    const url = (a) => ($root.attr('data-' + a + '-url') || '').replace('__ID__', id);
    const tones = { new: 'warn', in_progress: 'info', resolved: 'ok', rejected: '' };
    const ptones = { low: '', normal: 'info', high: 'bad' };
    let status = $root.attr('data-status');
    let priority = $root.attr('data-priority');
    let busy = false;

    /* ── وضعیت و اولویت ── */
    function paint() {
        $root.find('[data-rp-status] button').each(function () { $(this).attr('aria-pressed', $(this).attr('data-v') === status ? 'true' : 'false'); });
        $root.find('[data-rp-prio] button').each(function () { $(this).attr('aria-pressed', $(this).attr('data-v') === priority ? 'true' : 'false'); });
        $root.find('[data-rp-badge]').text(t('status.' + status)).attr('data-tone', tones[status] || '');
        $root.find('[data-rp-pbadge]').text(t('priority.' + priority)).attr('data-tone', ptones[priority] || '');
    }
    async function update(patch) {
        if (busy) return;
        const notify = shouldNotify(status, patch.status || status, $root.find('[data-rp-notify]').prop('checked'));
        busy = true;
        $root.find('[data-rp-status] button, [data-rp-prio] button').prop('disabled', true);
        try {
            let res = null;
            const ev = await viaEvent('admin:report-update', { report: id, patch, notify });
            if (ev) {
                if (ev.err !== undefined) throw { message: ev.err };
                res = ev.ok || {};
            } else if (url('update')) {
                res = await send(url('update'), Object.assign({}, patch, notify ? { notify: true } : {}), 'POST');
            }
            status = (res && res.status) || patch.status || status;
            priority = (res && res.priority) || patch.priority || priority;
            paint();
            toast(t(notify ? 's.updated_notify' : 's.updated'));
        } catch (e) {
            toast((e && e.message) || t('errors.update'), true);
        } finally {
            busy = false;
            $root.find('[data-rp-status] button, [data-rp-prio] button').prop('disabled', false);
        }
    }
    $root.on('click', '[data-rp-status] button', function () {
        const v = $(this).attr('data-v');
        if (v !== status) update({ status: v });
    });
    $root.on('click', '[data-rp-prio] button', function () {
        const v = $(this).attr('data-v');
        if (v !== priority) update({ priority: v });
    });

    /* ── یادداشت داخلی ── */
    const $note = $root.find('[data-rp-note]');
    const $err = $root.find('[data-rp-note-err]');
    const $list = $root.find('[data-rp-notes]');
    const KEY = 'teodel:admin:rpnote:' + id;
    try {
        const d = localStorage.getItem(KEY);
        if (d && !$note.val()) $note.val(d);
    } catch (e) { /* ignore */ }
    $note.on('input', function () {
        $err.prop('hidden', true).closest('.ad-field').removeClass('has-error');
        try { const v = String($note.val() || ''); v ? localStorage.setItem(KEY, v) : localStorage.removeItem(KEY); } catch (e) { /* ignore */ }
    });
    function addNote(n) {
        const li = el('li', 'td-msg');
        li.setAttribute('data-from', 'note');
        const h = el('div', 'td-msg__h');
        h.appendChild(el('b', null, n.by || t('s.you')));
        h.appendChild(el('time', null, ago(n.at)));
        li.appendChild(h);
        const p = el('p', 'td-msg__t', n.text);
        p.setAttribute('dir', 'auto');
        li.appendChild(p);
        $list.append(li);
        $root.find('[data-rp-nonotes]').prop('hidden', true);
    }
    async function saveNote() {
        if (busy) return;
        const code = validateNote($note.val());
        if (code) {
            $err.text(t('errors.' + code)).prop('hidden', false).closest('.ad-field').addClass('has-error');
            $note.trigger('focus');
            return;
        }
        const text = String($note.val()).trim();
        busy = true;
        const $b = $root.find('[data-rp-add]').prop('disabled', true);
        $root.find('[data-rp-add-t]').text(t('s.adding'));
        try {
            let res = null;
            const ev = await viaEvent('admin:report-note', { report: id, text });
            if (ev) {
                if (ev.err !== undefined) throw { message: ev.err };
                res = ev.ok;
            } else if (url('note')) {
                const r = await send(url('note'), { text }, 'POST');
                res = r.note || r;
            }
            addNote(res && res.text ? res : { id: Date.now(), by: t('s.you'), text, at: Math.floor(Date.now() / 1000) });
            $note.val('');
            try { localStorage.removeItem(KEY); } catch (e) { /* ignore */ }
            toast(t('s.note_added'));
        } catch (e) {
            $err.text((e && e.errors && [].concat(Object.values(e.errors)[0])[0]) || (e && e.message) || t('errors.note')).prop('hidden', false).closest('.ad-field').addClass('has-error');
        } finally {
            busy = false;
            $b.prop('disabled', false);
            $root.find('[data-rp-add-t]').text(t('s.add_note'));
        }
    }
    $root.on('click', '[data-rp-add]', saveNote);
    $note.on('keydown', (e) => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); saveNote(); } });

    /* ── اسکرین‌شات: بزرگ‌نمایی ── */
    $root.on('click', '[data-rp-zoom]', () => {
        $('[data-rp-zbody]').removeClass('is-actual');
        $('[data-rp-fit]').text($('[data-rp-fit]').attr('data-actual'));
        openDialog('#rp-zoom');
    });
    $(document).on('click', '[data-rp-fit]', function () {
        const on = $('[data-rp-zbody]').toggleClass('is-actual').hasClass('is-actual');
        $(this).text($(this).attr(on ? 'data-fit' : 'data-actual'));
    });

    /* ── کپی اطلاعات ── */
    $root.on('click', '[data-rp-copy]', async function () {
        const text = readJSON($root, 'data-copy', '') || '';
        let ok = false;
        try {
            await navigator.clipboard.writeText(text);
            ok = true;
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { ok = document.execCommand('copy'); } catch (e2) { /* ignore */ }
            ta.remove();
        }
        toast(ok ? t('s.copied') : t('errors.update'), !ok);
    });

    paint();
    API.admin.report = { status: () => status, priority: () => priority };
});
