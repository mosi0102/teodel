import $ from 'jquery';
import { demoEngine, audioEngine, fmt, SPEEDS } from './player';
import { digits } from './digits';

/**
 * کلیپ‌های کوچک مرحله‌ی listening
 *
 * - هر کلیپ فقط از data-start تا data-end پخش می‌شود و بعد خودش می‌ایستد.
 * - همه‌ی کلیپ‌های یک مرحله یک فایل صوتی مشترک دارند (یک دانلود) و هم‌زمان فقط یکی پخش می‌شود.
 * - اگر data-src خالی باشد «حالت دمو» (زمان‌سنج) کار می‌کند، مثل پخش‌کننده‌ی اصلی.
 * - هر کلیپ سرعت خودش (۰.۵ تا ۱.۵) و جای توقف خودش را نگه می‌دارد.
 *
 * API:  Teodel.clips.pauseAll()
 */
window.Teodel = window.Teodel || {};

$('[data-listening-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const src = $root.attr('data-src') || '';
    const demoDuration = Number($root.attr('data-duration')) || 60;

    let eng = null;
    let cur = null; // کلیپِ در حال پخش
    let raf = 0;

    const clips = $root.find('[data-clip]').map(function () {
        const $c = $(this);
        const c = {
            $c,
            start: Number($c.attr('data-start')) || 0,
            end: Number($c.attr('data-end')) || 0,
            rateIdx: SPEEDS.indexOf(1),
            at: null, // جای توقف (ثانیه‌ی موزیک)؛ null = از اول
        };
        c.len = Math.max(c.end - c.start, 0.5);
        $c.data('clip', c);
        return c;
    }).get();

    function paint(c, t) {
        const done = Math.min(Math.max((t - c.start) / c.len, 0), 1);
        c.$c.find('[data-clip-fill]').css('width', (done * 100).toFixed(1) + '%');
        c.$c.find('[data-clip-cur]').text(fmt(done * c.len));
    }

    function setState(c, st) {
        c.$c.attr('data-state', st);
        const $b = c.$c.find('[data-clip-toggle]');
        $b.attr('aria-label', $b.attr(st === 'playing' ? 'data-label-pause' : 'data-label-play'));
    }

    function loop() {
        cancelAnimationFrame(raf);
        if (!cur || !eng || !eng.playing) return;
        const t = eng.time;
        if (t >= cur.end - 0.02) {
            const c = cur;
            eng.pause();
            cur = null;
            c.at = null;
            paint(c, c.start);
            setState(c, 'paused');
            return;
        }
        paint(cur, t);
        raf = requestAnimationFrame(loop);
    }

    function stop(c, keep) {
        if (!c) return;
        if (cur === c) {
            if (eng) {
                c.at = keep ? eng.time : null;
                eng.pause();
            }
            cur = null;
        }
        cancelAnimationFrame(raf);
        if (!keep) paint(c, c.start);
        setState(c, 'paused');
    }

    function ensure() {
        if (eng) return eng;
        const hooks = {
            tick() {},
            state() {},
            meta() {},
            ended() {
                // فایل تمام شد (بخشِ آخر تا انتهای فایل)
                if (cur) stop(cur, false);
            },
            error() {
                if (cur) {
                    cur.$c.find('[data-clip-error]').prop('hidden', false);
                    stop(cur, false);
                }
            },
        };
        eng = src ? audioEngine(src, demoDuration, hooks) : demoEngine(demoDuration, hooks);
        return eng;
    }

    function play(c) {
        if (window.Teodel.player && window.Teodel.player.pauseAll) window.Teodel.player.pauseAll();
        if (cur && cur !== c) stop(cur, true);
        const e = ensure();
        c.$c.find('[data-clip-error]').prop('hidden', true);
        const from = c.at !== null && c.at > c.start && c.at < c.end - 0.15 ? c.at : c.start;
        cur = c;
        e.rate = SPEEDS[c.rateIdx];
        e.seek(from);
        e.play();
        setState(c, 'playing');
        raf = requestAnimationFrame(loop);
    }

    function setRate(c, i) {
        c.rateIdx = Math.min(Math.max(i, 0), SPEEDS.length - 1);
        if (cur === c && eng) eng.rate = SPEEDS[c.rateIdx];
        c.$c.find('[data-clip-speed]').text(digits(SPEEDS[c.rateIdx]) + 'x');
        c.$c.find('[data-clip-slower]').prop('disabled', c.rateIdx === 0);
        c.$c.find('[data-clip-faster]').prop('disabled', c.rateIdx === SPEEDS.length - 1);
    }

    clips.forEach((c) => {
        setRate(c, c.rateIdx);
        c.$c.on('click', '[data-clip-toggle]', () => (cur === c ? stop(c, true) : play(c)));
        c.$c.on('click', '[data-clip-slower]', () => setRate(c, c.rateIdx - 1));
        c.$c.on('click', '[data-clip-faster]', () => setRate(c, c.rateIdx + 1));
    });

    const pauseAll = () => cur && stop(cur, true);
    $root.data('pauseClips', pauseAll);

    // با رفتن به مرحله‌ی دیگر، صدا قطع شود
    $(document).on('lesson:step-change', () => {
        if ($panel.prop('hidden')) pauseAll();
    });
    $(document).on('visibilitychange', () => document.hidden && pauseAll());

    window.Teodel.clips = { pauseAll };
});
