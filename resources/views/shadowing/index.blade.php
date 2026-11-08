@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/music.js')
@endpush

@section('title', __('shadowing.title'))

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
                    <x-icon name="repeat" class="size-5" />
                </span>
                    {{ __('shadowing.title') }}
                </h1>
                <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('shadowing.subtitle') }}</p>
            </div>

            <x-page-tools class="shrink-0 flex-wrap" />
        </header>

        {{-- آمار (در حالت تمرکز مخفی می‌شود) --}}
        <section data-focus-hide class="mb-5 grid grid-cols-2 gap-3 md:mb-6 md:gap-4 xl:grid-cols-4" aria-label="{{ __('shadowing.stats.label') }}">
            <x-music.stat-card :label="__('shadowing.stats.completed')" :value="num($stats['completed'])" icon="check-circle" tone="green" />
            <x-music.stat-card :label="__('shadowing.stats.in_progress')" :value="num($stats['in_progress'])" icon="list-music" tone="amber" />
            <x-music.stat-card :label="__('shadowing.stats.points')" :value="num($stats['points'])" icon="award" tone="brand" />
            <x-music.stat-card :label="__('shadowing.stats.highlights')" :value="num($stats['highlights'])" icon="bookmark" tone="violet"
                               :href="url('/words')" :link-text="__('shadowing.stats.view')" />
        </section>

        {{-- نوار ابزار: جستجو + فیلتر + مرتب‌سازی + تب‌های سطح --}}
        <div class="card p-3 md:p-4">
            <form id="music-filters" method="GET" action="{{ route('shadowing.index') }}" role="search">
                <input type="hidden" name="level" value="{{ $level }}">

                <div class="flex gap-2 md:grid md:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3">

                    <label class="relative block min-w-0 flex-1 mt-3">
                        <span class="sr-only">{{ __('shadowing.search') }}</span>
                        <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="q" value="{{ $q }}" enterkeyhint="search" autocomplete="off"
                               placeholder="{{ __('shadowing.search') }}" class="field h-12 rounded-full ps-12 pe-4">
                    </label>

                    {{-- فقط موبایل: دکمه‌ی باز کردن شیت فیلتر --}}
                    <button type="button"
                            class="icon-btn relative !size-12 border border-slate-200 !shadow-none md:hidden mt-3"
                            data-dialog-open="#filter-dialog" aria-label="{{ __('shadowing.filters') }}">
                        <x-icon name="sliders" class="size-5" />
                        {{-- نقطه‌ی «فیلتر فعال»: music-list.js بعد از هر تعویض بدون رفرش، آن را نشان/پنهان می‌کند --}}
                        <span data-filter-dot @if (! $hasActiveFilter) hidden @endif class="absolute end-2.5 top-2.5 size-2.5 rounded-full bg-brand-700 ring-2 ring-white"></span>
                    </button>

                    {{-- موبایل: Bottom Sheet | md به بالا: دو ستون inline کنار جستجو --}}
                    <div id="filter-dialog" class="filter-dialog" data-dialog data-no-aria aria-label="{{ __('shadowing.filters') }}">
                        <div class="absolute inset-0 bg-slate-900/50 md:hidden" data-dialog-close></div>

                        <div class="filter-dialog__panel">
                            <div class="dialog-handle md:hidden"></div>
                            <p class="pb-1 text-base font-extrabold text-brand-900 md:hidden">{{ __('shadowing.filters') }}</p>

                            <label class="select-pill mt-0 pt-0">
                                <span class="shrink-0 text-slate-500">{{ __('shadowing.filter_label') }}</span>
                                <select name="filter">
                                    @foreach (['all', 'new', 'in_progress', 'done', 'free'] as $opt)
                                        <option value="{{ $opt }}" @selected($filter === $opt)>{{ __('shadowing.filter.' . $opt) }}</option>
                                    @endforeach
                                </select>
                                <x-icon name="chevron-down" class="chev" />
                            </label>

                            <label class="select-pill mt-0 pt-0">
                                <span class="shrink-0 text-slate-500">{{ __('shadowing.sort_label') }}</span>
                                <select name="sort">
                                    @foreach (['default', 'newest', 'title'] as $opt)
                                        <option value="{{ $opt }}" @selected($sort === $opt)>{{ __('shadowing.sort.' . $opt) }}</option>
                                    @endforeach
                                </select>
                                <x-icon name="chevron-down" class="chev" />
                            </label>

                            <button type="submit" class="btn-primary w-full md:hidden">{{ __('shadowing.apply') }}</button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- تب‌های سطح: لینک واقعی‌اند (بدون جاوااسکریپت هم کار می‌کنند)؛ music-list.js آن‌ها را بدون رفرش می‌کند --}}
            <nav id="music-tabs" class="mt-3 scroll-mt-24 flex divide-x divide-slate-200 overflow-hidden rounded-xl border border-slate-200 rtl:divide-x-reverse md:mt-4" aria-label="{{ __('shadowing.levels_label') }}">
                @foreach (['all', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'] as $l)
                    <a href="{{ route('shadowing.index', array_merge(request()->except(['page', 'level']), ['level' => $l])) }}"
                       class="level-tab" @if ($l === $level) aria-current="page" @endif>
                        {{ $l === 'all' ? __('shadowing.levels.all') : strtoupper($l) }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- لیست پادکست‌ها + صفحه‌بندی: کل این بخش با تعویض تب/فیلتر بدون رفرش جایگزین می‌شود --}}
        <div id="music-results">
            @if ($topics->count())
                <section id="song-grid" class="mt-4 grid gap-3.5 md:mt-5 md:grid-cols-2 md:gap-4 xl:grid-cols-3" aria-label="{{ __('shadowing.title') }}">
                    @foreach ($topics as $i => $song)
                        <x-shadowing.topic-card :topic="$song" :subscribed="$subscribed" :index="$i" :level="$level" :user-level="$userLevel" />
                        
                    @endforeach
                </section>
            @else
                <div class="card mt-4 flex flex-col items-center px-6 py-14 text-center md:mt-5">
            <span class="mb-4 grid size-16 place-items-center rounded-full bg-brand-50 text-brand-700">
                <x-icon name="search" class="size-8" />
            </span>
                    <h2 class="text-base font-extrabold text-brand-900">{{ __('shadowing.empty.title') }}</h2>
                    <p class="mt-1.5 max-w-sm text-sm leading-6 text-slate-500">{{ __('shadowing.empty.text') }}</p>
                    <a href="{{ route('shadowing.index', ['level' => $level]) }}" class="btn-ghost mt-5">{{ __('shadowing.empty.reset') }}</a>
                </div>
            @endif

            {{-- صفحه‌بندی: دسکتاپ/تبلت = شماره‌ها | موبایل = «نمایش بیشتر» --}}
            <div class="mt-6 md:mt-8">
                {{ $topics->onEachSide(1)->links('vendor.pagination.teodel') }}

                @if ($topics->hasMorePages())
                    <div data-load-more-wrap class="md:hidden">
                        <button type="button" data-load-more data-url="{{ $topics->nextPageUrl() }}"
                                class="btn-ghost w-full !bg-white shadow-card">
                            <span>{{ __('shadowing.load_more') }}</span>
                        </button>
                        <p id="load-more-error" hidden class="mt-2 text-center text-sm text-rose-600">{{ __('shadowing.load_error') }}</p>
                    </div>
                @endif
            </div>
        </div>{{-- /#music-results --}}
    </div>
@endsection
