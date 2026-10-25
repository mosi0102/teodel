@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/tickets.js')
@endpush

@section('title', __('tickets.title'))

{{--
    تیکت‌ها: لیست + وضعیت، تیکت جدید، گفتگو و مدیریت (جواب‌دادن، بستن، بازکردن دوباره). همه در یک صفحه و بدون رفرش.
    دسکتاپ: دو ستون (لیست | گفتگو). موبایل: لیست ← (گفتگو یا فرم) با دکمه‌ی برگشت.
    رفتار: resources/js/modules/tickets.js — قرارداد داده و آدرس‌ها: app/Support/Tickets.php و README.
--}}
@php
    $ic = fn (string $d, string $c = 'size-5') => '<svg viewBox="0 0 24 24" class="'.$c.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $chevBack = '<svg viewBox="0 0 24 24" class="size-5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>';
    $urls = $urls ?? [];
@endphp
@section('content')
<div class="page-wrap" data-tk
    data-tickets='@json($tickets ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    @isset($urls['detail']) data-detail-url="{{ $urls['detail'] }}" @endisset
    @isset($urls['create']) data-create-url="{{ $urls['create'] }}" @endisset
    @isset($urls['reply']) data-reply-url="{{ $urls['reply'] }}" @endisset
    @isset($urls['close']) data-close-url="{{ $urls['close'] }}" @endisset
    @isset($urls['reopen']) data-reopen-url="{{ $urls['reopen'] }}" @endisset
    data-t='@json(__('tickets'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>

    <header class="tk-head mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">{!! $ic('<path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4z"/><path d="M13 5v2m0 4v2m0 4v2"/>') !!}</span>
                {{ __('tickets.title') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('tickets.subtitle') }}</p>
        </div>
        <button type="button" class="btn-primary w-full sm:w-auto sm:min-w-44" data-tk-new>
            {!! $ic('<path d="M12 5v14M5 12h14"/>') !!} {{ __('tickets.new') }}
        </button>
    </header>

    {{-- کارت‌های خلاصه: کلیک = فیلتر --}}
    <div class="tk-stats" data-tk-stats>
        @foreach (['open', 'answered', 'closed'] as $k)
            <button type="button" class="tk-stat" data-filter="{{ $k }}" data-s="{{ $k }}" aria-pressed="false">
                <b data-tk-count="{{ $k }}">{{ num(0) }}</b>
                <span>{{ __('tickets.stats.' . $k) }}</span>
            </button>
        @endforeach
    </div>

    <div class="tk-shell" data-tk-shell data-view="list">

        {{-- ───────── لیست ───────── --}}
        <section class="tk-list" aria-label="{{ __('tickets.title') }}">
            <div class="tk-list__bar">
                <label class="tk-search">
                    {!! $ic('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>', 'size-4 shrink-0 text-slate-400') !!}
                    <span class="sr-only">{{ __('tickets.list.search_label') }}</span>
                    <input type="search" data-tk-search placeholder="{{ __('tickets.list.search') }}" autocomplete="off" enterkeyhint="search">
                    <button type="button" data-tk-search-clear hidden aria-label="{{ __('tickets.list.clear') }}">{!! $ic('<path d="M6 6l12 12M18 6 6 18"/>', 'size-4') !!}</button>
                </label>
                <div class="tk-chips" role="group" data-tk-filters>
                    @foreach (['all', 'open', 'answered', 'closed'] as $k)
                        <button type="button" class="chip-btn" data-filter="{{ $k }}" aria-pressed="{{ $k === 'all' ? 'true' : 'false' }}">
                            {{ __('tickets.filters.' . $k) }} <span class="tk-chip-n" data-tk-chip-n="{{ $k }}"></span>
                        </button>
                    @endforeach
                </div>
            </div>
            <ul class="tk-items" data-tk-items></ul>
            <div class="tk-empty" data-tk-empty hidden>
                <span class="tk-empty__ic">{!! $ic('<path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4z"/>', 'size-7') !!}</span>
                <h2 class="mt-3 text-base font-extrabold text-brand-900" data-tk-empty-title></h2>
                <p class="mt-1.5 text-sm leading-7 text-slate-500" data-tk-empty-text></p>
                <button type="button" class="btn-primary mt-4" data-tk-new>{{ __('tickets.new') }}</button>
            </div>
        </section>

        {{-- ───────── پنل راست: خالی | گفتگو | فرم ───────── --}}
        <section class="tk-pane">

            <div class="tk-placeholder" data-tk-placeholder>
                <span class="tk-empty__ic">{!! $ic('<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>', 'size-7') !!}</span>
                <h2 class="mt-3 text-base font-extrabold text-brand-900">{{ __('tickets.pane.title') }}</h2>
                <p class="mt-1.5 max-w-xs text-sm leading-7 text-slate-500">{{ __('tickets.pane.text') }}</p>
            </div>

            {{-- گفتگو --}}
            <div class="tk-detail" data-tk-detail hidden>
                <header class="tk-detail__head">
                    <button type="button" class="pt-back lg:hidden" data-tk-back>{!! $chevBack !!} {{ __('tickets.detail.back') }}</button>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-extrabold leading-8 text-brand-900 md:text-lg" data-tk-d-subject></h2>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-slate-400">
                                <bdi dir="ltr" class="font-bold text-slate-500" data-tk-d-code></bdi><span aria-hidden="true">·</span>
                                <span data-tk-d-dept></span><span aria-hidden="true">·</span><span data-tk-d-created></span>
                            </p>
                        </div>
                        <span class="tk-badge" data-tk-d-status></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" data-tk-d-hint></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="btn-outline tk-act" data-tk-close>{!! $ic('<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>', 'size-4') !!} {{ __('tickets.detail.close') }}</button>
                        <button type="button" class="btn-outline tk-act" data-tk-reopen hidden>{!! $ic('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>', 'size-4') !!} {{ __('tickets.detail.reopen') }}</button>
                    </div>
                </header>

                <div class="tk-thread" data-tk-thread role="log" aria-live="polite" aria-label="{{ __('tickets.detail.messages') }}"></div>

                <div class="tk-loading" data-tk-loading hidden>
                    <p class="text-sm font-bold text-slate-500" data-tk-loading-text>{{ __('tickets.detail.loading') }}</p>
                    <button type="button" class="btn-outline mt-3" data-tk-retry hidden>{{ __('tickets.detail.retry') }}</button>
                </div>

                {{-- جواب‌دادن --}}
                <form class="tk-composer" data-tk-reply novalidate>
                    <label class="sr-only" for="tk-reply-text">{{ __('tickets.detail.reply') }}</label>
                    <textarea id="tk-reply-text" rows="2" maxlength="3000" placeholder="{{ __('tickets.detail.reply_ph') }}" data-tk-reply-text></textarea>
                    <ul class="tk-files" data-tk-reply-files></ul>
                    <p class="tk-err" data-tk-reply-error role="alert" hidden></p>
                    <div class="tk-composer__row">
                        <label class="tk-attach" tabindex="0" data-tk-attach-btn>
                            {!! $ic('<rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/>', 'size-5') !!}
                            <span class="sr-only">{{ __('tickets.detail.attach') }}</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-tk-reply-input>
                        </label>
                        <span class="hidden flex-1 text-xs text-slate-400 md:block">{{ __('tickets.detail.hint') }}</span>
                        <button type="submit" class="btn-primary tk-send" data-tk-reply-send>{{ __('tickets.detail.send') }}</button>
                    </div>
                </form>
                <div class="tk-closed" data-tk-closed hidden>
                    <p class="text-sm leading-7 text-slate-500">{{ __('tickets.detail.closed_note') }}</p>
                </div>
            </div>

            {{-- تیکت جدید --}}
            <form class="tk-form" data-tk-form hidden novalidate>
                <button type="button" class="pt-back lg:hidden" data-tk-back>{!! $chevBack !!} {{ __('tickets.form.back') }}</button>
                <h2 class="mt-1 text-lg font-extrabold text-brand-900 lg:mt-0">{{ __('tickets.form.heading') }}</h2>
                <p class="mt-1.5 text-sm leading-7 text-slate-500">{{ __('tickets.form.text') }}</p>

                <fieldset class="mt-5">
                    <legend class="tk-label">{{ __('tickets.form.dept') }}</legend>
                    <div class="tk-depts" role="radiogroup" data-tk-depts>
                        @foreach ($departments ?? ['technical', 'payment', 'content', 'account', 'suggestion'] as $d)
                            <button type="button" class="tk-dept" role="radio" aria-checked="false" data-dept="{{ $d }}">{{ __('tickets.departments.' . $d) }}</button>
                        @endforeach
                    </div>
                    <p class="tk-err" data-tk-err="dept" hidden></p>
                </fieldset>

                <div class="mt-4">
                    <label class="tk-label" for="tk-subject">{{ __('tickets.form.subject') }}</label>
                    <input id="tk-subject" type="text" class="tk-input" maxlength="120" data-tk-subject placeholder="{{ __('tickets.form.subject_ph') }}" autocomplete="off">
                    <p class="tk-err" data-tk-err="subject" hidden></p>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between gap-3">
                        <label class="tk-label" for="tk-message">{{ __('tickets.form.message') }}</label>
                        <span class="text-xs font-medium text-slate-400" data-tk-counter></span>
                    </div>
                    <textarea id="tk-message" class="tk-input min-h-40 resize-y" rows="6" maxlength="3000" data-tk-message placeholder="{{ __('tickets.form.message_ph') }}"></textarea>
                    <p class="tk-err" data-tk-err="message" hidden></p>
                </div>

                <div class="mt-4">
                    <p class="tk-label">{{ __('tickets.form.attach') }}</p>
                    <ul class="tk-files" data-tk-new-files></ul>
                    <label class="tk-drop" tabindex="0" data-tk-attach-btn>
                        {!! $ic('<rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/>', 'size-5') !!}
                        <span class="text-sm font-bold">{{ __('tickets.form.add_file') }}</span>
                        <span class="text-xs text-slate-400" data-tk-attach-hint></span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-tk-new-input>
                    </label>
                    <p class="tk-err" data-tk-err="files" hidden></p>
                </div>

                <aside class="tk-tips">
                    <h3 class="text-xs font-extrabold text-brand-800">{{ __('tickets.form.tips_title') }}</h3>
                    <ul class="mt-2 grid gap-1.5">
                        @foreach (__('tickets.form.tips') as $tip)
                            <li class="flex gap-2 text-xs leading-6 text-slate-500">{!! $ic('<path d="m5 12.5 4.5 4.5L19 7.5"/>', 'mt-1 size-3.5 shrink-0 text-brand-600') !!}<span>{{ $tip }}</span></li>
                        @endforeach
                    </ul>
                </aside>

                <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                    <button type="button" class="btn-ghost" data-tk-cancel>{{ __('tickets.form.cancel') }}</button>
                    <button type="submit" class="btn-primary sm:min-w-44" data-tk-submit>{{ __('tickets.form.submit') }}</button>
                </div>
            </form>
        </section>
    </div>

    <div class="pf-toast" data-tk-toast role="status" hidden></div>
</div>
@endsection

@push('modals')
<div id="tk-close-dialog" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="tk-close-title">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="tk-close-title" class="text-lg font-extrabold text-brand-900">{{ __('tickets.confirm_close.title') }}</h2>
        <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('tickets.confirm_close.text') }}</p>
        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('tickets.confirm_close.back') }}</button>
            <button type="button" class="btn-primary" data-tk-close-ok>{{ __('tickets.confirm_close.ok') }}</button>
        </div>
    </div>
</div>
@endpush
