import $ from 'jquery';

/**
 * Main idea پادکست (اختیاری، یک یا دو جمله):
 *   مرحله‌ی ۱  → [data-idea] کادر نوشتن؛ ذخیره‌ی خودکار (بعد از ۶۰۰ms توقف تایپ)
 *   مرحله‌ی ۷  → [data-idea-mine] نوشته‌ی کاربر کنار Main idea رسمی
 *
 * 🔌 hook بک‌اند:
 *   $(document).on('podcast:main-idea', (e, { song, text }) => {...})
 *   data-save-url روی [data-idea]: POST JSON { song, text } (اگر بگذارید). مقدار اولیه: data-text (user_main_idea)
 * LOCAL = true → تا وصل‌شدن بک‌اند در localStorage نگه داشته می‌شود (فقط دمو).
 */
const LOCAL = true;
const key = (song) => 'teodel:podcast:idea:' + song;

const read = (song, fallback) => {
    if (fallback) return fallback;
    if (!LOCAL) return '';
    try {
        return localStorage.getItem(key(song)) || '';
    } catch (e) {
        return '';
    }
};

const digits = (n) => (document.documentElement.lang === 'fa' ? String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]) : String(n));

$('[data-idea]').each(function () {
    const $root = $(this);
    const song = $root.attr('data-song');
    const url = $root.attr('data-save-url');
    const $in = $root.find('[data-idea-input]');
    const $count = $root.find('[data-idea-count]');
    const $saved = $root.find('[data-idea-saved]');
    let timer = 0;
    let hideTimer = 0;

    $in.val(read(song, $root.attr('data-text')));
    $count.text(digits($in.val().length));

    const save = () => {
        const text = String($in.val()).trim();
        if (LOCAL) {
            try {
                localStorage.setItem(key(song), text);
            } catch (e) {
                /* localStorage در دسترس نیست */
            }
        }
        $(document).trigger('podcast:main-idea', [{ song, text }]);
        if (url) {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: JSON.stringify({ song, text }),
            }).catch(() => {});
        }
        $saved.css('opacity', 1);
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => $saved.css('opacity', 0), 1600);
    };

    $in.on('input', () => {
        $count.text(digits($in.val().length));
        clearTimeout(timer);
        timer = setTimeout(save, 600);
    });
    $in.on('blur', () => {
        clearTimeout(timer);
        if (String($in.val()).trim() !== read(song, '').trim()) save();
    });
});

$('[data-idea-mine]').each(function () {
    const $root = $(this);
    const text = read($root.attr('data-song'), $root.attr('data-text'));
    $root.find('[data-idea-mine-text]').text(text).prop('hidden', !text);
    $root.find('[data-idea-mine-empty]').prop('hidden', !!text);
});
