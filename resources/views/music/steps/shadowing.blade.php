{{--
    مرحله 5: shadowing — بخشی از موزیک را می‌شنوی، بعد «نوبت توست» و تکرار می‌کنی (حداقل ۳ بار).
    هر دور = یک بار گوش‌دادن + یک نوبت تکرار. رفتار: resources/js/modules/{clip,shadowing}.js
    داده‌ها: app/Support/Shadowing.php ($song['shadowing']؛ اگر نرسد، نمونه از لیریک ساخته می‌شود)
--}}
@php
    $parts = \App\Support\Shadowing::forSong($song);
    $target = \App\Support\Shadowing::target($song);
    $src = (string) ($song['audio'] ?? '');
    if ($src !== '' && ! preg_match('#^(https?:)?//|^/#i', $src)) {
        $src = asset($src);
    }
    $demoDuration = (int) ceil(collect($parts)->max('end') ?? 60) + 5;
@endphp

@if (! count($parts))
    <p class="rounded-2xl bg-slate-50 px-6 py-12 text-center text-sm leading-7 text-slate-500">{{ __('lesson.shadowing.empty') }}</p>
@else
<div class="space-y-5" data-clips data-shadowing-step data-song="{{ $song['id'] }}" data-src="{{ $src }}" data-duration="{{ $demoDuration }}" data-target="{{ $target }}" data-total="{{ count($parts) }}">

    {{-- راهنمای ۳ مرحله‌ای + شمارنده‌ی کلی --}}
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-2xl bg-brand-50/70 px-4 py-3 md:px-5">
        <ol class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-extrabold text-brand-900 md:text-sm">
            @foreach (['listen', 'repeat', 'again'] as $i => $k)
                <li class="flex items-center gap-1.5">
                    <span class="grid size-6 place-items-center rounded-full bg-white text-xs text-brand-700 shadow-sm">{{ num($i + 1) }}</span>
                    {{ __('lesson.shadowing.how_' . $k, ['n' => num($target)]) }}
                </li>
            @endforeach
        </ol>
        <span class="lyrics-chip pointer-events-none" aria-live="polite">
            <x-icon name="check-circle" class="size-4 text-emerald-600" />
            <b class="tabular-nums" data-sx-done>{{ num(0) }}</b>/<span class="tabular-nums">{{ num(count($parts)) }}</span>
        </span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($parts as $p)
            <article class="sx" data-sx data-id="{{ $p['id'] }}" data-rounds="0" data-state="idle">
                <x-lesson.clip :song="$song" :start="$p['start']" :end="$p['end']" :n="$p['n']" :turn="true" />

                {{-- شمارنده‌ی تکرار: یک نقطه برای هر دور --}}
                <div class="sx__rounds">
                    <span class="flex items-center gap-1.5" aria-hidden="true">
                        @for ($d = 0; $d < $target; $d++)
                            <i class="sx__dot" data-sx-dot></i>
                        @endfor
                    </span>
                    <p class="text-xs font-extrabold md:text-sm" data-sx-count aria-live="polite"
                        data-tpl="{{ __('lesson.shadowing.count') }}" data-tpl-done="{{ __('lesson.shadowing.count_done') }}" data-total="{{ $target }}">
                        {{ __('lesson.shadowing.count', ['n' => num(0), 'total' => num($target)]) }}
                    </p>
                    <x-icon name="check-circle" class="sx__tick size-5 text-emerald-600" />
                </div>

                {{-- متن بخش: اول تار؛ با کلیک نمایش داده می‌شود --}}
                <button type="button" class="sx-lyrics" data-sx-reveal aria-pressed="false"
                    data-label-show="{{ __('lesson.shadowing.show') }}" data-label-hide="{{ __('lesson.shadowing.hide') }}" aria-label="{{ __('lesson.shadowing.show') }}">
                    <span class="sx-lyrics__text" dir="ltr" lang="en">
                        @foreach ($p['lines'] as $line)<span class="block">{{ $line }}</span>@endforeach
                    </span>
                    <span class="sx-lyrics__veil">
                        <x-icon name="eye-off" class="size-5 sx-off" />
                        <x-icon name="eye" class="size-5 sx-on" />
                        <span data-sx-reveal-label>{{ __('lesson.shadowing.show') }}</span>
                    </span>
                </button>
            </article>
        @endforeach
    </div>

    <div class="lx-summary" data-sx-summary hidden role="status">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check-circle" class="size-6" /></span>
        <p class="text-sm font-extrabold leading-7 text-emerald-900 md:text-base">{{ __('lesson.shadowing.summary', ['n' => num($target)]) }}</p>
    </div>
</div>
@endif
