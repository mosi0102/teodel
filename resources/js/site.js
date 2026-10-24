import $ from 'jquery';

/**
 * صفحه‌های عمومی (نخست، سوالات، قوانین): هدر چسبان، منوی موبایل، هایلایت منو هنگام اسکرول،
 * ورود آرام بخش‌ها، «مطالعهٔ بیشتر» (نظرات و متن سئو) و آکاردئون سوال‌ها (هر بار یکی باز).
 * هیچ فراخوانی به بک‌اند ندارد. اگر JS لود نشود، همه‌ی محتوا باز و دیده می‌شود (کلاس st-js را فقط همین صفحه می‌گذارد).
 */
const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const $html = $(document.documentElement);

/* ───── هدر: سایه بعد از اسکرول ───── */
function initHead() {
    const $head = $('[data-st-head]');
    if (!$head.length) return;
    const tick = () => $head.toggleClass('is-stuck', window.scrollY > 8);
    tick();
    $(window).on('scroll', tick);
}

/* ───── منوی موبایل ───── */
function initSheet() {
    const $sheet = $('[data-st-sheet]');
    if (!$sheet.length) return;
    const $open = $('[data-st-open]');
    let last = null;
    const open = () => {
        last = document.activeElement;
        $sheet.prop('hidden', false);
        $html.addClass('st-sheet-open');
        $open.attr('aria-expanded', 'true');
        $sheet.find('a, button').filter(':visible').first().trigger('focus');
    };
    const close = () => {
        $sheet.prop('hidden', true);
        $html.removeClass('st-sheet-open');
        $open.attr('aria-expanded', 'false');
        if (last && last.focus) last.focus();
    };
    $open.on('click', open);
    $sheet.on('click', '[data-st-close]', close);
    $(document).on('keydown', (e) => { if (e.key === 'Escape' && !$sheet.prop('hidden')) close(); });
    // با بزرگ شدن صفحه (چرخش گوشی) منو بسته شود
    window.matchMedia('(min-width: 1024px)').addEventListener?.('change', (m) => { if (m.matches && !$sheet.prop('hidden')) close(); });
}

/* ───── هایلایت منو (فقط صفحهٔ نخست) ───── */
function initSpy() {
    if ($('body').data('st-page') !== 'home' || !('IntersectionObserver' in window)) return;
    const map = { about: 'about', features: 'services', pricing: 'pricing', faq: 'faq' };
    const $links = $('.st-nav__a');
    const visible = new Map();
    const paint = () => {
        let best = null;
        visible.forEach((ratio, id) => { if (!best || ratio > visible.get(best)) best = id; });
        $links.removeClass('is-on');
        if (best) $links.filter(`[data-st-nav="${map[best]}"]`).addClass('is-on');
        else $links.filter('[data-st-nav="home"]').addClass('is-on');
    };
    const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
            if (en.isIntersecting) visible.set(en.target.id, en.intersectionRatio); else visible.delete(en.target.id);
        });
        paint();
    }, { rootMargin: '-25% 0px -55% 0px', threshold: [0, 0.2, 0.5, 1] });
    Object.keys(map).forEach((id) => { const el = document.getElementById(id); if (el) io.observe(el); });
    paint();
}

/* ───── ورود آرام ───── */
function initReveal() {
    const $items = $('[data-st-in]');
    if (!$items.length) return;
    if (reduce || !('IntersectionObserver' in window)) { $items.addClass('is-in'); return; }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    $items.each((_, el) => io.observe(el));
}

/* ───── مطالعهٔ بیشتر ───── */
function initFolds() {
    const $folds = $('[data-st-fold]');
    if (!$folds.length) return;
    const measure = () => {
        $folds.each(function () {
            const $f = $(this);
            if ($f.hasClass('is-open')) return;
            const box = $f.find('.st-revs, .st-seo__body').get(0);
            if (!box) return;
            $f.removeClass('is-fit'); // قبل از اندازه‌گیری در حالت جمع‌شده
            if (box.scrollHeight <= box.clientHeight + 24) $f.addClass('is-fit');
        });
    };
    $folds.on('click', '[data-st-more]', function () {
        const $b = $(this);
        const $f = $b.closest('[data-st-fold]');
        const open = !$f.hasClass('is-open');
        $f.toggleClass('is-open', open);
        $b.attr('aria-expanded', String(open)).find('[data-st-more-t]').text($b.data(open ? 'less' : 'more'));
        if (!open) { const top = $f.offset().top - 90; if (window.scrollY > top) window.scrollTo({ top, behavior: reduce ? 'auto' : 'smooth' }); }
    });
    measure();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);
    let t;
    $(window).on('resize', () => { clearTimeout(t); t = setTimeout(measure, 150); });
}

/* ───── آکاردئون: هر بار یک سوال باز ───── */
function initFaq() {
    $('[data-st-faq]').each(function () {
        const $d = $(this).find('details');
        $d.on('toggle', function () {
            if (this.open) $d.not(this).prop('open', false);
        });
    });
}

/* ───── قوانین: هایلایت بخش فعلی در فهرست؛ در موبایل بعد از انتخاب، فهرست جمع می‌شود ───── */
function initToc() {
    const $toc = $('[data-st-toc]');
    if (!$toc.length) return;
    const $box = $toc.find('details');
    if (window.matchMedia('(max-width: 1023.98px)').matches) $box.prop('open', false);
    $toc.on('click', 'a', () => { if (window.matchMedia('(max-width: 1023.98px)').matches) $box.prop('open', false); });
    if (!('IntersectionObserver' in window)) return;
    const $a = $toc.find('[data-st-toc-a]');
    const seen = new Map();
    const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) seen.set(en.target.id, en.target.getBoundingClientRect().top); else seen.delete(en.target.id); });
        let best = null;
        seen.forEach((top, id) => { if (best === null || top < seen.get(best)) best = id; });
        if (best) { $a.removeClass('is-on').filter(`[data-st-toc-a="${best}"]`).addClass('is-on'); }
    }, { rootMargin: '-15% 0px -70% 0px' });
    $('.st-doc__s').each((_, el) => io.observe(el));
}

$(() => {
    initToc(); initHead(); initSheet(); initSpy(); initReveal(); initFolds(); initFaq();
});
