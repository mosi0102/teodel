@extends('layouts.app')

@section('title', __('dictionary.title'))

{{--
    دیکشنری: الفبایی، بدون سطح‌بندی، لیست جمع‌وجور (چند ستونی) برای تعداد زیاد.
    رفتار: resources/js/modules/dictionary.js — داده و قرارداد دو حالت (لیست کامل یا endpoint): app/Support/Dictionary.php
    ترجمه‌های فارسی پیش‌فرض تارند (کلیک = نمایش)؛ جزئیات هر لغت در offcanvas باز می‌شود.
--}}
@php
    $types = ['all', 'noun', 'verb', 'adjective', 'adverb', 'other'];
    $az = range('a', 'z');
@endphp
@section('content')
<div class="page-wrap" data-dd
    data-words='@json($words ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-letters='@json($letters ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-saved='@json($savedKeys ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    @isset($endpoint) data-endpoint="{{ $endpoint }}" @endisset
    data-words-url="{{ url('/words') }}"
    data-t-count="{{ __('dictionary.count') }}" data-t-count-letter="{{ __('dictionary.count_letter') }}" data-t-position="{{ __('dictionary.position') }}"
    data-t-saved="{{ __('dictionary.saved_msg') }}" data-t-unsaved="{{ __('dictionary.unsaved_msg') }}"
    data-t-save="{{ __('dictionary.save') }}" data-t-unsave="{{ __('dictionary.unsave') }}"
    data-t-reveal="{{ __('dictionary.reveal_all') }}" data-t-hide="{{ __('dictionary.hide_all') }}" data-t-failed="{{ __('dictionary.failed') }}"
    data-t-see-saved="{{ __('dictionary.see_saved') }}" data-t-speak="{{ __('dictionary.speak') }}" data-t-more="{{ __('dictionary.load_more') }}">

    {{-- عنوان + ابزارها --}}
    <header class="mb-4 flex flex-col gap-3 md:mb-5 md:flex-row md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/><path d="M9 8h7M9 12h5"/></svg>
                </span>
                {{ __('dictionary.title') }}
            </h1>
            <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('dictionary.subtitle') }}</p>
        </div>

        <x-page-tools class="shrink-0 flex-wrap" />
    </header>

    {{-- نوار ابزار (چسبنده): جست‌وجو + فیلترها + الفبا --}}
    <div class="dd-bar" data-dd-bar>
        <div class="dd-tools">
            <label class="dd-search">
                <span class="sr-only">{{ __('dictionary.search') }}</span>
                <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" class="field min-h-11 ps-11 pe-10" data-dd-q placeholder="{{ __('dictionary.search') }}" autocomplete="off" spellcheck="false" enterkeyhint="search">
                <button type="button" class="dd-clear" data-dd-clear aria-label="{{ __('dictionary.clear') }}" hidden><x-icon name="x" class="size-4" /></button>
            </label>

            <select class="field dd-sel" data-dd-type aria-label="{{ __('dictionary.types.all') }}">
                @foreach ($types as $t)
                    <option value="{{ $t }}">{{ __('dictionary.types.' . $t) }}</option>
                @endforeach
            </select>
            <select class="field dd-sel" data-dd-sort aria-label="{{ __('dictionary.sort.az') }}">
                <option value="az">{{ __('dictionary.sort.az') }}</option>
                <option value="za">{{ __('dictionary.sort.za') }}</option>
            </select>

            <button type="button" class="chip-btn dd-chip" data-dd-saved aria-pressed="false" title="{{ __('dictionary.saved_only') }}">
                <x-icon name="bookmark" class="size-4" /><span class="max-sm:sr-only">{{ __('dictionary.saved_only') }}</span>
            </button>
            <button type="button" class="chip-btn dd-chip" data-dd-reveal aria-pressed="false" title="{{ __('dictionary.reveal_all') }}">
                <x-icon name="eye" class="size-4" /><span data-dd-reveal-label class="max-sm:sr-only">{{ __('dictionary.reveal_all') }}</span>
            </button>
        </div>

        <nav class="dd-letters" aria-label="A–Z" data-dd-letters dir="ltr">
            <button type="button" class="dd-letter-btn dd-letter-btn--all" data-letter="" aria-pressed="true">{{ __('dictionary.all') }}</button>
            @foreach ($az as $c)
                <button type="button" class="dd-letter-btn" data-letter="{{ $c }}" aria-pressed="false">{{ strtoupper($c) }}</button>
            @endforeach
        </nav>
    </div>

    <p class="dd-summary" data-dd-summary aria-live="polite"></p>

    {{-- لیست --}}
    <div class="dd-list" data-dd-list role="list"></div>
    <div data-dd-sentinel class="h-px"></div>

    <div class="mt-4 flex justify-center" data-dd-more-wrap hidden>
        <button type="button" class="btn-outline" data-dd-more>{{ __('dictionary.load_more') }}</button>
    </div>

    <div class="card mt-2 px-6 py-14 text-center" data-dd-noresult hidden>
        <p class="text-lg font-extrabold text-brand-900">{{ __('dictionary.noresult.title') }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-500">{{ __('dictionary.noresult.text') }}</p>
        <button type="button" class="btn-ghost mt-5" data-dd-reset>{{ __('dictionary.noresult.reset') }}</button>
    </div>

    <div class="card mt-2 px-6 py-10 text-center" data-dd-error hidden>
        <p class="text-sm font-bold text-rose-600">{{ __('dictionary.failed') }}</p>
        <button type="button" class="btn-ghost mt-4" data-dd-retry>{{ __('dictionary.retry') }}</button>
    </div>

    <div class="pf-toast" data-dd-toast role="status" aria-live="polite" hidden></div>

    {{-- قالب ردیف --}}
    <template data-dd-row-tpl>
        <article class="dd-row" role="listitem" data-dd-row>
            <div class="min-w-0 flex-1">
                <button type="button" class="dd-main" data-dd-open dir="ltr" lang="en">
                    <bdi class="dd-word" data-r="phrase"></bdi><small class="dd-type" data-r="type"></small>
                </button>
                <button type="button" class="dd-tr" data-dd-tr aria-pressed="false" aria-label="{{ __('dictionary.see') }}">
                    <span data-r="translate" dir="auto"></span>
                </button>
            </div>
            <button type="button" class="dd-ic" data-dd-speak aria-label="{{ __('dictionary.speak') }}"><x-icon name="volume" class="size-[1.15rem]" /></button>
            <button type="button" class="dd-ic dd-ic--bm" data-dd-bm aria-pressed="false" aria-label="{{ __('dictionary.save') }}"><x-icon name="bookmark" class="size-[1.15rem]" /></button>
        </article>
    </template>
</div>
@endsection

@push('modals')
{{-- جزئیات لغت: offcanvas (ناحیه‌ی اسکرول + نوار پایین ثابت) --}}
<div id="dd-dialog" class="dialog dialog--offcanvas" data-dialog data-dd-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dd-phrase">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel">
        <div class="sw-scroll" data-dd-scroll>
            <div data-dd-body>
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1" dir="ltr" lang="en">
                        <h2 id="dd-phrase" class="break-words text-2xl font-extrabold leading-9 text-brand-900"><bdi data-d="phrase"></bdi></h2>
                        <p class="text-sm text-slate-500" data-d="type"></p>
                    </div>
                    <button type="button" data-dialog-close class="sw-x -me-2 -mt-1" aria-label="{{ __('ui.close') }}"><x-icon name="x" class="size-5" /></button>
                </div>

                <div class="mt-3 space-y-1.5" dir="ltr" lang="en">
                    @foreach (['uk', 'us'] as $acc)
                        <div class="flex items-center gap-2" data-d-pron="{{ $acc }}" hidden>
                            <button type="button" class="wc-speak" data-d-speak="{{ $acc }}" aria-label="{{ __('dictionary.speak') }} ({{ __('dictionary.' . $acc) }})"><x-icon name="volume" class="size-5" /></button>
                            <span class="text-sm text-slate-600" data-d-ipa></span>
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500">{{ $acc }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- ترجمه‌ی فارسی (تار) --}}
                <div class="mt-4 flex items-center gap-3" data-d-tr-wrap>
                    <span class="shrink-0 text-xs font-bold text-slate-500">{{ __('dictionary.translate') }}</span>
                    <button type="button" class="sw-b sw-b--text" aria-pressed="false" aria-label="{{ __('dictionary.see') }}">
                        <span class="sw-b__content text-sm font-extrabold text-brand-900" data-d="translate" dir="auto"></span>
                        <span class="sw-b__veil"><x-icon name="eye-off" class="size-4 sw-off" /><x-icon name="eye" class="size-4 sw-on" /><span>{{ __('dictionary.see') }}</span></span>
                    </button>
                </div>

                <p class="mt-4 text-left text-[0.95rem] leading-7 text-slate-700" dir="ltr" lang="en" data-d="meaning"></p>

                <div class="mt-4 rounded-2xl bg-slate-50 p-3.5" data-d-ex-wrap>
                    <p class="text-xs font-bold text-slate-500">{{ __('dictionary.example') }}</p>
                    <p class="mt-1 text-left text-sm font-semibold leading-7 text-brand-900" dir="ltr" lang="en" data-d="example"></p>
                    <button type="button" class="sw-b sw-b--block mt-2" aria-pressed="false" aria-label="{{ __('dictionary.see') }}" data-d-extr-wrap>
                        <span class="sw-b__content text-sm leading-7 text-slate-600" dir="auto" data-d="example_tr"></span>
                        <span class="sw-b__veil"><x-icon name="eye-off" class="size-4 sw-off" /><x-icon name="eye" class="size-4 sw-on" /><span>{{ __('dictionary.see') }}</span></span>
                    </button>
                </div>

                <dl class="wc-table mt-4" dir="ltr" lang="en">
                    <div data-d-row="synonyms"><dt>{{ __('dictionary.synonyms') }}</dt><dd data-d="synonyms"></dd></div>
                    <div data-d-row="antonyms"><dt>{{ __('dictionary.antonyms') }}</dt><dd data-d="antonyms"></dd></div>
                </dl>
            </div>
        </div>

        <div class="sw-foot">
            <div class="flex items-center gap-2">
                <button type="button" class="btn-ghost sw-foot__close" data-dialog-close>{{ __('ui.close') }}</button>
                <button type="button" class="sw-round sw-round--bm dd-bm-big" data-d-bm aria-pressed="false" aria-label="{{ __('dictionary.save') }}"><x-icon name="bookmark" class="size-5" /></button>
                <span class="flex-1 text-center text-xs font-extrabold tabular-nums text-slate-400" data-d-pos aria-live="polite"></span>
                <button type="button" class="sw-round" data-d-prev aria-label="{{ __('dictionary.prev') }}"><x-icon name="chevron-start" class="size-5" /></button>
                <button type="button" class="sw-round" data-d-next aria-label="{{ __('dictionary.next') }}"><x-icon name="chevron-end" class="size-5" /></button>
            </div>
        </div>
    </div>
</div>
@endpush
