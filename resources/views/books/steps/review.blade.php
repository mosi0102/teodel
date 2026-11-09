{{--
    کتاب — مرحله ۳: مرور. جمله‌های محبوب و لغت‌های ذخیره‌شده از همین کتاب (book-reader.js آن‌ها را از سرور/مرورگر پر می‌کند)،
    لغت‌های کلیدی کتاب، و خلاصه‌ی هر فصل با AI.
    🔌 خلاصه‌ی AI: data-summary-url (POST { book, chapter } ← { text }) یا رویداد لغوشدنی book:chapter-summary-request
       یا Teodel.books.summarize = async ({ book, chapter }) => text   (دمو: summary_* داده‌ی نمونه با کمی تأخیر)
    تکمیل مرحله: مثل details.js (رسیدن به انتها + چند ثانیه ماندن).
--}}
@php
    $book = $song;
    $L = fn ($k, $r = []) => __('lesson.book.' . $k, $r);
    $fa = app()->getLocale() === 'fa';
    $jf = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $demo = collect($book['chapters'])->mapWithKeys(fn ($c) => [$c['n'] => $fa ? ($c['summary_fa'] ?? null) : ($c['summary_en'] ?? null)]);
@endphp

<div class="space-y-5" data-details-step data-bk-review data-book="{{ $book['id'] }}"
    data-demo='@json($demo, $jf)'
    @if (! empty($urls['summary'])) data-summary-url="{{ $urls['summary'] }}" @endif
    data-t-remove="{{ $L('remove') }}" data-t-goto="{{ $L('goto') }}" data-t-chapter="{{ $L('chapter') }}">

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- جمله‌های محبوب --}}
        <section class="bk-box" aria-labelledby="bk-rs-t">
            <h4 id="bk-rs-t" class="bk-box__t"><x-icon name="quote" class="size-4" />{{ $L('saved_sentences') }} <b class="bk-count" data-bk-rs-count>{{ num(0) }}</b></h4>
            <ul class="bk-sents" data-bk-rs-list></ul>
            <p class="bk-empty" data-bk-rs-empty>{{ $L('saved_sentences_empty') }}</p>
        </section>

        {{-- لغت‌های ذخیره‌شده از این کتاب --}}
        <section class="bk-box" aria-labelledby="bk-rw-t">
            <h4 id="bk-rw-t" class="bk-box__t"><x-icon name="bookmark" class="size-4" />{{ $L('saved_words') }} <b class="bk-count" data-bk-rw-count>{{ num(0) }}</b></h4>
            <ul class="bk-chips" data-bk-rw-list dir="ltr"></ul>
            <p class="bk-empty" data-bk-rw-empty>{{ $L('saved_words_empty') }}</p>
        </section>
    </div>

    @if (! empty($book['words']))
        <section class="bk-box" aria-labelledby="bk-kw-t">
            <h4 id="bk-kw-t" class="bk-box__t"><x-icon name="star" class="size-4" />{{ $L('key_words') }}</h4>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($book['words'] as $w)
                    <li class="bk-kw">
                        <div class="flex min-w-0 items-baseline gap-2" dir="ltr">
                            <b class="truncate text-brand-900">{{ $w['phrase'] }}</b>
                            <small class="shrink-0 text-xs text-slate-400">{{ $w['type'] }} · {{ $w['level'] }}</small>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-600">{{ $w['translate'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- خلاصه‌ی فصل‌ها با AI --}}
    <section class="bk-box" aria-labelledby="bk-cs-t">
        <h4 id="bk-cs-t" class="bk-box__t"><x-icon name="sparkles" class="size-4" />{{ $L('ch_summaries') }}</h4>
        <ol class="space-y-2.5">
            @foreach ($book['chapters'] as $c)
                <li class="bk-sum" data-bk-sum="{{ $c['n'] }}" data-state="idle">
                    <div class="flex items-center gap-3">
                        <span class="bk-toc__n">{{ num($c['n']) }}</span>
                        <b class="min-w-0 flex-1 truncate text-sm text-brand-900" dir="ltr">{{ $c['title'] }}</b>
                        @if ($c['locked'])
                            <x-icon name="lock" class="size-4 shrink-0 text-rose-500" />
                        @else
                            <button type="button" class="bk-ai" data-bk-sum-go>
                                <x-icon name="sparkles" class="size-4" />
                                <span data-bk-sum-label data-again="{{ $L('ai_again') }}">{{ $L('ai_summary') }}</span>
                            </button>
                        @endif
                    </div>
                    <p class="bk-sum__load" data-bk-sum-load hidden><span class="spinner"></span>{{ $L('ai_loading') }}</p>
                    <p class="bk-sum__err" data-bk-sum-err hidden>{{ $L('ai_error') }}</p>
                    <div class="bk-sum__out" data-bk-sum-out hidden>
                        <span class="bk-sum__badge"><x-icon name="sparkles" class="size-3" />{{ $L('ai_badge') }}</span>
                        <p data-bk-sum-text dir="auto"></p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <a href="{{ url('/words?tab=sentences') }}" class="btn-ghost w-full sm:w-auto">{{ $L('all_words') }}<x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" /></a>

    <span data-details-end class="block h-px" aria-hidden="true"></span>
</div>
