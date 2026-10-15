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
export const SPEEDS = [0.5, 0.75, 1, 1.25, 1.5];

export const fmt = (sec) => {
    sec = Math.max(0, Math.floor(sec));
    return digits(String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0'));
};

/** موتور دمو: همان رابط <audio> را با زمان‌سنج شبیه‌سازی می‌کند */
export function demoEngine(duration, hooks) {
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

/**
 * iOS مقدار element.volume را نادیده می‌گیرد؛ آنجا (و فقط آنجا) صدا از GainNode رد می‌شود.
 * اگر فایل از دامنه‌ی دیگری باشد و CORS نداشته باشد، WebAudio آن را بی‌صدا می‌کند؛ پس در آن حالت استفاده نمی‌شود.
 */
const isIOS = /iP(hone|ad|od)/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
let audioCtx = null;
function webAudioGain(el, src) {
    if (!isIOS) return null;
    try {
        if (new URL(src, location.href).origin !== location.origin) return null;
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        audioCtx = audioCtx || new AC();
        const g = audioCtx.createGain();
        audioCtx.createMediaElementSource(el).connect(g);
        g.connect(audioCtx.destination);
        return g;
    } catch (e) {
        return null;
    }
}

/**
 * موتور واقعی: <audio> (فقط هنگام اولین پخش ساخته می‌شود تا صفحه سبک بماند)
 *
 * نکته: بعضی سرورها (مثل php artisan serve) «Range request» را پشتیبانی نمی‌کنند و در آن‌ها پرش
 * روی نوار، موزیک را از اول پخش می‌کند. اگر چنین چیزی دیده شود، فایل یک‌بار کامل دانلود می‌شود
 * (blob) تا پرش همیشه دقیق باشد.
 */
export function audioEngine(src, fallbackDuration, hooks) {
    const a = new Audio();
    let blobbed = false;
    let vol = 1;
    let gain; // undefined = هنوز ساخته نشده | null = لازم نیست | GainNode (فقط iOS)
    let want = null; // مقصد آخرین پرش

    a.preload = 'metadata';
    a.src = src;

    /** فایل را کامل می‌گیرد و از آن پخش می‌کند (پرش در آن همیشه کار می‌کند) */
    async function toBlob() {
        if (blobbed) return;
        blobbed = true;
        const target = want !== null ? want : a.currentTime;
        const wasPlaying = !a.paused;
        try {
            const res = await fetch(src, { credentials: 'same-origin' });
            if (!res.ok) throw new Error(res.status);
            const url = URL.createObjectURL(await res.blob());
            a.src = url;
            a.addEventListener('loadedmetadata', function once() {
                a.removeEventListener('loadedmetadata', once);
                a.currentTime = target;
            });
            if (wasPlaying) {
                a.addEventListener('canplay', function again() {
                    a.removeEventListener('canplay', again);
                    a.play().catch(() => {});
                });
            }
        } catch (err) {
            // دانلود کامل نشد (مثلاً CORS)؛ پخش عادی بدون خطا ادامه دارد، فقط پرش دقیق نیست
            console.warn('[player] blob fallback failed:', err);
        }
    }

    a.addEventListener('timeupdate', hooks.tick);
    a.addEventListener('play', hooks.state);
    a.addEventListener('pause', hooks.state);
    a.addEventListener('ended', () => { hooks.state(); hooks.ended(); });
    a.addEventListener('error', () => {
        // فقط خطای واقعیِ فایل (۴۰۴، فرمت نامعتبر، ...) خطا نشان می‌دهد
        const e = a.error;
        console.warn('[player] audio error', e && e.code, e && e.message, a.currentSrc || src);
        hooks.error();
    });
    a.addEventListener('loadedmetadata', () => {
        hooks.tick();
        hooks.meta();
    });
    a.addEventListener('seeked', () => {
        // پرش انجام نشد (برگشت به اول) → راه امن: blob
        if (!blobbed && want !== null && want > 2 && a.currentTime < want - 2) toBlob();
        want = null;
    });

    return {
        get time() { return a.currentTime || 0; },
        get duration() { return Number.isFinite(a.duration) && a.duration > 0 ? a.duration : fallbackDuration; },
        get playing() { return !a.paused && !a.ended; },
        set rate(v) { a.playbackRate = v; },
        set volume(v) {
            vol = Math.min(Math.max(v, 0), 1);
            if (gain) gain.gain.value = vol;
            else a.volume = vol;
        },
        play() {
            if (gain === undefined) {
                gain = webAudioGain(a, src);
                if (gain) gain.gain.value = vol;
            }
            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
            const p = a.play();
            if (p && p.catch) p.catch((err) => {
                if (err && err.name === 'AbortError') return; // پخش با عوض‌شدن منبع (blob) قطع شد؛ خطا نیست
                console.warn('[player] play() rejected:', err && err.name, err && err.message);
                hooks.error();
            });
        },
        pause() { a.pause(); },
        seek(v) {
            want = Math.min(Math.max(v, 0), this.duration);
            a.currentTime = want;
        },
    };
}

/**
 * موتور دو‌ترکه (کارائوکه): صدای خواننده و موزیک (بدون خواننده) دو فایل جدا هستند و هم‌زمان پخش می‌شوند.
 * زمان‌بندی از ترک موزیک (backing) خوانده می‌شود و اگر ترک خواننده بیشتر از ۰٫۲۵ ثانیه عقب/جلو افتاد، دوباره هم‌زمان می‌شود.
 */
export function stemEngine(srcVocal, srcBacking, fallbackDuration, hooks) {
    const back = audioEngine(srcBacking, fallbackDuration, hooks);
    const voc = audioEngine(srcVocal, fallbackDuration, { tick() {}, state() {}, meta() {}, ended() {}, error: hooks.error });
    let timer = null;
    const sync = () => {
        if (back.playing && Math.abs(voc.time - back.time) > 0.25) voc.seek(back.time);
    };
    const stop = () => {
        clearInterval(timer);
        timer = null;
    };
    return {
        get time() { return back.time; },
        get duration() { return back.duration; },
        get playing() { return back.playing; },
        set rate(v) { back.rate = v; voc.rate = v; },
        setGain(kind, v) { (kind === 'vocal' ? voc : back).volume = v; },
        play() {
            voc.seek(back.time);
            back.play();
            voc.play();
            if (!timer) timer = setInterval(sync, 500);
        },
        pause() {
            stop();
            back.pause();
            voc.pause();
        },
        seek(v) {
            back.seek(v);
            voc.seek(v);
        },
    };
}

function init(el) {
    const $p = $(el);
    const song = Number($p.attr('data-song'));
    const src = $p.attr('data-src') || '';
    const srcVocal = $p.attr('data-src-vocal') || '';
    const srcBacking = $p.attr('data-src-backing') || '';
    const stems = !!(srcVocal && srcBacking); // کارائوکه: دو ترکِ جدا
    const baseDuration = Number($p.attr('data-duration')) || 180;
    const $seek = $p.find('[data-p-seek]');

    let rateIdx = SPEEDS.indexOf(1);
    let scrubbing = false;
    let ended = false;
    const heard = new Set(); // ثانیه‌هایی که واقعاً پخش شده‌اند
    let lastT = 0;
    let lastPct = -1;

    const hooks = { tick, state, ended: onEnded, error: onError, meta: drawMarks };
    const engine = src || stems ? null : demoEngine(baseDuration, hooks);
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
        $(document).trigger('player:time', [{ el, song, time: t, duration: d }]);
    }

    /** نشانه‌های بخش‌های خوانده‌شده؛ با زمان واقعی فایل هم‌اندازه می‌شوند */
    function drawMarks() {
        const $m = $p.find('[data-p-marks]');
        if (!$m.length) return;
        let segs = [];
        try { segs = JSON.parse($p.attr('data-vocals') || '[]'); } catch (e) { /* خالی */ }
        const d = dur();
        const html = segs
            .filter((x) => Array.isArray(x) && x[0] >= 0 && x[0] < d)
            .map(([a, b]) => '<b style="left:' + (a / d) * 100 + '%;width:' + ((Math.min(b, d) - a) / d) * 100 + '%"></b>'
                + '<i style="left:' + (a / d) * 100 + '%"></i>')
            .join('');
        $m.html(html);
        $p.find('[data-p-legend]').prop('hidden', !html);
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
            eng = stems ? stemEngine(srcVocal, srcBacking, baseDuration, hooks) : audioEngine(src, baseDuration, hooks);
            eng.rate = SPEEDS[rateIdx];
            applyMix();
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

    // ---------- میکسر (مرحله‌ی کارائوکه): صدای خواننده / موزیک ----------
    // ترک‌های جدا (data-src-vocal و data-src-backing) → دو اسلایدر مستقل
    // فقط یک فایل کامل → فقط «صدای موزیک» (= بلندی کل) کار می‌کند و خواننده قابل‌حذف نیست
    // بدون فایل (دمو) → اسلایدرها کار می‌کنند ولی صدایی نیست
    const $mix = $p.find('[data-mix]');
    const MIX_KEY = 'teodel:mixer';
    const mix = { vocal: 1, backing: 1 };
    const muted = { vocal: false, backing: false };
    const canVocal = stems || !src;
    try {
        const m = JSON.parse(localStorage.getItem(MIX_KEY) || '{}');
        ['vocal', 'backing'].forEach((k) => {
            if (typeof m[k] === 'number') mix[k] = Math.min(Math.max(m[k], 0), 1);
            if (m['mute_' + k] === true) muted[k] = true;
        });
    } catch (e) { /* ignore */ }
    if (!canVocal) muted.vocal = false;

    function applyMix() {
        if (!eng || !$mix.length) return;
        const v = (k) => (muted[k] ? 0 : mix[k]);
        if (stems && eng.setGain) {
            eng.setGain('vocal', v('vocal'));
            eng.setGain('backing', v('backing'));
        } else if (src && 'volume' in eng) {
            eng.volume = v('backing');
        }
    }

    function renderMix() {
        $mix.each(function () {
            const $r = $(this);
            const k = $r.attr('data-mix');
            const off = muted[k] || mix[k] === 0;
            const pct = Math.round((muted[k] ? 0 : mix[k]) * 100);
            const dead = k === 'vocal' && !canVocal;
            $r.attr('data-muted', off ? 'true' : 'false').attr('data-disabled', dead ? 'true' : 'false');
            $r.find('[data-mix-range]').val(muted[k] ? 0 : Math.round(mix[k] * 100)).css('--v', pct + '%').prop('disabled', dead)
                .attr('aria-valuetext', digits(pct) + '%');
            $r.find('[data-mix-mute]').attr('aria-pressed', off ? 'true' : 'false').prop('disabled', dead);
        });
        $p.find('[data-mix-karaoke]').attr('aria-pressed', muted.vocal ? 'true' : 'false').prop('disabled', !canVocal);
        $p.find('[data-mix-note]').prop('hidden', canVocal);
        $p.attr('data-vocal-off', muted.vocal || mix.vocal === 0 ? 'true' : 'false');
    }

    function saveMix() {
        applyMix();
        renderMix();
        try {
            localStorage.setItem(MIX_KEY, JSON.stringify({ vocal: mix.vocal, backing: mix.backing, mute_vocal: muted.vocal, mute_backing: muted.backing }));
        } catch (e) { /* ignore */ }
    }

    $p.on('input', '[data-mix-range]', function () {
        const k = $(this).closest('[data-mix]').attr('data-mix');
        mix[k] = Number(this.value) / 100;
        muted[k] = false;
        saveMix();
    });
    $p.on('click', '[data-mix-mute]', function () {
        const k = $(this).closest('[data-mix]').attr('data-mix');
        const off = muted[k] || mix[k] === 0;
        if (off) {
            muted[k] = false;
            if (mix[k] === 0) mix[k] = 1;
        } else muted[k] = true;
        saveMix();
    });
    $p.on('click', '[data-mix-karaoke]', () => {
        muted.vocal = !muted.vocal;
        if (!muted.vocal && mix.vocal === 0) mix.vocal = 1;
        saveMix();
    });
    renderMix();

    $p.on('click', '[data-p-toggle]', toggle);
    $p.on('click', '[data-p-back]', () => { const e = ensure(); e.seek(e.time - 10); lastT = e.time; render(); });
    $p.on('click', '[data-p-slower]', () => setSpeed(rateIdx - 1));
    $p.on('click', '[data-p-faster]', () => setSpeed(rateIdx + 1));

    // کشیدن نوار: فقط پیش‌نمایش (بدون درخواست به سرور)؛ پرش واقعی بعد از رها کردن / کلیک انجام می‌شود
    $seek.on('input', function () {
        scrubbing = true;
        const d = dur();
        const t = (Number(this.value) / 1000) * d;
        $p.find('[data-p-fill]').css('width', t / d * 100 + '%');
        $p.find('[data-p-thumb]').css('left', t / d * 100 + '%');
        $p.find('[data-p-cur]').text(fmt(t));
    });
    $seek.on('change', function () {
        const e = ensure();
        e.seek((Number(this.value) / 1000) * e.duration);
        lastT = e.time;
        ended = false;
        scrubbing = false;
        render();
    });
    $seek.on('blur', () => { scrubbing = false; render(); });

    // API برای مرحله‌های دیگر (مثلاً لیریک): زمان فعلی، پرش، پخش
    $p.data('player', {
        pause: () => eng && eng.playing && eng.pause(),
        ratio,
        time: () => (eng ? eng.time : 0),
        playing: () => !!(eng && eng.playing),
        seek(t) {
            const e = ensure();
            e.seek(t);
            lastT = e.time;
            ended = false;
            render();
        },
        play() {
            const e = ensure();
            $p.find('[data-p-error]').prop('hidden', true);
            if (!e.playing) e.play();
            window.Teodel.player.pauseAll(el);
        },
    });

    setSpeed(rateIdx);
    state();
    render();
    drawMarks();
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
    if (window.Teodel.clips) window.Teodel.clips.pauseAll();
});
