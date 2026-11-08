{{--
    Shadowing — مرحله‌ی ۴ (text): متن کامل، لغت‌های دشوار (کلیک = معنی + ذخیره) و جدول «هر لغت در کدام جمله آمده».
    ذخیره‌ی لغت همان رویداد word:bookmark موزیک است (با شماره‌ی خط/جمله‌ی منبع).
--}}
@php
    $wordRows = collect(\App\Support\Words::forSong($song))->map(function ($w) use ($song) {
        $at = [];
        foreach ($song['sentences'] as $s) {
            if (mb_stripos($s['text'], $w['phrase']) !== false) {
                $at[] = $s['n'];
            }
        }

        return $w + ['at' => $at];
    });
@endphp
<div class="space-y-4">
    <x-lesson.words :song="$song" />

    @if ($wordRows->count())
        <section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5" aria-labelledby="sh-hard-title">
            <h3 id="sh-hard-title" class="flex items-center gap-2 text-sm font-extrabold text-brand-900 md:text-base">
                <x-icon name="bookmark" class="size-5 text-brand-700" />{{ __('shadowing.step.hard_title') }}
            </h3>
            <ul class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach ($wordRows as $w)
                    <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-brand-900" dir="ltr" lang="en">{{ $w['phrase'] }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $w['translate'] }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                            @foreach ($w['at'] as $n)
                                <button type="button" class="sh-ref" data-sh-jump="{{ $n }}" data-key="{{ mb_strtolower($w['phrase']) }}">{{ __('shadowing.step.in_sentence', ['n' => num($n)]) }}</button>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
