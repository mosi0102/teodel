@props(['song'])

{{--
    مرحله‌ی ۳ (words): لیریک بدون پخش موزیک + لغت‌های دشوار (انتخاب پشتیبان) هایلایت‌شده.
    کلیک روی لغت هایلایت‌شده، یا روی هر کلمه‌ی دیگر، کارت توضیحات را باز می‌کند (دسکتاپ: کنار لیریک | موبایل: Bottom Sheet).
    ترجمه تار است و با کلیک نمایش داده می‌شود. بوکمارک هم‌جا هست. رفتار: resources/js/modules/words.js

    🔌 hook بک‌اند (شکل داده‌ها در app/Support/Words.php):
       $song['words']        لغت‌های دشوار (انتخاب پشتیبان)       $song['lyrics']  لیریک (app/Support/Lrc.php)
       $song['saved_words']  کلیدهای بوکمارک‌شده‌ی کاربر
       data-lookup-url      آدرس جست‌وجوی «کلمه‌ی دلخواه کاربر» (GET ?word=...؛ جواب JSON هم‌شکل یک لغت)
       رویداد بوکمارک:      $(document).on('word:bookmark', (e, { song, key, text, saved, line }) => {...})
--}}
@php
    $lines = \App\Support\Lrc::forSong($song);
    $words = \App\Support\Words::forSong($song);
    $saved = array_map('mb_strtolower', $song['saved_words'] ?? []);
    $byKey = collect($words)->keyBy('key');
    $total = $byKey->count();
@endphp

<div class="grid gap-4 lg:h-[34rem] lg:grid-cols-2 lg:gap-6"
    data-words-step data-song="{{ $song['id'] }}"
    data-words='@json($byKey, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-saved='@json($saved, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-lookup-url="{{ $song['lookup_url'] ?? '' }}"
    data-dict-url="{{ url('/dictionary') }}">

    {{-- کارت توضیحات (فقط lg به بالا؛ در موبایل همین کارت داخل Bottom Sheet است) --}}
    <div class="hidden min-h-0 lg:block" data-word-slot></div>

    {{-- لیریک --}}
    <section class="wlyrics flex min-h-0 flex-col overflow-hidden rounded-2xl bg-white lg:border lg:border-slate-200" aria-label="{{ __('lesson.lyrics.label') }}">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5 md:px-4">
            <p class="flex min-w-0 items-center gap-2 text-xs font-bold text-slate-500 md:text-sm">
                <x-icon name="info" class="size-4 shrink-0 text-brand-700" />
                <span>{{ __('lesson.words.hint') }}</span>
            </p>
            <div class="flex items-center gap-1.5">
                @if ($total)
                    <span class="lyrics-chip pointer-events-none" aria-live="polite">
                        <x-icon name="check-circle" class="size-4 text-emerald-600" />
                        {{ __('lesson.words.viewed') }}
                        <b class="tabular-nums" data-w-viewed>{{ num(0) }}</b>/<span class="tabular-nums">{{ num($total) }}</span>
                    </span>
                @endif
                <span class="lyrics-chip pointer-events-none" aria-live="polite">
                    <x-icon name="bookmark" class="size-4 text-brand-700" />
                    {{ __('lesson.words.saved') }}
                    <b class="tabular-nums" data-w-saved>{{ num(count($saved)) }}</b>
                </span>
            </div>
        </div>

        <div class="min-h-0 flex-1 px-3 py-3 md:px-5 md:py-4 lg:overflow-y-auto lg:overscroll-contain" data-w-scroll dir="ltr" lang="en">
            @forelse ($lines as $li => $l)
                <p class="wline" data-w-line="{{ $li }}">@foreach (\App\Support\Words::annotate($l['text'], $words) as $tk)@if ($tk['k'] === 'support')<button type="button" class="wd wd--support" data-w-key="{{ $tk['key'] }}">{{ $tk['text'] }}</button>@elseif ($tk['k'] === 'word')<span class="wd" data-w-word>{{ $tk['text'] }}</span>@else{{ $tk['text'] }}@endif @endforeach</p>
            @empty
                <p class="px-3 py-10 text-center text-sm leading-7 text-slate-500">{{ __('lesson.lyrics.empty') }}</p>
            @endforelse
        </div>
    </section>

    {{-- قالب کارت: js آن را هم داخل ستون دسکتاپ و هم داخل Bottom Sheet کپی می‌کند --}}
    <template data-word-tpl>
        <div class="wcard" data-word-card data-state="empty" data-total="{{ $total }}">

            {{-- هنوز لغتی انتخاب نشده --}}
            <div class="wcard__empty" data-wc-empty>
                <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="bookmark" class="size-7" /></span>
                <h3 class="mt-4 text-base font-extrabold text-brand-900">{{ __('lesson.words.empty_title') }}</h3>
                <p class="mt-1.5 max-w-xs text-sm leading-7 text-slate-500">{{ __('lesson.words.empty_text') }}</p>
                @if ($total)
                    <button type="button" class="btn-primary mt-5" data-wc-first>{{ __('lesson.words.first') }}</button>
                @endif
            </div>

            {{-- اطلاعات لغت --}}
            <div class="wcard__body" data-wc-body>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0" dir="ltr" lang="en">
                        <h3 class="break-words text-xl font-extrabold leading-8 text-brand-900 md:text-2xl"><bdi data-wc-phrase></bdi></h3>
                        <p class="text-sm text-slate-500" data-wc-type-wrap hidden>(<span data-wc-type></span>)</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" class="wc-bookmark" data-wc-bookmark aria-pressed="false"
                            data-label-add="{{ __('lesson.words.save') }}" data-label-remove="{{ __('lesson.words.unsave') }}" aria-label="{{ __('lesson.words.save') }}">
                            <x-icon name="bookmark" class="size-5" />
                        </button>
                        <button type="button" data-dialog-close class="grid size-10 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 lg:hidden" aria-label="{{ __('ui.close') }}">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>
                </div>

                {{-- تلفظ --}}
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2" dir="ltr">
                    @foreach (['uk', 'us'] as $acc)
                        <div class="flex items-center gap-2" data-wc-pron="{{ $acc }}" hidden>
                            <button type="button" class="wc-speak" data-wc-speak="{{ $acc }}" aria-label="{{ __('lesson.words.speak') }} ({{ __('lesson.words.' . $acc) }})">
                                <x-icon name="volume" class="size-5" />
                            </button>
                            <span class="text-sm text-slate-600" data-wc-ipa></span>
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500">{{ $acc }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- جمله‌ی موزیک که این لغت در آن آمده --}}
                <div class="mt-4 rounded-xl bg-slate-50 p-3" data-wc-ctx-wrap hidden>
                    <p class="text-xs font-bold text-slate-500">{{ __('lesson.words.in_song') }}</p>
                    <p class="wc-ctx mt-1 text-sm font-semibold leading-7 text-brand-900" dir="ltr" lang="en" data-wc-ctx></p>
                </div>

                <p class="mt-4 text-left text-[0.95rem] leading-7 text-slate-700" dir="ltr" lang="en" data-wc-meaning></p>

                <p class="mt-4 rounded-xl bg-amber-50 p-3.5 text-sm leading-7 text-amber-800" data-wc-nodata hidden>
                    {{ __('lesson.words.nodata') }}
                    <a class="mt-1 block font-bold underline" data-wc-dict href="{{ url('/dictionary') }}">{{ __('lesson.words.dict') }}</a>
                </p>

                {{-- جدول مشخصات --}}
                <dl class="wc-table mt-4" data-wc-table>
                    <div data-wc-row="level"><dt>{{ __('lesson.words.level') }}</dt><dd dir="ltr" class="text-left" data-wc-level></dd></div>
                    <div data-wc-row="synonyms"><dt>{{ __('lesson.words.synonyms') }}</dt><dd dir="ltr" class="text-left" data-wc-synonyms></dd></div>
                    <div data-wc-row="antonyms"><dt>{{ __('lesson.words.antonyms') }}</dt><dd dir="ltr" class="text-left" data-wc-antonyms></dd></div>
                    <div data-wc-row="translate">
                        <dt>{{ __('lesson.words.translate') }}</dt>
                        <dd>
                            {{-- ترجمه تار است؛ کلیک = نمایش / پنهان --}}
                            <button type="button" class="w-tr" data-wc-tr aria-pressed="false"
                                data-label-show="{{ __('lesson.words.show') }}" data-label-hide="{{ __('lesson.words.hide') }}" aria-label="{{ __('lesson.words.show') }}">
                                <span class="w-tr__text" data-wc-translate dir="auto"></span>
                                <span class="w-tr__veil">
                                    <x-icon name="eye-off" class="size-4 w-tr__off" />
                                    <x-icon name="eye" class="size-4 w-tr__on" />
                                    <span data-wc-tr-label>{{ __('lesson.words.show') }}</span>
                                </span>
                            </button>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- در حال دریافت (فقط برای کلمه‌ی دلخواه کاربر) --}}
            <div class="wcard__loading" data-wc-loading aria-live="polite">
                <span class="spinner"></span> {{ __('lesson.words.loading') }}
            </div>

            {{-- قبلی / بعدی بین لغت‌های پشتیبان --}}
            @if ($total)
                <div class="wcard__nav" data-wc-nav>
                    <button type="button" class="wc-nav-btn wc-nav-btn--solid" data-wc-prev>
                        <x-icon name="chevron-start" class="size-4 rtl:-scale-x-100" /> {{ __('lesson.words.prev') }}
                    </button>
                    <span class="text-sm font-extrabold tabular-nums text-white"><span data-wc-pos>—</span> / {{ num($total) }}</span>
                    <button type="button" class="wc-nav-btn" data-wc-next>
                        {{ __('lesson.words.next') }} <x-icon name="chevron-end" class="size-4 rtl:-scale-x-100" />
                    </button>
                </div>
            @endif
        </div>
    </template>
</div>

@push('modals')
{{-- موبایل / تبلت: کارت توضیحات به‌صورت Bottom Sheet (در lg به بالا کارت کنار لیریک است) --}}
<div id="word-dialog" class="dialog lg:!hidden" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-label="{{ __('lesson.words.label') }}">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel !max-h-[88dvh] !overflow-hidden !p-0">
        <div class="dialog-handle !mb-0 !mt-3 md:hidden"></div>
        <div data-word-slot class="flex max-h-[84dvh] min-h-0 flex-col"></div>
    </div>
</div>
@endpush
