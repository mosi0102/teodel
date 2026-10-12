import $ from 'jquery';

/**
 * بستن/باز کردن سایدبار (تبلت و دسکتاپ).
 * با کلاس sidebar-closed روی <html> کار می‌کند و انتخاب کاربر در localStorage می‌ماند.
 * (کلاس اولیه با اسکریپت کوچک داخل <head> لایه اعمال می‌شود تا صفحه پرش نکند.)
 */
const KEY = 'teodel:sidebar';
const $html = $('html');

function sync() {
    const closed = $html.hasClass('sidebar-closed');
    $('[data-sidebar-toggle]').each(function () {
        const $btn = $(this);
        const label = $btn.attr(closed ? 'data-label-open' : 'data-label-close');
        $btn.attr({ 'aria-expanded': closed ? 'false' : 'true', 'aria-label': label, title: label });
    });
}

function setClosed(closed) {
    $html.toggleClass('sidebar-closed', closed);
    try {
        localStorage.setItem(KEY, closed ? 'closed' : 'open');
    } catch (e) {
        /* localStorage ممکن است در دسترس نباشد */
    }
    sync();
}

$(function () {
    sync();
    $(document).on('click', '[data-sidebar-toggle]', () => setClosed(!$html.hasClass('sidebar-closed')));
});
