@props(['series', 'subscribed' => false, 'index' => 0])

{{--
    کارت یک پادکست (سری). مدت و تعداد اپیزود عمداً درشت است تا کاربر بتواند تصمیم بگیرد کدام را گوش بدهد.
    قرارداد داده ($series = Podcast::series()[i]):
      id, title, host, category, level(a|b|c), cover, recommended(bool: پیشنهادی ادمین), is_free,
      episodes_count, episodes_done, total_seconds, min_seconds, max_seconds, progress(0-100)
    پیشرفت داینامیک مثل موزیک با data-status / data-progress.
--}}
@php
    $limited = ! $subscribed && ! $series['is_free'];
    $started = ($series['episodes_done'] ?? 0) > 0 || collect($series['episodes'] ?? [])->contains(fn ($e) => $e['completed_steps'] > 0);
    $progress = (int) $series['progress'];
    $status = $progress >= 100 ? 'done' : ($started ? 'in_progress' : 'new');
    $hue = ($series['id'] * 61) % 360;
    $href = route('podcast.series', ['id' => $series['id']]);
    $total = \App\Support\Podcast::fmt((int) $series['total_seconds']);
    $range = $series['min_seconds'] === $series['max_seconds']
        ? \App\Support\Podcast::fmt((int) $series['min_seconds'])
        : __('podcast.card.range', ['a' => num((int) round($series['min_seconds'] / 60)), 'b' => num((int) round($series['max_seconds'] / 60))]);
@endphp

<article class="song-card group relative flex animate-rise flex-col rounded-2xl border border-transparent bg-white p-4 shadow-card transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-lift sm:p-5"
    style="animation-delay: {{ min($index, 8) * 45 }}ms" data-series data-series-id="{{ $series['id'] }}" data-status="{{ $status }}" data-progress="{{ $progress }}">

    @if ($series['recommended'])
        <span class="pc-reco"><x-icon name="star" class="size-3.5" />{{ __('podcast.card.recommended') }}</span>
    @endif

    <div class="flex items-start gap-3.5">
        <div class="relative size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:size-[72px]">
            @if ($series['cover'])
                <img src="{{ $series['cover'] }}" alt="" width="72" height="72" loading="lazy" decoding="async" class="size-full object-cover transition-transform duration-500 group-hover:scale-110">
            @else
                <span class="grid size-full place-items-center text-white" style="background: linear-gradient(135deg, hsl({{ $hue }} 70% 55%), hsl({{ ($hue + 40) % 360 }} 70% 40%))"><x-icon name="mic" class="size-7" /></span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h3 class="line-clamp-2 text-[15px] font-bold leading-6 text-brand-900 sm:text-base"><bdi>{{ $series['title'] }}</bdi></h3>
            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-slate-500">
                <x-icon :name="$limited ? 'lock' : 'lock-open'" class="size-4 shrink-0 {{ $limited ? 'text-rose-500' : 'text-emerald-500' }}" />
                <bdi class="truncate">{{ $series['host'] }}</bdi>
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700">{{ __('podcast.levels.' . $series['level']) }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600"><bdi>{{ $series['category'] }}</bdi></span>
                @if ($limited)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600"><x-icon name="lock" class="size-3" />{{ __('podcast.card.limited', ['n' => num($series['episodes'][0]['free_steps'] ?? 2)]) }}</span>
                @elseif (! $subscribed)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-600"><x-icon name="check-circle" class="size-3" />{{ __('podcast.card.free') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- زمان و تعداد اپیزود: مهم‌ترین اطلاعات برای انتخاب --}}
    <dl class="mt-4 grid grid-cols-2 gap-2">
        <div class="pc-stat">
            <span class="pc-stat__ic"><x-icon name="list-music" class="size-5" /></span>
            <div class="min-w-0">
                <dt>{{ __('podcast.card.episodes') }}</dt>
                <dd>{{ __('podcast.card.episodes_value', ['n' => num($series['episodes_count'])]) }}</dd>
            </div>
        </div>
        <div class="pc-stat">
            <span class="pc-stat__ic"><x-icon name="timer" class="size-5" /></span>
            <div class="min-w-0">
                <dt>{{ __('podcast.card.total') }}</dt>
                <dd>{{ $total }}</dd>
            </div>
        </div>
    </dl>
    <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
        <x-icon name="timer" class="size-3.5 shrink-0" />
        {{ __('podcast.card.each', ['range' => $range]) }}
    </p>

    {{-- پیشرفت: چند اپیزود از چند اپیزود --}}
    <div class="mt-3">
        <div class="mb-1.5 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-600">{{ __('podcast.card.progress', ['done' => num($series['episodes_done']), 'total' => num($series['episodes_count'])]) }}</span>
            <span class="tabular-nums font-extrabold text-brand-900">{{ num($progress) }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-600 transition-[width] duration-500" style="width: {{ $progress }}%"></div></div>
    </div>

    <div class="mt-auto pt-4">
        <a href="{{ $href }}" class="btn-primary w-full !min-h-11">
            {{ $status === 'new' ? __('podcast.cta.start') : ($status === 'done' ? __('podcast.cta.review') : __('podcast.cta.continue')) }}
            <x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" />
        </a>
    </div>
</article>
