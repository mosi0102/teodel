{{--
    پادکست — مرحله‌ی ۷ (details): اطلاعات پادکست + مقایسه‌ی Main idea کاربر با Main idea رسمی.
    رفتار پیشرفت: resources/js/modules/details.js | نوشته‌ی کاربر: podcast-idea.js ([data-idea-mine])
    🔌 $song: host, description, tags[], words_count, main_idea (رسمی)، user_main_idea (نوشته‌ی کاربر؛ اگر بک‌اند ندهد از localStorage می‌آید)
--}}
@php
    $val = fn ($v) => filled($v) ? $v : null;
    $levelKey = $song['level'] ?? null;
    $hue = ($song['id'] * 47) % 360;
    $series = $series ?? null;
    $facts = [
        ['level', 'award', $levelKey ? __('music.levels.' . $levelKey) : null],
        ['duration', 'timer', $val($song['duration'] ?? null) ? num($song['duration']) : null],
        ['episode', 'list-music', $series ? __('podcast.info.episode_value', ['n' => num($song['number']), 'total' => num($series['episodes_count'])]) : null],
        ['host', 'mic', $val($song['host'] ?? null)],
        ['accent', 'globe', $val($song['accent'] ?? null) ? __('music.accents.' . $song['accent']) : null],
        ['wpm', 'gauge', $val($song['wpm'] ?? null) ? __('music.info.wpm_value', ['n' => num($song['wpm'])]) : null],
        ['topic', 'bookmark', $val($song['topic'] ?? null)],
        ['words_count', 'file-text', $val($song['words_count'] ?? null) ? num($song['words_count']) : null],
    ];
@endphp

<div class="space-y-5 md:space-y-6" data-details-step data-song="{{ $song['id'] }}">
    <section class="det-hero" aria-labelledby="det-title">
        <div class="det-cover">
            @if (! empty($song['cover']))
                <img src="{{ $song['cover'] }}" alt="" class="size-full object-cover" loading="lazy" decoding="async">
            @else
                <div class="grid size-full place-items-center bg-brand-700 text-white"><x-icon name="mic" class="size-16 opacity-80 md:size-20" /></div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h2 id="det-title" class="text-2xl font-extrabold leading-tight text-brand-900 md:text-4xl"><bdi>{{ $song['title'] }}</bdi></h2>
            <p class="mt-1.5 text-sm font-bold text-slate-500 md:text-base"><bdi>{{ $song['artist'] }}</bdi></p>

            <dl class="det-facts">
                @foreach ($facts as [$k, $icon, $value])
                    <div class="det-fact">
                        <span class="det-fact__icon"><x-icon :name="$icon" class="size-5" /></span>
                        <div class="min-w-0">
                            <dt>{{ __('podcast.info.' . $k) }}</dt>
                            <dd @if (! $value) class="is-unknown" @endif><bdi>{{ $value ?? __('lesson.details.unknown') }}</bdi></dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if (filled($song['description'] ?? null))
        <section class="det-card" aria-labelledby="det-about">
            <h3 id="det-about" class="det-h"><span class="det-h__icon"><x-icon name="file-text" class="size-5" /></span>{{ __('podcast.info.about') }}</h3>
            <div class="det-prose"><p dir="auto">{{ $song['description'] }}</p></div>
            @if (! empty($song['tags']))
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($song['tags'] as $tag)<li class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600"><bdi>{{ $tag }}</bdi></li>@endforeach
                </ul>
            @endif
        </section>
    @endif

    {{-- Main idea: نوشته‌ی کاربر (مرحله‌ی ۱) کنار Main idea رسمی --}}
    <section class="det-card" aria-labelledby="det-idea">
        <h3 id="det-idea" class="det-h"><span class="det-h__icon"><x-icon name="sparkles" class="size-5" /></span>{{ __('podcast.idea.compare_title') }}</h3>
        <div class="mt-4 grid gap-3 md:grid-cols-2 md:gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4" data-idea-mine data-song="{{ $song['id'] }}" data-text="{{ $song['user_main_idea'] ?? '' }}">
                <p class="text-xs font-extrabold text-slate-500">{{ __('podcast.idea.yours') }}</p>
                <p class="mt-2 text-sm leading-7 text-slate-700" dir="auto" data-idea-mine-text></p>
                <p class="mt-2 text-sm leading-7 text-slate-400" data-idea-mine-empty hidden>{{ __('podcast.idea.none') }}</p>
            </div>
            <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                <p class="text-xs font-extrabold text-brand-700">{{ __('podcast.idea.official') }}</p>
                <p class="mt-2 text-sm leading-7 text-slate-700" dir="ltr" lang="en">{{ $song['main_idea'] ?? '—' }}</p>
            </div>
        </div>
    </section>

    {{-- اپیزود بعدی --}}
    @if (! empty($nextEpisode))
        <a href="{{ route('podcast.step', ['id' => $nextEpisode['id'], 'step' => 1]) }}" class="det-card flex items-center justify-between gap-3 transition hover:shadow-lift">
            <span class="min-w-0"><span class="block text-xs font-bold text-slate-500">{{ __('podcast.info.next') }}</span><b class="mt-0.5 block truncate text-brand-900"><bdi>{{ $nextEpisode['title'] }}</bdi></b></span>
            <span class="inline-flex items-center gap-1.5 text-sm font-extrabold text-brand-700">{{ num($nextEpisode['duration']) }}<x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" /></span>
        </a>
    @endif

    <div data-details-end aria-hidden="true" class="h-px"></div>
</div>
