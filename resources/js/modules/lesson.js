import $ from 'jquery';

/**
 * صفحه‌ی آموزش (تک‌صفحه‌ای): تعویض مرحله بدون رفرش + پیشرفت
 *
 * محتوای هر ۸ مرحله با @include داخل صفحه آمده؛ اینجا فقط پنل فعال نشان داده می‌شود
 * و آدرس مرورگر با history عوض می‌شود (لینک مستقیم و دکمه‌ی Back کار می‌کنند).
 *
 * ── API برای محتوای هر مرحله ─────────────────────────────────────────────
 *   Teodel.lesson.setStepProgress(0.4);  // ۴۰٪ از «مرحله‌ی جاری» انجام شد (۰ تا ۱)
 *   Teodel.lesson.completeStep();        // مرحله‌ی جاری تمام شد
 *   Teodel.lesson.go(3);                 // رفتن به مرحله‌ی ۳
 *
 * ── رویدادها (برای محتوای مرحله‌ها و بک‌اند) ──────────────────────────────
 *   $(document).on('lesson:step-change',   (e, { song, step, key }) => {...});
 *       ← مرحله عوض شد (در لود اولیه هم یک‌بار برای مرحله‌ی فعلی). برای شروع/توقف صدا و ویدیو
 *         و ساخت سنگین‌ها فقط وقتی مرحله‌شان فعال شد، از این استفاده کنید.
 *   $(document).on('lesson:progress',      (e, { song, step, fraction, percent, steps }) => {...});
 *   $(document).on('lesson:step-complete', (e, data) => {...});
 *
 * فرمول: درصد = (جمع پیشرفت هر مرحله، هر کدام ۰ تا ۱) ÷ تعداد مرحله‌ها × ۱۰۰
 *
 * نکته: چون همه‌ی مرحله‌ها هم‌زمان در صفحه‌اند، id های داخل هر مرحله را با نام مرحله شروع کنید
 * (مثلاً lyrics-list) تا با مرحله‌های دیگر یکی نشوند.
 */
const $root = $('[data-lesson]');

if ($root.length) {
    const total = Number($root.attr('data-total')) || 8;
    const song = Number($root.attr('data-song'));
    const finishUrl = $root.attr('data-finish-url') || '#';
    const $items = $root.find('[data-step-item]');
    const $panels = $root.find('[data-step-panel]');
    const $actions = $root.find('.step-actions');

    const state = { step: Number($root.attr('data-step')) || 1, fr: [0] };

    // پیشرفت اولیه از سرور: مرحله‌های تمام‌شده ۱، بخشی از مرحله‌ی بعدی = fraction
    const completed = Number($root.attr('data-completed')) || 0;
    const fraction = Number($root.attr('data-fraction')) || 0;
    for (let n = 1; n <= total; n++) {
        state.fr[n] = n <= completed ? 1 : n === completed + 1 ? Math.min(Math.max(fraction, 0), 1) : 0;
    }

    const item = (n) => $items.filter('[data-step-item="' + n + '"]');
    const isLocked = (n) => item(n).attr('data-locked') === '1';
    const percent = () => Math.round((state.fr.slice(1).reduce((a, b) => a + b, 0) / total) * 100);

    /** مرحله‌ی «جاری» (نارنجی) = اولین مرحله‌ی تمام‌نشده‌ی قفل‌نشده */
    const nextTodo = () => {
        for (let n = 1; n <= total; n++) if (state.fr[n] < 1 && !isLocked(n)) return n;
        return 0;
    };

    function renderStepper() {
        const todo = nextTodo();
        $items.each(function () {
            const $li = $(this);
            const n = Number($li.attr('data-step-item'));
            const st = isLocked(n) ? 'locked' : state.fr[n] >= 1 ? 'done' : n === todo ? 'current' : 'upcoming';
            $li.attr('data-state', st);

            const $a = $li.find('[data-step-link]');
            if (n === state.step) $a.attr('aria-current', 'step');
            else $a.removeAttr('aria-current');
        });
    }

    function renderActions() {
        const n = state.step;
        const last = n >= total;

        const $prev = $actions.find('[data-step-prev]');
        if (n > 1) $prev.attr('href', item(n - 1).find('[data-step-link]').attr('href')).removeClass('is-disabled');
        else $prev.removeAttr('href').addClass('is-disabled');

        const $next = $actions.find('[data-step-next]');
        $next.attr('href', last ? finishUrl : item(n + 1).find('[data-step-link]').attr('href'));
        $next.find('[data-next-label]').text($actions.attr(last ? 'data-label-finish' : 'data-label-next'));
        $next.find('[data-next-icon="chevron"]').attr('hidden', last ? '' : null);
        $next.find('[data-next-icon="check"]').attr('hidden', last ? null : '');
    }

    function payload() {
        return { song, step: state.step, fraction: state.fr[state.step], percent: percent(), steps: state.fr.slice(1) };
    }

    /**
     * رفتن به مرحله‌ی n بدون رفرش.
     * opts.push: ثبت در history | opts.user: کاربر خودش زده (اسکرول و اعلام به صفحه‌خوان)
     */
    function show(n, opts) {
        opts = Object.assign({ push: true, user: true, initial: false }, opts);
        n = Math.min(Math.max(Number(n) || 1, 1), total);
        const changed = n !== state.step;
        state.step = n;

        const $li = item(n);
        let $active = $();
        $panels.each(function () {
            const on = Number($(this).attr('data-step-panel')) === n;
            $(this).attr('hidden', on ? null : '');
            if (on) $active = $(this);
        });

        $('#step-title').text($li.attr('data-title'));
        $('#step-desc').text($li.attr('data-desc'));
        renderStepper();
        renderActions();

        if (changed || opts.initial) {
            // انیمیشن ورود پنل (با ری‌استارت کلاس)
            if (!opts.initial && $active.length) {
                $active.removeClass('animate-rise');
                void $active[0].offsetWidth;
                $active.addClass('animate-rise');
            }

            // مرحله‌ی جاری در نوار اسکرول‌شونده‌ی موبایل وسط بیاید
            const el = $li[0];
            if (el && typeof el.scrollIntoView === 'function') {
                el.scrollIntoView({ inline: 'center', block: 'nearest', behavior: opts.initial ? 'auto' : 'smooth' });
            }

            if (opts.push && !opts.initial) {
                history.pushState({ step: n }, '', $li.find('[data-step-link]').attr('href'));
            }

            if (opts.user) {
                $('#step-announce').text($li.attr('data-title') + ' — ' + $li.find('.step-label').text());
                // اگر صفحه پایین‌تر از نوار مراحل اسکرول شده، برگرد بالا
                const stepper = document.getElementById('lesson-stepper');
                if (stepper && stepper.getBoundingClientRect().top < 0) {
                    stepper.scrollIntoView({ block: 'start', behavior: 'smooth' });
                }
            }

            $(document).trigger('lesson:step-change', [{ song, step: n, key: $active.attr('data-step-key') }]);
        }
    }

    window.Teodel = window.Teodel || {};
    window.Teodel.lesson = {
        /** رفتن به یک مرحله (بدون رفرش) */
        go(n) {
            show(n);
        },
        /** بخشی از مرحله‌ی جاری که انجام شده (۰ تا ۱). عدد کمتر از قبل، پیشرفت را کم نمی‌کند. */
        setStepProgress(f) {
            if (isLocked(state.step)) return;
            const v = Math.min(Math.max(Number(f) || 0, 0), 1);
            state.fr[state.step] = Math.max(state.fr[state.step], v);
            renderStepper();
            $(document).trigger('lesson:progress', [payload()]);
        },
        /** مرحله‌ی جاری تمام شد */
        completeStep() {
            if (isLocked(state.step)) return;
            state.fr[state.step] = 1;
            renderStepper();
            const data = payload();
            $(document).trigger('lesson:progress', [data]);
            $(document).trigger('lesson:step-complete', [data]);
        },
        get step() {
            return state.step;
        },
        get percent() {
            return percent();
        },
    };

    $(function () {
        // آدرس اولیه هم state داشته باشد تا Back/Forward درست کار کند
        history.replaceState({ step: state.step }, '', location.href);
        show(state.step, { push: false, user: false, initial: true });

        // کلیک روی دایره‌ی مرحله
        $root.on('click', '[data-step-link]', function (e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return; // باز کردن در تب جدید را خراب نکن
            e.preventDefault();
            show($(this).closest('[data-step-item]').attr('data-step-item'));
        });

        // قبل / بعد (در آخرین مرحله «بعد» لینک عادی به لیست موزیک است)
        $root.on('click', '[data-step-prev]', function (e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
            e.preventDefault();
            if (state.step > 1) show(state.step - 1);
        });
        $root.on('click', '[data-step-next]', function (e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
            if (state.step >= total) return;
            e.preventDefault();
            show(state.step + 1);
        });

        // دکمه‌ی Back / Forward مرورگر
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.step) show(e.state.step, { push: false, user: false });
        });
    });
}
