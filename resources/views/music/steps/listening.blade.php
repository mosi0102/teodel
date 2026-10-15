{{--
    مرحله 4: listening — دو بخش «آسان» (یک کلمه‌ی خالی) و «سخت» (یک عبارت / چند کلمه).
    هر تمرین = کلیپ کوچک از موزیک + متن با جای خالی. بررسی درست/غلط: resources/js/modules/listening.js
    داده‌ها: app/Support/Listening.php ($song['listening']؛ اگر نرسد، نمونه از لیریک ساخته می‌شود)
--}}
@php
    $sets = \App\Support\Listening::forSong($song);
    $all = array_merge($sets['easy'], $sets['hard']);
    $src = (string) ($song['audio'] ?? '');
    if ($src !== '' && ! preg_match('#^(https?:)?//|^/#i', $src)) {
        $src = asset($src);
    }
    // مدت دمو (وقتی فایل صوتی نیست): کمی بیشتر از پایان آخرین بخش
    $demoDuration = (int) ceil(collect($all)->max('end') ?? 60) + 5;
@endphp

<div class="space-y-7" data-clips data-listening-step data-song="{{ $song['id'] }}" data-src="{{ $src }}" data-duration="{{ $demoDuration }}" data-total="{{ count($all) }}"
    data-msg-correct="{{ __('lesson.listening.correct') }}" data-msg-wrong="{{ __('lesson.listening.wrong') }}" data-msg-fill="{{ __('lesson.listening.fill') }}" data-msg-revealed="{{ __('lesson.listening.revealed') }}">
    @foreach (['easy', 'hard'] as $mode)
        @if (count($sets[$mode]))
            <section aria-labelledby="ls-{{ $mode }}-title">
                {{-- سرتیتر بخش (آسان / سخت) --}}
                <div class="lsec lsec--{{ $mode }}">
                    <h3 id="ls-{{ $mode }}-title" class="flex items-center gap-2.5 text-base font-extrabold text-brand-900 md:text-lg">
                        <span class="lsec__icon"><x-icon name="gauge" class="size-6" /></span>
                        {{ __('lesson.listening.' . $mode) }}
                    </h3>
                    <div class="flex items-center gap-3">
                        <p class="hidden text-xs font-bold text-slate-600 sm:block md:text-sm">{{ __('lesson.listening.' . $mode . '_hint') }}</p>
                        <span class="lyrics-chip pointer-events-none" aria-live="polite">
                            <x-icon name="check-circle" class="size-4 text-emerald-600" />
                            <b class="tabular-nums" data-ls-count="{{ $mode }}">{{ num(0) }}</b>/<span class="tabular-nums">{{ num(count($sets[$mode])) }}</span>
                        </span>
                    </div>
                </div>
                <p class="mt-2 text-xs font-bold leading-6 text-slate-500 sm:hidden">{{ __('lesson.listening.' . $mode . '_hint') }}</p>

                <div class="mt-3 grid gap-4 md:mt-4 md:grid-cols-2">
                    @foreach ($sets[$mode] as $ex)
                        <article class="lx" data-ex data-id="{{ $ex['id'] }}" data-mode="{{ $mode }}" data-state="idle" data-attempts="0"
                            data-answers='@json($ex['answers'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>
                            <x-lesson.clip :song="$song" :start="$ex['start']" :end="$ex['end']" :n="$ex['n']" />

                            <form class="lx__body" data-ex-form novalidate autocomplete="off">
                                <p class="lx-text" dir="ltr" lang="en">{{ $ex['before'] }}@foreach ($ex['answers'] as $gi => $w)@if ($gi > 0) @endif<input type="text" class="lx-input" data-gap="{{ $gi }}" style="--n: {{ max(mb_strlen($w), 2) }}"
                                        autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="{{ $loop->last ? 'done' : 'next' }}"
                                        aria-label="{{ __('lesson.listening.gap', ['i' => num($gi + 1), 'n' => num(count($ex['answers']))]) }}">@endforeach{{ $ex['after'] }}</p>

                                <p class="lx-msg" data-ex-msg role="status" aria-live="polite"></p>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button type="submit" class="btn-primary !px-6" data-ex-check>
                                        <x-icon name="check" class="size-4" /> {{ __('lesson.listening.check') }}
                                    </button>
                                    <button type="button" class="btn-ghost" data-ex-show hidden>{{ __('lesson.listening.show') }}</button>
                                </div>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    @if (! count($all))
        <p class="rounded-2xl bg-slate-50 px-6 py-12 text-center text-sm leading-7 text-slate-500">{{ __('lesson.listening.empty') }}</p>
    @else
        {{-- خلاصه‌ی پایان (وقتی همه‌ی تمرین‌ها انجام شد) --}}
        <div class="lx-summary" data-ls-summary hidden role="status">
            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check-circle" class="size-6" /></span>
            <p class="text-sm font-extrabold leading-7 text-emerald-900 md:text-base" data-ls-summary-text data-tpl="{{ __('lesson.listening.summary') }}"></p>
        </div>
    @endif
</div>
