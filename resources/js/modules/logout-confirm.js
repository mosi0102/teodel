import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/**
 * تأیید خروج: هر فرمی که به آدرس …/logout می‌رود (هدر دسکتاپ، منوی «بیشتر» موبایل) قبل از ارسال، دیالوگ تأیید نشان می‌دهد.
 * دیالوگ از resources/views/partials/logout-dialog.blade.php می‌آید (در bottom-nav include شده تا در همه‌ی صفحه‌ها باشد).
 * اگر آن دیالوگ در صفحه نبود، به confirm() مرورگر برمی‌گردد تا خروجِ بی‌تأیید ممکن نباشد.
 */
const SELECTOR = 'form[action$="/logout"]';
let pending = null;

document.addEventListener(
    'submit',
    (e) => {
        const form = e.target;
        if (!form || !form.matches || !form.matches(SELECTOR) || form.dataset.confirmed === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        const $dlg = $('#logout-dialog');
        if (!$dlg.length) {
            if (window.confirm($('html').attr('lang') === 'fa' ? 'از حساب خارج می‌شوی؟' : 'Log out?')) {
                form.dataset.confirmed = '1';
                form.submit();
            }
            return;
        }
        pending = form;
        openDialog('#logout-dialog');
        setTimeout(() => $dlg.find('[data-logout-stay]').trigger('focus'), 60);
    },
    true
);

$(document).on('click', '[data-logout-ok]', function () {
    const form = pending;
    pending = null;
    closeDialog('#logout-dialog');
    if (form) {
        form.dataset.confirmed = '1';
        form.submit(); // submit() رویداد submit را دوباره صدا نمی‌زند
    }
});
$(document).on('click', '#logout-dialog [data-dialog-close]', () => {
    pending = null;
});
