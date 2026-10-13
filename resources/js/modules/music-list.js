import $ from 'jquery';
import { openDialog, closeDialog } from './dialogs';

/* ------------------------------------------------------------------
   ۱) لیست بدون رفرش: تب‌های سطح، فیلتر، مرتب‌سازی و جستجو
   همان صفحه را با fetch می‌گیرد و فقط «تب‌ها + لیست» را جایگزین می‌کند.
   آدرس مرورگر هم عوض می‌شود (لینک مستقیم و دکمه‌ی Back کار می‌کنند)
   و اگر هر مشکلی پیش بیاید، به‌صورت عادی به همان آدرس می‌رود.
------------------------------------------------------------------- */
let seq = 0;
let controller = null;

/** فیلدهای فرم (جستجو/فیلتر/مرتب‌سازی) و نقطه‌ی «فیلتر فعال» را با آدرس هماهنگ می‌کند (برای Back/Forward لازم است) */
function syncForm(url) {
    const sp = new URL(url, location.href).searchParams;
    const filter = sp.get('filter') || 'all';
    const sort = sp.get('sort') || 'default';
    const $form = $('#music-filters');

    $form.find('[name="q"]').val(sp.get('q') || '');
    $form.find('[name="filter"]').val(filter);
    $form.find('[name="sort"]').val(sort);
    $('[data-filter-dot]').attr('hidden', filter !== 'all' || sort !== 'default' ? null : '');
}

async function loadList(url, push = true, scroll = false) {
    const mine = ++seq;
    if (controller) controller.abort();
    controller = new AbortController();

    // اگر کاربر با کیبورد روی تب‌ها بود، بعد از تعویض هم فوکوس روی تب فعال بماند
    const hadTabFocus = !!document.activeElement && !!document.activeElement.closest && !!document.activeElement.closest('#music-tabs');

    $('#music-results').addClass('is-loading-list').attr('aria-busy', 'true');

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal,
        });
        if (!res.ok) throw new Error(res.status);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        if (mine !== seq) return; // درخواست جدیدتری ارسال شده

        const $results = $(doc).find('#music-results');
        const $tabs = $(doc).find('#music-tabs');
        if (!$results.length || !$tabs.length) throw new Error('bad response');

        $('#music-tabs').replaceWith($tabs);
        $('#music-results').replaceWith($results);
        $('#music-filters input[name="level"]').val($(doc).find('#music-filters input[name="level"]').val());
        syncForm(url);

        if (push) history.pushState({ music: true }, '', url);
        if (hadTabFocus) $('#music-tabs [aria-current="page"]').trigger('focus');

        // بعد از رفتن به صفحه‌ی دیگر، اگر بالای لیست از دید خارج شده برگرد بالا
        const tabs = document.getElementById('music-tabs');
        if (scroll && tabs && tabs.getBoundingClientRect().top < 0) tabs.scrollIntoView({ block: 'start', behavior: 'smooth' });

        $(document).trigger('music:list-updated');
    } catch (err) {
        if (err && err.name === 'AbortError') return;
        window.location.href = url; // راه امن: رفرش عادی
    } finally {
        if (mine === seq) $('#music-results').removeClass('is-loading-list').removeAttr('aria-busy');
    }
}

// کلیک روی تب‌های سطح
$(document).on('click', '#music-tabs a', function (e) {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return; // باز کردن در تب جدید را خراب نکن
    if ($(this).attr('aria-current') === 'page') {
        e.preventDefault();
        return;
    }
    e.preventDefault();
    loadList(this.href);
});

// صفحه‌بندی عددی (دسکتاپ/تبلت) هم بدون رفرش
$(document).on('click', '#music-results nav[role="navigation"] a[href]', function (e) {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
    e.preventDefault();
    loadList(this.href, true, true);
});

// فیلتر / مرتب‌سازی / جستجو (آدرس تمیز: مقدارهای پیش‌فرض در آدرس نمی‌آیند)
$(document).on('submit', '#music-filters', function (e) {
    e.preventDefault();
    const params = new URLSearchParams();
    new FormData(this).forEach((value, name) => {
        const v = String(value).trim();
        if (v === '' || (name === 'filter' && v === 'all') || (name === 'sort' && v === 'default')) return;
        params.set(name, v);
    });
    const qs = params.toString();
    closeDialog($('#filter-dialog')); // شیت فیلتر موبایل بسته شود
    loadList(this.action + (qs ? '?' + qs : ''));
});

// روی دسکتاپ با تغییر انتخاب بلافاصله اعمال شود؛ در موبایل کاربر دکمه‌ی «اعمال» را می‌زند
$(document).on('change', '#music-filters select', function () {
    if (window.matchMedia('(min-width: 768px)').matches) {
        $('#music-filters')[0].requestSubmit();
    }
});

// دکمه‌ی Back / Forward مرورگر
$(function () {
    if (!$('#music-results').length) return;
    history.replaceState({ music: true }, '', location.href);
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.music) loadList(location.href, false);
    });
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
        $(document).trigger('music:list-updated');

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
    const fill = (key, value) => $dlg.find('[data-fill="' + key + '"]').text(value || '—'); // فیلد خالی = «—»

    fill('title', d('title'));
    fill('artist', d('artist'));
    fill('level', d('level-text'));
    fill('accent', d('accent-text'));
    fill('year', d('year'));
    fill('genre', d('genre'));
    fill('wpm', d('wpm-text'));
    fill('topic', d('topic'));
    fill('duration', d('duration'));
    fill('album', d('album'));
    fill('access', d('access-text'));

    // متن دکمه از وضعیت «فعلی» کارت خوانده می‌شود (پیشرفت زنده ممکن است عوضش کرده باشد)
    const ctaKey = { new: 'start', in_progress: 'continue', done: 'review' }[d('status')] || 'start';
    const ctaText = $.trim($card.find('[data-cta="' + ctaKey + '"]').first().text());

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
