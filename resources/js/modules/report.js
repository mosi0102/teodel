import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/**
 * گزارش خرابی (هر صفحه)
 *
 * جریان: کلیک → اسکرین‌شات از بخش قابل‌مشاهده‌ی صفحه (html2canvas، فقط همین لحظه لود می‌شود)
 *        → دیالوگ با پیش‌نمایش و کادر توضیحات → ساخت payload.
 *
 * ارسال به سرور کار بک‌اند است. دو نقطه‌ی اتصال آماده است:
 *   1) رویداد:   $(document).on('report:submit', (e, payload) => { ... })
 *   2) آدرس:     data-endpoint روی #report-form (اگر خالی باشد درخواستی ارسال نمی‌شود)
 */
let screenshot = null;

async function captureViewport() {
    try {
        const { default: html2canvas } = await import('html2canvas');
        const canvas = await html2canvas(document.documentElement, {
            x: window.scrollX,
            y: window.scrollY,
            width: window.innerWidth,
            height: window.innerHeight,
            scale: 1,
            useCORS: true,
            logging: false,
            backgroundColor: '#f4f5fa',
            ignoreElements: (el) => el.hasAttribute && el.hasAttribute('data-report-ignore'),
        });
        return canvas.toDataURL('image/jpeg', 0.7);
    } catch (err) {
        return null; // اگر اسکرین‌شات ممکن نشد، گزارش بدون تصویر هم قابل ارسال است
    }
}

function resetDialog() {
    $('#report-step-form').prop('hidden', false);
    $('#report-step-done').prop('hidden', true);
    $('#report-text').val('');
    $('#report-count').text('0');
    $('#report-error').prop('hidden', true);
}

$(document).on('click', '[data-report-open]', async function () {
    const $btn = $(this);
    if ($btn.hasClass('is-loading')) return;
    $btn.addClass('is-loading').attr('aria-busy', 'true');

    // اسکرین‌شات قبل از باز شدن دیالوگ گرفته می‌شود تا خود دیالوگ در تصویر نیفتد
    screenshot = await captureViewport();

    const $img = $('#report-shot-img');
    const $empty = $('#report-shot-empty');
    const $attach = $('#report-attach');
    if (screenshot) {
        $img.attr('src', screenshot).prop('hidden', false);
        $empty.prop('hidden', true);
        $attach.prop({ disabled: false, checked: true });
    } else {
        $img.prop('hidden', true).removeAttr('src');
        $empty.prop('hidden', false);
        $attach.prop({ disabled: true, checked: false });
    }

    resetDialog();
    $btn.removeClass('is-loading').removeAttr('aria-busy');
    openDialog('#report-dialog');
});

$(document).on('input', '#report-text', function () {
    $('#report-count').text(this.value.length);
    if (this.value.trim()) $('#report-error').prop('hidden', true);
});

// پیش‌نمایش با تیک «ضمیمه‌ی تصویر» کم‌رنگ می‌شود
$(document).on('change', '#report-attach', function () {
    $('#report-shot-wrap').toggleClass('opacity-40', !this.checked);
});

$(document).on('submit', '#report-form', async function (e) {
    e.preventDefault();
    const $form = $(this);
    const description = $.trim($('#report-text').val());

    if (!description) {
        $('#report-error').prop('hidden', false);
        $('#report-text').trigger('focus');
        return;
    }

    const payload = {
        description,
        screenshot: $('#report-attach').is(':checked') ? screenshot : null,
        meta: {
            url: location.href,
            locale: document.documentElement.lang,
            userAgent: navigator.userAgent,
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            sentAt: new Date().toISOString(),
        },
    };

    const $submit = $form.find('[type="submit"]').addClass('is-loading');

    // نقطه‌ی اتصال ۱: رویداد (برای هر منطق سمت کلاینت)
    $(document).trigger('report:submit', [payload]);

    // نقطه‌ی اتصال ۲: آدرس API (توسط بک‌اند تعیین می‌شود)
    const endpoint = $form.attr('data-endpoint');
    if (endpoint) {
        try {
            await $.ajax({
                url: endpoint,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
            });
        } catch (err) {
            $submit.removeClass('is-loading');
            $('#report-error').text($form.attr('data-error-text')).prop('hidden', false);
            return;
        }
    }

    $submit.removeClass('is-loading');
    $('#report-step-form').prop('hidden', true);
    $('#report-step-done').prop('hidden', false);
    $('#report-done-btn').trigger('focus');
});

$(document).on('click', '#report-done-btn', () => closeDialog($('#report-dialog')));
