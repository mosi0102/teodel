import $ from 'jquery';

/**
 * حالت تمرکز: با یک کلاس روی <html> کار می‌کند و عناصر دارای [data-focus-hide] را مخفی می‌کند.
 * انتخاب کاربر در localStorage می‌ماند و در تمام صفحات (مراحل ۱ تا ۸) برقرار است.
 * (کلاس اولیه با یک اسکریپت inline در <head> اعمال می‌شود تا صفحه پرش نکند.)
 */
const KEY = 'teodel:focus';
const $html = $('html');

function setFocus(on) {
    $html.toggleClass('focus-mode', on);

    try {
        localStorage.setItem(KEY, on ? '1' : '0');
    } catch (e) {
        /* localStorage در حالت خصوصی ممکن است در دسترس نباشد */
    }

    $('[data-focus-toggle]').each(function () {
        const $btn = $(this);
        $btn.attr('aria-pressed', on ? 'true' : 'false');
        $btn.find('[data-focus-label]').text($btn.attr(on ? 'data-label-off' : 'data-label-on'));
    });
}

$(function () {
    setFocus($html.hasClass('focus-mode'));

    $(document).on('click', '[data-focus-toggle]', () => setFocus(!$html.hasClass('focus-mode')));

    // Esc = خروج از حالت تمرکز (فقط وقتی دیالوگی باز نیست؛ دیالوگ‌ها Esc را خودشان می‌گیرند)
    $(document).on('keydown', (e) => {
        if (e.key === 'Escape' && $html.hasClass('focus-mode') && !$('[data-dialog].is-open').length) {
            setFocus(false);
        }
    });
});
