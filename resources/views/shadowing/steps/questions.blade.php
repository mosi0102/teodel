{{--
    Shadowing — مرحله‌ی ۵ (questions): چند سوال هم‌سطح متن را شنیده/خوانده و با صدا جواب بده؛ AI بررسی می‌کند.
    رفتار: resources/js/modules/shadowing-topic.js ([data-sq-step])
    🔌 بک‌اند: data-answer-url (POST صوت + شماره‌ی سوال → JSON { score, matched: [..], transcript })
--}}
@php $qs = $song['questions']; @endphp
<div class="space-y-4" data-sq-step data-song="{{ $song['id'] }}" data-total="{{ count($qs) }}" data-answer-url="{{ $urls['answer'] ?? '' }}">
    @include('shadowing.steps._head', ['icon' => 'mic', 'hint' => __('shadowing.step.questions_hint'), 'count' => count($qs)])

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($qs as $i => $q)
            <article class="shc" data-sq data-i="{{ $i }}" data-keys='@json($q['keys'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)' data-question="{{ $q['q'] }}" data-state="idle" data-msg-nomic="{{ __('shadowing.step.no_mic') }}" data-msg-denied="{{ __('shadowing.step.mic_denied') }}">
                <header class="flex items-center justify-between gap-3">
                    <span class="shc__no">{{ __('shadowing.step.question', ['n' => num($i + 1)]) }}</span>
                    <span class="shc__tick" aria-hidden="true"><x-icon name="check-circle" class="size-5" /></span>
                </header>
                <div class="flex items-start gap-3">
                    <button type="button" class="shc__play !size-10 shrink-0" data-sq-play aria-label="{{ __('shadowing.step.play') }}"
                        data-label-play="{{ __('shadowing.step.play') }}" data-label-stop="{{ __('shadowing.step.stop') }}">
                        <x-icon name="volume" class="shc__ico-play size-4" />
                        <x-icon name="pause" class="shc__ico-stop size-4 fill-current" />
                    </button>
                    <p class="shc__text !mt-0" dir="ltr" lang="en">{{ $q['q'] }}</p>
                </div>

                <div class="shc__rec">
                    <button type="button" class="shc__mic" data-sq-rec
                        data-label-start="{{ __('shadowing.step.answer') }}" data-label-stop="{{ __('shadowing.step.stop_record') }}">
                        <x-icon name="mic" class="size-5" />
                        <span data-sq-rec-label>{{ __('shadowing.step.answer') }}</span>
                        <b class="tabular-nums" data-sq-timer hidden>0:00</b>
                    </button>
                </div>
                <p class="shc__msg" data-sq-msg role="status" hidden></p>

                <div class="shc__result" data-sq-result hidden>
                    <p class="text-xs font-bold text-slate-500">{{ __('shadowing.step.heard') }}</p>
                    <p class="shc__words !mt-1" dir="ltr" lang="en" data-sq-heard></p>
                    <p class="mt-3 text-xs font-bold text-slate-500">{{ __('shadowing.step.keywords') }}</p>
                    <p class="mt-1.5 flex flex-wrap gap-1.5" data-sq-keys></p>
                    <p class="shc__verdict" data-sq-verdict
                        data-good="{{ __('shadowing.step.q_good') }}" data-ok="{{ __('shadowing.step.q_ok') }}" data-retry="{{ __('shadowing.step.q_retry') }}"></p>
                    <details class="mt-2 rounded-xl bg-slate-50 px-3.5 py-2.5 text-sm">
                        <summary class="cursor-pointer text-xs font-extrabold text-brand-700">{{ __('shadowing.step.model') }}</summary>
                        <p class="mt-2 leading-7 text-slate-700" dir="ltr" lang="en">{{ $q['answer'] }}</p>
                    </details>
                    <button type="button" class="btn-ghost mt-2 !min-h-9 !text-xs" data-sq-retry><x-icon name="repeat" class="size-4" />{{ __('shadowing.step.retry') }}</button>
                </div>
                <button type="button" class="shc__sim" data-sq-sim-btn hidden>{{ __('shadowing.step.simulate') }}</button>
            </article>
        @endforeach
    </div>

    <div class="lx-summary" data-sq-summary hidden role="status">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check-circle" class="size-6" /></span>
        <p class="text-sm font-extrabold leading-7 text-emerald-900 md:text-base">{{ __('shadowing.step.questions_done') }}</p>
    </div>
</div>
