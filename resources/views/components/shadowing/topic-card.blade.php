@props(['topic', 'subscribed' => false, 'index' => 0, 'level' => 'all', 'userLevel' => 'a2'])

{{--
    کارت یک موضوع Shadowing. هر موضوع برای هر سطح یک نسخه دارد؛ کاربر روی «چیپ سطح» می‌زند و وارد همان نسخه می‌شود.
    قرارداد داده ($topic = ShadowingTopics::topics()[i]): id, key, title, category, is_free, versions[], levels[],
      versions_done, status, min_seconds, max_seconds, sentences_min, sentences_max
    رنگ چیپ: done (سبز) | doing (نارنجی) | new | سطح پیشنهادی کاربر (حلقه‌ی برند) | سطحی که این موضوع ندارد (کم‌رنگ، بدون لینک)
--}}
@php
    $limited = ! $subscribed && ! $topic['is_free'];
    $hue = ($topic['id'] * 67) % 360;
    $byLevel = collect($topic['versions'])->keyBy('level');
    $focus = $level !== 'all' ? $level : (in_array($userLevel, $topic['levels'], true) ? $userLevel : $topic['levels'][0]);
    $main = $byLevel[$focus];
    $started = $main['completed_steps'] > 0 || $main['current_step_fraction'] > 0;
    $fmt = fn (int $sec) => \App\Support\ShadowingTopics::fmt($sec);
    $range = match (true) {
        $topic['min_seconds'] === $topic['max_seconds'] => $fmt($topic['min_seconds']),
        $topic['max_seconds'] < 60 => __('shadowing.dur.range_s', ['a' => num($topic['min_seconds']), 'b' => num($topic['max_seconds'])]),
        default => __('shadowing.dur.range', ['a' => (string) num((int) round($topic['min_seconds'] / 60)), 'b' => num((int) round($topic['max_seconds'] / 60))]),
    };
@endphp

<article class="song-card group relative flex animate-rise flex-col overflow-hidden rounded-2xl border border-transparent bg-white p-4 shadow-card transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-lift"
    style="animation-delay: {{ min($index, 8) * 45 }}ms" data-topic data-topic-id="{{ $topic['id'] }}" data-status="{{ $topic['status'] }}" data-progress="{{ $main['progress'] }}">

    <div class="flex items-start gap-3.5">
        <span class="grid size-16 shrink-0 place-items-center rounded-xl text-white sm:size-[72px]" style="background: linear-gradient(135deg, hsl({{ $hue }} 70% 55%), hsl({{ ($hue + 40) % 360 }} 70% 40%))">
            <x-icon name="repeat" class="size-7" />
        </span>

        <div class="min-w-0 flex-1">
            <h3 class="line-clamp-2 text-[15px] font-bold leading-6 text-brand-900 sm:text-base"><bdi>{{ $topic['title'] }}</bdi></h3>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('shadowing.topic.' . $topic['key'] . '.title') }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600"><bdi>{{ $topic['category'] }}</bdi></span>
                @if ($limited)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600"><x-icon name="lock" class="size-3" />{{ __('shadowing.card.limited', ['n' => num($main['free_steps'])]) }}</span>
                @elseif (! $subscribed)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-600"><x-icon name="check-circle" class="size-3" />{{ __('shadowing.card.free') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- مدت و تعداد جمله: برای تصمیم‌گیری --}}
    <dl class="mt-4 grid grid-cols-2 gap-2">
        <div class="pc-stat">
            <span class="pc-stat__ic"><x-icon name="file-text" class="size-5" /></span>
            <div class="min-w-0">
                <dt>{{ __('shadowing.card.sentences') }}</dt>
                <dd>{{ __('shadowing.card.sentences_value', ['n' => $topic['sentences_min'] === $topic['sentences_max'] ? num($topic['sentences_min']) : num($topic['sentences_min']) . '–' . num($topic['sentences_max'])]) }}</dd>
            </div>
        </div>
        <div class="pc-stat">
            <span class="pc-stat__ic"><x-icon name="timer" class="size-5" /></span>
            <div class="min-w-0">
                <dt>{{ __('shadowing.card.duration') }}</dt>
                <dd class="!text-[13px]">{{ $range }}</dd>
            </div>
        </div>
    </dl>

    {{-- نسخه‌های سطحی --}}
    <div class="mt-4">
        <p class="mb-2 text-xs font-bold text-slate-500">{{ __('shadowing.card.versions') }}</p>
        <div class="grid grid-cols-6 gap-1.5" role="list">
            @foreach (\App\Support\ShadowingTopics::LEVELS as $l)
                @php $v = $byLevel[$l] ?? null; @endphp
                @if ($v)
                    @php $st = $v['status'] === 'done' ? 'done' : ($v['completed_steps'] > 0 || $v['current_step_fraction'] > 0 ? 'doing' : 'new'); @endphp
                    <a role="listitem" href="{{ route('shadowing.show', ['id' => $v['id']]) }}" class="sh-lv sh-lv--{{ $st }} {{ $l === $userLevel ? 'is-mine' : '' }} {{ $l === $focus ? 'is-focus' : '' }}"
                        title="{{ __('shadowing.card.version_title', ['level' => strtoupper($l), 'n' => num(count($v['sentences'])), 'time' => \App\Support\ShadowingTopics::fmt($v['duration'])]) }}">
                        <b>{{ strtoupper($l) }}</b>
                        @if ($st === 'done')<x-icon name="check" class="size-3" />@elseif ($st === 'doing')<i class="sh-lv__dot" aria-hidden="true"></i>@endif
                    </a>
                @else
                    <span role="listitem" class="sh-lv sh-lv--none" title="{{ __('shadowing.card.no_version') }}"><b>{{ strtoupper($l) }}</b></span>
                @endif
            @endforeach
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between text-xs font-bold text-slate-500">
        <span>{{ __('shadowing.card.progress', ['level' => strtoupper($focus)]) }}</span>
        <span class="tabular-nums text-brand-700" data-progress-text>{{ num($main['progress']) }}%</span>
    </div>
    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100"><i class="block h-full rounded-full bg-brand-600" style="width: {{ $main['progress'] }}%"></i></div>

    <div class="mt-auto pt-4">
        <a href="{{ route('shadowing.show', ['id' => $main['id']]) }}" class="btn-primary w-full !min-h-11">
            {{ __('shadowing.cta.' . ($main['status'] === 'done' ? 'review' : ($started ? 'continue' : 'start')), ['level' => strtoupper($focus)]) }}
            <x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" />
        </a>
    </div>
</article>
