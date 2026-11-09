{{--
    کتاب — مرحله ۲: خواندن. رفتار کامل در resources/js/modules/book-reader.js:
      فصل‌ها (یکی‌یکی)، نشانک و «ادامه از جایی که ماندم»، تنظیمات خواندن برای هر کتاب جدا (تم/فونت/اندازه)،
      لمس لغت ← کارت معنی (همان قالب کارت لغت موزیک)، انتخاب جمله ← «ذخیره‌ی جمله» (هایلایت داخل کتاب + تب «جمله‌های محبوب» در /words).
    ⚠️ فصل‌های قفل (کاربر رایگان) متن ندارند؛ فقط کارت اشتراک نشان داده می‌شود.
    🔌 آدرس‌ها ($urls از BookController) و رویدادها: بالای book-reader.js
--}}
@php
    $book = $song;
    $L = fn ($k, $r = []) => __('lesson.book.' . $k, $r);
    $jf = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $words = collect($book['words'] ?? [])->keyBy(fn ($w) => mb_strtolower($w['phrase']));
    $open = collect($book['chapters'])->where('locked', false)->count();
    $t = [
        'saved' => $L('sentence_saved'), 'removed' => $L('sentence_removed'), 'bm' => $L('bookmark_saved'), 'resumed' => $L('resumed'),
        'pct' => $L('read_pct'), 'chapter_of' => $L('chapter_of'), 'none' => $L('word_none'), 'finish' => $L('finish_reading'),
    ];
@endphp

<div class="bk-read" data-bk-reader data-book="{{ $book['id'] }}" data-title="{{ $book['title'] }}"
    data-total="{{ count($book['chapters']) }}" data-open="{{ $open }}"
    data-words='@json($words, $jf)'
    data-t='@json($t, $jf)'
    @if (! empty($urls['progress'])) data-progress-url="{{ $urls['progress'] }}" @endif
    @if (! empty($urls['settings'])) data-settings-url="{{ $urls['settings'] }}" @endif
    @if (! empty($urls['sentences'])) data-sentences-url="{{ $urls['sentences'] }}" @endif
    @if (! empty($urls['lookup'])) data-lookup-url="{{ $urls['lookup'] }}" @endif
    data-dict-url="{{ url('/dictionary') }}">

    {{-- نوار ابزار خواننده (چسبان) --}}
    <div class="bk-bar" data-bk-bar>
        <label class="bk-chsel">
            <span class="sr-only">{{ $L('chapters_label') }}</span>
            <x-icon name="list" class="size-4 shrink-0 text-brand-700" />
            <select data-bk-chapter-select>
                @foreach ($book['chapters'] as $c)
                    <option value="{{ $c['n'] }}">{{ $L('chapter', ['n' => num($c['n'])]) }} · {{ $c['title'] }}@if ($c['locked']) 🔒@endif</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" class="size-4 shrink-0 text-slate-400" />
        </label>

        <span class="bk-pct" aria-live="polite"><span data-bk-pct>{{ $L('read_pct', ['n' => num(0)]) }}</span></span>

        <div class="flex items-center gap-1">
            <button type="button" class="bk-ibtn" data-bk-bookmark aria-pressed="false" aria-label="{{ $L('bookmark_add') }}" title="{{ $L('bookmark_add') }}">
                <x-icon name="bookmark" class="size-5" />
            </button>
            <button type="button" class="bk-ibtn" data-bk-settings-open aria-expanded="false" aria-controls="bk-settings" aria-label="{{ $L('settings') }}" title="{{ $L('settings') }}">
                <x-icon name="type" class="size-5" />
            </button>
        </div>

        {{-- تنظیمات خواندن (برای همین کتاب) --}}
        <div id="bk-settings" class="bk-set" data-bk-settings hidden role="group" aria-label="{{ $L('settings') }}">
            <p class="bk-set__t">{{ $L('theme') }}</p>
            <div class="bk-seg" role="radiogroup" aria-label="{{ $L('theme') }}">
                @foreach (['light' => 'sun', 'sepia' => 'book', 'dark' => 'moon'] as $th => $ic)
                    <button type="button" role="radio" aria-checked="{{ $th === 'light' ? 'true' : 'false' }}" class="bk-seg__b bk-swatch--{{ $th }}" data-bk-theme="{{ $th }}">
                        <x-icon :name="$ic" class="size-4" />{{ $L('themes.' . $th) }}
                    </button>
                @endforeach
            </div>
            <p class="bk-set__t">{{ $L('font') }}</p>
            <div class="bk-seg" role="radiogroup" aria-label="{{ $L('font') }}">
                @foreach (['sans', 'serif'] as $f)
                    <button type="button" role="radio" aria-checked="{{ $f === 'sans' ? 'true' : 'false' }}" class="bk-seg__b bk-font--{{ $f }}" data-bk-font="{{ $f }}">
                        <span class="text-base" dir="ltr">Aa</span>{{ $L('fonts.' . $f) }}
                    </button>
                @endforeach
            </div>
            <p class="bk-set__t">{{ $L('size') }}</p>
            <div class="flex items-center gap-2">
                <button type="button" class="bk-ibtn !border !border-slate-200" data-bk-size="-1" aria-label="{{ $L('smaller') }}"><span class="text-sm font-extrabold" dir="ltr">A−</span></button>
                <input type="range" min="15" max="26" step="1" value="18" class="bk-range" data-bk-size-range aria-label="{{ $L('size') }}">
                <button type="button" class="bk-ibtn !border !border-slate-200" data-bk-size="1" aria-label="{{ $L('bigger') }}"><span class="text-lg font-extrabold" dir="ltr">A+</span></button>
            </div>
        </div>
    </div>

    <p class="mt-3 flex items-start gap-2 text-xs leading-6 text-slate-500" data-focus-hide>
        <x-icon name="info" class="mt-0.5 size-4 shrink-0 text-brand-700" />{{ $L('tap_hint') }}
    </p>

    {{-- برگه‌ی خواندن (تم/فونت/اندازه روی همین عنصر) --}}
    <div class="bk-page bk-theme-light bk-font-sans" data-bk-page style="--bk-size: 18px">
        @foreach ($book['chapters'] as $c)
            <section class="bk-ch" data-bk-ch="{{ $c['n'] }}" @if (! $loop->first) hidden @endif aria-labelledby="bk-ch-t-{{ $c['n'] }}">
                <p class="bk-ch__k">{{ $L('chapter_of', ['n' => num($c['n']), 'total' => num(count($book['chapters']))]) }}</p>
                <h3 id="bk-ch-t-{{ $c['n'] }}" class="bk-ch__t" dir="ltr" lang="en">{{ $c['title'] }}</h3>

                @if ($c['locked'])
                    <div class="bk-lock">
                        <span class="grid size-14 place-items-center rounded-full bg-rose-50 text-rose-500"><x-icon name="lock" class="size-7" /></span>
                        <p class="mt-3 text-base font-extrabold text-brand-900">{{ $L('locked_ch') }}</p>
                        <p class="mt-1.5 max-w-sm text-sm leading-7 text-slate-500">{{ $L('locked_text') }}</p>
                        <a href="{{ url('/subscription') }}" class="btn-primary mt-4">{{ $L('subscribe') }}</a>
                    </div>
                @else
                    @php $audio = $c['audio'] ?? $book['audio']; @endphp
                    @if ($audio)
                        <div class="bk-audio">
                            <x-icon name="headphones" class="size-5 shrink-0 text-violet-600" />
                            <span class="sr-only">{{ $L('listen') }}</span>
                            <audio controls preload="none" src="{{ $audio }}" class="min-w-0 flex-1" data-bk-audio></audio>
                        </div>
                    @endif
                    <div class="bk-text" dir="ltr" lang="en" data-bk-text>
                        @foreach ($c['paragraphs'] as $pi => $p)
                            <p data-bk-p="{{ $pi }}">{{ $p }}</p>
                        @endforeach
                    </div>
                    <p class="bk-end" data-bk-end><span>{{ $L('end_ch') }}</span></p>
                @endif

                <nav class="bk-chnav" aria-label="{{ $L('chapters_label') }}">
                    @if (! $loop->first)
                        <button type="button" class="btn-outline !min-h-11" data-bk-goto="{{ $c['n'] - 1 }}">
                            <x-icon name="chevron-start" class="size-4 rtl:-scale-x-100" />{{ $L('prev_ch') }}
                        </button>
                    @else
                        <span></span>
                    @endif
                    @if (! $loop->last)
                        <button type="button" class="btn-primary !min-h-11" data-bk-goto="{{ $c['n'] + 1 }}">
                            {{ $L('next_ch') }}<x-icon name="chevron-end" class="size-4 rtl:-scale-x-100" />
                        </button>
                    @endif
                </nav>
            </section>
        @endforeach
    </div>

    {{-- دکمه‌ی شناور «ذخیره‌ی جمله» (بعد از انتخاب متن) --}}
    <button type="button" class="bk-savebtn" data-bk-save-sentence hidden>
        <x-icon name="quote" class="size-4" />{{ $L('save_sentence') }}
    </button>

    <p class="bk-toast" data-bk-toast role="status" aria-live="polite" hidden></p>
</div>

{{-- کارت لغت (دسکتاپ: دیالوگ وسط | موبایل: Bottom Sheet) --}}
@push('modals')
<div id="bk-word" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bk-w-phrase">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0" dir="ltr" lang="en">
                <h2 id="bk-w-phrase" class="break-words text-xl font-extrabold leading-8 text-brand-900 md:text-2xl" data-bw-phrase></h2>
                <p class="text-sm text-slate-500" data-bw-type-wrap hidden>(<span data-bw-type></span>)</p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <button type="button" class="wc-bookmark" data-bw-bookmark aria-pressed="false"
                    data-label-add="{{ __('lesson.words.save') }}" data-label-remove="{{ __('lesson.words.unsave') }}" aria-label="{{ __('lesson.words.save') }}">
                    <x-icon name="bookmark" class="size-5" />
                </button>
                <button type="button" data-dialog-close class="grid size-10 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="{{ __('ui.close') }}">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2" dir="ltr">
            <button type="button" class="wc-speak" data-bw-speak aria-label="{{ __('lesson.words.speak') }}"><x-icon name="volume" class="size-5" /></button>
            <span class="text-sm text-slate-600" data-bw-ipa></span>
            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500" data-bw-level hidden></span>
        </div>

        <div class="mt-4 rounded-xl bg-slate-50 p-3" data-bw-ctx-wrap hidden>
            <p class="wc-ctx text-sm font-semibold leading-7 text-brand-900" dir="ltr" lang="en" data-bw-ctx></p>
        </div>

        <p class="mt-4 text-left text-[0.95rem] leading-7 text-slate-700" dir="ltr" lang="en" data-bw-meaning></p>

        <p class="mt-4 rounded-xl bg-amber-50 p-3.5 text-sm leading-7 text-amber-800" data-bw-nodata hidden>
            {{ $L('word_none') }}
            <a class="mt-1 block font-bold underline" data-bw-dict href="{{ url('/dictionary') }}">{{ $L('word_dict') }}</a>
        </p>

        <dl class="wc-table mt-4" data-bw-table>
            <div data-bw-row="synonyms"><dt>{{ __('lesson.words.synonyms') }}</dt><dd dir="ltr" class="text-left" data-bw-synonyms></dd></div>
            <div data-bw-row="antonyms"><dt>{{ __('lesson.words.antonyms') }}</dt><dd dir="ltr" class="text-left" data-bw-antonyms></dd></div>
            <div data-bw-row="translate">
                <dt>{{ __('lesson.words.translate') }}</dt>
                <dd>
                    <button type="button" class="w-tr" data-bw-tr aria-pressed="false" aria-label="{{ __('lesson.words.show') }}">
                        <span class="w-tr__text" data-bw-translate dir="auto"></span>
                        <span class="w-tr__veil">
                            <x-icon name="eye-off" class="size-4 w-tr__off" />
                            <x-icon name="eye" class="size-4 w-tr__on" />
                            <span>{{ __('lesson.words.show') }}</span>
                        </span>
                    </button>
                </dd>
            </div>
        </dl>

        <div class="bk-loading" data-bw-loading hidden aria-live="polite"><span class="spinner"></span> {{ __('lesson.words.loading') }}</div>
    </div>
</div>
@endpush
