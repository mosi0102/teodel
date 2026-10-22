import $ from 'jquery';

/**
 * ابزار مشترک «بانک»‌های پنل (بانک سؤال آزمون، دیکشنری): ذخیره‌ی دمو + سه راه اتصال بک‌اند + نمایش لیست.
 *
 * 🔌 هر عملیات (save | upload | remove | bulk) با همین ترتیب اولویت اجرا می‌شود:
 *   ۱) رویداد لغوشدنی  admin:{kind}-{save|upload|delete|bulk}  با { …detail, done(result), fail('پیام') }  ← e.preventDefault()
 *   ۲) Teodel.admin.{ns}.api.{save|upload|remove|bulk}(…)  (async؛ خطا: throw { message, errors })
 *   ۳) آدرس روی ریشه‌ی صفحه: data-save-url / data-upload-url / data-delete-url / data-bulk-url
 *   ۴) LOCAL=true: ذخیره‌ی دمو در localStorage مرورگر (وقتی بک‌اند وصل شد LOCAL را false کنید)
 */

export const API = (window.Teodel = window.Teodel || {});
API.admin = API.admin || {};

/** آرایه‌ی ثابت → با تغییرات دمو (ساخته/ویرایش/حذف‌شده) ادغام می‌شود */
export function overlay(key) {
    const empty = () => ({ rows: {}, del: [], seq: 1000 });
    const read = () => {
        try {
            return Object.assign(empty(), JSON.parse(localStorage.getItem(key) || 'null') || {});
        } catch (e) {
            return empty();
        }
    };
    const write = (o) => {
        try {
            localStorage.setItem(key, JSON.stringify(o));
        } catch (e) {
            /* بدون localStorage */
        }
    };
    return {
        merge(base) {
            const o = read();
            const map = new Map(base.map((r) => [String(r.id), r]));
            o.del.forEach((id) => map.delete(String(id)));
            Object.keys(o.rows).forEach((id) => map.set(String(id), o.rows[id]));
            return [...map.values()];
        },
        get(id) {
            return read().rows[id] || null;
        },
        upsert(row) {
            const o = read();
            if (!row.id) row.id = ++o.seq;
            row.updated_at = Math.floor(Date.now() / 1000);
            o.rows[row.id] = row;
            o.del = o.del.filter((x) => String(x) !== String(row.id));
            write(o);
            return row;
        },
        remove(id) {
            const o = read();
            delete o.rows[id];
            if (!o.del.map(String).includes(String(id))) o.del.push(id);
            write(o);
        },
        reset() {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                /* ignore */
            }
        },
    };
}

export function viaEvent(name, detail) {
    return new Promise((resolve) => {
        const ev = $.Event(name);
        $(document).trigger(ev, Object.assign({}, detail, { done: (r) => resolve({ ok: r || {} }), fail: (m) => resolve({ err: m }) }));
        if (!ev.isDefaultPrevented()) resolve(null);
    });
}

const ATTR = { save: 'data-save-url', upload: 'data-upload-url', remove: 'data-delete-url', bulk: 'data-bulk-url' };

/**
 * createApi({ $root, kind:'question', ns:'placement', local, overlay, t })
 *   → { save(row,isNew), remove(id), upload(file,what), bulk(items) }
 */
export function createApi({ $root, kind, ns, local, ov, t, send }) {
    API.admin[ns] = API.admin[ns] || {};
    API.admin[ns].api = API.admin[ns].api || {};
    const evName = (op) => `admin:${kind}-${op === 'remove' ? 'delete' : op}`;

    async function call(op, detail, http, localFn) {
        const ev = await viaEvent(evName(op), detail);
        if (ev) {
            if (ev.err !== undefined) throw { message: ev.err };
            return ev.ok;
        }
        const custom = API.admin[ns].api[op];
        if (typeof custom === 'function') return custom(...(detail.args || []));
        const url = $root.attr(ATTR[op]);
        if (url) return http(url);
        if (local) return localFn();
        throw { message: t('errors.no_backend') };
    }

    return {
        save: (row, isNew) =>
            call('save', { row, isNew, args: [row, isNew] }, (url) => send(url, row, isNew ? 'POST' : 'PUT'), async () => {
                const r = ov.upsert(Object.assign({}, row));
                return { id: r.id, status: r.status };
            }),
        remove: (id) => call('remove', { id, args: [id] }, (url) => send(url, {}, 'DELETE'), async () => ov.remove(id)),
        upload: (file, what) =>
            call(
                'upload',
                { file, kind: what, args: [file, what] },
                async (url) => {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('kind', what);
                    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', Accept: 'application/json' }, credentials: 'same-origin' });
                    let data = {};
                    try {
                        data = await res.json();
                    } catch (e) {
                        /* ignore */
                    }
                    if (!res.ok || !data.url) throw { message: data.message || t('errors.upload') };
                    return data;
                },
                async () => ({ url: URL.createObjectURL(file) })
            ),
        bulk: (items) =>
            call('bulk', { items, args: [items] }, (url) => send(url, { items }, 'POST'), async () => {
                items.forEach((it) => ov.upsert(Object.assign({}, it, { id: null })));
                return { saved: items.length };
            }),
    };
}

/** صفحه‌بندی ساده روی آرایه */
export function paginate(arr, page, per) {
    const pages = Math.max(1, Math.ceil(arr.length / per));
    const p = Math.min(Math.max(1, page), pages);
    return { rows: arr.slice((p - 1) * per, p * per), page: p, pages, total: arr.length };
}

/** جایگزینی :name در متن‌های lang */
export function tFrom(TT) {
    return (path, rep) => {
        let v = path.split('.').reduce((o, k) => (o == null ? o : o[k]), TT);
        if (typeof v !== 'string') return path;
        if (rep) Object.keys(rep).forEach((k) => (v = v.split(':' + k).join(rep[k])));
        return v;
    };
}

/** XSS-safe ساخت المان با متن */
export const el = (tag, cls, text) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
};

/** تبدیل لیست جداشده با ویرگول (فارسی/انگلیسی) به آرایه */
export const splitList = (s) => String(s || '').split(/[,،]/).map((x) => x.trim()).filter(Boolean);
