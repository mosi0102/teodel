@props(['song'])

{{--
    لیریک هم‌زمان با موزیک (مرحله‌ی ۲) — رفتار در resources/js/modules/lyrics.js

    🔌 hook بک‌اند: $song['lyrics'] = رشته‌ی LRC ("[00:21.53]Mm\n...") یا آرایه‌ی [['t' => 21.53, 'text' => '...', 'tr' => 'ترجمه (اختیاری)']].
       (تبدیل در app/Support/Lrc.php انجام می‌شود؛ اگر چیزی نرسد، لیریک نمونه برای تست نشان داده می‌شود.)
    هر خط: data-t = شروع، data-end = پایان خواندن (ثانیه). کلمه‌ها در جاوااسکریپت به نسبت طولشان زمان می‌گیرند.
--}}
@php
    $lines = \App\Support\Lrc::forSong($song);
    $hasTr = collect($lines)->contains(fn ($l) => ! empty($l['tr']));
@endphp

<div class="lyrics relative flex h-[26rem] flex-col overflow-hidden rounded-2xl bg-white lg:h-auto" data-lyrics data-follow="on" data-tr="off">
    @if (count($lines))
        {{-- نوار ابزار کوچک --}}
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 md:px-4">
            <p class="flex min-w-0 items-center gap-2 text-xs font-bold text-slate-500 md:text-sm">
                <x-icon name="file-text" class="size-4 shrink-0 text-brand-700" />
                <span class="truncate">{{ __('lesson.lyrics.hint') }}</span>
            </p>

            <div class="flex shrink-0 items-center gap-1.5">
                @if ($hasTr)
                    <button type="button" class="lyrics-chip" data-l-tr aria-pressed="false">
                        <x-icon name="languages" class="size-4" />
                        {{ __('lesson.lyrics.translate') }}
                    </button>
                @endif
                <button type="button" class="lyrics-chip" data-l-follow aria-pressed="true">
                    <x-icon name="headphones" class="size-4" />
                    {{ __('lesson.lyrics.follow') }}
                </button>
            </div>
        </div>

        {{-- خط‌ها (انگلیسی همیشه چپ‌به‌راست) --}}
        <div class="relative min-h-0 flex-1">
            <div class="lyrics__scroll absolute inset-0 overflow-y-auto px-3 py-4 md:px-5 md:py-6" data-l-scroll dir="ltr" tabindex="0" aria-label="{{ __('lesson.lyrics.label') }}">
                <ol class="mx-auto max-w-2xl space-y-1 text-left">
                    @foreach ($lines as $l)
                        <li>
                            <button type="button" class="lyric-line" data-l-line data-t="{{ $l['t'] }}" data-end="{{ $l['end'] }}" data-state="next">
                                <span class="lyric-text" lang="en">@foreach (preg_split('/(\s+)/u', $l['text'], -1, PREG_SPLIT_NO_EMPTY) as $w)<span class="lw" data-c="{{ mb_strlen($w) }}">{{ $w }}</span> @endforeach</span>
                                @if (! empty($l['tr']))
                                    <span class="lyric-tr" dir="auto">{{ $l['tr'] }}</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ol>
                <div class="h-24" aria-hidden="true"></div>
            </div>

            {{-- وقتی کاربر خودش اسکرول کرده: برگشت به خط جاری --}}
            <button type="button" hidden class="lyrics-now" data-l-now>
                <x-icon name="arrow-end" class="size-4 rotate-90" />
                {{ __('lesson.lyrics.now') }}
            </button>
        </div>
    @else
        <p class="m-auto px-6 text-center text-sm leading-7 text-slate-500">{{ __('lesson.lyrics.empty') }}</p>
    @endif
</div>
