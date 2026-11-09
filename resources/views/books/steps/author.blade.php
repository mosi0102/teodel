{{--
    کتاب — مرحله ۴: نویسنده. بیوگرافی (fa + en)، تولد، کشور، آثار دیگر + چند کتاب دیگر هم‌سطح.
    تکمیل مرحله: مثل details.js. داده: author_* در app/Support/Books.php
--}}
@php
    $book = $song;
    $L = fn ($k, $r = []) => __('lesson.book.' . $k, $r);
    $fa = app()->getLocale() === 'fa';
    $initials = collect(explode(' ', $book['author']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $hue = ($book['id'] * 53 + 120) % 360;
    $more = collect(\App\Support\Books::all())->where('level', $book['level'])->where('id', '!=', $book['id'])->take(3);
@endphp

<div class="space-y-5" data-details-step data-bk-author>
    <section class="bk-box">
        <div class="flex items-center gap-4">
            <span class="bk-avatar" style="--h: {{ $hue }}" aria-hidden="true" dir="ltr">{{ $initials }}</span>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">{{ $L('about_author') }}</p>
                <h3 class="truncate text-xl font-extrabold text-brand-900" dir="ltr">{{ $book['author'] }}</h3>
                <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    @if (! empty($book['author_born']))<span>{{ $L('born') }}: <b class="text-slate-700">{{ num($book['author_born']) }}</b></span>@endif
                    @if (! empty($book['author_country']))<span>{{ $L('country') }}: <b class="text-slate-700" dir="ltr">{{ $book['author_country'] }}</b></span>@endif
                </p>
            </div>
        </div>

        <p class="mt-4 text-sm leading-8 text-slate-700 md:text-[0.95rem]">{{ $fa ? $book['author_bio_fa'] : $book['author_bio_en'] }}</p>
        @if ($fa && ! empty($book['author_bio_en']))
            <details class="bk-more">
                <summary>{{ $L('bio_en') }}</summary>
                <p dir="ltr" lang="en" class="mt-2 text-left text-sm leading-7 text-slate-600">{{ $book['author_bio_en'] }}</p>
            </details>
        @endif

        @if (! empty($book['author_works']))
            <p class="mt-4 text-xs font-bold text-slate-500">{{ $L('works') }}</p>
            <ul class="bk-chips mt-2" dir="ltr">
                @foreach ($book['author_works'] as $w)
                    <li><span class="bk-chip {{ $w === $book['title'] ? 'is-on' : '' }}">{{ $w }}</span></li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($more->count())
        <section aria-labelledby="bk-more-t">
            <h4 id="bk-more-t" class="mb-3 text-sm font-extrabold text-brand-900">{{ $L('more_books') }}</h4>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($more as $m)
                    <a href="{{ route('books.show', ['id' => $m['id']]) }}" class="bk-mini">
                        <span class="bk-cover bk-cover--sm" style="--h: {{ ($m['id'] * 53) % 360 }}" aria-hidden="true">
                            <span class="bk-cover__fake"><b dir="ltr">{{ $m['title'] }}</b></span>
                        </span>
                        <span class="min-w-0">
                            <b class="line-clamp-2 text-sm text-brand-900" dir="ltr">{{ $m['title'] }}</b>
                            <small class="block truncate text-xs text-slate-500" dir="ltr">{{ $m['author'] }} · {{ $m['cefr'] }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <span data-details-end class="block h-px" aria-hidden="true"></span>
</div>
