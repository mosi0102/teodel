@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/words.js')
@endpush

@section('title', __('words.title'))

{{--
    لغات و عبارت‌های هایلایت‌شده: لیست + دیالوگ اطلاعات هر لغت + حالت مرور.
    رفتار کامل در resources/js/modules/saved-words.js (جست‌وجو، فیلتر، مرتب‌سازی، حذف با بازگردانی، مرور).
    داده: app/Support/SavedWords.php ($words از کنترلر) — هر ترجمه/مثالِ ترجمه‌شده تار است و با کلیک نمایش داده می‌شود.
--}}
@section('content')
<div class="page-wrap" data-sw
    data-words='@json($words, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-song-url="{{ url('/music') }}" data-dict-url="{{ url('/dictionary') }}"
    data-t-removed="{{ __('words.removed') }}" data-t-position="{{ __('words.position') }}" data-t-count="{{ __('words.count') }}"
    data-t-review-count="{{ __('words.review.progress') }}" data-t-done-text="{{ __('words.review.done_text') }}"
    data-t-again="{{ __('words.review.again') }}" data-t-status-learning="{{ __('words.status.learning') }}" data-t-status-learned="{{ __('words.status.learned') }}"
    data-t-see="{{ __('words.see') }}" data-t-hide="{{ __('words.hide') }}" data-t-start="{{ __('words.review.start') }}">

    {{-- عنوان + ابزارها --}}
    <header class="mb-5 flex flex-col gap-3 md:mb-6 md:flex-row md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon name="bookmark" class="size-5" />
                </span>
                {{ __('words.title') }}
            </h1>
            <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('words.subtitle') }}</p>
        </div>

        <x-page-tools class="shrink-0 flex-wrap" />
    </header>

    <div data-sw-content>
        {{-- نوار ابزار: جست‌وجو + فیلتر + مرتب‌سازی + تب‌های سطح --}}
        <div class="card p-3 md:p-4" data-sw-toolbar>
            <div class="grid gap-2 md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3">
                <label class="relative block min-w-0">
                    <span class="sr-only">{{ __('words.search') }}</span>
                    <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" data-sw-q enterkeyhint="search" autocomplete="off" placeholder="{{ __('words.search') }}" class="field h-12 rounded-full ps-12 pe-4">
                </label>

                <div class="grid grid-cols-2 gap-2 md:contents">
                    <label class="select-pill">
                        <span class="shrink-0 text-slate-500">{{ __('words.filter_label') }}</span>
                        <select data-sw-filter>
                            @foreach (['all', 'new', 'learning', 'learned'] as $o)
                                <option value="{{ $o }}">{{ __('words.filter.' . $o) }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="chev" />
                    </label>

                    <label class="select-pill">
                        <span class="shrink-0 text-slate-500">{{ __('words.sort_label') }}</span>
                        <select data-sw-sort>
                            @foreach (['newest', 'oldest', 'az', 'level'] as $o)
                                <option value="{{ $o }}">{{ __('words.sort.' . $o) }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="chev" />
                    </label>
                </div>
            </div>

            {{-- تب‌های سطح (با شمارنده) --}}
            <nav class="no-scrollbar mt-3 flex divide-x divide-slate-200 overflow-x-auto rounded-xl border border-slate-200 rtl:divide-x-reverse md:mt-4" aria-label="{{ __('words.levels_label') }}" data-sw-tabs>
                @foreach (['all', 'a1', 'a2', 'b1', 'b2', 'c1'] as $l)
                    <button type="button" class="level-tab sw-tab" data-level="{{ $l }}" @if ($l === 'all') aria-current="page" @endif>
                        <span>{{ $l === 'all' ? __('words.tabs.all') : strtoupper($l) }}</span>
                        <span class="sw-tab__n" data-sw-tab-n>{{ num(0) }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- خلاصه‌ی وضعیت --}}
        <p class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 px-1 text-sm font-bold text-slate-500" aria-live="polite" data-sw-summary></p>

        {{-- لیست کارت‌ها --}}
        <section class="mt-3 grid gap-3.5 md:grid-cols-2 md:gap-4 xl:grid-cols-3" data-sw-grid aria-label="{{ __('words.title') }}"></section>

        <div class="mt-5 text-center" data-sw-more-wrap hidden>
            <button type="button" class="btn-ghost !bg-white shadow-card" data-sw-more>{{ __('words.load_more') }}</button>
        </div>

        {{-- حالت‌های خالی --}}
        <div class="card mt-4 flex flex-col items-center px-6 py-14 text-center" data-sw-empty hidden>
            <span class="mb-4 grid size-16 place-items-center rounded-full bg-brand-50 text-brand-700"><x-icon name="bookmark" class="size-8" /></span>
            <h2 class="text-base font-extrabold text-brand-900">{{ __('words.empty.title') }}</h2>
            <p class="mt-1.5 max-w-md text-sm leading-7 text-slate-500">{{ __('words.empty.text') }}</p>
            <a href="{{ route('music.index') }}" class="btn-primary mt-5">{{ __('words.empty.cta') }}</a>
        </div>

        <div class="card mt-4 flex flex-col items-center px-6 py-14 text-center" data-sw-noresult hidden>
            <span class="mb-4 grid size-16 place-items-center rounded-full bg-brand-50 text-brand-700"><x-icon name="search" class="size-8" /></span>
            <h2 class="text-base font-extrabold text-brand-900">{{ __('words.noresult.title') }}</h2>
            <p class="mt-1.5 max-w-sm text-sm leading-7 text-slate-500">{{ __('words.noresult.text') }}</p>
            <button type="button" class="btn-ghost mt-5" data-sw-reset>{{ __('words.noresult.reset') }}</button>
        </div>

        {{-- شروع مرور (چسبیده پایین تا در لیست بلند همیشه در دسترس باشد) --}}
        <div class="sw-cta" data-sw-cta hidden>
            <button type="button" class="btn-primary sw-cta__btn" data-sw-review>
                <x-icon name="rewind" class="size-5" />
                <span data-sw-review-label>{{ __('words.review.start') }}</span>
            </button>
        </div>
    </div>

    {{-- پیام «حذف شد + بازگردانی» --}}
    <div class="sw-toast" data-sw-toast role="status" aria-live="polite" hidden>
        <span data-sw-toast-text></span>
        <button type="button" data-sw-undo>{{ __('words.undo') }}</button>
    </div>

    {{-- قالب کارت --}}
    <template data-sw-card-tpl>
        <article class="sw-card" data-sw-card>
            <div class="flex items-start gap-3.5">
                <button type="button" class="sw-thumb" data-c-open tabindex="-1" aria-hidden="true"></button>
                <div class="min-w-0 flex-1" dir="ltr" lang="en">
                    <h3 class="sw-card__word"><bdi data-c="phrase"></bdi> <span class="sw-type" data-c="type"></span></h3>
                    <p class="sw-ipa"><button type="button" class="sw-speak" data-c-speak aria-label="{{ __('words.speak') }}"><x-icon name="volume" class="size-4" /></button><span data-c="ipa"></span></p>
                    <p class="sw-meaning" data-c="meaning"></p>
                </div>
            </div>

            <div class="sw-card__foot">
                <button type="button" class="sw-bm" data-c-remove aria-label="{{ __('words.remove') }}" title="{{ __('words.remove') }}">
                    <x-icon name="bookmark" class="size-5 fill-current" />
                </button>
                <span class="sw-badge" data-c="status"></span>
                <span class="flex-1"></span>
                <button type="button" class="btn-primary sw-more" data-c-open>{{ __('words.more') }}</button>
            </div>
        </article>
    </template>
</div>
@endsection

@push('modals')
{{-- اطلاعات لغت (حالت دیدن) و مرور (حالت review): کشوی کناری (offcanvas)؛ فقط ترجمه‌های فارسی پیش‌فرض تارند --}}
<div id="sw-dialog" class="dialog dialog--offcanvas sw-dialog" data-dialog data-sw-dialog data-mode="view" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sw-phrase">
    <div class="dialog-backdrop" data-dialog-close></div>

    <div class="dialog-panel sw-panel" data-sw-panel>
        <div class="sw-scroll" data-sw-scroll>
        {{-- بالای حالت مرور: پیشرفت --}}
        <div class="sw-rhead" data-sw-rhead>
            <div class="sw-rbar"><i data-sw-rbar></i></div>
            <p class="text-xs font-extrabold text-slate-500" data-sw-rcount></p>
        </div>

        <div data-sw-body>
            <div class="flex items-start gap-4">
                {{-- تصویر لغت (تار نمی‌شود) --}}
                <div class="sw-pic"><span class="block size-full" data-d-img></span></div>

                <div class="min-w-0 flex-1" dir="ltr" lang="en">
                    <h2 id="sw-phrase" class="break-words text-xl font-extrabold leading-8 text-brand-900 md:text-2xl"><bdi data-d="phrase"></bdi></h2>
                    <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        <span data-d="type"></span>
                        <span class="sw-level" data-d="level" hidden></span>
                    </p>
                    <div class="mt-2 space-y-1.5">
                        @foreach (['uk', 'us'] as $acc)
                            <div class="flex items-center gap-2" data-d-pron="{{ $acc }}" hidden>
                                <button type="button" class="wc-speak" data-d-speak="{{ $acc }}" aria-label="{{ __('words.speak') }} ({{ __('words.' . $acc) }})"><x-icon name="volume" class="size-5" /></button>
                                <span class="text-sm text-slate-600" data-d-ipa></span>
                                <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500">{{ $acc }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="button" data-dialog-close class="sw-x -me-2 -mt-1" aria-label="{{ __('ui.close') }}"><x-icon name="x" class="size-5" /></button>
            </div>

            {{-- ترجمه‌ی لغت (تار) --}}
            <div class="mt-4 flex items-center gap-3" data-d-tr-wrap>
                <span class="shrink-0 text-xs font-bold text-slate-500">{{ __('words.translate') }}</span>
                <button type="button" class="sw-b sw-b--text" data-b="translate" aria-pressed="false" aria-label="{{ __('words.see') }}">
                    <span class="sw-b__content text-sm font-extrabold text-brand-900" data-d="translate" dir="auto"></span>
                    <span class="sw-b__veil"><x-icon name="eye-off" class="size-4 sw-off" /><x-icon name="eye" class="size-4 sw-on" /><span data-sw-see>{{ __('words.see') }}</span></span>
                </button>
            </div>

            {{-- تعریف انگلیسی (تار نمی‌شود) --}}
            <p class="mt-4 text-left text-[0.95rem] leading-7 text-slate-700" dir="ltr" lang="en" data-d="meaning"></p>

            {{-- مثال + ترجمه‌ی مثال (تار) --}}
            <div class="mt-4 rounded-2xl bg-slate-50 p-3.5" data-d-ex-wrap>
                <p class="text-xs font-bold text-slate-500">{{ __('words.example') }}</p>
                <p class="mt-1 text-left text-sm font-semibold leading-7 text-brand-900" dir="ltr" lang="en" data-d="example"></p>
                <button type="button" class="sw-b sw-b--block mt-2" data-b="example" aria-pressed="false" aria-label="{{ __('words.see') }}" data-d-extr-wrap>
                    <span class="sw-b__content text-sm leading-7 text-slate-600" dir="auto" data-d="example_tr"></span>
                    <span class="sw-b__veil"><x-icon name="eye-off" class="size-4 sw-off" /><x-icon name="eye" class="size-4 sw-on" /><span data-sw-see>{{ __('words.see') }}</span></span>
                </button>
            </div>

            <dl class="wc-table mt-4" dir="ltr" lang="en">
                <div data-d-row="synonyms"><dt>{{ __('words.synonyms') }}</dt><dd data-d="synonyms"></dd></div>
                <div data-d-row="antonyms"><dt>{{ __('words.antonyms') }}</dt><dd data-d="antonyms"></dd></div>
            </dl>

            <a href="#" class="mt-4 inline-flex items-center gap-2 rounded-full bg-brand-50 px-3.5 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100" data-d-song hidden>
                <x-icon name="music" class="size-4" />
                <span>{{ __('words.from_song') }}: <bdi data-d="song"></bdi></span>
            </a>
        </div>

        {{-- پایان مرور --}}
        <div class="sw-done" data-sw-done hidden>
            <span class="grid size-16 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check-circle" class="size-8" /></span>
            <h3 class="mt-4 text-lg font-extrabold text-brand-900">{{ __('words.review.done_title') }}</h3>
            <p class="mt-1.5 text-sm font-bold leading-7 text-slate-600" data-sw-done-text></p>
        </div>
        </div>{{-- /sw-scroll --}}

        {{-- پایین دیالوگ --}}
        <div class="sw-foot" data-sw-foot>
            {{-- حالت دیدن --}}
            <div class="flex items-center gap-2" data-foot="view">
                <button type="button" class="btn-ghost sw-foot__close" data-dialog-close>{{ __('ui.close') }}</button>
                <button type="button" class="sw-round sw-round--bm" data-d-remove aria-label="{{ __('words.remove') }}" title="{{ __('words.remove') }}"><x-icon name="bookmark" class="size-5 fill-current" /></button>
                <span class="flex-1 text-center text-xs font-extrabold tabular-nums text-slate-400" data-d-pos aria-live="polite"></span>
                <button type="button" class="sw-round" data-d-next aria-label="{{ __('words.next') }}"><x-icon name="chevron-end" class="size-5" /></button>

                <button type="button" class="sw-round" data-d-prev aria-label="{{ __('words.prev') }}"><x-icon name="chevron-start" class="size-5" /></button>


            </div>

            {{-- مرور: قبلی / (نمایش ترجمه ← بلد بودم یا نه) / بعدی --}}
            <div class="flex items-center gap-2" data-foot="review" hidden>
                <button type="button" class="sw-round" data-r-next aria-label="{{ __('words.next') }}"><x-icon name="chevron-end" class="size-5" /></button>

                <div class="min-w-0 flex-1">
                    <div data-r-reveal-wrap>
                        <button type="button" class="btn-primary w-full" data-r-reveal><x-icon name="eye" class="size-5" />{{ __('words.review.reveal') }}</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 " data-r-answer-wrap hidden>
                        <button type="button" class="btn-ghost sw-ans sw-ans--no" data-r-answer="no"><x-icon name="x" class="size-4" />{{ __('words.review.no') }}</button>
                        <button type="button" class="btn-primary sw-ans sw-ans--yes" data-r-answer="yes"><x-icon name="check" class="size-4" />{{ __('words.review.yes') }}</button>
                    </div>
                </div>
                <button type="button" class="sw-round" data-r-prev aria-label="{{ __('words.prev') }}"><x-icon name="chevron-start" class="size-5" /></button>

            </div>
            <div class="grid gap-2.5" data-foot="done" hidden>
                <button type="button" class="btn-primary" data-r-again hidden></button>
                <button type="button" class="btn-ghost" data-dialog-close>{{ __('ui.close') }}</button>
            </div>
        </div>
    </div>
</div>
@endpush
