import $ from 'jquery';

/**
 * صحنه‌ی لیریک کارائوکه (مرحله‌ی ۶)
 *
 * - خط جاری بزرگ نشان داده می‌شود و کلمه‌ها به نسبت طولشان بین شروع و پایان خط یکی‌یکی روشن می‌شوند؛ خط بعدی کم‌رنگ زیرش است.
 * - بین خط‌ها: اگر کمتر از ۵ ثانیه به خط بعدی مانده، شمارش معکوس (۳ نقطه) و «آماده شو»؛ وگرنه «بخش بی‌کلام».
 * - پخش و میکسر (صدای خواننده/موزیک) در player.js است؛ این فایل فقط لیریک و پیشرفت مرحله را مدیریت می‌کند.
 *
 * وابسته به پخش‌کننده‌ی همان مرحله: $(el).data('player') → time() / playing()
 */
const GAP = 0.8; // چند ثانیه بعد از پایان خواندن، خط «تمام‌شده» حساب می‌شود
const READY = 5; // چند ثانیه مانده به خط بعدی، شمارش معکوس شروع می‌شود
const DONE_AT = 0.9; // بعد از شنیدن این مقدار از موزیک، مرحله تمام می‌شود

function init(root) {
    const $root = $(root);
    const $panel = $root.closest('[data-step-panel]');
    const playerEl = $panel.find('[data-player]')[0];
    const $stage = $root.find('[data-k-stage]');
    if (!playerEl || !$stage.length) return;

    let raw = [];
    try { raw = JSON.parse($root.attr('data-lines') || '[]'); } catch (e) { /* خالی */ }
    if (!raw.length) return;

    const api = () => $(playerEl).data('player');
    const cur = $stage.find('[data-k-cur]')[0];
    const next = $stage.find('[data-k-next]')[0];
    const $count = $stage.find('[data-k-count]');
    const $msg = $stage.find('[data-k-msg]');
    const msgs = {
        idle: $root.attr('data-msg-idle'),
        instr: $root.attr('data-msg-instr'),
        ready: $root.attr('data-msg-ready'),
        end: $root.attr('data-msg-end'),
    };

    // خط‌ها با زمان هر کلمه
    const lines = raw.map((l) => {
        const parts = String(l.text).split(/\s+/).filter(Boolean);
        const total = parts.reduce((n, w) => n + w.length, 0) || 1;
        let acc = 0;
        const words = parts.map((w) => {
            const s = l.t + ((l.end - l.t) * acc) / total;
            acc += w.length;
            return { w, s };
        });
        return { t: l.t, end: l.end, words };
    });

    let key = '';
    let curWords = [];
    let sung = -1;
    let dots = -1;
    let raf = 0;
    let done = false;
    let started = false; // تا کاربر یک‌بار پخش نکرده، پیام «پخش را بزن» می‌ماند

    const lineAt = (time) => {
        let ans = -1;
        for (let i = 0; i < lines.length && lines[i].t <= time; i++) ans = i;
        return ans;
    };

    function fill(el, line, cls) {
        el.textContent = '';
        el.className = 'kline ' + cls;
        if (!line) return [];
        return line.words.map((x, i) => {
            const s = document.createElement('span');
            s.className = 'kw';
            s.textContent = x.w;
            el.appendChild(s);
            if (i < line.words.length - 1) el.appendChild(document.createTextNode(' '));
            return s;
        });
    }

    function phaseOf(time, playing) {
        const i = lineAt(time);
        const l = lines[i];
        if (l && time <= l.end + GAP) return { ph: 'line', show: i };
        const up = i + 1;
        if (up >= lines.length) return { ph: 'end', show: -1 };
        if (!started && !playing && time < 0.5) return { ph: 'idle', show: up };
        return { ph: lines[up].t - time <= READY ? 'ready' : 'instr', show: up };
    }

    function update(time, playing) {
        if (playing) started = true;
        const { ph, show } = phaseOf(time, playing);
        const k = ph + ':' + show;
        if (k !== key) {
            key = k;
            $stage.attr('data-phase', ph);
            curWords = fill(cur, lines[show], ph === 'line' ? 'kline--cur is-swap' : 'kline--cur is-upcoming');
            fill(next, lines[show + 1], 'kline--next');
            sung = -1;
            dots = -1;
            $msg.text(ph === 'line' ? '' : msgs[ph] || '');
            $count.prop('hidden', ph !== 'ready');
        }
        if (ph === 'line') {
            const l = lines[show];
            let n = 0;
            while (n < l.words.length && time >= l.words[n].s) n++;
            if (n !== sung) {
                curWords.forEach((el, i) => el.classList.toggle('is-sung', i < n));
                sung = n;
            }
        } else if (ph === 'ready') {
            const left = Math.min(Math.ceil(lines[show].t - time), 3);
            if (left !== dots) {
                dots = left;
                $count.find('i').each((i, el) => { el.classList.toggle('is-on', i < left); });
                $count.attr('data-left', left);
            }
        }
    }

    function loop() {
        const a = api();
        if (a) update(a.time(), a.playing());
        raf = a && a.playing() && !$panel.prop('hidden') ? requestAnimationFrame(loop) : 0;
    }
    const startLoop = () => { if (!raf) raf = requestAnimationFrame(loop); };

    new MutationObserver(() => {
        if (playerEl.dataset.state === 'playing') startLoop();
    }).observe(playerEl, { attributes: true, attributeFilter: ['data-state'] });

    // پرش یا توقف: یک‌بار به‌روز کن
    $(document).on('player:time', (e, d) => {
        if (d.el === playerEl && !raf) update(d.time, false);
    });

    // پیشرفت مرحله: شنیدن حدود ۹۰٪ موزیک
    $(document).on('player:listened', (e, d) => {
        if (d.el !== playerEl || done) return;
        const f = Math.min(d.ratio / DONE_AT, 1);
        if (window.Teodel && window.Teodel.lesson) {
            window.Teodel.lesson.setStepProgress(f * 0.95);
            if (f >= 1) {
                done = true;
                window.Teodel.lesson.completeStep();
            }
        }
    });

    $(document).on('lesson:step-change', () => {
        if (!$panel.prop('hidden') && api()) update(api().time(), api().playing());
    });

    update(0, false);
}

$(function () {
    $('[data-karaoke]').each(function () { init(this); });
});
