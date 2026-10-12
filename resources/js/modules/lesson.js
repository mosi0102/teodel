import $ from 'jquery';

/**
 * صفحه‌ی آموزش: پیشرفت زنده + نوار مراحل
 *
 * محتوای هر مرحله (تمرین) فقط باید این دو تابع را صدا بزند:
 *
 *   Teodel.lesson.setStepProgress(0.4);   // ۴۰٪ از همین مرحله انجام شد (عدد بین ۰ تا ۱)
 *   Teodel.lesson.completeStep();         // این مرحله تمام شد
 *
 * نوار پیشرفت و درصد صفحه خودکار به‌روز می‌شود، و این رویدادها فرستاده می‌شود
 * تا بک‌اند‌کار (هر وقت خواست) ذخیره‌سازی را به آن‌ها وصل کند:
 *
 *   $(document).on('lesson:progress',      (e, data) => {...});  // { song, step, fraction, completed, percent }
 *   $(document).on('lesson:step-complete', (e, data) => {...});
 *
 * فرمول: درصد = (مرحله‌های تمام‌شده + بخشی از مرحله‌ی جاری) ÷ کل مرحله‌ها × ۱۰۰
 */
const $root = $('[data-lesson]');

const toLocaleDigits = (value) => {
    const s = String(value);
    if (document.documentElement.lang !== 'fa') return s;
    return s.replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
};

if ($root.length) {
    const state = {
        song: Number($root.attr('data-song')),
        step: Number($root.attr('data-step')),
        total: Number($root.attr('data-total')) || 8,
        completed: Number($root.attr('data-completed')) || 0,
        fraction: Number($root.attr('data-fraction')) || 0,
    };

    const percent = () => {
        const done = Math.min(state.completed, state.total);
        const part = done >= state.total ? 0 : Math.min(Math.max(state.fraction, 0), 1);
        return Math.round(((done + part) / state.total) * 100);
    };

    const render = () => {
        const p = percent();
        $('[data-progress-bar]').css('transform', `scaleX(${p / 100})`);
        $('[data-progress-text]').text(toLocaleDigits(p) + '%');
        $('[data-progress-track]').attr('aria-valuenow', p);
    };

    const payload = () => ({ song: state.song, step: state.step, fraction: state.fraction, completed: state.completed, percent: percent() });

    window.Teodel = window.Teodel || {};
    window.Teodel.lesson = {
        /** بخشی از مرحله‌ی جاری که انجام شده (۰ تا ۱) */
        setStepProgress(fraction) {
            state.fraction = Math.min(Math.max(Number(fraction) || 0, 0), 1);
            render();
            $(document).trigger('lesson:progress', [payload()]);
        },
        /** مرحله‌ی جاری تمام شد */
        completeStep() {
            state.completed = Math.max(state.completed, state.step);
            state.fraction = 0;
            $(`[data-step-item="${state.step}"]`).attr('data-state', 'done');
            render();
            $(document).trigger('lesson:progress', [payload()]);
            $(document).trigger('lesson:step-complete', [payload()]);
        },
        get percent() {
            return percent();
        },
    };

    $(function () {
        render();

        // در موبایل نوار مراحل اسکرول می‌شود؛ مرحله‌ی جاری را وسط بیاور
        const current = $('[data-step-item][data-state="current"]')[0];
        if (current && typeof current.scrollIntoView === 'function') {
            current.scrollIntoView({ inline: 'center', block: 'nearest' });
        }
    });
}
