@extends('layouts.app')

@section('title', __('music.title'))

@php
    $q = $filters['q'] ?? '';
    $filter = $filters['filter'] ?? 'all';
    $sort = $filters['sort'] ?? 'default';
    $hasActiveFilter = $filter !== 'all' || $sort !== 'default';
@endphp

@section('content')
    <div class="page-wrap">

        {{-- عنوان + ابزارها (حالت تمرکز / گزارش خرابی) --}}
        <header class="mb-5 flex flex-col gap-3 md:mb-6 md:flex-row md:items-start md:justify-between md:gap-6">
            <div class="min-w-0">
                <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon name="music" class="size-5" />
                </span>
                    {{ __('music.title') }}
                </h1>
                <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('music.subtitle') }}</p>
            </div>

            <x-page-tools class="shrink-0 flex-wrap" />
        </header>

        {{-- آمار (در حالت تمرکز مخفی می‌شود) --}}
        <section data-focus-hide class="mb-5 grid grid-cols-2 gap-3 md:mb-6 md:gap-4 xl:grid-cols-4" aria-label="{{ __('music.stats.label') }}">
            <x-music.stat-card :label="__('music.stats.completed')" :value="num($stats['completed'])" icon="check-circle" tone="green" />
            <x-music.stat-card :label="__('music.stats.in_progress')" :value="num($stats['in_progress'])" icon="list-music" tone="amber" />
            <x-music.stat-card :label="__('music.stats.points')" :value="num($stats['points'])" icon="award" tone="brand" />
            <x-music.stat-card :label="__('music.stats.highlights')" :value="num($stats['highlights'])" icon="bookmark" tone="violet"
                               :href="url('/words')" :link-text="__('music.stats.view')" />
        </section>

        {{-- نوار ابزار: جستجو + فیلتر + مرتب‌سازی + تب‌های سطح --}}
        <div class="card p-3 md:p-4">
            <form id="music-filters" method="GET" action="{{ route('music.index') }}" role="search">
                <input type="hidden" name="level" value="{{ $level }}">

                <div class="flex gap-2 md:grid md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3">

                    <label class="relative block min-w-0 flex-1 mt-3">
                        <span class="sr-only">{{ __('music.search') }}</span>
                        <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="q" value="{{ $q }}" enterkeyhint="search" autocomplete="off"
                               placeholder="{{ __('music.search') }}" class="field h-12 rounded-full ps-12 pe-4">
                    </label>

                    {{-- فقط موبایل: دکمه‌ی باز کردن شیت فیلتر --}}
                    <button type="button"
                            class="icon-btn relative !size-12 border border-slate-200 !shadow-none md:hidden mt-3"
                            data-dialog-open="#filter-dialog" aria-label="{{ __('music.filters') }}">
                        <x-icon name="sliders" class="size-5" />
                        {{-- نقطه‌ی «فیلتر فعال»: music-list.js بعد از هر تعویض بدون رفرش، آن را نشان/پنهان می‌کند --}}
                        <span data-filter-dot @if (! $hasActiveFilter) hidden @endif class="absolute end-2.5 top-2.5 size-2.5 rounded-full bg-brand-700 ring-2 ring-white"></span>
                    </button>

                    {{-- موبایل: Bottom Sheet | md به بالا: دو ستون inline کنار جستجو --}}
                    <div id="filter-dialog" class="filter-dialog" data-dialog data-no-aria aria-label="{{ __('music.filters') }}">
                        <div class="absolute inset-0 bg-slate-900/50 md:hidden" data-dialog-close></div>

                        <div class="filter-dialog__panel">
                            <div class="dialog-handle md:hidden"></div>
                            <p class="pb-1 text-base font-extrabold text-brand-900 md:hidden">{{ __('music.filters') }}</p>

                            <label class="select-pill mt-0 pt-0">
                                <span class="shrink-0 text-slate-500">{{ __('music.filter_label') }}</span>
                                <select name="filter">
                                    @foreach (['all', 'new', 'in_progress', 'done', 'free'] as $opt)
                                        <option value="{{ $opt }}" @selected($filter === $opt)>{{ __('music.filter.' . $opt) }}</option>
                                    @endforeach
                                </select>
                                <x-icon name="chevron-down" class="chev" />
                            </label>

                            <label class="select-pill mt-0 pt-0">
                                <span class="shrink-0 text-slate-500">{{ __('music.sort_label') }}</span>
                                <select name="sort">
                                    @foreach (['default', 'newest', 'points', 'duration', 'title'] as $opt)
                                        <option value="{{ $opt }}" @selected($sort === $opt)>{{ __('music.sort.' . $opt) }}</option>
                                    @endforeach
                                </select>
                                <x-icon name="chevron-down" class="chev" />
                            </label>

                            <button type="submit" class="btn-primary w-full md:hidden">{{ __('music.apply') }}</button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- تب‌های سطح: لینک واقعی‌اند (بدون جاوااسکریپت هم کار می‌کنند)؛ music-list.js آن‌ها را بدون رفرش می‌کند --}}
            <nav id="music-tabs" class="mt-3 scroll-mt-24 flex divide-x divide-slate-200 overflow-hidden rounded-xl border border-slate-200 rtl:divide-x-reverse md:mt-4" aria-label="{{ __('music.levels_label') }}">
                @foreach (['a', 'b', 'c'] as $l)
                    <a href="{{ route('music.index', array_merge(request()->except(['page', 'level']), ['level' => $l])) }}"
                       class="level-tab" @if ($l === $level) aria-current="page" @endif>
                        {{ __('music.levels.' . $l) }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- لیست آهنگ‌ها + صفحه‌بندی: کل این بخش با تعویض تب/فیلتر بدون رفرش جایگزین می‌شود --}}
        <div id="music-results">
            @if ($songs->count())
                <section id="song-grid" class="mt-4 grid gap-3.5 md:mt-5 md:grid-cols-2 md:gap-4 xl:grid-cols-3" aria-label="{{ __('music.title') }}">
                    @foreach ($songs as $i => $song)
                        <x-music.song-card :song="$song" :subscribed="$subscribed" :index="$i" />
                    @endforeach
                </section>
            @else
                <div class="card mt-4 flex flex-col items-center px-6 py-14 text-center md:mt-5">
            <span class="mb-4 grid size-16 place-items-center rounded-full bg-brand-50 text-brand-700">
                <x-icon name="search" class="size-8" />
            </span>
                    <h2 class="text-base font-extrabold text-brand-900">{{ __('music.empty.title') }}</h2>
                    <p class="mt-1.5 max-w-sm text-sm leading-6 text-slate-500">{{ __('music.empty.text') }}</p>
                    <a href="{{ route('music.index', ['level' => $level]) }}" class="btn-ghost mt-5">{{ __('music.empty.reset') }}</a>
                </div>
            @endif

            {{-- صفحه‌بندی: دسکتاپ/تبلت = شماره‌ها | موبایل = «نمایش بیشتر» --}}
            <div class="mt-6 md:mt-8">
                {{ $songs->onEachSide(1)->links('vendor.pagination.teodel') }}

                @if ($songs->hasMorePages())
                    <div data-load-more-wrap class="md:hidden">
                        <button type="button" data-load-more data-url="{{ $songs->nextPageUrl() }}"
                                class="btn-ghost w-full !bg-white shadow-card">
                            <span>{{ __('music.load_more') }}</span>
                        </button>
                        <p id="load-more-error" hidden class="mt-2 text-center text-sm text-rose-600">{{ __('music.load_error') }}</p>
                    </div>
                @endif
            </div>
        </div>{{-- /#music-results --}}
    </div>
@endsection

@push('modals')
    {{-- اطلاعات موزیک: با jQuery از data-attribute های کارت پر می‌شود --}}
    <div id="info-dialog" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="info-title">
        <div class="dialog-backdrop" data-dialog-close></div>

        <div class="dialog-panel">
            <div class="dialog-handle md:hidden"></div>

            {{-- کاور + نام موزیک + نام خواننده --}}
            <div class="flex items-start gap-4">
                <div data-fill-cover class="size-24 shrink-0 overflow-hidden rounded-2xl bg-slate-100 shadow-card md:size-28"></div>

                <div class="min-w-0 flex-1 pt-1">
                    <h2 id="info-title" class="text-lg font-extrabold leading-7 text-brand-900 md:text-xl"><bdi data-fill="title"></bdi></h2>
                    <p class="mt-1 text-sm text-slate-500 md:text-base"><bdi data-fill="artist"></bdi></p>
                </div>

                <button type="button" data-dialog-close class="-me-2 -mt-1 grid size-10 shrink-0 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="{{ __('ui.close') }}">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            {{-- مشخصات موزیک (هر فیلد از data-attribute کارت پر می‌شود؛ اگر خالی باشد «—» نشان داده می‌شود) --}}
            <dl class="mt-5 grid grid-cols-2 gap-2.5">
                @foreach ([
                    ['level', 'info.level'], ['accent', 'info.accent'],
                    ['year', 'info.year'], ['genre', 'info.genre'],
                    ['wpm', 'info.wpm'], ['duration', 'info.duration'],
                ] as [$key, $label])
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">{{ __('music.' . $label) }}</dt>
                        <dd class="mt-0.5 text-sm font-extrabold text-brand-900"><bdi data-fill="{{ $key }}"></bdi></dd>
                    </div>
                @endforeach

                @foreach ([['topic', 'info.topic'], ['album', 'info.album']] as [$key, $label])
                    <div class="col-span-2 rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">{{ __('music.' . $label) }}</dt>
                        <dd class="mt-0.5 text-sm font-extrabold text-brand-900"><bdi data-fill="{{ $key }}"></bdi></dd>
                    </div>
                @endforeach
            </dl>

            <p data-fill="access" class="mt-4 rounded-xl bg-brand-50/60 p-3.5 text-sm leading-6 text-slate-600"></p>

            <div class="mt-5 grid gap-2.5">
                <a data-fill-start href="#" class="btn-primary">
                    <span data-fill-start-label></span>
                    <x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" />
                </a>
                <a data-fill-upgrade hidden href="{{ url('/subscription') }}" class="btn-ghost !bg-amber-50 !text-amber-700 hover:!bg-amber-100">
                    <x-icon name="sparkles" class="size-4" />
                    {{ __('ui.buy') }}
                </a>
            </div>
        </div>
    </div>
@endpush
