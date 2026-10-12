import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/* ------------------------------------------------------------------
   ۱) فیلتر و مرتب‌سازی: با تغییر انتخاب، فرم ارسال می‌شود (GET)
   در موبایل داخل Bottom Sheet است و با دکمه‌ی «اعمال» ارسال می‌شود.
------------------------------------------------------------------- */
$(document).on('change', '#music-filters select', function () {
    // روی دسکتاپ بلافاصله اعمال شود؛ در موبایل کاربر دکمه‌ی «اعمال» را می‌زند
    if (window.matchMedia('(min-width: 768px)').matches) {
        $('#music-filters').addClass('busy')[0].requestSubmit();
    }
});

// بعد از اعمال فیلتر در موبایل، شیت بسته شود (صفحه رفرش می‌شود، این فقط حالت امن است)
$(document).on('submit', '#music-filters', function () {
    closeDialog($('#filter-dialog'));
});

/* ------------------------------------------------------------------
   ۲) «نمایش بیشتر» در موبایل: صفحه‌ی بعد را با fetch می‌گیرد و کارت‌ها را اضافه می‌کند
   (به‌جای نیاز به API، از همان خروجی HTML پیجینیشن لاراول استفاده می‌کند)
------------------------------------------------------------------- */
$(document).on('click', '[data-load-more]', async function () {
    const $btn = $(this);
    const url = $btn.attr('data-url');
    if (!url || $btn.hasClass('is-loading')) return;

    $btn.addClass('is-loading');
    $('#load-more-error').prop('hidden', true);

    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!res.ok) throw new Error(res.status);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const $items = $(doc).find('#song-grid > *');
        $('#song-grid').append($items);

        const next = $(doc).find('[data-load-more]').attr('data-url');
        if (next) {
            $btn.attr('data-url', next);
        } else {
            $btn.closest('[data-load-more-wrap]').remove();
        }
    } catch (err) {
        $('#load-more-error').prop('hidden', false);
    } finally {
        $btn.removeClass('is-loading');
    }
});

/* ------------------------------------------------------------------
   ۳) دیالوگ «اطلاعات موزیک»: از data-attribute های کارت پر می‌شود
------------------------------------------------------------------- */
$(document).on('click', '[data-song-info]', function () {
    const $card = $(this).closest('[data-song]');
    const $dlg = $('#info-dialog');
    if (!$card.length || !$dlg.length) return;

    const d = (name) => $card.attr('data-' + name) || '';
    const fill = (key, value) => $dlg.find('[data-fill="' + key + '"]').text(value);

    fill('title', d('title'));
    fill('artist', d('artist'));
    fill('level', d('level-text'));
    fill('duration', d('duration'));
    fill('points', d('points'));
    fill('steps', d('steps-text'));
    fill('access', d('access-text'));
    // وضعیت و متن دکمه از وضعیت «فعلی» کارت خوانده می‌شود (پیشرفت زنده ممکن است عوضش کرده باشد)
    const status = d('status');
    const ctaKey = { new: 'start', in_progress: 'continue', done: 'review' }[status] || 'start';
    const ctaText = $.trim($card.find('[data-cta="' + ctaKey + '"]').first().text());
    fill('status', d('label-' + status));

    // کاور: تصویر واقعی یا گرادیان جایگزین
    const $cover = $dlg.find('[data-fill-cover]');
    const cover = d('cover');
    $cover.empty().css('background', '');
    if (cover) {
        $('<img>', { src: cover, alt: '', class: 'size-full object-cover' }).appendTo($cover);
    } else {
        const hue = parseInt(d('hue'), 10) || 220;
        $cover.css('background', `linear-gradient(135deg, hsl(${hue} 70% 55%), hsl(${(hue + 40) % 360} 70% 40%))`);
    }

    // اگر آهنگ محدود است، دکمه‌ی خرید اشتراک نمایش داده شود
    $dlg.find('[data-fill-upgrade]').prop('hidden', d('limited') !== '1');
    $dlg.find('[data-fill-start]').attr('href', d('href')).find('[data-fill-start-label]').text(ctaText);

    openDialog('#info-dialog');
});
