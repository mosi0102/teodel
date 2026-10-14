import $ from 'jquery';
import { digits } from './digits';

/**
 * مرحله‌ی ۴ (listening): آسان = یک کلمه‌ی خالی، سخت = یک عبارت (هر کلمه یک کادر)
 *
 * ── برای بک‌اند ─────────────────────────────────────────────────────────
 *  ۱) داده: $song['listening'] = [ ['start'=>12.5,'end'=>16,'text'=>'…','answer'=>'run'], … ]
 *  ۲) بررسی جواب (اختیاری). پیش‌فرض در مرورگر و با data-answers مقایسه می‌کند؛ این یعنی جواب در HTML هست.
 *     اگر می‌خواهید جواب‌ها پنهان بمانند، این تابع را عوض کنید تا از سرور بپرسد:
 *       Teodel.listening.check = async ({ song, id, mode, answers }, values) => [true, false, …]   // یک boolean برای هر کادر
 *  ۳) رویداد نتیجه (برای ذخیره در دیتابیس):
 *       $(document).on('listening:result', (e, { song, id, mode, correct, attempts, revealed }) => {...})
 *     correct = درست نوشت؛ revealed = جواب را دید (تمرین تمام‌شده حساب می‌شود، ولی «درست» نه).
 *  ۴) پیشرفت مرحله: هر تمرینِ تمام‌شده (درست یا نمایش جواب) سهم دارد؛ با همه‌ی آن‌ها completeStep().
 *
 * وضعیت‌ها (data-state روی .lx): idle | wrong | correct | revealed
 */
window.Teodel = window.Teodel || {};

const LOCAL_SAVE = true; // دمو: ذخیره‌ی نتیجه در مرورگر تا بک‌اند وصل شود

const norm = (s) =>
    String(s)
        .toLowerCase()
        .replace(/[’‘`]/g, "'")
        .replace(/[^\p{L}\p{N}\s']/gu, ' ')
        .replace(/'/g, '')
        .replace(/\s+/g, ' ')
        .trim();

window.Teodel.listening = window.Teodel.listening || {};
if (!window.Teodel.listening.check) {
    window.Teodel.listening.check = async (ex, values) => values.map((v, i) => norm(v) === norm(ex.answers[i]));
}

$('[data-listening-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const song = Number($root.attr('data-song'));
    const key = 'teodel:listening:' + song;
    const $all = $root.find('[data-ex]');
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

    const gaps = ($ex) => $ex.find('[data-gap]');
    const isDone = ($ex) => ['correct', 'revealed'].includes($ex.attr('data-state'));

    function msg($ex, text, kind) {
        $ex.find('[data-ex-msg]').text(text || '').attr('data-kind', kind || '');
    }

    function lock($ex, on) {
        gaps($ex).prop('readonly', on);
        $ex.find('[data-ex-check]').prop('disabled', on);
        if (on) $ex.find('[data-ex-show]').prop('hidden', true);
    }

    function refresh() {
        ['easy', 'hard'].forEach((m) => {
            const n = $all.filter('[data-mode="' + m + '"]').filter((_, el) => isDone($(el))).length;
            $root.find('[data-ls-count="' + m + '"]').text(digits(n));
        });
        const done = $all.filter((_, el) => isDone($(el))).length;
        const ok = $all.filter('[data-state="correct"]').length;
        const $sum = $root.find('[data-ls-summary]');
        if (done >= total && total) {
            const tpl = $root.find('[data-ls-summary-text]').attr('data-tpl') || '';
            $root.find('[data-ls-summary-text]').text(tpl.replace(':ok', digits(ok)).replace(':total', digits(total)));
            $sum.prop('hidden', false);
        } else {
            $sum.prop('hidden', true);
        }
        progress(done);
    }

    function progress(done) {
        if ($panel.prop('hidden') || !window.Teodel.lesson || !total) return;
        window.Teodel.lesson.setStepProgress((done / total) * 0.95);
        if (done >= total && !finished) {
            finished = true;
            window.Teodel.lesson.completeStep();
        }
    }

    function finish($ex, state, attempts) {
        const id = $ex.attr('data-id');
        $ex.attr('data-state', state);
        lock($ex, true);
        saved[id] = { s: state, a: attempts };
        persist();
        refresh();
        $(document).trigger('listening:result', {
            song,
            id,
            mode: $ex.attr('data-mode'),
            correct: state === 'correct',
            attempts,
            revealed: state === 'revealed',
        });
    }

    function answersOf($ex) {
        try { return JSON.parse($ex.attr('data-answers')) || []; } catch (e) { return []; }
    }

    // ---------- بازگردانی ----------
    $all.each(function () {
        const $ex = $(this);
        const r = saved[$ex.attr('data-id')];
        if (!r) return;
        const ans = answersOf($ex);
        gaps($ex).each((i, el) => { el.value = ans[i] || ''; });
        $ex.attr('data-state', r.s).attr('data-attempts', r.a || 0);
        lock($ex, true);
        gaps($ex).addClass('is-ok');
        msg($ex, $root.attr('data-msg-' + r.s) || '', r.s);
    });

    // ---------- تایپ ----------
    const focusGap = ($ex, i, end) => {
        const el = gaps($ex).get(i);
        if (!el) return;
        el.focus();
        if (end) el.setSelectionRange(el.value.length, el.value.length);
        else el.select();
    };

    $root.on('input', '[data-gap]', function () {
        $(this).removeClass('is-bad');
        const $ex = $(this).closest('[data-ex]');
        if ($ex.attr('data-state') === 'wrong') msg($ex, '', '');
    });

    $root.on('keydown', '[data-gap]', function (e) {
        const $ex = $(this).closest('[data-ex]');
        const $g = gaps($ex);
        const i = $g.index(this);
        if (e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            if (this.value.trim() && i < $g.length - 1) focusGap($ex, i + 1);
        } else if (e.key === 'Backspace' && this.value === '' && i > 0) {
            e.preventDefault();
            focusGap($ex, i - 1, true);
        } else if (e.key === 'Enter' && i < $g.length - 1) {
            const next = $g.filter((j, el) => j > i && !el.value.trim()).first();
            if (next.length) {
                e.preventDefault();
                focusGap($ex, $g.index(next[0]));
            }
        } else if (e.key === 'ArrowLeft' && this.selectionStart === 0 && i > 0 && !e.shiftKey) {
            e.preventDefault();
            focusGap($ex, i - 1, true);
        } else if (e.key === 'ArrowRight' && this.selectionStart === this.value.length && i < $g.length - 1 && !e.shiftKey) {
            e.preventDefault();
            focusGap($ex, i + 1);
        }
    });

    // چسباندن یک عبارت: کلمه‌ها بین کادرها پخش می‌شوند
    $root.on('paste', '[data-gap]', function (e) {
        const text = ((e.originalEvent.clipboardData || window.clipboardData).getData('text') || '').trim();
        const parts = text.split(/\s+/).filter(Boolean);
        if (parts.length < 2) return;
        e.preventDefault();
        const $ex = $(this).closest('[data-ex]');
        const $g = gaps($ex);
        let i = $g.index(this);
        parts.forEach((w) => {
            if (i < $g.length) {
                $g[i].value = w;
                $($g[i]).removeClass('is-bad');
                i++;
            }
        });
        focusGap($ex, Math.min(i, $g.length - 1), true);
    });

    // ---------- بررسی ----------
    $root.on('submit', '[data-ex-form]', async function (e) {
        e.preventDefault();
        const $ex = $(this).closest('[data-ex]');
        if (isDone($ex) || $ex.data('busy')) return;
        const $g = gaps($ex);
        const values = $g.map((_, el) => el.value.trim()).get();
        const empty = values.findIndex((v) => !v);
        if (empty >= 0) {
            msg($ex, $root.attr('data-msg-fill'), 'wrong');
            $g.eq(empty).addClass('is-bad');
            focusGap($ex, empty);
            return;
        }
        $ex.data('busy', true);
        let res;
        try {
            res = await window.Teodel.listening.check(
                { song, id: $ex.attr('data-id'), mode: $ex.attr('data-mode'), answers: answersOf($ex) },
                values,
            );
        } catch (err) {
            console.warn('[listening] check failed', err);
            $ex.data('busy', false);
            return;
        }
        $ex.data('busy', false);
        const attempts = Number($ex.attr('data-attempts')) + 1;
        $ex.attr('data-attempts', attempts);

        if (res.every(Boolean)) {
            $g.removeClass('is-bad').addClass('is-ok');
            msg($ex, $root.attr('data-msg-correct'), 'correct');
            finish($ex, 'correct', attempts);
        } else {
            $ex.attr('data-state', 'wrong');
            $g.each((i, el) => $(el).toggleClass('is-bad', !res[i]));
            msg($ex, $root.attr('data-msg-wrong'), 'wrong');
            // لرزش نرم: انیمیشن را دوباره راه می‌اندازد
            $ex.removeClass('is-shake');
            void $ex[0].offsetWidth;
            $ex.addClass('is-shake');
            if (attempts >= 2) $ex.find('[data-ex-show]').prop('hidden', false);
            focusGap($ex, res.findIndex((r) => !r));
        }
    });

    // ---------- نمایش جواب ----------
    $root.on('click', '[data-ex-show]', function () {
        const $ex = $(this).closest('[data-ex]');
        if (isDone($ex)) return;
        const ans = answersOf($ex);
        gaps($ex).each((i, el) => { el.value = ans[i] || ''; }).removeClass('is-bad').addClass('is-ok');
        msg($ex, $root.attr('data-msg-revealed'), 'revealed');
        finish($ex, 'revealed', Number($ex.attr('data-attempts')));
    });

    $(document).on('lesson:step-change', () => {
        if (!$panel.prop('hidden')) refresh();
    });
    refresh();
});
