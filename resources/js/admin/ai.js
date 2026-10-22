import $ from 'jquery';
import { openDialog, closeDialog } from '../modules/dialogs';
import { readJSON, send, toast } from './common';

/**
 * دکمه‌ی AI (ساخت اطلاعات با هوش مصنوعی) — یک دیالوگ مشترک برای همه‌ی فرم‌های پنل.
 *
 * ── در فرم (فرانت) ─────────────────────────────────────────────
 *   <form data-ai-form="song" data-ai-url="/admin/ai/generate">
 *     <div class="ad-field"> … <input name="title" data-ai-field> … </div>
 *     <button type="button" class="ad-ai" data-ai-open data-ai-seeds="title,artist" data-ai-fields="level,desc_fa,desc_en,genre">AI</button>
 *     <button type="button" class="ad-ai ad-ai--sm" data-ai-one="desc_fa">…</button>      ← ساخت فقط همین یک فیلد
 *   </form>
 *   • دکمه می‌تواند بیرون فرم باشد (مثلاً در هدر کارت): data-ai-target="#form-id".
 *   • seeds: فیلدهایی که کاربر به AI «می‌دهد» (در دیالوگ ویرایش‌شدنی‌اند). • fields: فیلدهایی که AI «می‌سازد».
 *
 * ── 🔌 بک‌اند (یکی از سه راه) ──────────────────────────────────
 *   ۱) POST به data-ai-url (CSRF + JSON):
 *        { kind:'song', seeds:{title,artist}, fields:['level',…], note:'', current:{…همه‌ی مقدارهای فعلی فرم…}, locale:'fa' }
 *      ← 200 { fields:{ level:'A2', desc_fa:'…' }, missing:['cover'] }
 *        missing = چیزهایی که AI نتوانست بسازد (کاربر دستی وارد می‌کند). مقدار فیلد را می‌شود null یا '' گذاشت = ساخته نشد.
 *        ← 4xx { message }
 *   ۲) رویداد لغوشدنی:  $(document).on('admin:ai-generate', (e, { kind, payload, done, fail }) => { e.preventDefault(); … done({fields,missing}) })
 *   ۳) جایگزین کردن:   Teodel.admin.ai.generate = async (payload) => ({ fields, missing })
 *   بدون هیچ‌کدام، «حالت دمو» نتیجه‌ی ساختگی می‌سازد (فقط برای دیدن جریان فرانت).
 *
 * ── رویدادها ──  admin:ai-applied  { kind, fields:[names] }
 * هر فیلدی که AI پر کند علامت «ساخته‌ی AI — بررسی کن» می‌گیرد (.ad-field.is-ai) و با ویرایش دستی علامت برداشته می‌شود.
 */

const demoText = {
    title: (s) => s.title || 'Untitled',
    level: () => 'A2',
    genre: () => 'Pop',
    accent: () => 'us',
    year: () => '2019',
    wpm: () => '104',
    topic: () => 'Love',
    album: () => 'Demo Album',
    desc_fa: (s) => `«${s.title || s.term || s.q || 'این مورد'}» را با یک جمله‌ی ساده و کاربردی توضیح می‌دهد تا زبان‌آموز سریع‌تر بفهمد.`,
    desc_en: (s) => `A short, simple explanation of “${s.title || s.term || s.q || 'this item'}” for English learners.`,
    pron: (s) => '/' + String(s.term || s.title || 'word').toLowerCase() + '/',
    meaning_fa: (s) => 'معنی فارسی (نمونه)',
    example: (s) => `I really like the word "${s.term || s.title || 'this'}".`,
    example_fa: () => 'من واقعاً این کلمه را دوست دارم.',
    synonyms: () => 'similar, alike',
    antonyms: () => 'different',
    q_fa: () => 'کدام گزینه درست است؟',
    q_en: (s) => s.q || 'Choose the correct option.',
    o1: () => 'Option A', o2: () => 'Option B', o3: () => 'Option C', o4: () => 'Option D',
    answer: () => '1',
    explain: () => 'توضیح کوتاه درباره‌ی پاسخ درست (نمونه).',
};

const API = (window.Teodel = window.Teodel || {});
API.admin = API.admin || {};
API.admin.ai = API.admin.ai || {};
API.admin.ai.demos = API.admin.ai.demos || {};

/**
 * درخواست برنامه‌ای (برای بخش‌های لیستی مثل لغت‌ها، تمرین‌ها، ترجمه‌ی لیریک): همان قرارداد بک‌اند را دارد.
 *   Teodel.admin.ai.request(payload, { url, demo }) ← { fields, missing }
 * اولویت: رویداد admin:ai-generate ← Teodel.admin.ai.generate ← POST به url ← demo()
 */
API.admin.ai.request = async function (payload, opts = {}) {
    const ev = $.Event('admin:ai-generate');
    const viaEvent = await new Promise((resolve) => {
        const done = (r) => resolve({ ok: r });
        const fail = (m) => resolve({ err: m });
        $(document).trigger(ev, { kind: payload.kind, payload, done, fail });
        if (!ev.isDefaultPrevented()) resolve(null);
    });
    if (viaEvent) { if (viaEvent.err !== undefined) throw { message: viaEvent.err }; return viaEvent.ok; }
    if (typeof API.admin.ai.generate === 'function') return API.admin.ai.generate(payload);
    if (opts.url) return send(opts.url, payload);
    await new Promise((r) => setTimeout(r, 500));
    if (typeof API.admin.ai.demos[payload.kind] === 'function') return API.admin.ai.demos[payload.kind](payload);
    return opts.demo ? opts.demo() : { fields: {}, missing: [] };
};

$(function () {
    const $dlg = $('#ad-ai');
    if (!$dlg.length) return;
    const t = readJSON($dlg, 'data-t', {});
    const tr = (k, rep) => {
        let v = (t[k] != null ? t[k] : k) + '';
        if (rep) Object.keys(rep).forEach((r) => (v = v.split(':' + r).join(rep[r])));
        return v;
    };
    const $seeds = $dlg.find('[data-ai-seeds-box]');
    const $res = $dlg.find('[data-ai-res]');
    const $miss = $dlg.find('[data-ai-miss]');
    const $err = $dlg.find('[data-ai-err]');
    const $gen = $dlg.find('[data-ai-gen]');
    const $apply = $dlg.find('[data-ai-apply]');
    let ctx = null; // { $form, kind, url, seeds[], fields[] , last }

    const $field = ($form, name) => {
        let $f = $form.find('[name="' + name + '"]').first();
        if (!$f.length && $form.attr('id')) $f = $('[form="' + $form.attr('id') + '"][name="' + name + '"]').first();
        return $f;
    };
    const labelOf = ($form, name) => $field($form, name).closest('.ad-field').find('.ad-label').first().clone().children().remove().end().text().trim() || $field($form, name).attr('aria-label') || name;
    const valueOf = ($form, name) => {
        const $f = $field($form, name);
        return $f.is(':checkbox') ? ($f.prop('checked') ? '1' : '') : ($f.val() || '').toString();
    };
    const currentOf = ($form) => {
        const o = {};
        $form.find('[data-ai-field]').each(function () { if (this.name) o[this.name] = valueOf($form, this.name); });
        return o;
    };

    async function generate(payload, kind) {
        const ev = $.Event('admin:ai-generate');
        const viaEvent = await new Promise((resolve) => {
            let settled = false;
            const done = (r) => { settled = true; resolve({ ok: r }); };
            const fail = (m) => { settled = true; resolve({ err: m }); };
            $(document).trigger(ev, { kind, payload, done, fail });
            if (!ev.isDefaultPrevented()) resolve(null);
            else if (!settled) { /* بک‌اند done/fail را بعداً صدا می‌زند */ }
        });
        if (viaEvent) { if (viaEvent.err !== undefined) throw { message: viaEvent.err }; return viaEvent.ok; }
        if (typeof API.admin.ai.generate === 'function') return API.admin.ai.generate(payload);
        if (ctx && ctx.url) return send(ctx.url, payload);
        // حالت دمو (هر بخش می‌تواند Teodel.admin.ai.demos[kind] = (payload) => ({fields, missing}) ثبت کند)
        await new Promise((r) => setTimeout(r, 650));
        if (typeof API.admin.ai.demos[ctx && ctx.kind] === 'function') return API.admin.ai.demos[ctx.kind](payload);
        const out = {};
        const missing = [];
        payload.fields.forEach((f) => {
            if (/cover|image|audio|photo/.test(f)) return missing.push(f);
            out[f] = demoText[f] ? demoText[f](payload.seeds) : '';
            const $sel = ctx && ctx.$form ? ctx.$form.find('select[name="' + f + '"]') : $();
            if ($sel.length) {
                const vals = $sel.find('option').map((_, o) => o.value).get().filter(Boolean);
                if (!vals.includes(out[f])) out[f] = vals[Math.min(1, vals.length - 1)];
            }
            if (!out[f]) missing.push(f);
        });
        return { fields: out, missing };
    }

    function renderSeeds() {
        $seeds.empty();
        ctx.seeds.forEach((n) => {
            const id = 'ai-seed-' + n;
            $seeds.append(
                $('<div class="ad-field">').append(
                    $('<label class="ad-label">').attr('for', id).text(labelOf(ctx.$form, n)),
                    $('<input type="text" class="field">').attr({ id, 'data-seed': n }).val(valueOf(ctx.$form, n))
                )
            );
        });
    }

    function open($btn) {
        const $form = $btn.attr('data-ai-target') ? $($btn.attr('data-ai-target')) : $btn.closest('[data-ai-form]');
        if (!$form.length) return;
        const split = (s) => (s || '').split(',').map((x) => x.trim()).filter(Boolean);
        ctx = {
            $form,
            kind: $form.attr('data-ai-form'),
            url: $form.attr('data-ai-url') || '',
            seeds: split($btn.attr('data-ai-seeds')),
            fields: split($btn.attr('data-ai-fields')),
        };
        $dlg.find('[data-ai-kind]').text((t.kinds && t.kinds[ctx.kind]) || tr('title'));
        renderSeeds();
        $res.empty().prop('hidden', true);
        $miss.prop('hidden', true).empty();
        $err.prop('hidden', true).text('');
        $apply.prop('hidden', true);
        $dlg.find('[data-ai-note]').val('');
        $gen.find('[data-ai-gen-t]').text(tr('generate'));
        openDialog('#ad-ai');
        setTimeout(() => $seeds.find('input').first().trigger('focus'), 80);
    }

    $(document).on('click', '[data-ai-open]', function () { open($(this)); });

    $gen.on('click', async function () {
        if (!ctx || $dlg.hasClass('is-busy')) return;
        const seeds = {};
        $seeds.find('input').each(function () { seeds[$(this).attr('data-seed')] = $(this).val().toString().trim(); });
        if (!Object.values(seeds).some(Boolean)) { $err.text(tr('empty_seed')).prop('hidden', false); return; }
        $err.prop('hidden', true);
        $dlg.addClass('is-busy'); $gen.prop('disabled', true);
        try {
            const payload = { kind: ctx.kind, seeds, fields: ctx.fields, note: $dlg.find('[data-ai-note]').val().toString().trim(), current: currentOf(ctx.$form), locale: document.documentElement.lang };
            const r = await generate(payload, ctx.kind);
            showResult(r || { fields: {}, missing: ctx.fields });
        } catch (e) {
            $err.text((e && e.message) || tr('error')).prop('hidden', false);
        } finally {
            $dlg.removeClass('is-busy'); $gen.prop('disabled', false);
        }
    });

    function showResult(r) {
        const overwrite = $dlg.find('[data-ai-overwrite]').prop('checked');
        const miss = new Set(r.missing || []);
        $res.empty();
        ctx.fields.forEach((n) => {
            const v = r.fields && r.fields[n];
            if (v == null || v === '') { miss.add(n); return; }
            const had = valueOf(ctx.$form, n).trim() !== '';
            const skip = had && !overwrite;
            $res.append(
                $('<li>').toggleClass('is-skip', skip).append(
                    $('<input type="checkbox">').attr({ 'data-name': n, 'aria-label': labelOf(ctx.$form, n) }).prop('checked', !skip),
                    $('<div class="min-w-0">').append($('<b>').text(labelOf(ctx.$form, n)), $('<p>').text(v), skip ? $('<small>').text(tr('skipped')) : null)
                ).data('value', v)
            );
        });
        $res.prop('hidden', $res.children().length === 0);
        $miss.empty();
        if (miss.size) {
            const names = [...miss].map((n) => labelOf(ctx.$form, n)).join('، ');
            $miss.append($('<b>').text(tr('missing_title')), $('<p>').text(names + ' — ' + tr('missing_hint'))).prop('hidden', false);
        } else $miss.prop('hidden', true);
        ctx.last = { miss: [...miss] };
        $apply.prop('hidden', $res.children().length === 0);
        $gen.find('[data-ai-gen-t]').text(tr('again'));
    }

    function mark($form, name, ai) {
        const $w = $field($form, name).closest('.ad-field');
        $w.toggleClass('is-ai', ai).removeClass('is-missing');
    }
    function setVal($form, name, v) {
        const $f = $field($form, name);
        if ($f.is(':checkbox')) $f.prop('checked', !!v);
        else $f.val(Array.isArray(v) ? v.join(', ') : v);
        $f.trigger('input').trigger('change');
    }

    $apply.on('click', function () {
        const done = [];
        $res.children().each(function () {
            const $c = $(this).find('input[type=checkbox]');
            if (!$c.prop('checked')) return;
            const n = $c.attr('data-name');
            setVal(ctx.$form, n, $(this).data('value'));
            mark(ctx.$form, n, true);
            done.push(n);
        });
        (ctx.last.miss || []).forEach((n) => $field(ctx.$form, n).closest('.ad-field').addClass('is-missing'));
        closeDialog('#ad-ai');
        toast(tr('applied', { n: done.length }));
        ctx.$form.trigger('admin:ai-applied', { kind: ctx.kind, fields: done });
        $(document).trigger('admin:ai-applied', { kind: ctx.kind, fields: done });
    });

    // با ویرایش دستی، علامت AI برداشته می‌شود
    $(document).on('input', '[data-ai-form] .is-ai [name]', function () { $(this).closest('.ad-field').removeClass('is-ai'); });

    // ساخت فقط یک فیلد (یا چند فیلد: data-ai-one="o1,o2,o3,o4,answer")
    $(document).on('click', '[data-ai-one]', async function () {
        const $b = $(this);
        const $form = $b.closest('[data-ai-form]');
        const names = ($b.attr('data-ai-one') || '').split(',').map((x) => x.trim()).filter(Boolean);
        const seedNames = ($b.attr('data-ai-seeds') || $form.find('[data-ai-open]').first().attr('data-ai-seeds') || '').split(',').map((x) => x.trim()).filter(Boolean);
        const seeds = {};
        seedNames.forEach((n) => (seeds[n] = valueOf($form, n)));
        if (!Object.values(seeds).some(Boolean)) { toast(tr('empty_seed'), true); return; }
        ctx = { $form, kind: $form.attr('data-ai-form'), url: $form.attr('data-ai-url') || '', seeds: seedNames, fields: names };
        $b.prop('disabled', true).addClass('is-busy');
        try {
            const r = await generate({ kind: ctx.kind, seeds, fields: names, note: '', current: currentOf($form), locale: document.documentElement.lang }, ctx.kind);
            const done = [];
            names.forEach((name) => {
                const v = r && r.fields && r.fields[name];
                if (v == null || v === '') { $field($form, name).closest('.ad-field').addClass('is-missing'); return; }
                setVal($form, name, v);
                mark($form, name, true);
                done.push(name);
            });
            if (!done.length) toast(tr('missing_title'), true);
            else { toast(tr('applied', { n: done.length })); $form.trigger('admin:ai-applied', { kind: ctx.kind, fields: done }); $(document).trigger('admin:ai-applied', { kind: ctx.kind, fields: done }); }
        } catch (e) {
            toast((e && e.message) || tr('error'), true);
        } finally {
            $b.prop('disabled', false).removeClass('is-busy');
        }
    });
});
