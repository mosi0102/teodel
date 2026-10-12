@extends('layouts.app')

@section('title', __('lesson.title'))

@php
    // آیکون مرحله‌ی «جاری» (دایره‌ی نارنجی)
    $stepIcons = ['play' => 'play', 'lyrics' => 'file-text', 'words' => 'bookmark', 'listening' => 'headphones', 'shadowing' => 'mic', 'karaoke' => 'music', 'repeat' => 'repeat', 'details' => 'info'];
    $isLast = $step >= $total;
@endphp

@section('content')
{{--
    صفحه‌ی آموزش تک‌صفحه‌ای است: محتوای هر ۸ مرحله با @include داخل همین صفحه می‌آید
    و تعویض مرحله با جاوااسکریپت (resources/js/modules/lesson.js) انجام می‌شود، بدون رفرش.
    آدرس مرورگر با هر مرحله عوض می‌شود تا لینک مستقیم و دکمه‌ی Back کار کنند.

    data-completed = تعداد مرحله‌های کاملاً تمام‌شده | data-fraction = بخشی از مرحله‌ی بعدی که انجام شده (۰ تا ۱)
--}}
<div class="page-wrap pb-24 md:pb-0" data-lesson
    data-song="{{ $song['id'] }}" data-step="{{ $step }}" data-total="{{ $total }}"
    data-completed="{{ $completed }}" data-fraction="{{ $fraction }}"
    data-finish-url="{{ $finishUrl }}">

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

    {{-- نوار مراحل (موبایل: اسکرول افقی و مرحله‌ی جاری وسط | md به بالا: همه‌ی ۸ مرحله در یک ردیف) --}}
    <section id="lesson-stepper" class="card scroll-mt-20 p-3.5 md:p-5" aria-label="{{ __('lesson.stepper') }}">
        <div class="no-scrollbar -mx-1 overflow-x-auto px-1 pb-1">
            <ol class="flex min-w-max md:min-w-0">
                @foreach ($steps as $s)
                    <li class="step-item w-[4.75rem] shrink-0 sm:w-24 md:w-auto md:min-w-0 md:flex-1"
                        data-step-item="{{ $s['n'] }}" data-state="{{ $s['state'] }}" @if ($s['locked']) data-locked="1" @endif
                        data-title="{{ __('lesson.ordinal.' . $s['n']) }}" data-desc="{{ __('lesson.steps.' . $s['key'] . '.desc') }}">
                        @if (! $loop->last)
                            <span class="step-line" aria-hidden="true"></span>
                        @endif

                        <a href="{{ $s['href'] }}" data-step-link class="step-link block rounded-xl py-1" @if ($s['n'] === $step) aria-current="step" @endif>
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

    {{-- عنوان مرحله + دکمه‌های قبل/بعد (عنوان و توضیح با جاوااسکریپت عوض می‌شود) --}}
    <div class="mt-5 md:mt-6 md:flex md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h2 id="step-title" class="text-lg font-extrabold text-brand-900 md:text-xl">{{ __('lesson.ordinal.' . $step) }}</h2>
            <p id="step-desc" class="mt-1.5 max-w-3xl text-sm leading-7 text-slate-500">{{ __('lesson.steps.' . $stepKey . '.desc') }}</p>
            {{-- اعلام تغییر مرحله به صفحه‌خوان‌ها --}}
            <p id="step-announce" class="sr-only" aria-live="polite"></p>
        </div>

        <div class="step-actions" data-label-next="{{ __('lesson.next') }}" data-label-finish="{{ __('lesson.finish') }}">
            <a @if ($prevUrl) href="{{ $prevUrl }}" @endif rel="prev" data-step-prev
                class="btn-outline flex-1 md:min-w-32 md:flex-none {{ $prevUrl ? '' : 'is-disabled' }}">
                <x-icon name="chevron-start" class="size-4 rtl:-scale-x-100" />
                {{ __('lesson.prev') }}
            </a>

            <a href="{{ $nextUrl }}" rel="next" data-step-next class="btn-primary !rounded-full flex-[1.4] md:min-w-32 md:flex-none">
                <span data-next-label>{{ $isLast ? __('lesson.finish') : __('lesson.next') }}</span>
                <x-icon name="chevron-end" class="size-4 rtl:-scale-x-100" data-next-icon="chevron" :hidden="$isLast" />
                <x-icon name="check" class="size-4" data-next-icon="check" :hidden="! $isLast" />
            </a>
        </div>
    </div>

    {{-- محتوای مرحله‌ها: هر مرحله فایل جدا دارد (resources/views/music/steps) و همه include می‌شوند.
         فقط پنل مرحله‌ی فعال دیده می‌شود. مرحله‌ی قفل‌شده به‌جای محتوای واقعی، پیام اشتراک می‌گیرد
         (محتوای پولی اصلاً داخل HTML نمی‌آید). --}}
    <section id="step-content" class="card mt-4 min-h-[22rem] p-4 md:mt-5 md:min-h-[32rem] md:p-6" aria-labelledby="step-title">
        @foreach ($steps as $s)
            <div data-step-panel="{{ $s['n'] }}" data-step-key="{{ $s['key'] }}" @if ($s['n'] !== $step) hidden @endif>
                @if ($s['locked'])
                    @include('music.steps._locked', ['step' => $s['n']])
                @else
                    @include('music.steps.' . $s['key'], ['step' => $s['n']])
                @endif
            </div>
        @endforeach
    </section>
</div>
@endsection
