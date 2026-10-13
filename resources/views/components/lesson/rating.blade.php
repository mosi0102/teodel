@props(['song', 'kind' => 'play'])

{{--
    امتیازدهی «چقدر متوجه شدی؟» (۱ تا ۱۰) — رفتار در resources/js/modules/rating.js

    kind = play   → مرحله‌ی ۱ (امتیاز اول، مبنای مقایسه)
    kind = repeat → مرحله‌ی ۷ (فقط متن پرسش فرق دارد؛ نتیجه با امتیاز مرحله‌ی اول مقایسه می‌شود)

    وضعیت با data-state روی ریشه: locked (هنوز کامل گوش نداده) | ready (می‌تواند امتیاز بدهد) | done (ثبت شده)

    🔌 hook بک‌اند:
       $song['play_score'], $song['repeat_score']  ← امتیاز ثبت‌شده‌ی قبلی (عدد ۱ تا ۱۰ یا null)
       ثبت امتیاز: رویداد  $(document).on('lesson:rating', (e, { song, kind, score, baseline }) => {...})
--}}
@php
    $score = $song[$kind . '_score'] ?? null;
    $baseline = $kind === 'repeat' ? ($song['play_score'] ?? null) : null;
    $state = $score ? 'done' : 'locked';
    $hue = fn (int $n) => (int) round(($n - 1) * 130 / 9); // ۰ = قرمز … ۱۳۰ = سبز
    $colored = fn (string $key) => '<b class="' . ($key === 'low' ? 'text-rose-600' : 'text-emerald-600') . '">' . e(__('lesson.rate.' . $key)) . '</b>';
@endphp

<div class="rating flex h-full flex-col" data-rating data-kind="{{ $kind }}" data-song="{{ $song['id'] }}"
    data-score="{{ $score }}" data-baseline="{{ $baseline }}" data-threshold="0.9" data-state="{{ $state }}">

    <h3 class="flex items-center gap-2 text-base font-extrabold text-brand-900 md:text-lg">
        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="{{ $kind === 'play' ? 'headphones' : 'repeat' }}" class="size-5" /></span>
        {{ __('lesson.rate.' . $kind . '.title') }}
    </h3>

    <p class="mt-3 text-sm leading-8 text-slate-600 md:text-[0.95rem]">
        {!! __('lesson.rate.' . $kind . '.prompt', ['low' => $colored('low'), 'high' => $colored('high')]) !!}
    </p>

    {{-- وضعیت: قفل (با نوار «چقدر شنیده شده») | آماده --}}
    <div class="rating__lock mt-4 rounded-xl bg-amber-50 p-3.5 text-amber-800" data-r-lock role="status">
        <p class="flex items-center gap-2 text-sm font-bold">
            <x-icon name="headphones" class="size-4 shrink-0" />
            <span>{{ __('lesson.rate.locked') }}</span>
            <span class="ms-auto tabular-nums" data-r-pct>{{ num(0) }}%</span>
        </p>
        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-amber-200/70"><div class="h-full w-0 rounded-full bg-amber-500 transition-[width] duration-300" data-r-meter></div></div>
    </div>
    <p class="rating__ready mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 p-3.5 text-sm font-bold text-emerald-700" role="status">
        <x-icon name="check-circle" class="size-5 shrink-0" />
        {{ __('lesson.rate.ready') }}
    </p>

    {{-- مقیاس ۱ تا ۱۰ --}}
    <form class="rating__form mt-5" data-r-form novalidate>
        <fieldset class="min-w-0">
            <legend class="sr-only">{{ __('lesson.rate.legend') }}</legend>
            <div class="grid grid-cols-5 gap-2 md:grid-cols-10 md:gap-1.5">
                @for ($n = 1; $n <= 10; $n++)
                    <label class="rate" style="--h: {{ $hue($n) }}">
                        <input type="radio" name="score-{{ $kind }}-{{ $song['id'] }}" value="{{ $n }}" class="sr-only" @checked($score === $n)>
                        <span class="rate-btn">{{ num($n) }}</span>
                    </label>
                @endfor
            </div>
            <div class="mt-2 flex justify-between text-xs text-slate-500">
                <span>{{ __('lesson.rate.min') }}</span>
                <span>{{ __('lesson.rate.max') }}</span>
            </div>
        </fieldset>

        {{-- توضیح امتیاز انتخاب‌شده --}}
        <p class="mt-4 min-h-6 text-center text-sm font-bold text-brand-800" data-r-caption aria-live="polite"></p>
        @for ($b = 1; $b <= 5; $b++)<span hidden data-cap="{{ $b }}">{{ __('lesson.rate.bands.' . $b) }}</span>@endfor

        <button type="submit" class="btn-primary mt-3 w-full" data-r-submit disabled>
            <x-icon name="check" class="size-4" />
            {{ __('lesson.rate.submit') }}
        </button>
    </form>

    {{-- نتیجه (بعد از ثبت) --}}
    <div class="rating__result mt-5" data-r-result>
        <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
            <span class="score-badge" data-r-badge><span data-r-score></span><small>/{{ num(10) }}</small></span>
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-brand-900">{{ __('lesson.rate.saved') }}</p>
                <p class="mt-1 text-sm leading-6 text-slate-600" data-r-saved-caption></p>
            </div>
        </div>

        @if ($kind === 'repeat')
            {{-- مقایسه‌ی «اول» و «حالا» --}}
            <div class="mt-3 rounded-2xl border border-slate-200 p-4" data-r-compare>
                <div class="space-y-3">
                    @foreach (['first' => 'bg-slate-400', 'now' => 'bg-emerald-500'] as $k => $bar)
                        <div>
                            <div class="mb-1 flex justify-between text-xs font-bold text-slate-600">
                                <span>{{ __('lesson.rate.compare.' . $k) }}</span>
                                <span class="tabular-nums" data-r-cmp="{{ $k }}">—</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-0 rounded-full {{ $bar }} transition-[width] duration-700 ease-out" data-r-bar="{{ $k }}"></div></div>
                        </div>
                    @endforeach
                </div>
                @foreach (['up', 'same', 'down', 'none'] as $m)
                    <p hidden class="mt-3 text-sm font-bold leading-7 text-brand-900" data-r-msg="{{ $m }}">{{ __('lesson.rate.compare.' . $m) }}</p>
                @endforeach
            </div>
        @else
            <p class="mt-3 flex items-start gap-2 rounded-xl bg-brand-50/70 p-3.5 text-sm leading-7 text-slate-600">
                <x-icon name="info" class="mt-1 size-4 shrink-0 text-brand-700" />
                {{ __('lesson.rate.play.note') }}
            </p>
        @endif
    </div>

    {{-- تصویر اختیاری (فایل را در public/images/lesson/mascot.webp بگذارید؛ نبود، چیزی نشان داده نمی‌شود) --}}
    @if (file_exists(public_path('images/lesson/mascot.webp')))
        <img src="{{ asset('images/lesson/mascot.webp') }}" alt="" class="mx-auto mt-auto hidden max-h-56 w-auto pt-4 md:block" loading="lazy" decoding="async">
    @endif
</div>
