import $ from 'jquery';

/**
 * مدیریت یکپارچه‌ی دیالوگ‌ها (Bottom Sheet در موبایل / مودال در دسکتاپ)
 *
 * HTML:  <div id="x" class="dialog" data-dialog aria-hidden="true"> ... </div>
 *        <button data-dialog-open="#x">باز کردن</button>
 *        <button data-dialog-close>بستن</button>
 */
const $html = $('html');
const FOCUSABLE = 'a[href],button:not([disabled]),textarea:not([disabled]),input:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])';

export function openDialog(selector) {
    const $d = $(selector);
    if (!$d.length) return;

    $d.data('opener', document.activeElement);
    $d.addClass('is-open');
    // data-no-aria: عنصری که در دسکتاپ inline می‌شود (مثل فیلتر) نباید aria-hidden بگیرد
    if (!$d.is('[data-no-aria]')) $d.attr('aria-hidden', 'false');
    $html.addClass('dialog-open');

    // فوکوس به اولین فیلد مناسب (بعد از شروع انیمیشن)
    setTimeout(() => {
        const $target = $d.find('[data-autofocus]').first();
        ($target.length ? $target : $d.find(FOCUSABLE).first()).trigger('focus');
    }, 60);

    $d.trigger('dialog:open');
}

export function closeDialog($d) {
    $d = $($d);
    if (!$d.length || !$d.hasClass('is-open')) return;

    $d.removeClass('is-open');
    if (!$d.is('[data-no-aria]')) $d.attr('aria-hidden', 'true');
    if (!$('[data-dialog].is-open').length) $html.removeClass('dialog-open');

    const opener = $d.data('opener');
    if (opener && typeof opener.focus === 'function') opener.focus();

    $d.trigger('dialog:close');
}

$(document)
    .on('click', '[data-dialog-open]', function (e) {
        e.preventDefault();
        openDialog($(this).attr('data-dialog-open'));
    })
    .on('click', '[data-dialog-close]', function () {
        closeDialog($(this).closest('[data-dialog]'));
    })
    .on('keydown', function (e) {
        const $open = $('[data-dialog].is-open').last();
        if (!$open.length) return;

        if (e.key === 'Escape') {
            closeDialog($open);
            e.stopImmediatePropagation();
            return;
        }

        // Focus trap ساده داخل دیالوگ باز
        if (e.key === 'Tab') {
            const $items = $open.find(FOCUSABLE).filter(':visible');
            if (!$items.length) return;
            const first = $items.first()[0];
            const last = $items.last()[0];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

// اگر روی دسکتاپ فیلتر موبایل باز مانده بود و صفحه عریض شد، تمیز بسته شود
window.matchMedia('(min-width: 768px)').addEventListener('change', (e) => {
    if (e.matches) $('.filter-dialog.is-open').each((_, el) => closeDialog($(el)));
});
