import $ from 'jquery';
import { ago, fmtDate, fmtShort } from './common';

/**
 * پوسته‌ی پنل: کشوی منوی موبایل (دکمه‌ی «بیشتر»)، زمان‌های نسبی (<time data-ad-ago="ثانیه‌ی یونیکس">).
 * جست‌وجوی هدر: فرم GET به /admin/users?q= (یا هر مقصدی که در data-ad-search-url بگذارید).
 */
$(function () {
    const $html = $('html');
    const $side = $('#ad-side');

    function setDrawer(open) {
        $html.toggleClass('ad-drawer-open', open);
        $('[data-ad-open]').attr('aria-expanded', open ? 'true' : 'false');
        if (open) $side.find('a,button').first().trigger('focus');
    }
    $(document).on('click', '[data-ad-open]', () => setDrawer(true));
    $(document).on('click', '[data-ad-close]', () => setDrawer(false));
    $side.on('click', 'a', () => setDrawer(false));
    $(document).on('keydown', (e) => {
        if (e.key === 'Escape' && $html.hasClass('ad-drawer-open')) setDrawer(false);
    });
    // با بزرگ شدن صفحه، حالت کشو بسته شود
    window.matchMedia('(min-width: 768px)').addEventListener('change', (e) => e.matches && setDrawer(false));

    $('[data-ad-ago]').each(function () {
        const ts = parseInt($(this).attr('data-ad-ago'), 10);
        if (ts) $(this).text(ago(ts)).attr('datetime', new Date(ts * 1000).toISOString());
    });

    $('[data-ad-date]').each(function () {
        const ts = parseInt($(this).attr('data-ad-date'), 10);
        if (ts) $(this).text(this.hasAttribute('data-ad-short') ? fmtShort(ts) : fmtDate(ts)).attr('datetime', new Date(ts * 1000).toISOString());
    });

    // نوارهای پیشرفت (.ad-meter i / .ad-mix i با data-w) بعد از اولین رندر پر می‌شوند
    requestAnimationFrame(() => requestAnimationFrame(() => {
        $('.ad-meter i[data-w], .ad-mix i[data-w]').each(function () {
            $(this).css('width', $(this).attr('data-w') + '%');
        });
    }));

    $('[data-ad-search]').on('submit', function (e) {
        e.preventDefault();
        const q = $(this).find('input[name=q]').val().toString().trim();
        const base = $(this).attr('data-ad-search-url') || '/admin/users';
        window.location.assign(base + (q ? '?q=' + encodeURIComponent(q) : ''));
    });
});
