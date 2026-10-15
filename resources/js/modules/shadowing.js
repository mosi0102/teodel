import $ from 'jquery';
import { digits } from './digits';

/**
 * مرحله‌ی ۵ (shadowing): بخشی از موزیک را می‌شنوی، «نوبت توست» و تکرار می‌کنی؛ هر دور کامل = یک تکرار.
 * پخش و نوبت کاربر در clip.js است؛ اینجا فقط شمارش، متنِ تار، پیشرفت و ذخیره.
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 *   $song['shadowing'] = [ ['start'=>24.4, 'end'=>30.6, 'text'=>"line 1\nline 2"], … ]   (و اختیاری $song['shadowing_repeats'] = 3)
 *   $(document).on('shadowing:round', (e, { song, id, rounds, target, done, revealed }) => {...})
 *       ← یک دور تمام شد. rounds = تعداد کل دورها، done = به هدف رسید، revealed = متن را دیده است.
 *   پیشرفت: هر بخش تا «هدف» سهم دارد؛ وقتی همه‌ی بخش‌ها به هدف رسیدند completeStep().
 *   (تکرار بیشتر از هدف هم شمرده می‌شود ولی پیشرفت را بیشتر نمی‌کند.)
 *   توجه: برنامه صدای کاربر را نمی‌شنود؛ «دور» یعنی کاربر نوبتش را تا آخر ادامه داده (یا «تکرار کردم» را زده).
 */
const LOCAL_SAVE = true; // دمو: ذخیره در مرورگر تا بک‌اند وصل شود

$('[data-shadowing-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const song = Number($root.attr('data-song'));
    const target = Number($root.attr('data-target')) || 3;
    const key = 'teodel:shadowing:' + song;
    const $all = $root.find('[data-sx]');
    const total = $all.length;
    let saved = {};
    let finished = false;

    try {
        saved = JSON.parse(localStorage.getItem(key) || '{}') || {};
    } catch (e) {
        saved = {};
    }
    const persist = () => {
        if (!LOCAL_SAVE) return;
        try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) { /* ignore */ }
    };

    function paint($sx) {
        const n = Number($sx.attr('data-rounds'));
        const done = n >= target;
        $sx.attr('data-state', done ? 'done' : n > 0 ? 'doing' : 'idle');
        $sx.find('[data-sx-dot]').each((i, el) => { el.classList.toggle('is-on', i < n); });
        const $c = $sx.find('[data-sx-count]');
        const tpl = $c.attr(done ? 'data-tpl-done' : 'data-tpl') || '';
        $c.text(tpl.replace(':n', digits(n)).replace(':total', digits(target)));
    }

    function refresh() {
        const done = $all.filter('[data-state="done"]').length;
        $root.find('[data-sx-done]').text(digits(done));
        $root.find('[data-sx-summary]').prop('hidden', done < total);
        if ($panel.prop('hidden') || !window.Teodel || !window.Teodel.lesson) return;
        let sum = 0;
        $all.each((_, el) => { sum += Math.min(Number($(el).attr('data-rounds')), target) / target; });
        window.Teodel.lesson.setStepProgress((sum / total) * 0.95);
        if (done >= total && !finished) {
            finished = true;
            window.Teodel.lesson.completeStep();
        }
    }

    $all.each(function () {
        const $sx = $(this);
        $sx.attr('data-rounds', Number(saved[$sx.attr('data-id')]) || 0);
        paint($sx);
    });

    // یک دور تمام شد (از clip.js)
    $root.on('clip:round', '[data-clip]', function () {
        const $sx = $(this).closest('[data-sx]');
        const id = $sx.attr('data-id');
        const n = Number($sx.attr('data-rounds')) + 1;
        $sx.attr('data-rounds', n);
        saved[id] = n;
        persist();
        paint($sx);
        refresh();
        $(document).trigger('shadowing:round', {
            song,
            id,
            rounds: n,
            target,
            done: n >= target,
            revealed: $sx.find('[data-sx-reveal]').attr('aria-pressed') === 'true',
        });
    });

    // متن: تار ⇄ نمایش
    $root.on('click', '[data-sx-reveal]', function () {
        const $b = $(this);
        const on = $b.attr('aria-pressed') !== 'true';
        $b.attr('aria-pressed', on);
        const label = $b.attr(on ? 'data-label-hide' : 'data-label-show');
        $b.attr('aria-label', label).find('[data-sx-reveal-label]').text(label);
    });

    $(document).on('lesson:step-change', () => {
        if (!$panel.prop('hidden')) refresh();
    });
    refresh();
});
