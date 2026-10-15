@props(['song', 'start', 'end', 'n' => 1, 'src' => '', 'turn' => false])

{{--
    پخش‌کننده‌ی کوچکِ یک «بخش» از موزیک (مرحله‌ی ۴) — رفتار در resources/js/modules/clip.js
    فقط از start تا end پخش می‌شود و بعد خودش می‌ایستد. همه‌ی بخش‌های یک مرحله یک فایل صوتی مشترک دارند و هم‌زمان فقط یکی پخش می‌شود.
--}}
@php
    $hue = ($song['id'] * 47) % 360;
    $len = max($end - $start, 0.5);
@endphp

<div class="clip" data-clip data-start="{{ $start }}" data-end="{{ $end }}" @if ($turn) data-turn @endif data-state="paused" role="group" aria-label="{{ __('lesson.listening.part', ['n' => num($n)]) }}">
    @if (! empty($song['cover']))
        <img src="{{ $song['cover'] }}" alt="" class="absolute inset-0 -z-10 size-full scale-110 object-cover blur-[2px]" loading="lazy" decoding="async">
    @else
        <div class="absolute inset-0 -z-10" style="background: radial-gradient(120% 120% at 15% 0%, hsl({{ ($hue + 40) % 360 }} 85% 66%), transparent 60%), linear-gradient(135deg, hsl({{ $hue }} 65% 46%), hsl({{ ($hue + 50) % 360 }} 60% 28%))"></div>
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-950/80 via-slate-950/35 to-slate-950/20"></div>

    <p data-clip-error hidden class="absolute inset-x-3 top-3 z-10 rounded-lg bg-rose-600/90 px-3 py-1.5 text-center text-xs font-bold">{{ __('lesson.player.error') }}</p>

    <div class="flex h-full flex-col justify-between p-3.5 md:p-4">
        {{-- نوار زمان بخش (چپ‌به‌راست) + زمان‌ها --}}
        <div dir="ltr">
            <div class="clip__bar"><i data-clip-fill></i></div>
            <div class="mt-1.5 flex justify-between text-xs font-bold tabular-nums text-white/90">
                <span data-clip-cur>{{ num('00:00') }}</span>
                <span data-clip-total>{{ num(sprintf('%02d:%02d', intdiv((int) round($len), 60), (int) round($len) % 60)) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" data-clip-toggle class="p-main grid size-12 shrink-0 place-items-center rounded-full bg-white text-brand-800 shadow-lift transition duration-200 active:scale-95 md:size-14"
                aria-label="{{ __('lesson.player.play') }}" data-label-play="{{ __('lesson.player.play') }}" data-label-pause="{{ __('lesson.player.pause') }}">
                <x-icon name="play" class="p-ico-play size-5 translate-x-px fill-current md:size-6 rtl:-translate-x-px" />
                <x-icon name="pause" class="p-ico-pause size-5 fill-current md:size-6" />
            </button>

            <div class="clip__info min-w-0 flex-1" dir="ltr">
                <p class="truncate text-sm font-extrabold md:text-base"><bdi>{{ $song['title'] }}</bdi> <span class="font-bold text-white/80">· {{ __('lesson.listening.part', ['n' => $n]) }}</span></p>
                <p class="truncate text-xs text-white/75 md:text-sm"><bdi>{{ $song['artist'] }}</bdi></p>
            </div>
            @if ($turn)
                {{-- نوبت کاربر (shadowing): بعد از پخش، نوار به‌اندازه‌ی بخش پر می‌شود و کاربر تکرار می‌کند --}}
                <div class="clip__turn min-w-0 flex-1">
                    <p class="flex items-center gap-1.5 text-sm font-extrabold md:text-base"><x-icon name="mic" class="size-4 shrink-0 animate-pulse" /> {{ __('lesson.shadowing.turn') }}</p>
                    <button type="button" data-clip-skip class="mt-1 rounded-full bg-white/20 px-3 py-1 text-xs font-bold backdrop-blur-sm transition active:scale-95">{{ __('lesson.shadowing.skip') }}</button>
                </div>
            @endif

            <div class="clip__speed flex shrink-0 items-center rounded-full bg-white/15 backdrop-blur-sm" role="group" aria-label="{{ __('lesson.player.speed') }}">
                <button type="button" data-clip-slower class="p-btn !bg-transparent" aria-label="{{ __('lesson.player.slower') }}"><x-icon name="minus" class="size-4" /></button>
                <span data-clip-speed dir="ltr" class="w-9 text-center text-xs font-bold tabular-nums md:text-sm" aria-live="polite">1x</span>
                <button type="button" data-clip-faster class="p-btn !bg-transparent" aria-label="{{ __('lesson.player.faster') }}"><x-icon name="plus" class="size-4" /></button>
            </div>
        </div>
    </div>
</div>
