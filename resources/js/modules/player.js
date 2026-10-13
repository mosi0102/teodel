import $ from 'jquery';
import { digits } from './digits';

/**
 * پخش‌کننده‌ی موزیک (مرحله‌های play و repeat)
 *
 * - اگر data-src داشته باشد، با <audio> واقعی پخش می‌کند؛ اگر نه، «حالت دمو» با زمان‌سنج (فقط برای تست فرانت).
 * - «شنیده‌شده» با پوشش ثانیه‌ها حساب می‌شود: فقط پخش عادی ثانیه‌ها را می‌شمارد، پرش (seek) نمی‌شمارد،
 *   پس کاربر نمی‌تواند با جلو زدن، موزیک را «شنیده» حساب کند.
 *
 * رویداد (برای امتیازدهی و مراحل دیگر):
 *   $(document).on('player:listened', (e, { el, song, ratio, ended }) => {...})   ratio = ۰ تا ۱
 * API:  Teodel.player.pauseAll()
 */
const SPEEDS = [0.5, 0.75, 1, 1.25, 1.5];

const fmt = (sec) => {
    sec = Math.max(0, Math.floor(sec));
    return digits(String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0'));
};

/** موتور دمو: همان رابط <audio> را با زمان‌سنج شبیه‌سازی می‌کند */
function demoEngine(duration, hooks) {
    let t = 0;
    let rate = 1;
    let timer = null;
    let last = 0;
    const stop = () => {
        clearInterval(timer);
        timer = null;
    };
    return {
        get time() { return t; },
        get duration() { return duration; },
        get playing() { return timer !== null; },
        set rate(v) { rate = v; },
        play() {
            if (timer) return;
            last = Date.now();
            timer = setInterval(() => {
                const now = Date.now();
                t = Math.min(duration, t + ((now - last) / 1000) * rate);
                last = now;
                if (t >= duration) {
                    stop();
                    hooks.tick();
                    hooks.ended();
                } else hooks.tick();
            }, 200);
            hooks.state();
        },
        pause() {
            stop();
            hooks.state();
        },
        seek(v) {
            t = Math.min(Math.max(v, 0), duration);
            hooks.tick();
        },
    };
}

/** موتور واقعی: <audio> (فقط هنگام اولین پخش ساخته می‌شود تا صفحه سبک بماند) */
function audioEngine(src, fallbackDuration, hooks) {
    const a = new Audio();
    a.preload = 'metadata';
    a.src = src;
    a.addEventListener('timeupdate', hooks.tick);
    a.addEventListener('play', hooks.state);
    a.addEventListener('pause', hooks.state);
    a.addEventListener('ended', () => { hooks.state(); hooks.ended(); });
    a.addEventListener('error', hooks.error);
    a.addEventListener('loadedmetadata', hooks.tick);
    return {
        get time() { return a.currentTime || 0; },
        get duration() { return Number.isFinite(a.duration) && a.duration > 0 ? a.duration : fallbackDuration; },
        get playing() { return !a.paused && !a.ended; },
        set rate(v) { a.playbackRate = v; },
        play() { const p = a.play(); if (p && p.catch) p.catch(hooks.error); },
        pause() { a.pause(); },
        seek(v) { a.currentTime = Math.min(Math.max(v, 0), this.duration); },
    };
}

function init(el) {
    const $p = $(el);
    const song = Number($p.attr('data-song'));
    const src = $p.attr('data-src') || '';
    const baseDuration = Number($p.attr('data-duration')) || 180;
    const $seek = $p.find('[data-p-seek]');

    let rateIdx = SPEEDS.indexOf(1);
    let scrubbing = false;
    let ended = false;
    const heard = new Set(); // ثانیه‌هایی که واقعاً پخش شده‌اند
    let lastT = 0;
    let lastPct = -1;

    const hooks = { tick, state, ended: onEnded, error: onError };
    const engine = src ? null : demoEngine(baseDuration, hooks);
    let eng = engine; // موتور واقعی هنگام اولین پخش ساخته می‌شود

    const dur = () => (eng ? eng.duration : baseDuration);

    function render() {
        const t = eng ? eng.time : 0;
        const d = dur();
        const r = Math.min(t / d, 1);
        $p.find('[data-p-fill]').css('width', r * 100 + '%');
        $p.find('[data-p-thumb]').css('left', r * 100 + '%');
        $p.find('[data-p-cur]').text(fmt(t));
        $p.find('[data-p-total]').text(fmt(d));
        if (!scrubbing) $seek.val(Math.round(r * 1000));
        $seek.attr('aria-valuetext', fmt(t) + ' / ' + fmt(d));
    }

    function ratio() {
        return Math.min(heard.size / Math.max(Math.ceil(dur()), 1), 1);
    }

    function emit() {
        const r = ratio();
        const pct = Math.floor(r * 100);
        if (pct === lastPct && !ended) return;
        lastPct = pct;
        $(document).trigger('player:listened', [{ el, song, ratio: r, ended }]);
    }

    function tick() {
        const t = eng.time;
        const delta = t - lastT;
        // پخش عادی = جلو رفتن کم؛ پرش (seek) شمرده نمی‌شود
        if (eng.playing && delta > 0 && delta < 2.5) {
            for (let s = Math.floor(lastT); s <= Math.floor(t); s++) heard.add(s);
        }
        lastT = t;
        render();
        emit();
    }

    function state() {
        $p.attr('data-state', eng && eng.playing ? 'playing' : 'paused');
        const $b = $p.find('[data-p-toggle]');
        $b.attr('aria-label', $b.attr(eng && eng.playing ? 'data-label-pause' : 'data-label-play'));
    }

    function onEnded() {
        ended = true;
        state();
        emit();
    }

    function onError() {
        $p.find('[data-p-error]').prop('hidden', false);
        state();
    }

    function ensure() {
        if (!eng) {
            eng = audioEngine(src, baseDuration, hooks);
            eng.rate = SPEEDS[rateIdx];
        }
        return eng;
    }

    function toggle() {
        const e = ensure();
        $p.find('[data-p-error]').prop('hidden', true);
        if (e.playing) return e.pause();
        if (e.time >= e.duration - 0.2) { e.seek(0); lastT = 0; ended = false; }
        e.play();
    }

    function setSpeed(i) {
        rateIdx = Math.min(Math.max(i, 0), SPEEDS.length - 1);
        if (eng) eng.rate = SPEEDS[rateIdx];
        $p.find('[data-p-speed]').text(digits(SPEEDS[rateIdx]).replace(/\.(?=\d)/, '.') + 'x');
        $p.find('[data-p-slower]').prop('disabled', rateIdx === 0);
        $p.find('[data-p-faster]').prop('disabled', rateIdx === SPEEDS.length - 1);
    }

    $p.on('click', '[data-p-toggle]', toggle);
    $p.on('click', '[data-p-back]', () => { const e = ensure(); e.seek(e.time - 10); lastT = e.time; render(); });
    $p.on('click', '[data-p-slower]', () => setSpeed(rateIdx - 1));
    $p.on('click', '[data-p-faster]', () => setSpeed(rateIdx + 1));

    $seek.on('input', function () {
        scrubbing = true;
        const e = ensure();
        e.seek((Number(this.value) / 1000) * e.duration);
        lastT = e.time;
        render();
    });
    $seek.on('change blur', () => { scrubbing = false; });

    $p.data('player', { pause: () => eng && eng.playing && eng.pause(), ratio });

    setSpeed(rateIdx);
    state();
    render();
}

window.Teodel = window.Teodel || {};
window.Teodel.player = {
    pauseAll(exceptEl) {
        $('[data-player]').each(function () {
            if (this !== exceptEl && $(this).data('player')) $(this).data('player').pause();
        });
    },
};

$(function () {
    $('[data-player]').each(function () { init(this); });
});

/* هر پخش‌کننده‌ای که پنلش دیده نمی‌شود (مرحله عوض شد) متوقف شود، و هم‌زمان فقط یکی پخش شود */
$(document).on('lesson:step-change', () => {
    $('[data-player]').each(function () {
        if ($(this).closest('[data-step-panel]').prop('hidden')) $(this).data('player') && $(this).data('player').pause();
    });
});
$(document).on('click', '[data-p-toggle]', function () {
    window.Teodel.player.pauseAll($(this).closest('[data-player]')[0]);
});
