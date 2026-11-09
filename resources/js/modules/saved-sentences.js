import $ from 'jquery';
import { digits } from './digits';
import { sentences as store } from './book-reader';

/**
 * صفحه‌ی /words — تب «جمله‌های محبوب» (جمله‌هایی که کاربر هنگام خواندن کتاب ذخیره کرده).
 *
 * - دو تب بالای صفحه: «لغت‌ها» | «جمله‌های محبوب»؛ ?tab=sentences مستقیم تب دوم را باز می‌کند (و آدرس با تعویض تب عوض می‌شود).
 * - جست‌وجو (متن جمله یا نام کتاب)، کپی، خواندن با صدای مرورگر، رفتن به کتاب، حذف با «بازگردانی» (۶ ثانیه).
 *
 * 🔌 بک‌اند: لیست اولیه از data-sentences (SavedWords::sentences()). حذف/بازگردانی: رویدادهای sentence:remove / sentence:save
 *    (همان رویدادهای خواننده‌ی کتاب، از طریق sentences در book-reader.js). LOCAL در book-reader.js تعیین می‌کند ذخیره در مرورگر باشد یا نه.
 */
const UNDO_MS = 6000;
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const $root = $('[data-ss]');
if ($root.length) {
    const server = JSON.parse($root.attr('data-sentences') || '[]');
    const bookUrl = $root.attr('data-book-url') || '/books';
    const T = (k) => $root.attr('data-t-' + k) || '';
    const $tabs = $('[data-ss-tab]');
    let q = '';
    let undo = null;
    let undoT = 0;

    function all() {
        const local = store.all();
        const have = new Set(local.map((s) => s.id));
        return local.concat(server.filter((s) => !have.has(s.id))).sort((a, b) => (b.at || 0) - (a.at || 0));
    }

    function render() {
        const list = all();
        $('[data-ss-total]').text(digits(list.length));
        const shown = q ? list.filter((s) => (s.text + ' ' + (s.book_title || '')).toLowerCase().includes(q)) : list;
        $root.find('[data-ss-empty]').prop('hidden', shown.length > 0);
        $root.find('[data-ss-list]').html(shown.map((s) => `
            <article class="ss-card" data-id="${esc(s.id)}">
                <span class="ss-card__q" aria-hidden="true">“</span>
                <p class="ss-card__t" dir="ltr" lang="en">${esc(s.text)}</p>
                <p class="ss-card__src">
                    <a href="${esc(bookUrl + '/' + s.book + '/step/2')}" class="ss-card__book" title="${esc(T('open'))}"><bdi>${esc(s.book_title || '')}</bdi></a>
                    <span>·</span><span>${esc(digits(T('chapter').replace(':n', s.chapter)))}</span>
                </p>
                <div class="ss-card__acts">
                    <button type="button" class="ss-btn" data-ss-speak aria-label="${esc(T('speak'))}" title="${esc(T('speak'))}"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/></svg></button>
                    <button type="button" class="ss-btn" data-ss-copy aria-label="${esc(T('copy'))}" title="${esc(T('copy'))}"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                    <span class="flex-1"></span>
                    <button type="button" class="ss-btn ss-btn--bad" data-ss-del>${esc(T('remove'))}</button>
                </div>
            </article>`).join(''));
    }

    function select(tab, push) {
        $tabs.each(function () {
            const on = $(this).attr('data-ss-tab') === tab;
            $(this).attr({ 'aria-selected': String(on), tabindex: on ? 0 : -1 });
        });
        $('#sw-panel-words').prop('hidden', tab !== 'words');
        $('#sw-panel-sentences').prop('hidden', tab !== 'sentences');
        if (push) {
            const u = new URL(location.href);
            if (tab === 'sentences') u.searchParams.set('tab', 'sentences');
            else u.searchParams.delete('tab');
            history.replaceState(history.state, '', u);
        }
    }

    $tabs.on('click', function () { select($(this).attr('data-ss-tab'), true); });
    $tabs.on('keydown', function (e) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
        e.preventDefault();
        const next = $(this).attr('data-ss-tab') === 'words' ? 'sentences' : 'words';
        select(next, true);
        $tabs.filter('[data-ss-tab="' + next + '"]').trigger('focus');
    });

    $root.on('input', '[data-ss-q]', function () {
        q = this.value.trim().toLowerCase();
        render();
    });

    $root.on('click', '[data-ss-del]', function () {
        const id = $(this).closest('[data-id]').attr('data-id');
        const s = all().find((x) => x.id === id);
        if (!s) return;
        store.remove(id);
        const i = server.findIndex((x) => x.id === id);
        if (i >= 0) server.splice(i, 1);
        undo = s;
        $root.find('[data-ss-undo-text]').text(T('removed'));
        $root.find('[data-ss-undo]').prop('hidden', false);
        clearTimeout(undoT);
        undoT = setTimeout(() => { $root.find('[data-ss-undo]').prop('hidden', true); undo = null; }, UNDO_MS);
        render();
    });
    $root.on('click', '[data-ss-undo-btn]', () => {
        if (!undo) return;
        store.add(undo);
        undo = null;
        $root.find('[data-ss-undo]').prop('hidden', true);
        render();
    });
    $root.on('click', '[data-ss-copy]', function () {
        const text = $(this).closest('[data-id]').find('.ss-card__t').text();
        const $b = $(this);
        const ok = () => { $b.attr('title', T('copied')).addClass('is-ok'); setTimeout(() => $b.removeClass('is-ok').attr('title', T('copy')), 1400); };
        if (navigator.clipboard) navigator.clipboard.writeText(text).then(ok, () => {});
    });
    $root.on('click', '[data-ss-speak]', function () {
        if (!window.speechSynthesis) return;
        const u = new SpeechSynthesisUtterance($(this).closest('[data-id]').find('.ss-card__t').text());
        u.lang = 'en-GB';
        u.rate = 0.9;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(u);
    });

    render();
    select(new URLSearchParams(location.search).get('tab') === 'sentences' ? 'sentences' : 'words', false);
}
