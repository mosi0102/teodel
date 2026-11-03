/**
 * کشوی سفارشی (Custom Select) — جایگزین ظاهری <select> داخل `.select-pill`
 * -------------------------------------------------------------------------------------------------
 * <select> اصلی سر جایش می‌ماند (مقدارش در فرم ارسال می‌شود و بدون جاوااسکریپت هم کار می‌کند)؛
 * این ماژول فقط یک دکمه + لیست هم‌شکل با بقیه‌ی طراحی می‌سازد. با انتخاب گزینه، `change` روی همان <select> فایر می‌شود
 * پس هر کدی که روی select گوش می‌دهد (music-list.js، saved-words.js، ...) بدون تغییر کار می‌کند.
 * مقدار select را با کد عوض کردید؟ بعدش  $(select).trigger('select:sync')  را بزنید تا ظاهر هم به‌روز شود.
 * کیبورد: Enter/Space/↓ باز می‌کند، ↑↓ Home End حرکت، Enter انتخاب، Esc بستن، تایپ حرف اول = پرش.
 */
import $ from 'jquery';

const CHECK = '<svg viewBox="0 0 24 24" class="sp-ck" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>';
let uid = 0;

function esc(s) { return $('<i>').text(s).html(); }

function enhance($sel) {
    if ($sel.data('sp') || $sel.prop('multiple')) return;
    $sel.data('sp', 1);
    const id = 'sp' + (++uid);
    const $pill = $sel.closest('.select-pill');
    const $lab = $pill.children('span').first();
    const $btn = $('<button type="button" class="sp-btn" aria-haspopup="listbox" aria-expanded="false"></button>')
        .attr('aria-controls', id);
    const $list = $('<ul class="sp-list" role="listbox" tabindex="-1"></ul>').attr('id', id);
    $sel.attr({ tabindex: '-1', 'aria-hidden': 'true' }).addClass('sr-only');
    $sel.after($list).after($btn);
    $pill.addClass('is-enhanced');

    function build() {
        $list.empty();
        $sel.find('option').each(function (i) {
            $list.append(`<li role="option" class="sp-opt" data-i="${i}" id="${id}-${i}"><span>${esc($(this).text())}</span>${CHECK}</li>`);
        });
        sync();
    }
    function sync() {
        const i = $sel.prop('selectedIndex');
        $btn.text($sel.find('option').eq(i).text());
        $list.children().attr('aria-selected', 'false').eq(i).attr('aria-selected', 'true');
        if ($lab.length) $btn.attr('aria-label', $lab.text().trim() + ': ' + $btn.text());
    }
    function active(i) {
        const $o = $list.children();
        i = Math.max(0, Math.min($o.length - 1, i));
        $o.removeClass('is-active').eq(i).addClass('is-active');
        $btn.attr('aria-activedescendant', id + '-' + i);
        const el = $o[i];
        if (el) el.scrollIntoView({ block: 'nearest' });
        return i;
    }
    function open() {
        $('.select-pill.is-open').not($pill).trigger('select:close');
        $pill.addClass('is-open');
        $btn.attr('aria-expanded', 'true');
        // جا برای باز شدن به پایین نبود (مثلاً شیت پایین موبایل) → به بالا باز شود
        $list.removeClass('is-up');
        const r = $list[0].getBoundingClientRect();
        $list.toggleClass('is-up', r.bottom > innerHeight - 8 && $pill[0].getBoundingClientRect().top > r.height + 16);
        active($sel.prop('selectedIndex'));
    }
    function close(focus) {
        $pill.removeClass('is-open');
        $btn.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
        if (focus) $btn.trigger('focus');
    }
    function pick(i) {
        const changed = $sel.prop('selectedIndex') !== i;
        $sel.prop('selectedIndex', i);
        sync(); close(true);
        if (changed) $sel.trigger('change');
    }

    $btn.on('click', () => ($pill.hasClass('is-open') ? close() : open()));
    $list.on('click', '.sp-opt', function () { pick(+$(this).data('i')); });
    $list.on('mousedown', (e) => e.preventDefault()); // فوکوس از دکمه نپرد
    $pill.on('select:close', () => close());
    $sel.on('select:sync change', sync);
    $btn.on('keydown', (e) => {
        const openNow = $pill.hasClass('is-open');
        const cur = openNow ? $list.children('.is-active').index() : $sel.prop('selectedIndex');
        const n = $list.children().length;
        switch (e.key) {
            case 'ArrowDown': case 'ArrowUp':
                e.preventDefault();
                if (!openNow) { open(); break; }
                active(cur + (e.key === 'ArrowDown' ? 1 : -1)); break;
            case 'Home': if (openNow) { e.preventDefault(); active(0); } break;
            case 'End': if (openNow) { e.preventDefault(); active(n - 1); } break;
            case 'Enter': case ' ':
                if (openNow) { e.preventDefault(); pick(cur); } break;
            case 'Escape': case 'Tab':
                if (openNow) close(e.key === 'Escape'); break;
            default:
                if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                    const c = e.key.toLowerCase();
                    const idx = $sel.find('option').toArray().findIndex((o, k) => k > cur && $(o).text().trim().toLowerCase().startsWith(c));
                    if (idx > -1) { if (!openNow) open(); active(idx); }
                }
        }
    });
    build();
}

function init(root) { $(root || document).find('.select-pill select').each(function () { enhance($(this)); }); }

// بیرون کلیک = بستن
$(document).on('click', (e) => { if (!$(e.target).closest('.select-pill').length) $('.select-pill.is-open').trigger('select:close'); });
$(init);
$(document).on('select:init', (e) => init(e.target));
