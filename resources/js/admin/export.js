import $ from 'jquery';
import { toast } from './common';

/**
 * خروجی Excel (.xlsx واقعی) و CSV از لیست‌های پنل — فقط ردیف‌های «فیلتر‌شده».
 *
 * مارکاپ: partial `admin.partials.export` (wrapper با data-ad-export).
 *   data-export-name   نام فایل بدون پسوند (users, payments, …)
 *   data-export-table  سلکتور جدول (پیش‌فرض: اولین .ad-tbl صفحه)
 *   data-export-url    اندپوینت بک‌اند. اگر باشد مرورگر به
 *                      {url}?{query فعلی صفحه}&format=csv|xlsx می‌رود و بک‌اند همان فیلترها را اعمال می‌کند
 *                      (پاسخ: فایل با Content-Disposition: attachment).
 *
 * بدون data-export-url (دمو): فایل در مرورگر ساخته می‌شود.
 *   • لیست‌های سرور-رندر: ردیف‌های همان جدول (که قبلاً با فیلتر GET کم شده‌اند).
 *   • لیست‌های جاوااسکریپتی (بانک سؤال، دیکشنری): ماژول لیست، همه‌ی ردیف‌های فیلترشده (نه فقط صفحه‌ی جاری) را
 *     از طریق رویداد `ad:export` می‌دهد؛ req.head و req.rows را پر می‌کند.
 *
 * رویداد: $(document).on('ad:export', (e, req) => {})  —  req = { format, name, head, rows, query }
 */

const AR = '٠١٢٣٤٥٦٧٨٩';
const PE = '۰۱۲۳۴۵۶۷۸۹';
const latin = (s) => String(s).replace(/[۰-۹]/g, (d) => PE.indexOf(d)).replace(/[٠-٩]/g, (d) => AR.indexOf(d));

/* ───────── ساخت فایل ───────── */
export function toCsv(head, rows) {
    const esc = (v) => {
        const s = String(v == null ? '' : v);
        return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    };
    return '﻿' + [head].concat(rows).map((r) => r.map(esc).join(',')).join('\r\n');
}

let crcTable = null;
function crc32(bytes) {
    if (!crcTable) {
        crcTable = new Uint32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
            crcTable[n] = c >>> 0;
        }
    }
    let c = 0xffffffff;
    for (let i = 0; i < bytes.length; i++) c = crcTable[(c ^ bytes[i]) & 0xff] ^ (c >>> 8);
    return (c ^ 0xffffffff) >>> 0;
}

/** zip بدون فشرده‌سازی (store) — برای xlsx کافی است */
function zip(files) {
    const enc = new TextEncoder();
    const parts = [];
    const central = [];
    let offset = 0;
    const u16 = (v) => [v & 255, (v >>> 8) & 255];
    const u32 = (v) => [v & 255, (v >>> 8) & 255, (v >>> 16) & 255, (v >>> 24) & 255];
    files.forEach((f) => {
        const name = enc.encode(f.name);
        const data = enc.encode(f.data);
        const crc = crc32(data);
        const local = new Uint8Array([0x50, 0x4b, 3, 4, ...u16(20), ...u16(0x0800), ...u16(0), ...u16(0), ...u16(0x21), ...u32(crc), ...u32(data.length), ...u32(data.length), ...u16(name.length), ...u16(0)]);
        parts.push(local, name, data);
        central.push(new Uint8Array([0x50, 0x4b, 1, 2, ...u16(20), ...u16(20), ...u16(0x0800), ...u16(0), ...u16(0), ...u16(0x21), ...u32(crc), ...u32(data.length), ...u32(data.length), ...u16(name.length), ...u16(0), ...u16(0), ...u16(0), ...u16(0), ...u32(0), ...u32(offset)]), name);
        offset += local.length + name.length + data.length;
    });
    const cSize = central.reduce((s, p) => s + p.length, 0);
    const end = new Uint8Array([0x50, 0x4b, 5, 6, ...u16(0), ...u16(0), ...u16(files.length), ...u16(files.length), ...u32(cSize), ...u32(offset), ...u16(0)]);
    return new Blob(parts.concat(central, [end]), { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
}

const xmlEsc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])).replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, '');
const colName = (i) => {
    let s = '';
    for (let n = i + 1; n > 0; n = Math.floor((n - 1) / 26)) s = String.fromCharCode(65 + ((n - 1) % 26)) + s;
    return s;
};
/** عدد خالص → سلول عددی (رقم‌های فارسی هم)؛ شماره‌های با صفر اول متن می‌مانند */
function cell(v, r, c, bold) {
    const ref = colName(c) + (r + 1);
    const s = String(v == null ? '' : v);
    const n = latin(s).replace(/[,٬\s]/g, '');
    if (!bold && /^-?(0|[1-9]\d{0,14})(\.\d+)?$/.test(n)) return `<c r="${ref}"><v>${n}</v></c>`;
    return `<c r="${ref}" t="inlineStr"${bold ? ' s="1"' : ''}><is><t xml:space="preserve">${xmlEsc(s)}</t></is></c>`;
}

export function toXlsx(head, rows, rtl) {
    const all = [head].concat(rows);
    const sheetRows = all.map((r, i) => `<row r="${i + 1}">${r.map((v, c) => cell(v, i, c, i === 0)).join('')}</row>`).join('');
    const cols = head.map((h, c) => {
        const w = Math.min(48, Math.max(10, ...all.slice(0, 200).map((r) => String(r[c] == null ? '' : r[c]).length + 2)));
        return `<col min="${c + 1}" max="${c + 1}" width="${w}" customWidth="1"/>`;
    }).join('');
    const x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n';
    const ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    return zip([
        { name: '[Content_Types].xml', data: x + '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>' },
        { name: '_rels/.rels', data: x + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>' },
        { name: 'xl/workbook.xml', data: x + `<workbook xmlns="${ns}" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>` },
        { name: 'xl/_rels/workbook.xml.rels', data: x + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>' },
        { name: 'xl/styles.xml', data: x + `<styleSheet xmlns="${ns}"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>` },
        { name: 'xl/worksheets/sheet1.xml', data: x + `<worksheet xmlns="${ns}"><sheetViews><sheetView workbookViewId="0"${rtl ? ' rightToLeft="1"' : ''}><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>${cols}</cols><sheetData>${sheetRows}</sheetData></worksheet>` },
    ]);
}

/* ───────── خواندن جدول ───────── */
const cellText = (el) => {
    const $c = $(el).clone();
    $c.find('.sr-only, svg, button, img, .ad-av, [data-no-export], script, style').remove();
    $c.find('b, small, p, div').each(function () { $(this).append(' · '); });
    const $t = $c.find('time[data-ad-ago], time[data-ad-date]').first();
    if ($t.length) {
        const ts = parseInt($t.attr('data-ad-ago') || $t.attr('data-ad-date'), 10);
        if (ts) return new Date(ts * 1000).toISOString().slice(0, 16).replace('T', ' ');
    }
    return $c.text().replace(/\s+/g, ' ').replace(/(\s·)+\s*$/, '').replace(/\s·\s·/g, ' ·').trim();
};

export function readTable(table) {
    const $t = $(table);
    const $ths = $t.find('thead th');
    const keep = [];
    $ths.each(function (i) {
        const $th = $(this);
        if ($th.is('[data-no-export], .ad-col-act') || !cellText(this)) return;
        keep.push(i);
    });
    const head = keep.map((i) => cellText($ths[i]));
    const rows = [];
    $t.find('tbody tr').each(function () {
        if (this.hidden || this.style.display === 'none') return;
        const $tds = $(this).children('td,th');
        if ($tds.length < $ths.length) return; // ردیف «خالی است»
        rows.push(keep.map((i) => cellText($tds[i])));
    });
    return { head, rows };
}

function download(blob, filename) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 800);
}

/* ───────── UI ───────── */
$(function () {
    const $all = $('[data-ad-export]');
    if (!$all.length) return;
    const rtl = document.documentElement.dir === 'rtl';

    const close = ($w) => {
        $w.find('[data-exp-menu]').prop('hidden', true);
        $w.find('[data-exp-toggle]').attr('aria-expanded', 'false');
    };
    const closeAll = () => $all.each((i, w) => close($(w)));

    $(document).on('click', '[data-exp-toggle]', function (e) {
        e.stopPropagation();
        const $w = $(this).closest('[data-ad-export]');
        const open = $w.find('[data-exp-menu]').prop('hidden');
        closeAll();
        $w.find('[data-exp-menu]').prop('hidden', !open);
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) $w.find('[data-exp-fmt]').first().trigger('focus');
    });
    $(document).on('click', (e) => { if (!$(e.target).closest('[data-ad-export]').length) closeAll(); });
    $(document).on('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const $o = $all.filter((i, w) => !$(w).find('[data-exp-menu]').prop('hidden')).first();
        if ($o.length) { close($o); $o.find('[data-exp-toggle]').trigger('focus'); }
    });

    $(document).on('click', '[data-exp-fmt]', function () {
        const $w = $(this).closest('[data-ad-export]');
        const format = $(this).attr('data-exp-fmt') === 'csv' ? 'csv' : 'xlsx';
        const name = $w.attr('data-export-name') || 'export';
        const url = $w.attr('data-export-url');
        close($w);

        if (url) {
            const q = location.search.replace(/^\?/, '').replace(/(^|&)(format|page)=[^&]*/g, '').replace(/^&/, '');
            location.assign(url + (url.indexOf('?') < 0 ? '?' : '&') + (q ? q + '&' : '') + 'format=' + format);
            return;
        }

        const req = { format, name, head: null, rows: null, query: location.search };
        $(document).trigger('ad:export', [req, $w[0]]);
        if (!req.rows) {
            const sel = $w.attr('data-export-table');
            Object.assign(req, readTable($(sel || '.ad-tbl').first()));
        }
        const file = name + '-' + new Date().toISOString().slice(0, 10) + '.' + format;
        if (!req.rows.length) { toast($w.attr('data-t-empty') || '', true); return; }
        download(format === 'csv' ? new Blob([toCsv(req.head, req.rows)], { type: 'text/csv;charset=utf-8' }) : toXlsx(req.head, req.rows, rtl), file);
        toast(($w.attr('data-t-done') || '').replace(':n', req.rows.length));
    });
});
