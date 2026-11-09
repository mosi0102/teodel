{{--
    کتاب — مرحله ۱: معرفی. خلاصه (fa + en)، اطلاعات کامل، فهرست فصل‌ها، دانلود فایل و شروع/ادامه‌ی خواندن.
    تکمیل مرحله: مثل «اطلاعات» موزیک (resources/js/modules/details.js) — رسیدن به انتها + چند ثانیه ماندن.
    داده: app/Support/Books.php
--}}
@php
    $book = $song;
    $hue = ($book['id'] * 53) % 360;
    $L = fn ($k, $r = []) => __('lesson.book.' . $k, $r);
    $info = array_filter([
        'author' => $book['author'],
        'level' => $book['cefr'],
        'type' => __('books.type.' . $book['type']),
        'genre' => $book['genre'] ?? null,
        'year' => isset($book['year']) ? num($book['year']) : null,
        'publisher' => $book['publisher'] ?? null,
        'pages' => num($book['pages']),
        'chapters' => num($book['chapters_count']),
        'time' => \App\Support\Books::fmtMinutes((int) $book['minutes']),
        'isbn' => $book['isbn'] ?? null,
        'language' => $L('lang_en'),
    ], fn ($v) => $v !== null && $v !== '');
    $ltr = ['author', 'level', 'genre', 'publisher', 'isbn'];
    $limited = ! ($subscribed ?? false) && ! $book['is_free'];
@endphp

<div class="space-y-5" data-details-step data-bk-intro data-book="{{ $book['id'] }}">

    <div class="bk-hero">
        <div class="bk-cover bk-cover--lg" style="--h: {{ $hue }}" aria-hidden="true">
            @if ($book['cover'])
                <img src="{{ $book['cover'] }}" alt="" class="size-full object-cover">
            @else
                <span class="bk-cover__fake"><b dir="ltr">{{ $book['title'] }}</b><small dir="ltr">{{ $book['author'] }}</small></span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h3 class="text-xl font-extrabold leading-8 text-brand-900 md:text-2xl" dir="ltr" lang="en"><bdi>{{ $book['title'] }}</bdi></h3>
            <p class="mt-1 text-sm text-slate-500" dir="ltr">{{ $book['author'] }}</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700" dir="ltr">{{ $book['cefr'] }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ __('books.type.' . $book['type']) }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600"><x-icon name="timer" class="size-3.5" />{{ \App\Support\Books::fmtMinutes((int) $book['minutes']) }}</span>
                @if ($book['audio'])
                    <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700"><x-icon name="headphones" class="size-3.5" />{{ __('books.card.audio') }}</span>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('books.step', ['id' => $book['id'], 'step' => 2]) }}" class="btn-primary !min-h-11" data-bk-go="2">
                    <x-icon name="book" class="size-4" />
                    <span data-bk-start-label data-resume="{{ $L('resume') }}">{{ $L('start') }}</span>
                </a>
                @if ($book['file_url'])
                    <a href="{{ $urls['download'] ?? route('books.download', ['id' => $book['id']]) }}" class="btn-outline !min-h-11" target="_blank" rel="noopener">
                        <x-icon name="download" class="size-4" />
                        {{ $L('download') }}
                    </a>
                @endif
            </div>

            @if ($limited)
                <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold leading-6 text-amber-800">
                    <x-icon name="lock" class="size-4 shrink-0" />
                    {{ $L('free_note') }}
                    <a href="{{ url('/subscription') }}" class="underline underline-offset-2">{{ $L('subscribe') }}</a>
                </p>
            @endif
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <section class="bk-box" aria-labelledby="bk-sum-t">
            <h4 id="bk-sum-t" class="bk-box__t"><x-icon name="file-text" class="size-4" />{{ $L('summary') }}</h4>
            <p class="text-sm leading-8 text-slate-700 md:text-[0.95rem]">{{ $book['summary_fa'] && app()->getLocale() === 'fa' ? $book['summary_fa'] : $book['summary_en'] }}</p>
            @if (app()->getLocale() === 'fa' && $book['summary_en'])
                <details class="bk-more">
                    <summary>{{ $L('summary_en') }}</summary>
                    <p dir="ltr" lang="en" class="mt-2 text-left text-sm leading-7 text-slate-600">{{ $book['summary_en'] }}</p>
                </details>
            @endif
        </section>

        <section class="bk-box" aria-labelledby="bk-info-t">
            <h4 id="bk-info-t" class="bk-box__t"><x-icon name="info" class="size-4" />{{ $L('info') }}</h4>
            <dl class="bk-info">
                @foreach ($info as $k => $v)
                    <div><dt>{{ $L('f.' . $k) }}</dt><dd @if (in_array($k, $ltr, true)) dir="ltr" @endif>{{ $v }}</dd></div>
                @endforeach
            </dl>
        </section>
    </div>

    <section class="bk-box" aria-labelledby="bk-toc-t">
        <h4 id="bk-toc-t" class="bk-box__t"><x-icon name="list" class="size-4" />{{ $L('contents') }}</h4>
        <ol class="bk-toc">
            @foreach ($book['chapters'] as $c)
                <li>
                    <a href="{{ route('books.step', ['id' => $book['id'], 'step' => 2]) }}" data-bk-go="2" data-bk-ch-link="{{ $c['n'] }}" class="bk-toc__a {{ $c['locked'] ? 'is-locked' : '' }}">
                        <span class="bk-toc__n">{{ num($c['n']) }}</span>
                        <span class="min-w-0 flex-1 truncate font-bold" dir="ltr" lang="en">{{ $c['title'] }}</span>
                        <span class="shrink-0 text-xs text-slate-400">{{ \App\Support\Books::fmtMinutes((int) $c['minutes']) }}</span>
                        @if ($c['locked'])<x-icon name="lock" class="size-4 shrink-0 text-rose-500" />@endif
                    </a>
                </li>
            @endforeach
        </ol>
    </section>

    <span data-details-end class="block h-px" aria-hidden="true"></span>
</div>
