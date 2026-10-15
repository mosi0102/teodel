import $ from 'jquery';

/**
 * مرحله‌ی ۸ (details): صفحه‌ی اطلاعات؛ فقط خواندنی است.
 * مرحله وقتی تمام می‌شود که کاربر به انتهای محتوا رسیده باشد و حداقل چند ثانیه روی این مرحله مانده باشد
 * (تا با اسکرول سریع «تمام‌شده» حساب نشود). با ورود به مرحله، نیمی از پیشرفتش ثبت می‌شود.
 */
const DWELL = 4000;

$('[data-details-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const end = $root.find('[data-details-end]')[0];
    let seen = typeof IntersectionObserver === 'undefined'; // مرورگر قدیمی: همیشه «دیده‌شده»
    let shownAt = 0;
    let timer = 0;
    let done = false;

    const active = () => !$panel.prop('hidden');

    function check() {
        clearTimeout(timer);
        if (done || !active() || !window.Teodel || !window.Teodel.lesson) return;
        const wait = DWELL - (Date.now() - shownAt);
        if (!seen) return;
        if (wait > 0) {
            timer = setTimeout(check, wait + 20);
            return;
        }
        done = true;
        window.Teodel.lesson.completeStep();
    }

    if (end && typeof IntersectionObserver !== 'undefined') {
        new IntersectionObserver((entries) => {
            seen = entries.some((e) => e.isIntersecting);
            check();
        }, { threshold: 0 }).observe(end);
    }

    $(document).on('lesson:step-change', () => {
        clearTimeout(timer);
        if (!active()) return;
        shownAt = Date.now();
        if (window.Teodel && window.Teodel.lesson) window.Teodel.lesson.setStepProgress(0.5);
        check();
    });
});
