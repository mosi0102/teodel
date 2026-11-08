import $ from 'jquery';
import { digits } from './digits';

/**
 * Shadowing (موضوع‌ها) — مرحله‌های ۱، ۲، ۳ (گوش‌دادن ۳ بار + ضبط صدا + بررسی AI) و ۵ (پاسخ صوتی به سوال‌ها) و مرجع لغت‌ها در مرحله‌ی ۴.
 *
 * ── جریان یک جمله ($ [data-shc]) ──────────────────────────────────────────
 *   گوش‌دادن (تا ۳ بار) → دکمه‌ی ضبط باز می‌شود → ضبط → بررسی → نتیجه (شباهت + تلفظ + کلمه‌های شنیده‌شده) → «دوباره»
 *   مرحله‌ی ۱ (data-mode=listen): فقط ۳ بار گوش دادن | ۲ (record): فقط ضبط | ۳ (full): هر دو، برای بقیه‌ی جمله‌ها.
 *
 * ── دمو / بک‌اند ──────────────────────────────────────────────────────────
 *   صدای جمله: data-audio (فایل TTS یا آپلود ادمین). اگر خالی بود، صدای مرورگر (speechSynthesis) پخش می‌شود.
 *   بررسی: اگر data-check-url روی ریشه‌ی مرحله باشد، صدا با POST (FormData: audio, text, song, n) فرستاده می‌شود و
 *          JSON { similarity: 0-100, pronunciation: 0-100, words: [true,false,…] (برای هر کلمه‌ی جمله), heard: "..." } برمی‌گردد.
 *          وگرنه (دمو): تشخیص گفتار مرورگر (SpeechRecognition) رونوشت را می‌دهد و شباهت با الگوریتم LCS روی کلمه‌ها حساب می‌شود.
 *          اگر مرورگر میکروفن/تشخیص گفتار نداشت، دکمه‌ی «شبیه‌سازی» (فقط دمو) نمایش داده می‌شود.
 *   سوال‌ها: data-answer-url روی [data-sq-step] → POST (audio, song, i) → JSON { score: 0-100, matched: [..], heard: "..." }.
 *          دمو: کلیدواژه‌های data-keys داخل رونوشت جست‌وجو می‌شوند.
 *
 * ── رویدادها ──────────────────────────────────────────────────────────────
 *   $(document).on('shadowing:listen',  (e, { song, n, count }) => {...})
 *   $(document).on('shadowing:attempt', (e, { song, n, similarity, pronunciation, heard, blob }) => {...})
 *   $(document).on('shadowing:answer',  (e, { song, i, score, heard, blob }) => {...})
 *
 * پیشرفت: هر جمله/سوال سهمی از مرحله دارد؛ همه که تمام شد completeStep().
 */
const LOCAL_SAVE = true; // دمو: ذخیره در مرورگر تا بک‌اند وصل شود
const LISTEN_TARGET = 3;
const PASS = 60; // حداقل شباهت برای «خوب»
const MAX_SECONDS = 30;

/* ---------- ابزارها ---------- */
const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const canRecord = () => !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);

const normalize = (s) => String(s || '').toLowerCase().replace(/[’']/g, "'").replace(/[^a-z0-9' ]+/g, ' ').split(/\s+/).filter(Boolean);

/** کلمه‌های جمله‌ی اصلی که در گفته‌ی کاربر آمده‌اند (LCS: ترتیب هم مهم است) */
function align(target, heard) {
    const a = target;
    const b = heard;
    const dp = Array.from({ length: a.length + 1 }, () => new Array(b.length + 1).fill(0));
    for (let i = 1; i <= a.length; i++) {
        for (let j = 1; j <= b.length; j++) {
            dp[i][j] = a[i - 1] === b[j - 1] ? dp[i - 1][j - 1] + 1 : Math.max(dp[i - 1][j], dp[i][j - 1]);
        }
    }
    const ok = new Array(a.length).fill(false);
    let i = a.length;
    let j = b.length;
    while (i > 0 && j > 0) {
        if (a[i - 1] === b[j - 1]) {
            ok[i - 1] = true;
            i--;
            j--;
        } else if (dp[i - 1][j] >= dp[i][j - 1]) i--;
        else j--;
    }
    return ok;
}

const hash = (s) => [...String(s)].reduce((h, c) => (h * 31 + c.charCodeAt(0)) >>> 0, 7);

let currentSpeech = null;
function stopSpeech() {
    if (currentSpeech) {
        currentSpeech.cancel();
        currentSpeech = null;
    }
}

/** پخش یک جمله: فایل صوتی یا TTS مرورگر. onend فقط وقتی تا آخر پخش شد صدا زده می‌شود. */
function speak(text, { audio, rate = 1, onend, onstop }) {
    stopSpeech();
    let done = false;
    let el = null;
    const finish = (ended) => {
        if (done) return;
        done = true;
        if (currentSpeech === handle) currentSpeech = null;
        const cb = ended ? onend : onstop;
        if (cb) cb();
    };
    const handle = {
        cancel() {
            if (el) el.pause();
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            finish(false);
        },
    };
    currentSpeech = handle;
    if (audio) {
        el = new Audio(audio);
        el.playbackRate = rate;
        el.addEventListener('ended', () => finish(true));
        el.addEventListener('error', () => finish(false));
        el.play().catch(() => finish(false));
    } else if ('speechSynthesis' in window) {
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'en-US';
        u.rate = rate;
        u.onend = () => finish(true);
        u.onerror = () => finish(false);
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
    } else {
        setTimeout(() => finish(true), 1200); // بدون صدا (مرورگر قدیمی): دمو
    }
    return handle;
}

/** ضبط صدا + (اختیاری) تشخیص گفتار. stop() → Promise<{ blob, heard, confidence }> */
async function capture(onTick) {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const mr = new MediaRecorder(stream);
    const chunks = [];
    mr.ondataavailable = (e) => e.data && e.data.size && chunks.push(e.data);
    let heard = '';
    let confs = [];
    let rec = null;
    let recEnded = !Recognition;
    if (Recognition) {
        rec = new Recognition();
        rec.lang = 'en-US';
        rec.continuous = true;
        rec.interimResults = false;
        rec.onresult = (e) => {
            for (let i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) {
                    heard += ' ' + e.results[i][0].transcript;
                    confs.push(e.results[i][0].confidence || 0);
                }
            }
        };
        rec.onend = () => { recEnded = true; };
        rec.onerror = () => { recEnded = true; };
        try { rec.start(); } catch (e) { recEnded = true; }
    }
    mr.start();
    const t0 = Date.now();
    const tick = setInterval(() => {
        const s = Math.floor((Date.now() - t0) / 1000);
        onTick && onTick(s);
        if (s >= MAX_SECONDS) api.autoStop && api.autoStop();
    }, 250);
    const api = {
        autoStop: null,
        stop() {
            clearInterval(tick);
            return new Promise((resolve) => {
                mr.onstop = () => {
                    stream.getTracks().forEach((t) => t.stop());
                    const blob = new Blob(chunks, { type: mr.mimeType || 'audio/webm' });
                    const waitStart = Date.now();
                    (function wait() {
                        if (recEnded || Date.now() - waitStart > 1500) {
                            const confidence = confs.length ? confs.reduce((a, b) => a + b, 0) / confs.length : null;
                            resolve({ blob, heard: heard.trim(), confidence, hasSpeech: !!Recognition });
                        } else setTimeout(wait, 80);
                    })();
                };
                try { if (rec) rec.stop(); } catch (e) { /* ignore */ }
                if (mr.state !== 'inactive') mr.stop();
            });
        },
    };
    return api;
}

const fmtTime = (s) => digits(Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'));

const store = (song) => {
    const key = 'teodel:sh:' + song;
    let data = {};
    try { data = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (e) { data = {}; }
    data.l = data.l || {};
    data.a = data.a || {};
    data.q = data.q || {};
    return {
        data,
        save() {
            if (!LOCAL_SAVE) return;
            try { localStorage.setItem(key, JSON.stringify(data)); } catch (e) { /* ignore */ }
        },
    };
};

const postAudio = (url, blob, fields) => {
    const fd = new FormData();
    fd.append('audio', blob, 'voice.webm');
    Object.keys(fields).forEach((k) => fd.append(k, fields[k]));
    return fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((r) => r.json());
};

const tpl = (str, vars) => Object.keys(vars).reduce((s, k) => s.replace(':' + k, vars[k]), str || '');

/* ---------- مرحله‌های جمله‌ای: listen / record / practice ---------- */
$('[data-shc-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const song = Number($root.attr('data-song'));
    const mode = $root.attr('data-mode');
    const checkUrl = $root.attr('data-check-url');
    const st = store(song);
    const $cards = $root.find('[data-shc]');
    const total = Number($root.attr('data-total')) || $cards.length;
    let finished = false;
    let busy = null; // کارتی که الان ضبط می‌کند

    const isDone = ($c) => {
        const n = $c.attr('data-n');
        const m = $c.attr('data-mode');
        if (m === 'listen') return (st.data.l[n] || 0) >= LISTEN_TARGET;
        return !!st.data.a[n];
    };

    function paint($c) {
        const n = $c.attr('data-n');
        const m = $c.attr('data-mode');
        const listened = Math.min(st.data.l[n] || 0, LISTEN_TARGET);
        $c.find('[data-shc-dot]').each((i, el) => el.classList.toggle('is-on', i < listened));
        const $count = $c.find('[data-shc-count]');
        if ($count.length) {
            $count.text(tpl($count.attr(listened >= LISTEN_TARGET ? 'data-tpl-done' : 'data-tpl'), { n: digits(listened), total: digits(LISTEN_TARGET) }));
        }
        if (m === 'full') {
            const open = listened >= LISTEN_TARGET || !!st.data.a[n];
            const $rec = $c.find('[data-shc-rec]');
            $rec.prop('disabled', !open);
            if (!$rec.hasClass('is-live')) $c.find('[data-shc-rec-label]').text($rec.attr(open ? 'data-label-start' : 'data-label-locked'));
        }
        $c.attr('data-state', isDone($c) ? 'done' : (listened > 0 || st.data.a[n]) ? 'doing' : 'idle');
    }

    function refresh() {
        const done = $cards.filter('[data-state="done"]').length;
        $root.find('[data-shc-done]').text(digits(done));
        $root.find('[data-shc-summary]').prop('hidden', done < total);
        if ($panel.prop('hidden') || !window.Teodel || !window.Teodel.lesson) return;
        window.Teodel.lesson.setStepProgress((done / total) * 0.95);
        if (done >= total && !finished) {
            finished = true;
            window.Teodel.lesson.completeStep();
        }
    }

    function showResult($c, r) {
        const n = $c.attr('data-n');
        const text = $c.attr('data-text');
        const words = text.split(/\s+/);
        const $res = $c.find('[data-shc-result]');
        $c.find('[data-shc-sim]').text(digits(r.similarity) + '%');
        $c.find('[data-shc-pron]').text(digits(r.pronunciation) + '%');
        $c.find('[data-meter="sim"] u').css('width', r.similarity + '%');
        $c.find('[data-meter="pron"] u').css('width', r.pronunciation + '%');
        $c.find('[data-meter]').each(function () {
            const v = $(this).attr('data-meter') === 'sim' ? r.similarity : r.pronunciation;
            this.setAttribute('data-level', v >= 80 ? 'good' : v >= PASS ? 'ok' : 'low');
        });
        const $w = $c.find('[data-shc-words]').empty();
        words.forEach((w, i) => $('<span>').addClass(r.words[i] ? 'is-ok' : 'is-miss').text(w + ' ').appendTo($w));
        const $v = $c.find('[data-shc-verdict]');
        const kind = r.similarity >= 80 ? 'good' : r.similarity >= PASS ? 'ok' : 'retry';
        $v.attr('data-kind', kind).text($v.attr('data-' + kind));
        $res.prop('hidden', false);
        st.data.a[n] = { sim: r.similarity, pron: r.pronunciation };
        st.save();
    }

    /** نتیجه‌ی بررسی: از بک‌اند یا (دمو) از رونوشت مرورگر */
    async function evaluate($c, cap) {
        const text = $c.attr('data-text');
        const target = normalize(text);
        // کلمه‌های نمایشی (با علامت) ↔ کلمه‌های نرمال‌شده یکی‌یکی
        const display = text.split(/\s+/);
        const flags = (okNorm) => {
            let k = 0;
            return display.map((d) => (normalize(d).length ? okNorm[k++] : true));
        };
        if (checkUrl && cap.blob) {
            try {
                const j = await postAudio(checkUrl, cap.blob, { text, song, n: $c.attr('data-n') });
                return { similarity: Math.round(j.similarity), pronunciation: Math.round(j.pronunciation), words: j.words || display.map(() => j.similarity >= PASS), heard: j.heard || '' };
            } catch (e) { /* به الگوریتم دمو برمی‌گردد */ }
        }
        if (cap.heard) {
            const ok = align(target, normalize(cap.heard));
            const sim = Math.round((ok.filter(Boolean).length / Math.max(target.length, 1)) * 100);
            const pron = cap.confidence != null ? Math.round(cap.confidence * 55 + sim * 0.45) : Math.max(sim - 6, 0);
            return { similarity: sim, pronunciation: Math.min(pron, 100), words: flags(ok), heard: cap.heard };
        }
        return demoResult($c, display, flags);
    }

    function demoResult($c, display, flags) {
        const n = $c.attr('data-n');
        const h = hash($c.attr('data-text') + (st.data.a[n] ? 'x' : ''));
        const sim = 68 + (h % 28);
        const miss = h % Math.max(display.length, 1);
        const target = normalize($c.attr('data-text'));
        const ok = target.map((_, i) => i !== miss || sim > 90);
        return { similarity: sim, pronunciation: Math.max(sim - 4 - (h % 7), 40), words: flags(ok), heard: '' };
    }

    function message($c, key) {
        const $m = $c.find('[data-shc-msg]');
        if (!key) return $m.prop('hidden', true);
        $m.text(key).prop('hidden', false);
    }

    $cards.each(function () { paint($(this)); const n = $(this).attr('data-n'); if (st.data.a[n]) { /* نتیجه‌ی قبلی نمایش داده می‌شود */ const a = st.data.a[n]; $(this).find('[data-shc-sim]').text(digits(a.sim) + '%'); } });

    /* گوش‌دادن */
    $root.on('click', '[data-shc-play]', function () {
        const $c = $(this).closest('[data-shc]');
        const $btn = $(this);
        if ($btn.hasClass('is-playing')) {
            stopSpeech();
            return;
        }
        $root.find('[data-shc-play]').removeClass('is-playing');
        const rate = $c.find('[data-shc-speed]').attr('aria-pressed') === 'true' ? 0.7 : 1;
        $btn.addClass('is-playing');
        speak($c.attr('data-text'), {
            audio: $c.attr('data-audio'),
            rate,
            onend() {
                $btn.removeClass('is-playing');
                if ($c.attr('data-mode') === 'record') return; // مرحله‌ی ضبط: شنیدن آزاد است و شمرده نمی‌شود
                const n = $c.attr('data-n');
                st.data.l[n] = (st.data.l[n] || 0) + 1;
                st.save();
                paint($c);
                refresh();
                $(document).trigger('shadowing:listen', [{ song, n: Number(n), count: st.data.l[n] }]);
            },
            onstop() { $btn.removeClass('is-playing'); },
        });
    });

    $root.on('click', '[data-shc-speed]', function () {
        const on = $(this).attr('aria-pressed') !== 'true';
        $(this).attr('aria-pressed', on).find('[data-shc-speed-label]').text(on ? '0.7x' : '1x');
    });

    /* ضبط */
    $root.on('click', '[data-shc-rec]', async function () {
        const $btn = $(this);
        const $c = $btn.closest('[data-shc]');
        if ($btn.data('cap')) { // توقف
            const cap = $btn.data('cap');
            $btn.data('cap', null).removeClass('is-live').prop('disabled', true);
            const res = await cap.stop();
            busy = null;
            $btn.prop('disabled', false).find('[data-shc-timer]').prop('hidden', true);
            $c.find('[data-shc-rec-label]').text($btn.attr('data-label-start'));
            const mine = $c.find('[data-shc-mine]')[0];
            if (res.blob && mine) { mine.src = URL.createObjectURL(res.blob); mine.hidden = false; }
            const r = await evaluate($c, res);
            showResult($c, r);
            paint($c);
            refresh();
            $(document).trigger('shadowing:attempt', [{ song, n: Number($c.attr('data-n')), similarity: r.similarity, pronunciation: r.pronunciation, heard: res.heard, blob: res.blob }]);
            return;
        }
        if (busy) return;
        stopSpeech();
        $root.find('[data-shc-play]').removeClass('is-playing');
        message($c, null);
        if (!canRecord()) {
            message($c, $c.attr('data-msg-nomic'));
            $c.find('[data-shc-sim-btn]').prop('hidden', false);
            return;
        }
        try {
            const cap = await capture((s) => $btn.find('[data-shc-timer]').prop('hidden', false).text(fmtTime(s)));
            cap.autoStop = () => $btn.trigger('click');
            busy = $c;
            $btn.data('cap', cap).addClass('is-live');
            $c.find('[data-shc-rec-label]').text($btn.attr('data-label-stop'));
            $c.find('[data-shc-result]').prop('hidden', true);
        } catch (e) {
            $c.find('[data-shc-sim-btn]').prop('hidden', false);
            message($c, $c.attr(e && e.name === 'NotAllowedError' ? 'data-msg-denied' : 'data-msg-nomic'));
        }
    });

    $root.on('click', '[data-shc-sim-btn]', async function () {
        const $c = $(this).closest('[data-shc]');
        const r = await evaluate($c, { blob: null, heard: '' });
        showResult($c, r);
        paint($c);
        refresh();
    });

    $root.on('click', '[data-shc-retry]', function () {
        const $c = $(this).closest('[data-shc]');
        $c.find('[data-shc-result]').prop('hidden', true);
        $c.find('[data-shc-rec]').trigger('focus');
    });

    $(document).on('lesson:step-change', () => {
        stopSpeech();
        if (!$panel.prop('hidden')) refresh();
    });
    refresh();
});

/* ---------- مرحله‌ی ۵: پاسخ صوتی به سوال‌ها ---------- */
$('[data-sq-step]').each(function () {
    const $root = $(this);
    const $panel = $root.closest('[data-step-panel]');
    const song = Number($root.attr('data-song'));
    const answerUrl = $root.attr('data-answer-url');
    const st = store(song);
    const $cards = $root.find('[data-sq]');
    const total = $cards.length;
    let finished = false;
    let busy = false;

    function refresh() {
        const done = $cards.filter('[data-state="done"]').length;
        $root.find('[data-shc-done]').text(digits(done));
        $root.find('[data-sq-summary]').prop('hidden', done < total);
        if ($panel.prop('hidden') || !window.Teodel || !window.Teodel.lesson) return;
        window.Teodel.lesson.setStepProgress((done / total) * 0.95);
        if (done >= total && !finished) {
            finished = true;
            window.Teodel.lesson.completeStep();
        }
    }

    function show($c, r) {
        const keys = JSON.parse($c.attr('data-keys') || '[]');
        $c.find('[data-sq-heard]').text(r.heard || '—');
        const $k = $c.find('[data-sq-keys]').empty();
        keys.forEach((k) => $('<span>').addClass('sq-key ' + (r.matched.includes(k) ? 'is-ok' : 'is-miss')).text(k).appendTo($k));
        const $v = $c.find('[data-sq-verdict]');
        const kind = r.score >= 70 ? 'good' : r.score >= 40 ? 'ok' : 'retry';
        $v.attr('data-kind', kind).text($v.attr('data-' + kind));
        $c.find('[data-sq-result]').prop('hidden', false);
        $c.attr('data-state', 'done');
        st.data.q[$c.attr('data-i')] = r.score;
        st.save();
        refresh();
    }

    function evaluate($c, cap) {
        const keys = JSON.parse($c.attr('data-keys') || '[]');
        const heard = normalize(cap.heard).join(' ');
        const matched = keys.filter((k) => heard.includes(k.toLowerCase()));
        if (cap.heard) return { score: keys.length ? Math.round((matched.length / keys.length) * 100) : 100, matched, heard: cap.heard };
        // دمو بدون تشخیص گفتار: بیشتر کلیدواژه‌ها «درست» فرض می‌شوند
        const h = hash($c.attr('data-question'));
        const m = keys.filter((_, i) => i !== h % Math.max(keys.length, 1) || keys.length === 1);
        return { score: keys.length ? Math.round((m.length / keys.length) * 100) : 100, matched: m, heard: '' };
    }

    $root.on('click', '[data-sq-play]', function () {
        const $c = $(this).closest('[data-sq]');
        const $btn = $(this);
        if ($btn.hasClass('is-playing')) return stopSpeech();
        $root.find('[data-sq-play]').removeClass('is-playing');
        $btn.addClass('is-playing');
        speak($c.attr('data-question'), { onend() { $btn.removeClass('is-playing'); }, onstop() { $btn.removeClass('is-playing'); } });
    });

    $root.on('click', '[data-sq-rec]', async function () {
        const $btn = $(this);
        const $c = $btn.closest('[data-sq]');
        const $msg = $c.find('[data-sq-msg]').prop('hidden', true);
        if ($btn.data('cap')) {
            const cap = $btn.data('cap');
            $btn.data('cap', null).removeClass('is-live').prop('disabled', true);
            const res = await cap.stop();
            busy = false;
            $btn.prop('disabled', false).find('[data-sq-timer]').prop('hidden', true);
            $c.find('[data-sq-rec-label]').text($btn.attr('data-label-start'));
            let r = null;
            if (answerUrl && res.blob) {
                try {
                    const j = await postAudio(answerUrl, res.blob, { song, i: $c.attr('data-i') });
                    r = { score: Math.round(j.score), matched: j.matched || [], heard: j.heard || '' };
                } catch (e) { r = null; }
            }
            r = r || evaluate($c, res);
            show($c, r);
            $(document).trigger('shadowing:answer', [{ song, i: Number($c.attr('data-i')), score: r.score, heard: r.heard, blob: res.blob }]);
            return;
        }
        if (busy) return;
        stopSpeech();
        if (!canRecord()) {
            $c.find('[data-sq-sim-btn]').prop('hidden', false);
            $msg.text($c.attr('data-msg-nomic')).prop('hidden', false);
            return;
        }
        try {
            const cap = await capture((s) => $btn.find('[data-sq-timer]').prop('hidden', false).text(fmtTime(s)));
            cap.autoStop = () => $btn.trigger('click');
            busy = true;
            $btn.data('cap', cap).addClass('is-live');
            $c.find('[data-sq-rec-label]').text($btn.attr('data-label-stop'));
            $c.find('[data-sq-result]').prop('hidden', true);
        } catch (e) {
            $c.find('[data-sq-sim-btn]').prop('hidden', false);
            $msg.text($c.attr(e && e.name === 'NotAllowedError' ? 'data-msg-denied' : 'data-msg-nomic')).prop('hidden', false);
        }
    });

    $root.on('click', '[data-sq-sim-btn]', function () {
        const $c = $(this).closest('[data-sq]');
        show($c, evaluate($c, { heard: '' }));
    });
    $root.on('click', '[data-sq-retry]', function () {
        $(this).closest('[data-sq]').find('[data-sq-result]').prop('hidden', true);
    });

    $(document).on('lesson:step-change', () => {
        stopSpeech();
        if (!$panel.prop('hidden')) refresh();
    });
    refresh();
});

/* ---------- مرحله‌ی ۴: «این لغت در کدام جمله آمده» → رفتن به همان جمله در متن ---------- */
$(document).on('click', '[data-sh-jump]', function () {
    const n = Number($(this).attr('data-sh-jump'));
    const $panel = $(this).closest('[data-step-panel]');
    const $line = $panel.find('.wline[data-w-line="' + (n - 1) + '"]');
    if (!$line.length) return;
    const box = $panel.find('[data-w-scroll]')[0];
    if (box && box.scrollHeight > box.clientHeight) box.scrollTop = Math.max($line[0].offsetTop - box.clientHeight / 3, 0);
    else $line[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
    $line.addClass('is-flash');
    setTimeout(() => $line.removeClass('is-flash'), 1800);
});
