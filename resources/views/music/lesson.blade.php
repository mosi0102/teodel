@extends('layouts.app')

@section('title', __('lesson.title'))

@php
    // آیکون مرحله‌ی «جاری» (دایره‌ی نارنجی)
    $stepIcons = ['play' => 'play', 'lyrics' => 'file-text', 'words' => 'bookmark', 'listening' => 'headphones', 'shadowing' => 'mic', 'karaoke' => 'music', 'repeat' => 'repeat', 'details' => 'info'];
@endphp

@section('content')
{{--
    data-* روی این عنصر را ماژول resources/js/modules/lesson.js می‌خواند تا پیشرفت زنده کار کند.
    completed = تعداد مرحله‌های کاملاً تمام‌شده | fraction = بخشی از مرحله‌ی جاری که انجام شده (۰ تا ۱)
--}}
<div class="page-wrap pb-24 md:pb-0" data-lesson
    data-song="{{ $song['id'] }}" data-step="{{ $step }}" data-total="{{ $total }}"
    data-completed="{{ $completed }}" data-fraction="{{ $fraction }}">

    {{-- عنوان + ابزارها --}}
    <header class="mb-5 flex flex-col gap-3 md:mb-6 md:flex-row md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon name="music" class="size-5" />
                </span>
                {{ __('lesson.title') }}
            </h1>
            <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('lesson.subtitle', ['n' => num($total)]) }}</p>

            {{-- آهنگ جاری --}}
            <p class="mt-2.5 inline-flex max-w-full items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-sm shadow-card">
                <x-icon name="music" class="size-4 shrink-0 text-brand-700" />
                <bdi class="truncate font-bold text-brand-900">{{ $song['title'] }}</bdi>
                <span class="text-slate-300" aria-hidden="true">•</span>
                <bdi class="truncate text-slate-500">{{ $song['artist'] }}</bdi>
            </p>
        </div>

        <x-page-tools class="shrink-0 flex-wrap" />
    </header>

    {{-- نوار مراحل + پیشرفت --}}
    <section class="card p-3.5 md:p-5" aria-label="{{ __('lesson.stepper') }}">
{{--        <div class="mb-4 flex items-center gap-3">--}}
{{--            <span class="text-xs font-bold text-slate-500 sm:text-sm">{{ __('lesson.progress') }}</span>--}}
{{--            <div class="progress-track flex-1" role="progressbar" aria-label="{{ __('lesson.progress') }}"--}}
{{--                aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" data-progress-track>--}}
{{--                <div class="progress-bar" data-progress-bar style="transform: scaleX({{ $percent / 100 }})"></div>--}}
{{--            </div>--}}
{{--            <span class="min-w-10 text-end text-sm font-extrabold text-brand-900" data-progress-text>{{ num($percent) }}%</span>--}}
{{--        </div>--}}

        {{-- موبایل: اسکرول افقی (مرحله‌ی جاری وسط می‌آید) | md به بالا: همه‌ی ۸ مرحله در یک ردیف --}}
        <div class="no-scrollbar -mx-1 overflow-x-auto px-1 pb-1">
            <ol class="flex min-w-max md:min-w-0">
                @foreach ($steps as $s)
                    <li class="step-item w-[4.75rem] shrink-0 sm:w-24 md:w-auto md:min-w-0 md:flex-1" data-step-item="{{ $s['n'] }}" data-state="{{ $s['state'] }}">
                        @if (! $loop->last)
                            <span class="step-line" aria-hidden="true"></span>
                        @endif

                        <a href="{{ $s['href'] }}" class="step-link block rounded-xl py-1" @if ($s['n'] === $step) aria-current="step" @endif>
                            <span class="step-dot">
                                <x-icon name="check" class="size-5" data-dot="check" />
                                <x-icon name="lock" class="size-5" data-dot="lock" />
                                <x-icon :name="$stepIcons[$s['key']]" class="size-5" data-dot="icon" />
                                <span data-dot="num">{{ num($s['n']) }}</span>
                            </span>
                            <span class="step-label">{{ __('lesson.steps.' . $s['key'] . '.label') }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- عنوان مرحله + دکمه‌های قبل/بعد --}}
    <div class="mt-5 md:mt-6 md:flex md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h2 id="step-title" class="text-lg font-extrabold text-brand-900 md:text-xl">{{ __('lesson.ordinal.' . $step) }}</h2>
            <p class="mt-1.5 max-w-3xl text-sm leading-7 text-slate-500">{{ __('lesson.steps.' . $stepKey . '.desc') }}</p>
        </div>

        <div class="step-actions" data-focus-keep>
            @if ($prevUrl)
                <a href="{{ $prevUrl }}" rel="prev" class="btn-outline flex-1 md:flex-none md:min-w-32">
                    <x-icon name="chevron-start" class="size-4 rtl:-scale-x-100" />
                    {{ __('lesson.prev') }}
                </a>
            @else
                <span class="btn-outline flex-1 cursor-not-allowed opacity-50 md:flex-none md:min-w-32" aria-disabled="true">
                    <x-icon name="chevron-start" class="size-4 rtl:-scale-x-100" />
                    {{ __('lesson.prev') }}
                </span>
            @endif

            <a href="{{ $nextUrl }}" @if ($step < $total) rel="next" @endif class="btn-primary !rounded-full flex-[1.4] md:flex-none md:min-w-32">
                {{ $step < $total ? __('lesson.next') : __('lesson.finish') }}
                <x-icon :name="$step < $total ? 'chevron-end' : 'check'" class="size-4 {{ $step < $total ? 'rtl:-scale-x-100' : '' }}" />
            </a>
        </div>
    </div>

    {{-- محتوای مرحله: هر مرحله فایل جدا دارد → resources/views/music/steps/ --}}
    <section class="card mt-4 min-h-[22rem] animate-rise p-4 md:mt-5 md:min-h-[32rem] md:p-6" aria-labelledby="step-title">
        @if ($locked)
            @include('music.steps._locked')
        @else
            @include('music.steps.' . $stepKey)
        @endif
    </section>
</div>
@endsection
