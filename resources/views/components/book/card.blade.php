@props(['book', 'subscribed' => false, 'index' => 0])

{{--
    کارت یک کتاب در لیست /books. کاور عمودی (۳:۴) + عنوان/نویسنده + سطح/نوع + صفحه/فصل/زمان خواندن + پیشرفت.
    قرارداد داده: app/Support/Books.php (id, title, author, cover, cefr, type, pages, chapters_count, minutes, is_free, recommended, audio, progress)
    پیشرفت داینامیک مثل موزیک با data-status / data-progress (progress-sync.js).
--}}
@php
    $limited = ! $subscribed && ! $book['is_free'];
    $progress = (int) $book['progress'];
    $status = \App\Support\Progress::status($progress);
    $hue = ($book['id'] * 53) % 360;
    $href = route('books.show', ['id' => $book['id']]);
@endphp

<article class="song-card bk-card group relative flex animate-rise flex-col rounded-2xl border border-transparent bg-white p-4 shadow-card transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-lift sm:p-5"
    style="animation-delay: {{ min($index, 8) * 45 }}ms" data-song data-song-id="{{ $book['id'] }}" data-total-steps="{{ $book['total_steps'] }}" data-status="{{ $status }}" data-progress="{{ $progress }}">

    @if ($book['recommended'])
        <span class="pc-reco"><x-icon name="star" class="size-3.5" />{{ __('books.card.recommended') }}</span>
    @endif

    <div class="flex items-start gap-4">
        <a href="{{ $href }}" class="bk-cover" tabindex="-1" aria-hidden="true" style="--h: {{ $hue }}">
            @if ($book['cover'])
                <img src="{{ $book['cover'] }}" alt="" width="84" height="112" loading="lazy" decoding="async" class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
            @else
                <span class="bk-cover__fake">
                    <b dir="ltr">{{ $book['title'] }}</b>
                    <small dir="ltr">{{ $book['author'] }}</small>
                </span>
            @endif
        </a>

        <div class="min-w-0 flex-1">
            <h3 class="line-clamp-2 text-[15px] font-bold leading-6 text-brand-900 sm:text-base"><a href="{{ $href }}" class="hover:text-brand-700"><bdi>{{ $book['title'] }}</bdi></a></h3>
            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-slate-500">
                <x-icon :name="$limited ? 'lock' : 'lock-open'" class="size-4 shrink-0 {{ $limited ? 'text-rose-500' : 'text-emerald-500' }}" />
                <bdi class="truncate">{{ $book['author'] }}</bdi>
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700" dir="ltr">{{ $book['cefr'] }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ __('books.type.' . $book['type']) }}</span>
                @if ($book['audio'])
                    <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700"><x-icon name="headphones" class="size-3" />{{ __('books.card.audio') }}</span>
                @endif
                @if ($limited)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600"><x-icon name="lock" class="size-3" />{{ __('books.card.limited') }}</span>
                @elseif (! $subscribed)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-600"><x-icon name="check-circle" class="size-3" />{{ __('books.card.free') }}</span>
                @endif
            </div>
        </div>
    </div>

    <dl class="mt-4 grid grid-cols-3 gap-2">
        <div class="bk-stat"><dt>{{ __('books.card.pages') }}</dt><dd>{{ num($book['pages']) }}</dd></div>
        <div class="bk-stat"><dt>{{ __('books.card.chapters') }}</dt><dd>{{ num($book['chapters_count']) }}</dd></div>
        <div class="bk-stat"><dt>{{ __('books.card.time') }}</dt><dd>{{ \App\Support\Books::fmtMinutes((int) $book['minutes']) }}</dd></div>
    </dl>

    <div class="mt-3">
        <div class="mb-1.5 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-600">{{ __('books.card.progress') }}</span>
            <span class="tabular-nums font-extrabold text-brand-900" data-prog-text>{{ num($progress) }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-600 transition-[width] duration-500" data-prog-bar style="width: {{ $progress }}%"></div></div>
    </div>

    <div class="mt-auto pt-4">
        <a href="{{ $href }}" class="btn-primary w-full !min-h-11">
            {{ $status === 'new' ? __('books.cta.start') : ($status === 'done' ? __('books.cta.review') : __('books.cta.continue')) }}
            <x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" />
        </a>
    </div>
</article>
