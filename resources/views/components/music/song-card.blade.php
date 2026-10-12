@props(['song', 'subscribed' => false, 'index' => 0])

{{--
    کارت یک آهنگ.
    قرارداد داده ($song): id, title, artist, cover(nullable), level(a|b|c), duration("02:32"), points,
                          completed_steps, current_step_fraction(0-1), total_steps,
                          is_free(bool: یکی از ۳ آهنگ کاملاً رایگان), free_steps(int)
    کاربر رایگان: روی آهنگ‌های غیر is_free فقط free_steps مرحله‌ی اول باز است.

    پیشرفت داینامیک: ظاهر کارت (حلقه، درصد، ریبون، رنگ حاشیه، متن دکمه) فقط به data-status و
    data-progress همین عنصر وابسته است. با Teodel.cards.set(songId, percent) هر لحظه عوض می‌شود.
--}}
@php
    $limited = ! $subscribed && ! $song['is_free'];
    // درصد از روی مرحله‌های انجام‌شده محاسبه می‌شود (App\Support\Progress)؛ وضعیت هم از روی همان درصد
    $progress = isset($song['completed_steps'])
        ? \App\Support\Progress::percent((int) $song['completed_steps'], (float) ($song['current_step_fraction'] ?? 0), (int) ($song['total_steps'] ?? 8))
        : (int) $song['progress'];
    $status = \App\Support\Progress::status($progress);
    $freeSteps = (int) ($song['free_steps'] ?? 2);
    $totalSteps = (int) ($song['total_steps'] ?? 8);
    $hue = ($song['id'] * 47) % 360;
    $href = url('/music/' . $song['id']);

    $limitedText = __('music.limited', ['free' => num($freeSteps), 'total' => num($totalSteps)]);
    $stepsText = __('music.steps_total', ['n' => num($totalSteps)]);
    $accessText = $limited ? __('music.access_limited', ['free' => num($freeSteps), 'total' => num($totalSteps)]) : __('music.access_full');

    // حلقه‌ی پیشرفت (محیط دایره‌ی r=9 تقریباً 56.55 است)
    $dash = round($progress * 0.5655, 2);
@endphp

<article
    class="song-card group relative flex animate-rise flex-col overflow-hidden rounded-2xl border border-transparent bg-white p-4 shadow-card transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-lift sm:p-5"
    style="animation-delay: {{ min($index, 8) * 45 }}ms"
    data-song
    data-song-id="{{ $song['id'] }}"
    data-status="{{ $status }}"
    data-progress="{{ $progress }}"
    data-title="{{ $song['title'] }}"
    data-artist="{{ $song['artist'] }}"
    data-cover="{{ $song['cover'] }}"
    data-hue="{{ $hue }}"
    data-level-text="{{ __('music.levels.' . $song['level']) }}"
    data-duration="{{ num($song['duration']) }}"
    data-points="{{ num($song['points']) }}"
    data-steps-text="{{ $stepsText }}"
    data-access-text="{{ $accessText }}"
    data-label-new="{{ __('music.status.new') }}"
    data-label-in_progress="{{ __('music.status.in_progress') }}"
    data-label-done="{{ __('music.status.done') }}"
    data-href="{{ $href }}"
    data-limited="{{ $limited ? 1 : 0 }}"
>
    {{-- ریبون‌ها: هر کدام فقط در وضعیت خودش دیده می‌شود (CSS) --}}
    <div class="ribbon" data-ribbon="in_progress" aria-hidden="true"><span class="bg-amber-500">{{ __('music.ribbon.in_progress') }}</span></div>
    <div class="ribbon" data-ribbon="done" aria-hidden="true"><span class="bg-emerald-500">{{ __('music.ribbon.done') }}</span></div>

    {{-- سربرگ: کاور + عنوان + خواننده --}}
    <div class="flex items-start gap-3.5">
        <div class="relative size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:size-[72px]">
            @if ($song['cover'])
                <img src="{{ $song['cover'] }}" alt="" width="72" height="72" loading="lazy" decoding="async"
                    class="size-full object-cover transition-transform duration-500 group-hover:scale-110">
            @else
                <span class="grid size-full place-items-center text-white transition-transform duration-500 group-hover:scale-110"
                    style="background: linear-gradient(135deg, hsl({{ $hue }} 70% 55%), hsl({{ ($hue + 40) % 360 }} 70% 40%))">
                    <x-icon name="music" class="size-7" />
                </span>
            @endif
        </div>

        <div class="min-w-0 flex-1 pe-6">
            <h3 class="line-clamp-2 text-[15px] font-bold leading-6 text-brand-900 sm:text-base"><bdi>{{ $song['title'] }}</bdi></h3>
            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-slate-500">
                <x-icon :name="$limited ? 'lock' : 'lock-open'" class="size-4 shrink-0 {{ $limited ? 'text-rose-500' : 'text-emerald-500' }}" />
                <bdi class="truncate">{{ $song['artist'] }}</bdi>
            </p>

            @if ($limited)
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600">
                    <x-icon name="lock" class="size-3" />
                    {{ $limitedText }}
                </span>
            @elseif (! $subscribed)
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-600">
                    <x-icon name="check-circle" class="size-3" />
                    {{ __('music.free_full') }}
                </span>
            @endif
        </div>
    </div>

    {{-- آمار: زمان / امتیاز / پیشرفت --}}
    <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="flex flex-col items-center gap-0.5 rounded-xl border border-slate-200 px-1 py-2.5">
            <x-icon name="timer" class="size-5 text-slate-400" />
            <dt class="text-[11px] text-slate-500">{{ __('music.card.time') }}</dt>
            <dd class="text-sm font-extrabold text-brand-900">{{ num($song['duration']) }}</dd>
        </div>

        <div class="flex flex-col items-center gap-0.5 rounded-xl border border-slate-200 px-1 py-2.5">
            <x-icon name="award" class="size-5 text-slate-400" />
            <dt class="text-[11px] text-slate-500">{{ __('music.card.points') }}</dt>
            <dd class="text-sm font-extrabold text-brand-900">{{ num($song['points']) }}</dd>
        </div>

        {{-- پیشرفت: حلقه‌ی ۰ تا ۱۰۰ (در ۱۰۰٪ جایش تیک می‌آید) --}}
        <div class="flex flex-col items-center gap-0.5 rounded-xl border border-slate-200 px-1 py-2.5">
            <svg data-prog-ring class="size-5 -rotate-90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="text-slate-200" />
                <circle data-prog-arc cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    style="--dash: {{ $dash }}" class="ring-fill prog-arc" />
            </svg>
            <x-icon name="check-circle" class="size-5 text-emerald-500" data-prog-done />
            <dt class="text-[11px] text-slate-500">{{ __('music.card.progress') }}</dt>
            <dd class="text-sm font-extrabold text-brand-900"><span data-prog-text>{{ num($progress) }}%</span></dd>
        </div>
    </dl>

    {{-- اقدام‌ها --}}
    <div class="mt-auto flex items-center justify-between gap-2 pt-4">
        <a href="{{ $href }}" class="group/cta -ms-1 inline-flex min-h-11 items-center gap-1.5 rounded-lg px-1 text-sm font-bold text-brand-700">
            {{-- متن دکمه بسته به وضعیت (CSS) --}}
            <span data-cta="start">{{ __('music.cta.start') }}</span>
            <span data-cta="continue">{{ __('music.cta.continue') }}</span>
            <span data-cta="review">{{ __('music.cta.review') }}</span>
            <x-icon name="arrow-end" class="size-4 transition-transform duration-200 rtl:-scale-x-100 group-hover/cta:translate-x-1 rtl:group-hover/cta:-translate-x-1" />
        </a>

        <button type="button" data-song-info
            class="min-h-10 rounded-full bg-brand-50 px-4 text-xs font-bold text-brand-700 transition duration-200 hover:bg-brand-100 active:scale-95">
            {{ __('music.card.info') }}
        </button>
    </div>
</article>
