import $ from 'jquery';
import { digits } from './digits';

/**
 * امتیازدهی «چقدر متوجه شدی؟» (مرحله‌ی ۱ و ۷)
 *
 * جریان: قفل (تا کامل گوش ندهد) → آماده (امتیاز می‌دهد) → ثبت‌شده.
 * پیشرفت زنده‌ی مرحله: تا ۸۰٪ با شنیدن، و ۱۰۰٪ با ثبت امتیاز.
 *
 * 🔌 hook بک‌اند:
 *   $(document).on('lesson:rating', (e, { song, kind, score, baseline }) => {...})
 *   kind = 'play' | 'repeat' ؛ baseline = امتیاز مرحله‌ی اول (فقط در repeat)
 *
 * LOCAL_SCORES = true → تا وصل‌شدن بک‌اند، امتیاز مرحله‌ی اول در مرورگر نگه داشته می‌شود
 * تا مرحله‌ی هفتم بتواند مقایسه کند (فقط دمو). با وصل‌شدن بک‌اند، false کنید؛ مقدار از data-baseline می‌آید.
 */
const LOCAL_SCORES = true;
const key = (song, kind) => 'teodel:score:' + song + ':' + kind;

const read = (song, kind) => {
    if (!LOCAL_SCORES) return null;
    try {
        return Number(localStorage.getItem(key(song, kind))) || null;
    } catch (e) {
        return null;
    }
};
const save = (song, kind, score) => {
    if (!LOCAL_SCORES) return;
    try {
        localStorage.setItem(key(song, kind), String(score));
    } catch (e) {
        /* localStorage در دسترس نیست */
    }
};

const band = (n) => Math.ceil(n / 2);
const hue = (n) => Math.round(((n - 1) * 130) / 9);

function init(el) {
    const $r = $(el);
    const kind = $r.attr('data-kind');
    const song = Number($r.attr('data-song'));
    const threshold = Number($r.attr('data-threshold')) || 0.9;
    const $panel = $r.closest('[data-step-panel]');
    const $submit = $r.find('[data-r-submit]');
    const $inputs = $r.find('input[type="radio"]');

    const caption = (n) => $r.find('[data-cap="' + band(n) + '"]').text();
    const setState = (s) => $r.attr('data-state', s);

    function showCaption() {
        const v = Number($inputs.filter(':checked').val());
        $r.find('[data-r-caption]').text(v ? caption(v) : '');
        $submit.prop('disabled', !v || $r.attr('data-state') !== 'ready');
    }

    function showResult(score) {
        $r.find('[data-r-score]').text(digits(score));
        $r.find('[data-r-badge]')[0].style.setProperty('--h', hue(score));
        $r.find('[data-r-saved-caption]').text(caption(score));

        if (kind === 'repeat') {
            const first = Number($r.attr('data-baseline')) || read(song, 'play');
            const $bar = (k) => $r.find('[data-r-bar="' + k + '"]');
            const $val = (k) => $r.find('[data-r-cmp="' + k + '"]');

            $val('now').text(digits(score) + ' / ' + digits(10));
            $val('first').text(first ? digits(first) + ' / ' + digits(10) : '—');
            $r.find('[data-r-msg]').prop('hidden', true);

            let m = 'none';
            let diff = 0;
            if (first) {
                diff = score - first;
                m = diff > 0 ? 'up' : diff < 0 ? 'down' : 'same';
            }
            $r.find('[data-r-msg="' + m + '"]').prop('hidden', false).text(function () {
                return $(this).text().replace(':n', digits(Math.abs(diff)));
            });

            // نوارها بعد از یک فریم پر شوند تا انیمیشن دیده شود
            $bar('first').css('width', '0%');
            $bar('now').css('width', '0%');
            requestAnimationFrame(() => requestAnimationFrame(() => {
                $bar('first').css('width', (first || 0) * 10 + '%');
                $bar('now').css('width', score * 10 + '%');
            }));
        }
    }

    // --- شروع: امتیاز ثبت‌شده‌ی قبلی از سرور → مستقیم «ثبت‌شده» ---
    const saved = Number($r.attr('data-score')) || 0;
    if (saved) {
        $inputs.filter('[value="' + saved + '"]').prop('checked', true);
        showResult(saved);
        setState('done');
    }
    showCaption();

    // --- گوش دادن: درصد شنیده‌شده و باز شدن قفل ---
    $(document).on('player:listened', (e, d) => {
        if (!$panel.length || !$.contains($panel[0], d.el)) return;
        if ($r.attr('data-state') === 'done') return;

        const pct = Math.round(Math.min(d.ratio / threshold, 1) * 100);
        $r.find('[data-r-meter]').css('width', pct + '%');
        $r.find('[data-r-pct]').text(digits(pct) + '%');

        if (window.Teodel && window.Teodel.lesson) window.Teodel.lesson.setStepProgress(Math.min(d.ratio / threshold, 1) * 0.8);

        if (d.ratio >= threshold && $r.attr('data-state') === 'locked') {
            setState('ready');
            showCaption();
        }
    });

    // --- انتخاب امتیاز ---
    $inputs.on('change', showCaption);

    // قفل بودن: به‌جای انتخاب، یادآوری ملایم (لرزش نوار هشدار)
    $r.on('click', '.rate', function (e) {
        if ($r.attr('data-state') !== 'locked') return;
        e.preventDefault();
        $r.removeClass('is-nudge');
        void el.offsetWidth;
        $r.addClass('is-nudge');
    });

    // --- ثبت ---
    $r.find('[data-r-form]').on('submit', function (e) {
        e.preventDefault();
        const score = Number($inputs.filter(':checked').val());
        if (!score || $r.attr('data-state') !== 'ready') return;

        const baseline = kind === 'repeat' ? Number($r.attr('data-baseline')) || read(song, 'play') || null : null;
        save(song, kind, score);
        showResult(score);
        setState('done');

        $(document).trigger('lesson:rating', [{ song, kind, score, baseline }]);
        if (window.Teodel && window.Teodel.lesson) window.Teodel.lesson.completeStep();
    });
}

$(function () {
    $('[data-rating]').each(function () { init(this); });
});
