@extends('layouts.app')

@section('title', __('placement.title'))

{{--
    آزمون تعیین سطح: چهار حالت در یک صفحه — معرفی ← انتخاب آزمون ← آزمون ← نتیجه/مرور.
    رفتار: resources/js/modules/level-test.js — قرارداد داده و رویدادها: app/Support/PlacementTest.php و README.
    ترجمه‌ی فارسی ندارد که تار شود؛ خط فارسیِ بالای سؤال دستور آزمون است (نه ترجمه) و همیشه دیده می‌شود.
--}}
@php
    $levels = \App\Support\PlacementTest::LEVELS;
    $icons = [
        'vocab' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/><path d="M9 8h7M9 12h5"/>',
        'grammar' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'listening' => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="3" y="14" width="4.5" height="7" rx="1.8"/><rect x="16.5" y="14" width="4.5" height="7" rx="1.8"/>',
    ];
    $chev = '<svg viewBox="0 0 24 24" class="size-5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>';
    $chevBack = '<svg viewBox="0 0 24 24" class="size-5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>';
@endphp
@section('content')
<div class="page-wrap" data-pt
    data-bank='@json($bank ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-results='@json($results ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-count="{{ $count ?? 15 }}"
    @isset($startUrl) data-start-url="{{ $startUrl }}" @endisset
    @isset($finishUrl) data-finish-url="{{ $finishUrl }}" @endisset
    data-t='@json(__('placement'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>

    {{-- ───────────── ۱) معرفی ───────────── --}}
    <section data-pt-stage="intro">
        <header class="mb-5">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                </span>
                {{ __('placement.title') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('placement.subtitle') }}</p>
        </header>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="card p-5 md:p-7">
                <h2 class="text-lg font-extrabold text-brand-900">{{ __('placement.intro.heading') }}</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('placement.intro.text') }}</p>

                <ol class="pt-steps">
                    @foreach (__('placement.intro.steps') as $s)
                        <li>
                            <span class="pt-steps__n" aria-hidden="true">{{ num($loop->iteration) }}</span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-slate-800">{{ $s['title'] }}</h3>
                                <p class="mt-1 text-sm leading-7 text-slate-500">{{ $s['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <button type="button" class="btn-primary mt-6 w-full sm:w-auto sm:min-w-56" data-pt-go="choose">
                    {{ __('placement.intro.start') }} {!! $chev !!}
                </button>
            </div>

            <aside class="grid gap-4">
                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-brand-900">{{ __('placement.intro.tips_title') }}</h2>
                    <ul class="mt-3 grid gap-2.5">
                        @foreach (__('placement.intro.tips') as $tip)
                            <li class="flex gap-2.5 text-sm leading-7 text-slate-600">
                                <svg viewBox="0 0 24 24" class="mt-1.5 size-4 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                <span>{{ $tip }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-brand-900">{{ __('placement.intro.scale') }}</h2>
                    <ul class="pt-scale">
                        @foreach ($levels as $lv)
                            <li data-pt-scale="{{ $lv }}">
                                <b dir="ltr">{{ strtoupper($lv) }}</b>
                                <span>{{ __('placement.levels.' . $lv) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    {{-- ───────────── ۲) انتخاب آزمون ───────────── --}}
    <section data-pt-stage="choose" hidden>
        <header class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div class="min-w-0">
                <button type="button" class="pt-back" data-pt-go="intro">{!! $chevBack !!} {{ __('placement.choose.back') }}</button>
                <h1 class="mt-2 text-xl font-extrabold text-brand-900 md:text-2xl">{{ __('placement.choose.heading') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('placement.choose.text') }}</p>
            </div>
            <div class="pt-overall" data-pt-overall>
                <span class="text-xs font-bold text-slate-500">{{ __('placement.choose.overall') }}</span>
                <b class="pt-overall__lv" dir="ltr" data-pt-overall-level>—</b>
                <span class="text-xs text-slate-500" data-pt-overall-text>{{ __('placement.choose.overall_none') }}</span>
            </div>
        </header>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($sections as $s)
                @php $k = $s['key']; @endphp
                <article class="pt-card" data-pt-card="{{ $k }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="pt-card__ic">
                            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$k] !!}</svg>
                        </span>
                        <span class="pt-card__lv" dir="ltr" data-pt-card-level hidden></span>
                    </div>
                    <h2 class="mt-4 text-lg font-extrabold text-brand-900">{{ __('placement.sections.' . $k . '.title') }}</h2>
                    <p class="mt-1.5 text-sm leading-7 text-slate-500">{{ __('placement.sections.' . $k . '.text') }}</p>
                    <p class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium text-slate-400">
                        <span data-pt-card-count></span><span aria-hidden="true">·</span><span data-pt-card-min></span>
                    </p>

                    <div class="pt-card__score" data-pt-card-score>
                        <div class="pt-meter" aria-hidden="true"><i data-pt-card-bar></i></div>
                        <p class="mt-2 flex min-h-6 items-center justify-between gap-2 text-xs">
                            <span class="font-bold text-slate-500" data-pt-card-label></span>
                            <b class="text-base text-brand-800" data-pt-card-num></b>
                        </p>
                    </div>

                    <button type="button" class="btn-primary mt-4 w-full" data-pt-start="{{ $k }}"><span data-pt-card-cta></span></button>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ───────────── ۳) آزمون ───────────── --}}
    <section data-pt-stage="exam" hidden>
        <div class="pt-exam">
            <header class="pt-exam__head">
                <button type="button" class="pt-back" data-pt-exit>{!! $chevBack !!} {{ __('placement.exam.exit') }}</button>
                <h1 class="min-w-0 flex-1 truncate text-center text-sm font-extrabold text-brand-900 md:text-base" data-pt-exam-title></h1>
                <p class="shrink-0 text-xs font-bold text-slate-500" data-pt-exam-count aria-live="polite"></p>
            </header>
            <div class="pt-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-pt-progress><i data-pt-progress-bar></i></div>

            <div class="pt-exam__body">
                {{-- سؤال --}}
                <article class="pt-q card" data-pt-q aria-live="polite">
                    <p class="pt-q__type" data-pt-q-type></p>
                    <p class="pt-q__fa" data-pt-q-fa dir="rtl" lang="fa"></p>

                    {{-- پخش‌کننده‌ی صوت (فقط در آزمون شنیداری) --}}
                    <div class="pt-audio" data-pt-audio hidden role="group" aria-label="{{ __('placement.audio.label') }}">
                        <div class="flex items-center gap-3">
                            <button type="button" class="pt-audio__play" data-pt-play aria-label="{{ __('placement.audio.play') }}">
                                <svg data-ic="play" viewBox="0 0 24 24" class="size-6" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.4-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
                                <svg data-ic="pause" viewBox="0 0 24 24" class="size-6" fill="currentColor" aria-hidden="true" hidden><rect x="6" y="5" width="4.2" height="14" rx="1.2"/><rect x="13.8" y="5" width="4.2" height="14" rx="1.2"/></svg>
                            </button>
                            <div class="min-w-0 flex-1">
                                <input type="range" class="pt-range" min="0" max="1000" value="0" step="1" data-pt-seek aria-label="{{ __('placement.audio.seek') }}" dir="ltr">
                                <p class="mt-1 flex justify-between text-[0.7rem] font-bold text-slate-400" dir="ltr"><span data-pt-time>0:00</span><span data-pt-dur>0:00</span></p>
                            </div>
                        </div>
                        <div class="pt-audio__row">
                            <label class="pt-audio__vol">
                                <button type="button" class="pt-audio__mute" data-pt-mute aria-label="{{ __('placement.audio.mute') }}">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path data-ic="wave" d="M15.5 8.5a5 5 0 0 1 0 7M18.5 6a9 9 0 0 1 0 12"/><path data-ic="x" d="m16 9 5 6m0-6-5 6" hidden/></svg>
                                </button>
                                <span class="sr-only">{{ __('placement.audio.volume') }}</span>
                                <input type="range" class="pt-range" min="0" max="100" value="100" step="1" data-pt-vol dir="ltr" aria-label="{{ __('placement.audio.volume') }}">
                            </label>
                            <div class="pt-speed" role="radiogroup" aria-label="{{ __('placement.audio.speed') }}" dir="ltr">
                                @foreach ([0.75, 1, 1.25, 1.5] as $sp)
                                    <button type="button" role="radio" aria-checked="false" data-pt-speed="{{ $sp }}">{{ $sp }}×</button>
                                @endforeach
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400" data-pt-audio-note>{{ __('placement.audio.hint') }}</p>
                    </div>

                    <h2 class="pt-q__text" data-pt-q-text dir="ltr" lang="en"></h2>

                    <div class="pt-opts" role="radiogroup" data-pt-opts dir="ltr"></div>
                </article>

                {{-- کناره: شمارنده و فهرست سؤال‌ها --}}
                <aside class="pt-side card">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="pt-stat"><b data-pt-n-done>0</b><span>{{ __('placement.exam.answered') }}</span></div>
                        <div class="pt-stat"><b data-pt-n-left>0</b><span>{{ __('placement.exam.remaining') }}</span></div>
                    </div>
                    <h2 class="mb-2 mt-4 hidden text-xs font-extrabold text-slate-500 md:block">{{ __('placement.exam.questions') }}</h2>
                    <div class="pt-dots" data-pt-dots></div>
                    <ul class="mt-3 hidden gap-1.5 text-[0.7rem] font-medium text-slate-500 md:grid">
                        <li class="flex items-center gap-2"><i class="pt-dot is-current !size-3 !min-h-0 !min-w-0 !p-0" aria-hidden="true"></i>{{ __('placement.exam.legend_current') }}</li>
                        <li class="flex items-center gap-2"><i class="pt-dot is-done !size-3 !min-h-0 !min-w-0 !p-0" aria-hidden="true"></i>{{ __('placement.exam.legend_done') }}</li>
                        <li class="flex items-center gap-2"><i class="pt-dot !size-3 !min-h-0 !min-w-0 !p-0" aria-hidden="true"></i>{{ __('placement.exam.legend_todo') }}</li>
                    </ul>
                    <button type="button" class="btn-outline mt-4 hidden w-full md:inline-flex" data-pt-finish>{{ __('placement.exam.finish') }}</button>
                    <p class="mt-3 hidden text-center text-[0.7rem] text-slate-400 md:block">{{ __('placement.exam.saved') }}</p>
                </aside>
            </div>

            {{-- نوار پایین (چسبنده) --}}
            <footer class="pt-bar">
                <button type="button" class="btn-ghost pt-bar__prev" data-pt-prev>{!! $chevBack !!}<span>{{ __('placement.exam.prev') }}</span></button>
                <button type="button" class="btn-outline pt-bar__fin md:hidden" data-pt-finish>{{ __('placement.exam.finish') }}</button>
                <button type="button" class="btn-primary pt-bar__next" data-pt-next><span data-pt-next-label>{{ __('placement.exam.next') }}</span>{!! $chev !!}</button>
            </footer>
        </div>
    </section>

    {{-- ───────────── ۴) نتیجه ───────────── --}}
    <section data-pt-stage="result" hidden>
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="card p-5 text-center md:p-8">
                <p class="text-sm font-extrabold text-slate-500" data-pt-r-title></p>
                <div class="pt-ring" data-pt-ring style="--p:0">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="pt-ring__bg"/><circle cx="60" cy="60" r="52" class="pt-ring__fg" pathLength="100"/></svg>
                    <div class="pt-ring__in">
                        <b data-pt-r-score>0</b>
                        <span class="text-xs font-bold text-slate-400">{{ __('placement.result.score') }}</span>
                    </div>
                </div>
                <p class="mt-4 text-sm font-bold text-slate-600" data-pt-r-correct></p>
                <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('placement.result.level') }}</span>
                    <b class="rounded-lg bg-brand-700 px-2.5 py-0.5 text-sm text-white" dir="ltr" data-pt-r-level></b>
                    <span class="text-sm font-bold text-brand-800" data-pt-r-level-name></span>
                </div>
                <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-slate-500" data-pt-r-msg></p>

                <div class="mx-auto mt-5 max-w-md rounded-2xl bg-slate-50 p-4 text-start" data-pt-r-overall hidden>
                    <p class="text-xs font-bold text-slate-500">{{ __('placement.result.overall_updated') }}
                        <b class="ms-1 rounded-md bg-brand-700 px-2 py-0.5 text-white" dir="ltr" data-pt-r-overall-level></b>
                    </p>
                    <p class="mt-1.5 text-xs leading-6 text-slate-400" data-pt-r-overall-note></p>
                </div>

                <div class="mt-6 flex flex-col gap-2.5 sm:flex-row sm:justify-center">
                    <button type="button" class="btn-primary sm:min-w-44" data-pt-review-toggle aria-expanded="false" data-t-show="{{ __('placement.result.review') }}" data-t-hide="{{ __('placement.result.hide_review') }}">{{ __('placement.result.review') }}</button>
                    <button type="button" class="btn-outline" data-pt-go="choose">{{ __('placement.result.to_tests') }}</button>
                    <button type="button" class="btn-ghost" data-pt-retake>{{ __('placement.result.retake') }}</button>
                </div>
            </div>

            <aside class="card p-5">
                <h2 class="text-sm font-extrabold text-brand-900">{{ __('placement.result.by_level') }}</h2>
                <ul class="pt-bylevel" data-pt-bylevel></ul>
                <p class="mt-4 text-xs leading-6 text-slate-400">{{ __('placement.result.level_note') }}</p>
            </aside>
        </div>

        {{-- مرور جواب‌ها (فقط بعد از پایان آزمون) --}}
        <div class="mt-4" data-pt-review hidden>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-brand-900">{{ __('placement.result.review_title') }}</h2>
                <div class="flex gap-2" role="group" data-pt-filter>
                    <button type="button" class="chip-btn" aria-pressed="true" data-f="all">{{ __('placement.result.all') }}</button>
                    <button type="button" class="chip-btn" aria-pressed="false" data-f="wrong">{{ __('placement.result.wrong') }}</button>
                </div>
            </div>
            <div class="grid gap-3" data-pt-review-list></div>
            <p class="card p-5 text-center text-sm font-bold text-emerald-700" data-pt-review-empty hidden>{{ __('placement.result.no_wrong') }}</p>
        </div>
    </section>

    <div class="pf-toast" data-pt-toast role="status" hidden></div>
</div>
@endsection

@push('modals')
{{-- تأیید پایان آزمون --}}
<div id="pt-finish-dialog" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pt-fin-title">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="pt-fin-title" class="text-lg font-extrabold text-brand-900">{{ __('placement.confirm_finish.title') }}</h2>
        <p class="mt-3 text-sm leading-7 text-slate-600" data-pt-fin-text></p>
        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('placement.confirm_finish.back') }}</button>
            <button type="button" class="btn-primary" data-pt-fin-ok>{{ __('placement.confirm_finish.ok') }}</button>
        </div>
    </div>
</div>
{{-- تأیید خروج --}}
<div id="pt-exit-dialog" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pt-exit-title">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="pt-exit-title" class="text-lg font-extrabold text-brand-900">{{ __('placement.confirm_exit.title') }}</h2>
        <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('placement.confirm_exit.text') }}</p>
        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('placement.confirm_exit.back') }}</button>
            <button type="button" class="btn-primary" data-pt-exit-ok>{{ __('placement.confirm_exit.ok') }}</button>
        </div>
    </div>
</div>
@endpush
