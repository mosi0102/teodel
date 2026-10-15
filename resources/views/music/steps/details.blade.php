{{--
    مرحله 8: details — اطلاعات موزیکی که کاربر تمرین کرده: کاور، نام، خواننده، مشخصات، تفسیر و توضیحات.
    مشخصات از خودِ $song می‌آیند (همان‌هایی که در دیالوگ «اطلاعات موزیک» لیست هستند)؛ متن‌ها: app/Support/Details.php
    رفتار (پیشرفت مرحله): resources/js/modules/details.js
--}}
@php
    $d = \App\Support\Details::forSong($song);
    $hue = ($song['id'] * 47) % 360;
    $val = fn ($v) => filled($v) ? $v : null;

    [$mm, $ss] = array_pad(array_map('intval', explode(':', (string) ($song['duration'] ?? ''))), 2, 0);
    $levelKey = $song['level'] ?? null;

    // [کلید، آیکون، مقدار نمایشی] — مقدار خالی = «نامشخص»
    $facts = [
        ['level', 'award', $levelKey ? __('music.levels.' . $levelKey) : null],
        ['accent', 'globe', $val($song['accent'] ?? null) ? __('music.accents.' . $song['accent']) : null],
        ['wpm', 'gauge', $val($song['wpm'] ?? null) ? __('music.info.wpm_value', ['n' => num($song['wpm'])]) : null],
        ['topic', 'bookmark', $val($song['topic'] ?? null)],
        ['year', 'calendar', $val($song['year'] ?? null) ? num($song['year']) : null],
        ['duration', 'timer', $val($song['duration'] ?? null) ? num(sprintf('%02d:%02d', $mm, $ss)) : null],
        ['genre', 'music', $val($song['genre'] ?? null)],
        ['album', 'list-music', $val($song['album'] ?? null)],
    ];
    $hasText = count($d['interpretation']) || count($d['sections']);
@endphp

<div class="space-y-5 md:space-y-6" data-details-step data-song="{{ $song['id'] }}">

    {{-- کاور + نام + مشخصات --}}
    <section class="det-hero" aria-labelledby="det-title">
        <div class="det-cover">
            @if (! empty($song['cover']))
                <img src="{{ $song['cover'] }}" alt="" class="size-full object-cover" loading="lazy" decoding="async">
            @else
                <div class="grid size-full place-items-center text-white" style="background: radial-gradient(120% 90% at 20% 10%, hsl({{ ($hue + 40) % 360 }} 85% 68%), transparent 60%), linear-gradient(135deg, hsl({{ $hue }} 70% 52%), hsl({{ ($hue + 50) % 360 }} 65% 32%))">
                    <x-icon name="music" class="size-16 opacity-80 md:size-20" />
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h2 id="det-title" class="text-2xl font-extrabold leading-tight text-brand-900 md:text-4xl"><bdi>{{ $song['title'] }}</bdi></h2>
            <p class="mt-1.5 text-sm font-bold text-slate-500 md:text-base"><bdi>{{ $song['artist'] }}</bdi></p>

            <dl class="det-facts">
                @foreach ($facts as [$k, $icon, $value])
                    <div class="det-fact" @if ($k === 'level' && $levelKey) data-level="{{ $levelKey }}" @endif>
                        <span class="det-fact__icon"><x-icon :name="$icon" class="size-5" /></span>
                        <div class="min-w-0">
                            <dt>{{ __('music.info.' . $k) }}</dt>
                            <dd @if (! $value) class="is-unknown" @endif><bdi>{{ $value ?? __('lesson.details.unknown') }}</bdi></dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if (! $hasText)
        <p class="rounded-2xl bg-slate-50 px-6 py-10 text-center text-sm leading-7 text-slate-500">{{ __('lesson.details.empty') }}</p>
    @endif

    {{-- تفسیر موزیک --}}
    @if (count($d['interpretation']))
        <section class="det-card" aria-labelledby="det-interp">
            <h3 id="det-interp" class="det-h"><span class="det-h__icon"><x-icon name="file-text" class="size-5" /></span>{{ __('lesson.details.interpretation') }}</h3>
            <div class="det-prose">
                @foreach ($d['interpretation'] as $p)
                    <p>{{ $p }}</p>
                @endforeach
            </div>
        </section>
    @endif

    {{-- توضیحات: آرایه‌های ادبی و نکته‌های زبان انگلیسی --}}
    @foreach ($d['sections'] as $si => $sec)
        <section class="det-card" aria-labelledby="det-sec-{{ $si }}">
            <h3 id="det-sec-{{ $si }}" class="det-h det-h--{{ $sec['type'] }}">
                <span class="det-h__icon"><x-icon :name="$sec['type'] === 'literary' ? 'sparkles' : 'languages'" class="size-5" /></span>
                {{ $sec['title'] ?? __('lesson.details.notes') }}
            </h3>

            @if (count($sec['paragraphs']))
                <div class="det-prose">
                    @foreach ($sec['paragraphs'] as $p)<p>{{ $p }}</p>@endforeach
                </div>
            @endif

            @if (count($sec['items']))
                <ul class="mt-4 grid gap-3 md:grid-cols-2 md:gap-4">
                    @foreach ($sec['items'] as $it)
                        <li class="det-note det-note--{{ $sec['type'] }}">
                            @if ($it['term'] !== '')<p class="det-note__term"><bdi>{{ $it['term'] }}</bdi></p>@endif
                            @if ($it['example'] !== '')<blockquote class="det-note__ex" dir="ltr" lang="en">{{ $it['example'] }}</blockquote>@endif
                            @if ($it['text'] !== '')<p class="det-note__text">{{ $it['text'] }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    {{-- نشانگر پایان محتوا: وقتی دیده شد، مرحله تمام می‌شود --}}
    <div data-details-end aria-hidden="true" class="h-px"></div>
</div>
