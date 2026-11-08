@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/music.js')
@endpush

@section('title', $series['title'])

{{--
    صفحه‌ی یک پادکست (سری): اطلاعات + لیست اپیزودها. مدت هر اپیزود درشت نشان داده می‌شود تا کاربر انتخاب کند.
    قرارداد داده: $series (Podcast::series()[i]) با episodes[] (هر اپیزود شکل «آهنگ» + number, duration, completed_steps, current_step_fraction, is_free)
    🔌 «پیشنهادی» را ادمین روی سری (و در صورت نیاز روی اپیزود: recommended) تعیین می‌کند.
--}}
@php
    $limitedSeries = ! $subscribed && ! $series['is_free'];
@endphp

@section('content')
    <div class="page-wrap">
        <a href="{{ route('podcast.index', ['level' => $series['level']]) }}" class="mb-4 inline-flex min-h-10 items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-brand-700">
            <x-icon name="arrow-end" class="size-4 -scale-x-100 rtl:scale-x-100" />
            {{ __('podcast.back') }}
        </a>

        {{-- سربرگ سری --}}
        <header class="card flex flex-col gap-4 p-4 md:flex-row md:items-center md:gap-6 md:p-6">
            <div class="size-24 shrink-0 overflow-hidden rounded-2xl bg-slate-100 shadow-card md:size-32">
                <img src="{{ $series['cover'] }}" alt="" class="size-full object-cover" loading="lazy" decoding="async">
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($series['recommended'])<span class="pc-reco !static"><x-icon name="star" class="size-3.5" />{{ __('podcast.card.recommended') }}</span>@endif
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700">{{ __('podcast.levels.' . $series['level']) }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600"><bdi>{{ $series['category'] }}</bdi></span>
                </div>
                <h1 class="mt-2 text-xl font-extrabold text-brand-900 md:text-2xl"><bdi>{{ $series['title'] }}</bdi></h1>
                <p class="mt-1 text-sm text-slate-500"><bdi>{{ $series['host'] }}</bdi></p>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600" dir="auto">{{ $series['description'] }}</p>
            </div>
            <dl class="grid shrink-0 grid-cols-3 gap-2 md:w-72 md:grid-cols-1">
                <div class="pc-stat"><span class="pc-stat__ic"><x-icon name="list-music" class="size-5" /></span><div><dt>{{ __('podcast.card.episodes') }}</dt><dd>{{ __('podcast.card.episodes_value', ['n' => num($series['episodes_count'])]) }}</dd></div></div>
                <div class="pc-stat"><span class="pc-stat__ic"><x-icon name="timer" class="size-5" /></span><div><dt>{{ __('podcast.card.total') }}</dt><dd>{{ \App\Support\Podcast::fmt($series['total_seconds']) }}</dd></div></div>
                <div class="pc-stat"><span class="pc-stat__ic"><x-icon name="check-circle" class="size-5" /></span><div><dt>{{ __('podcast.series.done') }}</dt><dd>{{ num($series['episodes_done']) }}/{{ num($series['episodes_count']) }}</dd></div></div>
            </dl>
        </header>

        {{-- لیست اپیزودها --}}
        <h2 class="mb-3 mt-6 text-base font-extrabold text-brand-900 md:text-lg">{{ __('podcast.series.episodes') }}</h2>
        <ol class="grid gap-3">
            @foreach ($series['episodes'] as $ep)
                @php
                    $locked = $limitedSeries;
                    $total = (int) $ep['total_steps'];
                    $done = (int) $ep['completed_steps'];
                    $pct = \App\Support\Progress::percent($done, (float) $ep['current_step_fraction'], $total);
                    $state = $pct >= 100 ? 'done' : ($pct > 0 ? 'in_progress' : 'new');
                    $href = route('podcast.show', ['id' => $ep['id']]);
                @endphp
                <li class="pc-ep" data-status="{{ $state }}">
                    <span class="pc-ep__n">{{ num($ep['number']) }}</span>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-[15px] font-bold text-brand-900"><bdi>{{ $ep['title'] }}</bdi></h3>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            <span class="pc-ep__time"><x-icon name="timer" class="size-4" />{{ num($ep['duration']) }}</span>
                            <span>{{ __('podcast.series.steps_done', ['n' => num(min($done, $total)), 'total' => num($total)]) }}</span>
                            @if ($state === 'done')<span class="font-bold text-emerald-600">{{ __('podcast.series.finished') }}</span>@endif
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $state === 'done' ? 'bg-emerald-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div></div>
                    </div>
                    <a href="{{ $locked ? url('/subscription') : $href }}" class="btn-ghost !min-h-10 shrink-0 !px-4 !text-xs">
                        @if ($locked)<x-icon name="lock" class="size-4" />@endif
                        {{ $locked ? __('podcast.series.subscribe') : ($state === 'new' ? __('podcast.cta.start') : ($state === 'done' ? __('podcast.cta.review') : __('podcast.cta.continue'))) }}
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
@endsection
