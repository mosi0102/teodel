import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/**
 * تیکت‌ها (/tickets): لیست و وضعیت، تیکت جدید، گفتگو با پشتیبانی، جواب‌دادن، بستن و بازکردن دوباره — همه بدون رفرش.
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 * قرارداد داده و آدرس‌ها: app/Support/Tickets.php (بالای فایل).
 * دو راه اتصال:
 *   ۱) آدرس‌ها را در کنترلر بدهید ($urls) تا data-*-url روی ریشه‌ی صفحه بنشیند؛ همه‌چیز خودکار می‌رود.
 *   ۲) یا توابع را جایگزین کنید (هر کدام Promise برمی‌گرداند؛ ورودی files آرایه‌ی File است):
 *        Teodel.tickets.api.create({ dept, subject, message, files })   ← ticket
 *        Teodel.tickets.api.reply(id, { message, files })               ← { message, status? }
 *        Teodel.tickets.api.close(id)  /  .reopen(id)                   ← { status }
 *        Teodel.tickets.api.detail(id)                                  ← ticket (با messages)
 *        Teodel.tickets.api.read(id)                                    ← (اعلام «دیده شد»)
 *      خطای اعتبارسنجی: throw { errors: { subject: 'متن' } } یا { message: 'متن' }.
 * رویدادها (بعد از موفقیت): ticket:created, ticket:replied, ticket:closed, ticket:reopened, ticket:read  ← (e, { ticket })
 *
 * LOCAL: در دمو، تیکت‌ها در localStorage می‌مانند (بدون عکس‌ها). با اتصال بک‌اند false کنید.
 */
const LOCAL = true;
const KEY = 'teodel:tickets';
export const LIMITS = { files: 3, mb: 2, subject: [3, 120], message: [10, 3000] };
const TYPES = /^image\/(jpeg|png|webp)$/;
const STATUSES = ['open', 'answered', 'closed'];

/* ── محاسبه‌های خالص (قابل تست) ───────────────────────────────────── */
export function counts(list) {
    const c = { all: list.length, open: 0, answered: 0, closed: 0 };
    list.forEach((t) => {
        if (c[t.status] != null) c[t.status]++;
    });
    return c;
}

export function filterList(list, { status = 'all', q = '' } = {}) {
    const n = q.trim().toLowerCase();
    return list
        .filter((t) => (status === 'all' || t.status === status) && (!n || (t.subject || '').toLowerCase().includes(n) || (t.code || '').toLowerCase().includes(n)))
        .sort((a, b) => b.updated_at - a.updated_at);
}

/** خطاها را به‌صورت { فیلد: کدِ پیام } برمی‌گرداند؛ خالی یعنی درست */
export function validateNew({ dept, subject, message }) {
    const e = {};
    if (!dept) e.dept = 'dept';
    const s = (subject || '').trim();
    if (s.length < LIMITS.subject[0]) e.subject = 'subject_min';
    else if (s.length > LIMITS.subject[1]) e.subject = 'subject_max';
    const m = (message || '').trim();
    if (m.length < LIMITS.message[0]) e.message = 'message_min';
    else if (m.length > LIMITS.message[1]) e.message = 'message_max';
    return e;
}

/** null یعنی فایل مجاز است؛ وگرنه کد خطا */
export function validateFile(file, currentCount) {
    if (currentCount >= LIMITS.files) return 'file_count';
    if (!TYPES.test(file.type || '')) return 'file_type';
    if (file.size > LIMITS.mb * 1024 * 1024) return 'file_size';
    return null;
}

export function relTime(ts, now, t, locale) {
    const d = Math.max(0, now - ts);
    if (d < 60) return t('time.now');
    if (d < 3600) return t('time.minutes', { n: fmtNum(Math.floor(d / 60), locale) });
    if (d < 86400) return t('time.hours', { n: fmtNum(Math.floor(d / 3600), locale) });
    if (d < 172800) return t('time.yesterday');
    if (d < 7 * 86400) return t('time.days', { n: fmtNum(Math.floor(d / 86400), locale) });
    return fmtDate(ts, locale);
}
function fmtNum(n, locale) {
    return new Intl.NumberFormat(locale === 'fa' ? 'fa-IR' : 'en-GB', { useGrouping: false }).format(n);
}
export function fmtDate(ts, locale) {
    try {
        return new Intl.DateTimeFormat(locale === 'fa' ? 'fa-IR-u-ca-persian' : 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(ts * 1000));
    } catch (e) {
        return new Date(ts * 1000).toISOString().slice(0, 10);
    }
}
export function fmtDateTime(ts, locale) {
    try {
        const lc = locale === 'fa' ? 'fa-IR-u-ca-persian' : 'en-GB';
        const d = new Intl.DateTimeFormat(lc, { year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(ts * 1000));
        const tm = new Intl.DateTimeFormat(lc, { hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(ts * 1000));
        return d + ' ' + tm;
    } catch (e) {
        return new Date(ts * 1000).toISOString().slice(0, 16).replace('T', ' ');
    }
}

/* ── ذخیره‌ی دمو ──────────────────────────────────────────────────── */
const store = {
    get() {
        try {
            return JSON.parse(localStorage.getItem(KEY) || 'null');
        } catch (e) {
            return null;
        }
    },
    set(v) {
        try {
            localStorage.setItem(KEY, JSON.stringify(v));
        } catch (e) {
            /* حالت خصوصی / پر */
        }
    },
};
// آدرس‌های blob بعد از رفرش از بین می‌روند؛ فقط نام فایل ذخیره می‌شود
const clean = (list) =>
    list.map((t) =>
        Object.assign({}, t, {
            messages: (t.messages || []).map((m) => Object.assign({}, m, { files: (m.files || []).map((f) => ({ name: f.name, url: /^blob:/.test(f.url || '') ? '' : f.url || '' })) })),
        })
    );

/* ── صفحه ────────────────────────────────────────────────────────── */
$(function () {
    const $root = $('[data-tk]');
    if (!$root.length) return;

    const locale = document.documentElement.lang === 'fa' ? 'fa' : 'en';
    const parse = (a, d) => {
        try {
            return JSON.parse($root.attr(a) || '') || d;
        } catch (e) {
            return d;
        }
    };
    const TT = parse('data-t', {});
    const t = (path, rep) => {
        let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };
    const num = (n) => fmtNum(n, locale);
    const urls = {
        detail: $root.attr('data-detail-url') || '',
        create: $root.attr('data-create-url') || '',
        reply: $root.attr('data-reply-url') || '',
        close: $root.attr('data-close-url') || '',
        reopen: $root.attr('data-reopen-url') || '',
    };
    const U = (tpl, id) => tpl.split('__ID__').join(encodeURIComponent(id));

    let tickets = parse('data-tickets', []);
    if (LOCAL) {
        const saved = store.get();
        if (saved && Array.isArray(saved.tickets)) tickets = saved.tickets;
    }
    const persist = () => {
        if (LOCAL) store.set({ tickets: clean(tickets) });
    };
    const find = (id) => tickets.find((x) => String(x.id) === String(id));

    /* ── API (پیش‌فرض: آدرس‌ها یا دمو) ─────────────────────────────── */
    async function request(method, url, form) {
        const csrf = $('meta[name="csrf-token"]').attr('content') || '';
        let res;
        try {
            res = await fetch(url, {
                method,
                headers: Object.assign({ Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                credentials: 'same-origin',
                body: form || undefined,
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
        if (res.status === 422) {
            const errs = {};
            Object.keys(data.errors || {}).forEach((k) => {
                errs[k === 'department' ? 'dept' : k.replace(/\..*$/, '')] = [].concat(data.errors[k])[0];
            });
            throw { errors: errs, message: data.message };
        }
        if (!res.ok) throw { message: data.message || t('toast.failed') };
        return data;
    }
    const toForm = (obj, files) => {
        const f = new FormData();
        Object.keys(obj).forEach((k) => f.append(k, obj[k]));
        (files || []).forEach((x) => f.append('files[]', x));
        return f;
    };
    const blobFiles = (files) => (files || []).map((f) => ({ name: f.name, url: URL.createObjectURL(f) }));
    const nowTs = () => Math.floor(Date.now() / 1000);

    const defaultApi = {
        async detail(id) {
            if (urls.detail) return (await request('GET', U(urls.detail, id))).ticket;
            return find(id);
        },
        async create(p) {
            if (urls.create) return (await request('POST', urls.create, toForm({ department: p.dept, subject: p.subject, message: p.message }, p.files))).ticket;
            const n = nowTs();
            const top = tickets.reduce((m, x) => Math.max(m, parseInt(x.id, 10) || 0), 1000);
            return {
                id: top + 1, code: 'T-' + (top + 1), subject: p.subject, dept: p.dept, status: 'open', unread: false, created_at: n, updated_at: n,
                messages: [{ id: 1, from: 'user', name: '', text: p.message, at: n, files: blobFiles(p.files) }],
            };
        },
        async reply(id, p) {
            if (urls.reply) return request('POST', U(urls.reply, id), toForm({ message: p.message }, p.files));
            const tk = find(id);
            const mid = ((tk.messages || []).reduce((m, x) => Math.max(m, x.id || 0), 0)) + 1;
            return { message: { id: mid, from: 'user', name: '', text: p.message, at: nowTs(), files: blobFiles(p.files) }, status: 'open' };
        },
        async close(id) {
            if (urls.close) return request('POST', U(urls.close, id));
            return { status: 'closed' };
        },
        async reopen(id) {
            if (urls.reopen) return request('POST', U(urls.reopen, id));
            return { status: 'open' };
        },
        async read() {
            return {};
        },
    };
    window.Teodel = window.Teodel || {};
    window.Teodel.tickets = {
        api: Object.assign({}, defaultApi, (window.Teodel.tickets && window.Teodel.tickets.api) || {}),
        set(list) {
            tickets = list || [];
            renderList();
        },
    };
    const A = () => window.Teodel.tickets.api;

    /* ── قالب‌ساز کوچک ─────────────────────────────────────────────── */
    const el = (tag, cls, text) => {
        const $e = $('<' + tag + '></' + tag + '>');
        if (cls) $e.addClass(cls);
        if (text != null) $e.text(text);
        return $e;
    };

    /* ── لیست ──────────────────────────────────────────────────────── */
    let filter = 'all';
    let q = '';
    let currentId = null;
    const $items = $root.find('[data-tk-items]');
    const $empty = $root.find('[data-tk-empty]');

    function renderList() {
        const c = counts(tickets);
        ['open', 'answered', 'closed'].forEach((k) => $root.find('[data-tk-count="' + k + '"]').text(num(c[k])));
        ['all', 'open', 'answered', 'closed'].forEach((k) => $root.find('[data-tk-chip-n="' + k + '"]').text(num(c[k])));
        $root.find('[data-filter]').each(function () {
            $(this).attr('aria-pressed', $(this).attr('data-filter') === filter ? 'true' : 'false');
        });

        const list = filterList(tickets, { status: filter, q });
        const now = nowTs();
        $items.empty();
        list.forEach((tk) => {
            const last = (tk.messages || []).slice(-1)[0];
            const $b = $('<button type="button" class="tk-item"></button>')
                .attr({ 'data-id': tk.id, 'aria-current': String(tk.id) === String(currentId) ? 'true' : 'false' })
                .toggleClass('is-unread', !!tk.unread);
            $b.append(el('span', 'tk-item__dot').attr('aria-hidden', 'true'));
            const $body = el('span', 'min-w-0 flex-1');
            $body.append(
                el('span', 'tk-item__top').append(el('bdi', '', tk.code).attr('dir', 'ltr'), el('span', '', relTime(tk.updated_at, now, t, locale))),
                el('span', 'tk-item__subject block', tk.subject),
                el('span', 'tk-item__foot').append(
                    el('span', 'tk-badge', t('status.' + tk.status)).attr('data-s', tk.status),
                    el('span', '', t('departments.' + tk.dept)),
                    tk.unread ? el('span', 'font-extrabold text-brand-700', t('list.unread')) : $(),
                    last && !tk.unread ? el('span', 'text-slate-400', last.from === 'user' ? t('list.you') : t('list.support')) : $()
                )
            );
            $b.append($body);
            $('<li></li>').append($b).appendTo($items);
        });

        const none = list.length === 0;
        $empty.prop('hidden', !none);
        $items.prop('hidden', none);
        if (none) {
            const fresh = tickets.length === 0;
            $empty.find('[data-tk-empty-title]').text(fresh ? t('list.empty_title') : t('list.empty_filter'));
            $empty.find('[data-tk-empty-text]').text(fresh ? t('list.empty_text') : '');
            $empty.find('[data-tk-new]').prop('hidden', !fresh);
        }
    }

    $root.on('click', '[data-filter]', function () {
        const f = $(this).attr('data-filter');
        filter = filter === f && $(this).is('.tk-stat') ? 'all' : f;
        renderList();
    });
    let st = 0;
    const $search = $root.find('[data-tk-search]');
    const $clear = $root.find('[data-tk-search-clear]');
    $search.on('input', function () {
        clearTimeout(st);
        $clear.prop('hidden', !this.value);
        st = setTimeout(() => {
            q = $search.val();
            renderList();
        }, 120);
    });
    $clear.on('click', () => {
        $search.val('').trigger('focus');
        $clear.prop('hidden', true);
        q = '';
        renderList();
    });
    $items.on('click', '.tk-item', function () {
        openTicket($(this).attr('data-id'), true);
    });

    /* ── نمایش‌ها: list | detail | new ─────────────────────────────── */
    const $shell = $root.find('[data-tk-shell]');
    const $placeholder = $root.find('[data-tk-placeholder]');
    const $detail = $root.find('[data-tk-detail]');
    const $form = $root.find('[data-tk-form]');
    let view = 'list';

    function setView(v, push) {
        view = v;
        $shell.attr('data-view', v);
        $root.attr('data-tk-view', v);
        $placeholder.prop('hidden', v !== 'list');
        $detail.prop('hidden', v !== 'detail');
        $form.prop('hidden', v !== 'new');
        $('html').toggleClass('tk-live', v !== 'list');
        if (v === 'list') currentId = null;
        if (push) history.pushState({ tk: v, id: currentId }, '');
        renderList();
        if (!window.matchMedia('(min-width: 1024px)').matches) window.scrollTo({ top: 0, behavior: 'auto' });
    }
    history.replaceState({ tk: 'list' }, '');
    window.addEventListener('popstate', (e) => {
        const s = e.state || { tk: 'list' };
        if (s.tk === 'detail' && s.id && find(s.id)) {
            currentId = s.id;
            showTicket(find(s.id));
            setView('detail', false);
        } else if (s.tk === 'new') setView('new', false);
        else setView('list', false);
    });
    const goBack = () => (history.state && history.state.tk && history.state.tk !== 'list' ? history.back() : setView('list', false));
    $root.on('click', '[data-tk-back]', goBack);

    /* ── گفتگو ─────────────────────────────────────────────────────── */
    const $thread = $root.find('[data-tk-thread]');
    const $loading = $root.find('[data-tk-loading]');
    const $composer = $root.find('[data-tk-reply]');
    const $closedBox = $root.find('[data-tk-closed]');

    async function openTicket(id, push) {
        const tk = find(id);
        if (!tk) return;
        currentId = tk.id;
        replyFiles = [];
        $root.find('[data-tk-reply-text]').val('');
        paintFiles($root.find('[data-tk-reply-files]'), replyFiles, 'reply');
        showErr($root.find('[data-tk-reply-error]'), '');
        setView('detail', push);
        showTicket(tk);
        if (!tk.messages || !tk.messages.length) {
            $thread.empty();
            $loading.prop('hidden', false).find('[data-tk-retry]').prop('hidden', true);
            $loading.find('[data-tk-loading-text]').text(t('detail.loading'));
            try {
                const full = await A().detail(tk.id);
                Object.assign(tk, full || {});
                if (String(currentId) !== String(tk.id)) return;
                $loading.prop('hidden', true);
                showTicket(tk);
            } catch (e) {
                $loading.find('[data-tk-loading-text]').text(t('detail.load_failed'));
                $loading.find('[data-tk-retry]').prop('hidden', false);
                return;
            }
        }
        if (tk.unread) {
            tk.unread = false;
            persist();
            renderList();
            Promise.resolve(A().read(tk.id)).catch(() => {});
            $(document).trigger('ticket:read', { ticket: tk });
        }
    }
    $root.on('click', '[data-tk-retry]', () => currentId != null && openTicket(currentId, false));

    function msgNode(m) {
        const mine = m.from === 'user';
        const $m = el('article', 'tk-msg ' + (mine ? 'tk-msg--user' : 'tk-msg--support')).attr('data-id', m.id);
        $m.append(
            el('p', 'tk-msg__meta').append(el('b', '', mine ? t('detail.you') : m.name || t('detail.support')), el('span', '', fmtDateTime(m.at, locale)))
        );
        if (m.text) $m.append(el('p', 'tk-msg__bubble', m.text).attr('dir', 'auto'));
        if (m.files && m.files.length) {
            const $f = el('div', 'tk-msg__files');
            m.files.forEach((f) => {
                if (f.url) {
                    $('<a class="tk-msg__file" target="_blank" rel="noopener"></a>')
                        .attr({ href: f.url, 'aria-label': t('detail.attachment') + ': ' + f.name })
                        .append($('<img alt="" loading="lazy" decoding="async">').attr('src', f.url))
                        .appendTo($f);
                } else {
                    el('span', 'tk-msg__chip', f.name).appendTo($f);
                }
            });
            $m.append($f);
        }
        return $m;
    }

    function showTicket(tk) {
        $root.find('[data-tk-d-subject]').text(tk.subject);
        $root.find('[data-tk-d-code]').text(tk.code);
        $root.find('[data-tk-d-dept]').text(t('departments.' + tk.dept));
        $root.find('[data-tk-d-created]').text(t('detail.created', { date: fmtDate(tk.created_at, locale) }));
        $root.find('[data-tk-d-status]').text(t('status.' + tk.status)).attr('data-s', tk.status);
        $root.find('[data-tk-d-hint]').text(t('status_hint.' + tk.status));
        const closed = tk.status === 'closed';
        $root.find('[data-tk-close]').prop('hidden', closed);
        $root.find('[data-tk-reopen]').prop('hidden', !closed);
        $composer.prop('hidden', closed);
        $closedBox.prop('hidden', !closed);
        $thread.empty();
        (tk.messages || []).forEach((m) => $thread.append(msgNode(m)));
        scrollEnd();
        renderList();
    }
    function scrollEnd() {
        requestAnimationFrame(() => {
            if (window.matchMedia('(min-width: 1024px)').matches) $thread[0].scrollTop = $thread[0].scrollHeight;
            else window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'auto' });
        });
    }

    /* ── پیوست عکس (مشترک: فرم جدید و جواب) ────────────────────────── */
    let newFiles = [];
    let replyFiles = [];
    const remember = (arr, f) => arr.push({ file: f, url: URL.createObjectURL(f) });

    function paintFiles($ul, arr, kind) {
        $ul.empty();
        arr.forEach((it, i) => {
            $('<li></li>')
                .append(
                    $('<img alt="">').attr('src', it.url),
                    $('<button type="button"></button>')
                        .attr({ 'data-rm': i, 'data-kind': kind, 'aria-label': t('form.remove_file', { name: it.file.name }) })
                        .append('<svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>')
                )
                .appendTo($ul);
        });
        if (kind === 'new') $root.find('.tk-drop').toggleClass('is-full', arr.length >= LIMITS.files);
    }
    function takeFiles(fileList, arr) {
        let err = '';
        Array.from(fileList || []).forEach((f) => {
            const code = validateFile(f, arr.length);
            if (code) err = t('errors.' + code, { mb: num(LIMITS.mb), n: num(LIMITS.files) });
            else remember(arr, f);
        });
        return err;
    }
    function showErr($e, msg) {
        $e.text(msg || '').prop('hidden', !msg);
    }
    $root.on('click', '[data-rm]', function () {
        const kind = $(this).attr('data-kind');
        const arr = kind === 'new' ? newFiles : replyFiles;
        const i = parseInt($(this).attr('data-rm'), 10);
        const [gone] = arr.splice(i, 1);
        if (gone) URL.revokeObjectURL(gone.url);
        paintFiles($root.find(kind === 'new' ? '[data-tk-new-files]' : '[data-tk-reply-files]'), arr, kind);
    });
    // دسترسی با کیبورد برای دکمه‌های «پیوست» (label)
    $root.on('keydown', '[data-tk-attach-btn]', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).find('input[type=file]')[0].click();
        }
    });
    $root.find('[data-tk-new-input]').on('change', function () {
        showErr($root.find('[data-tk-err="files"]'), takeFiles(this.files, newFiles));
        this.value = '';
        paintFiles($root.find('[data-tk-new-files]'), newFiles, 'new');
    });
    $root.find('[data-tk-reply-input]').on('change', function () {
        showErr($root.find('[data-tk-reply-error]'), takeFiles(this.files, replyFiles));
        this.value = '';
        paintFiles($root.find('[data-tk-reply-files]'), replyFiles, 'reply');
    });
    $root.find('[data-tk-attach-hint]').text(t('form.attach_hint', { n: num(LIMITS.files), mb: num(LIMITS.mb) }));

    /* ── جواب‌دادن ─────────────────────────────────────────────────── */
    const $replyText = $root.find('[data-tk-reply-text]');
    $replyText.on('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 160) + 'px';
    });
    $replyText.on('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            $composer.trigger('submit');
        }
    });
    $composer.on('submit', async function (e) {
        e.preventDefault();
        const tk = find(currentId);
        const $send = $root.find('[data-tk-reply-send]');
        if (!tk || $send.prop('disabled')) return;
        const text = $replyText.val().trim();
        const $err = $root.find('[data-tk-reply-error]');
        if (!text) return showErr($err, t('errors.reply_empty')), $replyText.trigger('focus');
        if (text.length > LIMITS.message[1]) return showErr($err, t('errors.message_max'));
        showErr($err, '');
        $send.prop('disabled', true).text(t('detail.sending'));
        try {
            const res = (await A().reply(tk.id, { message: text, files: replyFiles.map((x) => x.file) })) || {};
            const msg = res.message || { id: Date.now(), from: 'user', name: '', text, at: nowTs(), files: [] };
            tk.messages = (tk.messages || []).concat(msg);
            tk.status = res.status || 'open';
            tk.updated_at = msg.at || nowTs();
            persist();
            $replyText.val('').css('height', '');
            replyFiles = [];
            paintFiles($root.find('[data-tk-reply-files]'), replyFiles, 'reply');
            showTicket(tk);
            toast(t('toast.replied'));
            $(document).trigger('ticket:replied', { ticket: tk, message: msg });
        } catch (er) {
            showErr($err, (er && (er.message || (er.errors && Object.values(er.errors)[0]))) || t('toast.failed'));
        }
        $send.prop('disabled', false).text(t('detail.send'));
    });

    /* ── بستن / بازکردن دوباره ─────────────────────────────────────── */
    $root.on('click', '[data-tk-close]', () => openDialog('#tk-close-dialog'));
    $(document).on('click', '[data-tk-close-ok]', async function () {
        const tk = find(currentId);
        closeDialog('#tk-close-dialog');
        if (!tk) return;
        try {
            const r = (await A().close(tk.id)) || {};
            tk.status = r.status || 'closed';
            tk.updated_at = nowTs();
            persist();
            showTicket(tk);
            toast(t('toast.closed'));
            $(document).trigger('ticket:closed', { ticket: tk });
        } catch (er) {
            toast((er && er.message) || t('toast.failed'), true);
        }
    });
    $root.on('click', '[data-tk-reopen]', async function () {
        const tk = find(currentId);
        const $b = $(this);
        if (!tk || $b.prop('disabled')) return;
        $b.prop('disabled', true);
        try {
            const r = (await A().reopen(tk.id)) || {};
            tk.status = r.status || 'open';
            tk.updated_at = nowTs();
            persist();
            showTicket(tk);
            toast(t('toast.reopened'));
            $(document).trigger('ticket:reopened', { ticket: tk });
        } catch (er) {
            toast((er && er.message) || t('toast.failed'), true);
        }
        $b.prop('disabled', false);
    });

    /* ── تیکت جدید ─────────────────────────────────────────────────── */
    let dept = '';
    const $subject = $root.find('[data-tk-subject]');
    const $message = $root.find('[data-tk-message]');
    const $counter = $root.find('[data-tk-counter]');
    const fieldErr = (k, code, raw) => {
        const msg = raw || (code ? t('errors.' + code, { n: num(LIMITS.files), mb: num(LIMITS.mb) }) : '');
        showErr($root.find('[data-tk-err="' + k + '"]'), msg);
        const $f = k === 'subject' ? $subject : k === 'message' ? $message : null;
        if ($f) $f.attr('aria-invalid', msg ? 'true' : null);
    };
    const paintCounter = () => $counter.text(t('form.counter', { n: num($message.val().length), max: num(LIMITS.message[1]) }));
    paintCounter();
    $message.on('input', paintCounter);

    const $depts = $root.find('[data-tk-depts] .tk-dept');
    function setDept(d) {
        dept = d;
        $depts.each(function () {
            const on = $(this).attr('data-dept') === d;
            $(this).attr({ 'aria-checked': on ? 'true' : 'false', tabindex: on || (!d && $(this).is(':first-child')) ? 0 : -1 });
        });
        if (d) fieldErr('dept', '');
    }
    $depts.on('click', function () {
        setDept($(this).attr('data-dept'));
    });
    $depts.on('keydown', function (e) {
        if (!/^Arrow/.test(e.key)) return;
        e.preventDefault();
        const rtl = document.documentElement.dir === 'rtl';
        const fwd = e.key === 'ArrowDown' || (e.key === 'ArrowRight') !== rtl;
        const i = $depts.index(this);
        const n = $depts.eq((i + (fwd ? 1 : -1) + $depts.length) % $depts.length);
        n.trigger('focus').trigger('click');
    });
    setDept('');

    $root.on('click', '[data-tk-new]', () => {
        resetForm();
        currentId = null;
        setView('new', true);
        setTimeout(() => $root.find('[data-tk-depts] .tk-dept').first().trigger('focus'), 50);
    });
    $root.on('click', '[data-tk-cancel]', goBack);

    function resetForm() {
        setDept('');
        $subject.val('').attr('aria-invalid', null);
        $message.val('').attr('aria-invalid', null);
        newFiles.forEach((f) => URL.revokeObjectURL(f.url));
        newFiles = [];
        paintFiles($root.find('[data-tk-new-files]'), newFiles, 'new');
        ['dept', 'subject', 'message', 'files'].forEach((k) => fieldErr(k, ''));
        paintCounter();
    }
    $subject.on('input', () => $subject.attr('aria-invalid') && fieldErr('subject', ''));
    $message.on('input', () => $message.attr('aria-invalid') && fieldErr('message', ''));

    $form.on('submit', async function (e) {
        e.preventDefault();
        const $btn = $root.find('[data-tk-submit]');
        if ($btn.prop('disabled')) return;
        const v = { dept, subject: $subject.val(), message: $message.val() };
        const errs = validateNew(v);
        ['dept', 'subject', 'message'].forEach((k) => fieldErr(k, errs[k] || ''));
        if (Object.keys(errs).length) {
            const first = ['dept', 'subject', 'message'].find((k) => errs[k]);
            const $t = first === 'dept' ? $depts.filter('[tabindex=0]').first() : first === 'subject' ? $subject : $message;
            $t.trigger('focus');
            return;
        }
        $btn.prop('disabled', true).text(t('form.submitting'));
        try {
            const tk = await A().create({ dept: v.dept, subject: v.subject.trim(), message: v.message.trim(), files: newFiles.map((x) => x.file) });
            tickets.unshift(tk);
            persist();
            resetForm();
            filter = 'all';
            q = '';
            $search.val('');
            $clear.prop('hidden', true);
            await openTicket(tk.id, false);
            history.replaceState({ tk: 'detail', id: tk.id }, '');
            toast(t('toast.created'));
            $(document).trigger('ticket:created', { ticket: tk });
        } catch (er) {
            const map = (er && er.errors) || {};
            let any = false;
            ['dept', 'subject', 'message'].forEach((k) => {
                if (map[k]) {
                    fieldErr(k, '', map[k]);
                    any = true;
                }
            });
            if (map.files) {
                fieldErr('files', '', map.files);
                any = true;
            }
            if (!any) toast((er && er.message) || t('toast.failed'), true);
        }
        $btn.prop('disabled', false).text(t('form.submit'));
    });

    /* ── پیام کوتاه ────────────────────────────────────────────────── */
    const $toast = $root.find('[data-tk-toast]');
    let toastTimer = 0;
    function toast(text, bad) {
        $toast.text(text).toggleClass('is-bad', !!bad).prop('hidden', false).removeClass('is-in');
        void $toast[0].offsetWidth;
        $toast.addClass('is-in');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => $toast.removeClass('is-in').prop('hidden', true), 2800);
    }

    renderList();

    // /tickets?new=1 (از داشبورد): مستقیم فرم تیکت جدید باز شود
    if (/[?&]new=1\b/.test(location.search)) {
        resetForm();
        setView('new', false);
        history.replaceState({ tk: 'new' }, '');
    }
});
