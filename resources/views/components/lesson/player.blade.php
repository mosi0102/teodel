@props(['song', 'kind' => 'play'])

{{--
    پخش‌کننده‌ی موزیک (مرحله‌های play و repeat) — رفتار در resources/js/modules/player.js

    🔌 hook بک‌اند: $song['audio'] = آدرس فایل صوتی.
       اگر خالی باشد، پخش‌کننده در حالت «دمو» با زمان‌سنج کار می‌کند (فقط برای تست فرانت).
    🔌 hook بک‌اند: $song['vocals'] = [[شروع، پایان], ...] به ثانیه؛ بخش‌هایی که خواننده می‌خواند.
       روی نوار زمان با نشانه‌ی کوچک دیده می‌شود (خالی = نشانه‌ای نمی‌آید).
    مدت زمان از $song['duration'] (mm:ss) خوانده می‌شود.

    kind="karaoke" (مرحله‌ی ۶): پخش‌کننده‌ی عریض با میکسر «صدای خواننده / صدای موزیک».
    🔌 hook بک‌اند: $song['audio_vocal'] (فقط صدای خواننده) و $song['audio_backing'] (موزیک بدون خواننده)
       دو فایل هم‌طول و هم‌زمان. اگر هر دو بیایند، دو اسلایدر مستقل کار می‌کنند.
       اگر فقط $song['audio'] باشد، فقط «صدای موزیک» (= بلندی کل) کار می‌کند و حذف خواننده غیرفعال است.
--}}
@php
    [$mm, $ss] = array_pad(array_map('intval', explode(':', (string) ($song['duration'] ?? '03:00'))), 2, 0);
    $seconds = max($mm * 60 + $ss, 1);
    $hue = ($song['id'] * 47) % 360;
    // آدرس صوت: کامل (https://… یا /…) همان‌طور که هست؛ نسبی (مثلاً music/a.mp3) از public ساخته می‌شود
    $src = (string) ($song['audio'] ?? '');
    $abs = fn (string $u) => $u !== '' && ! preg_match('#^(https?:)?//|^/#i', $u) ? asset($u) : $u;
    $src = $abs($src);
    $karaoke = $kind === 'karaoke';
    $srcVocal = $karaoke ? $abs((string) ($song['audio_vocal'] ?? '')) : '';
    $srcBacking = $karaoke ? $abs((string) ($song['audio_backing'] ?? '')) : '';
    $stems = $srcVocal !== '' && $srcBacking !== '';
@endphp

<div class="player group relative isolate flex w-full flex-col justify-end overflow-hidden rounded-2xl bg-slate-900 text-white shadow-card {{ $karaoke ? 'min-h-[15rem] rounded-t-none' : 'aspect-square md:aspect-[5/4] lg:aspect-square' }}"
    data-player data-song="{{ $song['id'] }}" data-src="{{ $src }}" @if ($stems) data-src-vocal="{{ $srcVocal }}" data-src-backing="{{ $srcBacking }}" @endif data-duration="{{ $seconds }}" data-vocals="{{ json_encode($song['vocals'] ?? []) }}"
    data-state="paused" role="group" aria-label="{{ __('lesson.player.label') }}">

    {{-- کاور (تصویر واقعی یا گرادیان جایگزین) --}}
    @if (! empty($song['cover']))
        <img src="{{ $song['cover'] }}" alt="" class="player__cover absolute inset-0 -z-10 size-full object-cover" loading="lazy" decoding="async">
    @else
        <div class="player__cover absolute inset-0 -z-10"
            style="background: radial-gradient(120% 90% at 20% 10%, hsl({{ ($hue + 40) % 360 }} 85% 68%), transparent 60%), linear-gradient(135deg, hsl({{ $hue }} 70% 52%), hsl({{ ($hue + 50) % 360 }} 65% 32%))"></div>
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-950/85 via-slate-950/25 to-transparent"></div>

    {{-- اکولایزر کوچک (فقط وقتی پخش می‌شود حرکت می‌کند؛ CSS خالص) --}}
    @unless ($karaoke)
    <div class="player__eq absolute end-4 top-4 flex h-6 items-end gap-[3px] rounded-full bg-black/30 px-3 py-1.5 backdrop-blur-sm" aria-hidden="true">
        <i></i><i></i><i></i><i></i>
    </div>
    @endunless

    <p data-p-error hidden class="absolute inset-x-4 top-4 rounded-xl bg-rose-600/90 px-4 py-2.5 text-center text-sm font-bold">{{ __('lesson.player.error') }}</p>

    {{-- کنترل‌ها --}}
    <div class="{{ $karaoke ? 'relative pt-14' : 'absolute inset-x-0 bottom-0' }} p-4 md:p-5">
        {{-- نوار زمان (همیشه چپ‌به‌راست، مثل همه‌ی پخش‌کننده‌ها) --}}
        <div dir="ltr">
            <div class="p-seek relative h-7">
                <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 overflow-hidden rounded-full bg-white/30">
                    <div data-p-fill class="h-full w-0 rounded-full bg-white"></div>
                </div>
                {{-- نشانه‌های بخش‌های خوانده‌شده (با player.js ساخته می‌شوند) --}}
                <div data-p-marks class="p-marks pointer-events-none absolute inset-x-0 top-0 h-full" aria-hidden="true"></div>
                <span data-p-thumb class="p-thumb pointer-events-none absolute top-1/2 size-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow-md" style="left:0%"></span>
                <input data-p-seek type="range" min="0" max="1000" value="0" step="1"
                    class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="{{ __('lesson.player.seek') }}">
            </div>
            <div class="flex justify-between text-xs font-bold tabular-nums text-white/90 md:text-sm">
                <span data-p-cur>{{ num('00:00') }}</span>
                <span data-p-legend hidden class="p-legend"><i></i>{{ __('lesson.player.vocals') }}</span>
                <span data-p-total>{{ num(sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60)) }}</span>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-3 md:gap-4">
            <button type="button" data-p-toggle class="p-main grid size-14 shrink-0 place-items-center rounded-full bg-white text-brand-800 shadow-lift transition duration-200 active:scale-95 md:size-16"
                aria-label="{{ __('lesson.player.play') }}" data-label-play="{{ __('lesson.player.play') }}" data-label-pause="{{ __('lesson.player.pause') }}">
                <x-icon name="play" class="p-ico-play size-6 translate-x-px fill-current md:size-7 rtl:-translate-x-px" />
                <x-icon name="pause" class="p-ico-pause size-6 fill-current md:size-7" />
            </button>

            <div class="min-w-0 flex-1">
                <p class="truncate text-base font-extrabold md:text-lg"><bdi>{{ $song['title'] }}</bdi></p>
                <p class="truncate text-sm text-white/75"><bdi>{{ $song['artist'] }}</bdi></p>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 md:gap-2">
                <button type="button" data-p-back class="p-btn" aria-label="{{ __('lesson.player.back') }}" title="{{ __('lesson.player.back') }}">
                    <x-icon name="rewind" class="size-5" />
                </button>

                <div class="flex items-center rounded-full bg-white/15 backdrop-blur-sm" role="group" aria-label="{{ __('lesson.player.speed') }}">
                    <button type="button" data-p-slower class="p-btn !bg-transparent" aria-label="{{ __('lesson.player.slower') }}"><x-icon name="minus" class="size-4" /></button>
                    <span data-p-speed dir="ltr" class="w-9 text-center text-sm font-bold tabular-nums" aria-live="polite">1x</span>
                    <button type="button" data-p-faster class="p-btn !bg-transparent" aria-label="{{ __('lesson.player.faster') }}"><x-icon name="plus" class="size-4" /></button>
                </div>
            </div>
        </div>

        @if ($karaoke)
            {{-- میکسر: صدای خواننده / صدای موزیک --}}
            <button type="button" class="kchip" data-mix-karaoke aria-pressed="false">
                <x-icon name="mic" class="size-4" />
                <span class="kchip__off">{{ __('lesson.karaoke.vocal_off') }}</span>
                <span class="kchip__on">{{ __('lesson.karaoke.vocal_on') }}</span>
            </button>
            <div class="kmix" data-p-mix>
                @foreach (['vocal', 'backing'] as $m)
                    <div class="kmix__row" data-mix="{{ $m }}" data-muted="false">
                        <button type="button" class="kmix__btn" data-mix-mute aria-pressed="false" aria-label="{{ __('lesson.karaoke.mute_' . $m) }}" title="{{ __('lesson.karaoke.mute_' . $m) }}">
                            <x-icon name="volume" class="kmix__on size-5" />
                            <x-icon name="volume-x" class="kmix__off size-5" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="kmix__name">{{ __('lesson.karaoke.' . $m) }}</p>
                            <input type="range" min="0" max="100" step="1" value="100" data-mix-range class="kslider" dir="ltr" aria-label="{{ __('lesson.karaoke.' . $m) }}">
                        </div>
                    </div>
                @endforeach
            </div>
            <p data-p-mix-note data-mix-note hidden class="mt-2 text-xs font-bold leading-6 text-white/80">{{ __('lesson.karaoke.no_stems') }}</p>
        @endif
    </div>
</div>
