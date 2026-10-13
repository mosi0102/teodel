import $ from 'jquery';

/**
 * پیشرفت زنده‌ی کارت‌های لیست موزیک (۰ تا ۱۰۰)
 *
 * ظاهر کارت فقط به data-status و data-progress وابسته است؛ این ماژول فقط همان‌ها و حلقه را عوض می‌کند.
 *
 *   Teodel.cards.set(songId, 60);   // کارت آهنگ با شناسه‌ی songId الان ۶۰٪ شود (برای تست در کنسول هم عالی است)
 *
 * LOCAL_PROGRESS = true  → تا وقتی بک‌اند وصل نشده: پیشرفتی که در صفحه‌ی آموزش ثبت می‌شود
 *                          در مرورگر ذخیره و در لیست موزیک نشان داده می‌شود (فقط برای دمو/تست).
 * وقتی بک‌اند وصل شد، این را false کنید؛ مقدار همیشه از سرور (data-progress) می‌آید.
 */
const LOCAL_PROGRESS = true;
const key = (songId) => 'teodel:progress:' + songId;

const toLocaleDigits = (value) => {
    const s = String(value);
    if (document.documentElement.lang !== 'fa') return s;
    return s.replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
};

function statusOf(percent) {
    if (percent >= 100) return 'done';
    return percent > 0 ? 'in_progress' : 'new';
}

function setCard(card, percent) {
    const p = Math.min(100, Math.max(0, Math.round(Number(percent) || 0)));
    const $card = $(card);

    $card.attr({ 'data-status': statusOf(p), 'data-progress': p });
    $card.find('[data-prog-arc]').each(function () {
        this.style.setProperty('--dash', (p * 0.5655).toFixed(2));
    });
    $card.find('[data-prog-text]').text(toLocaleDigits(p) + '%');
}

window.Teodel = window.Teodel || {};
window.Teodel.cards = {
    set(songId, percent) {
        $('[data-song][data-song-id="' + songId + '"]').each((_, el) => setCard(el, percent));
    },
};

/* صفحه‌ی آموزش پیشرفت را خبر می‌دهد (رویداد lesson:progress از lesson.js) → ذخیره برای لیست */
$(document).on('lesson:progress', (e, data) => {
    if (!LOCAL_PROGRESS || !data) return;
    try {
        localStorage.setItem(key(data.song), String(data.percent));
    } catch (err) {
        /* localStorage در دسترس نیست */
    }
});

/* لیست موزیک: مقدار ذخیره‌شده را روی کارت‌ها اعمال کن (هنگام لود و بعد از هر تعویض تب/نمایش بیشتر) */
function applySaved() {
    if (!LOCAL_PROGRESS) return;
    $('[data-song]').each(function () {
        try {
            const saved = localStorage.getItem(key($(this).attr('data-song-id')));
            if (saved !== null) setCard(this, Number(saved));
        } catch (err) {
            /* localStorage در دسترس نیست */
        }
    });
}

$(applySaved);
$(document).on('music:list-updated', applySaved);
