{{--
    کارت یک جمله‌ی Shadowing — رفتار: resources/js/modules/shadowing-topic.js
    ورودی: $s (جمله: n, text, audio)، $song، $mode = listen | record | full
      listen = فقط گوش‌دادن (۳ بار) | record = فقط ضبط | full = گوش‌دادن (۳ بار) و بعد ضبط
    صدا: $s['audio'] اگر بود پخش می‌شود، وگرنه صدای مرورگر (TTS) — بک‌اند فایل هر جمله را بدهد.
--}}
@php $target = 3; @endphp
<article class="shc" data-shc data-n="{{ $s['n'] }}" data-text="{{ $s['text'] }}" data-audio="{{ $s['audio'] }}" data-mode="{{ $mode }}" data-state="idle" data-msg-nomic="{{ __('shadowing.step.no_mic') }}" data-msg-denied="{{ __('shadowing.step.mic_denied') }}">
    <header class="flex items-center justify-between gap-3">
        <span class="shc__no">{{ __('shadowing.step.sentence', ['n' => num($s['n'])]) }}</span>
        <span class="shc__tick" aria-hidden="true"><x-icon name="check-circle" class="size-5" /></span>
    </header>

    <p class="shc__text" dir="ltr" lang="en">{{ $s['text'] }}</p>

    @if ($mode !== 'record')
        <div class="shc__listen">
            <button type="button" class="shc__play" data-shc-play aria-label="{{ __('shadowing.step.play') }}"
                data-label-play="{{ __('shadowing.step.play') }}" data-label-stop="{{ __('shadowing.step.stop') }}">
                <x-icon name="play" class="shc__ico-play size-5 fill-current" />
                <x-icon name="pause" class="shc__ico-stop size-5 fill-current" />
            </button>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5" aria-hidden="true">
                    @for ($d = 0; $d < $target; $d++)<i class="sx__dot" data-shc-dot></i>@endfor
                </div>
                <p class="mt-1.5 text-xs font-extrabold md:text-sm" data-shc-count aria-live="polite"
                    data-tpl="{{ __('shadowing.step.listened') }}" data-tpl-done="{{ __('shadowing.step.listened_done') }}" data-total="{{ $target }}">
                    {{ __('shadowing.step.listened', ['n' => num(0), 'total' => num($target)]) }}
                </p>
            </div>
            <button type="button" class="shc__speed" data-shc-speed aria-pressed="false" title="{{ __('shadowing.step.slow') }}">
                <span data-shc-speed-label>1x</span>
            </button>
        </div>
    @endif

    @if ($mode !== 'listen')
        <div class="shc__rec" data-shc-recbox>
            @if ($mode === 'record')
                <button type="button" class="shc__play !size-10" data-shc-play aria-label="{{ __('shadowing.step.play') }}"
                    data-label-play="{{ __('shadowing.step.play') }}" data-label-stop="{{ __('shadowing.step.stop') }}">
                    <x-icon name="play" class="shc__ico-play size-4 fill-current" />
                    <x-icon name="pause" class="shc__ico-stop size-4 fill-current" />
                </button>
            @endif
            <button type="button" class="shc__mic" data-shc-rec @if ($mode === 'full') disabled @endif
                data-label-start="{{ __('shadowing.step.record') }}" data-label-stop="{{ __('shadowing.step.stop_record') }}" data-label-locked="{{ __('shadowing.step.record_locked') }}">
                <x-icon name="mic" class="size-5" />
                <span data-shc-rec-label>{{ $mode === 'full' ? __('shadowing.step.record_locked') : __('shadowing.step.record') }}</span>
                <b class="tabular-nums" data-shc-timer hidden>0:00</b>
            </button>
            <audio data-shc-mine controls hidden class="h-9 min-w-0 flex-1" preload="none"></audio>
        </div>
        <p class="shc__msg" data-shc-msg role="status" hidden></p>

        {{-- نتیجه‌ی بررسی AI --}}
        <div class="shc__result" data-shc-result hidden>
            <div class="grid grid-cols-2 gap-3">
                <div class="shc__meter" data-meter="sim"><span>{{ __('shadowing.step.similarity') }}</span><b data-shc-sim>0%</b><i><u></u></i></div>
                <div class="shc__meter" data-meter="pron"><span>{{ __('shadowing.step.pronunciation') }}</span><b data-shc-pron>0%</b><i><u></u></i></div>
            </div>
            <p class="mt-3 text-xs font-bold text-slate-500">{{ __('shadowing.step.heard') }}</p>
            <p class="shc__words" dir="ltr" lang="en" data-shc-words></p>
            <p class="shc__verdict" data-shc-verdict
                data-good="{{ __('shadowing.step.verdict_good') }}" data-ok="{{ __('shadowing.step.verdict_ok') }}" data-retry="{{ __('shadowing.step.verdict_retry') }}"></p>
            <button type="button" class="btn-ghost !min-h-9 !text-xs" data-shc-retry><x-icon name="repeat" class="size-4" />{{ __('shadowing.step.retry') }}</button>
        </div>
        <button type="button" class="shc__sim" data-shc-sim-btn hidden>{{ __('shadowing.step.simulate') }}</button>
    @endif
</article>
