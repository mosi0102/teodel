{{--
    یک نسخه‌ی موضوع (سطح $tab: a1…c2): روشن/خاموش، صوت (آپلود یا ساخت با AI)، جمله‌ها با لحن، لغات سخت (با شماره‌ی جمله)، سؤال‌های مرحله‌ی ۵.
    مسیر داده: versions.{سطح}.* (قرارداد: app/Support/AdminShadowing.php)
--}}
@php
    $L = fn ($k, $r = []) => __('admincontent.shadowing.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
    $v = 'versions.'.$tab;
    $LV = strtoupper($tab);
@endphp
<div class="space-y-4">
    <label class="ad-switch ce-vswitch"><input type="checkbox" data-f="{{ $v }}.enabled">
        <span class="ad-switch__t"><b>{{ $L('enable', ['l' => $LV]) }}</b><small>{{ $L('enable_hint') }}</small></span></label>

    <p class="ad-empty" data-ce-if="{{ $v }}.enabled=false">{{ $L('disabled', ['l' => $LV]) }}</p>

    <div class="space-y-4" data-ce-if="{{ $v }}.enabled" hidden>
        {{-- صوت --}}
        <section class="ad-card" aria-labelledby="ce-sa-{{ $tab }}">
            <div class="ad-card__h">
                <div class="min-w-0">
                    <h2 class="ad-card__t" id="ce-sa-{{ $tab }}">{{ $L('audio_title') }} <span class="text-rose-500">*</span></h2>
                    <p class="ad-hint !mt-0.5">{{ $L('audio_lead') }}</p>
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="ce-src">
                    <p class="ce-src__t">{!! $ic('upload') !!}{{ $L('src_upload') }}</p>
                    <x-ad.up :f="$v.'.audio'" type="audio" :label="$L('audio_file')" :hint="$L('audio_hint')" />
                </div>
                <div class="ce-src">
                    <p class="ce-src__t">{!! $ic('wand') !!}{{ $L('src_tts') }}</p>
                    <div class="grid grid-cols-3 gap-2">
                        <x-ad.f :f="$v.'.tts.voice'" :label="$L('voice')" type="select" :options="__('admincontent.tts.voices')" />
                        <x-ad.f :f="$v.'.tts.accent'" :label="$L('accent')" type="select" :options="__('admincontent.accent')" />
                        <x-ad.f :f="$v.'.tts.speed'" :label="$L('speed')" type="select" :options="['0.8' => '0.8×', '0.9' => '0.9×', '1' => '1×', '1.1' => '1.1×']" />
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="ad-ai" data-ce-tts="{{ $v }}.audio" data-text="{{ $v }}.sentences" data-voice="{{ $v }}.tts.voice" data-accent="{{ $v }}.tts.accent" data-speed="{{ $v }}.tts.speed">{!! $ic('sparkles') !!}{{ $L('tts_go') }}</button>
                        <button type="button" class="btn-outline !min-h-10 !text-xs" data-ce-speak="{{ $v }}.sentences">{!! $ic('play', 'size-3.5') !!}{{ $L('tts_preview') }}</button>
                    </div>
                    <p class="ad-hint">{{ $L('tts_hint') }}</p>
                </div>
            </div>
        </section>

        {{-- جمله‌ها + لحن --}}
        <section class="ad-card" aria-labelledby="ce-ss-{{ $tab }}">
            <div class="ad-card__h">
                <div class="min-w-0">
                    <h2 class="ad-card__t" id="ce-ss-{{ $tab }}">{{ $L('sent_title') }} <span class="ad-badge" data-tone="info" data-ce-count="{{ $v }}.sentences"></span> <span class="text-rose-500">*</span></h2>
                    <p class="ad-hint !mt-0.5">{{ $L('sent_lead') }}</p>
                </div>
                <button type="button" class="ad-ai" data-ce-ai="shadowing_text" data-list="{{ $v }}.sentences">{!! $ic('sparkles') !!}{{ $L('sent_ai', ['l' => $LV]) }}</button>
            </div>
            <div class="space-y-3" data-ce-list="{{ $v }}.sentences" data-ce-tpl="sentences"></div>
            <p class="ad-empty" data-ce-empty="{{ $v }}.sentences" hidden>{{ $L('sent_empty') }}</p>
            <button type="button" class="ed-add" data-ce-add="{{ $v }}.sentences" data-new='{"text":"","prosody":""}'>{!! $ic('plus') !!}{{ $L('sent_add') }}</button>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            {{-- لغات سخت --}}
            <section class="ad-card" aria-labelledby="ce-sw-{{ $tab }}">
                <div class="ad-card__h">
                    <h2 class="ad-card__t" id="ce-sw-{{ $tab }}">{{ __('admincontent.words.title') }} <span class="ad-badge" data-tone="info" data-ce-count="{{ $v }}.words"></span></h2>
                    <button type="button" class="ad-ai ad-ai--sm" data-ce-ai="words_suggest" data-list="{{ $v }}.words" data-mode="append">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admincontent.words.ai') }}</button>
                </div>
                <div class="space-y-3" data-ce-list="{{ $v }}.words" data-ce-tpl="vwords"></div>
                <p class="ad-empty" data-ce-empty="{{ $v }}.words" hidden>{{ __('admincontent.words.empty') }}</p>
                <button type="button" class="ed-add" data-ce-add="{{ $v }}.words" data-new='{"phrase":"","where":1}'>{!! $ic('plus') !!}{{ __('admincontent.words.add') }}</button>
            </section>

            {{-- سؤال‌ها --}}
            <section class="ad-card" aria-labelledby="ce-sq-{{ $tab }}">
                <div class="ad-card__h">
                    <div class="min-w-0">
                        <h2 class="ad-card__t" id="ce-sq-{{ $tab }}">{{ $L('q_title') }} <span class="ad-badge" data-tone="info" data-ce-count="{{ $v }}.questions"></span> <span class="text-rose-500">*</span></h2>
                        <p class="ad-hint !mt-0.5">{{ $L('q_lead') }}</p>
                    </div>
                    <button type="button" class="ad-ai ad-ai--sm" data-ce-ai="questions_suggest" data-list="{{ $v }}.questions" data-mode="append">{!! $ic('sparkles', 'size-3.5') !!}{{ $L('q_ai') }}</button>
                </div>
                <div class="space-y-3" data-ce-list="{{ $v }}.questions" data-ce-tpl="questions"></div>
                <p class="ad-empty" data-ce-empty="{{ $v }}.questions" hidden>{{ $L('q_empty') }}</p>
                <button type="button" class="ed-add" data-ce-add="{{ $v }}.questions" data-new='{"q":"","answer":"","keys":"","criteria":""}'>{!! $ic('plus') !!}{{ $L('q_add') }}</button>
            </section>
        </div>
    </div>
</div>

{{-- قالب‌ها فقط یک بار (در تب اولین سطح) --}}
@if ($tab === 'a1')
    <template data-ce-tpl="sentences">
        <article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
            <header class="ed-item__h">
                <b class="ed-item__n">#<span data-ce-n></span></b>
                <span class="flex-1"></span>
                <button type="button" class="ad-ai ad-ai--sm" data-ce-ai-item="prosody">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admincontent.episode.sh_prosody_ai') }}</button>
                <button type="button" class="ed-del" data-ce-up aria-label="{{ __('admincontent.list.up') }}">{!! $ic('up') !!}</button>
                <button type="button" class="ed-del" data-ce-down aria-label="{{ __('admincontent.list.down') }}">{!! $ic('down') !!}</button>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </header>
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                <x-ad.f k="text" :label="$L('sentence')" type="textarea" dir="ltr" rows="3" />
                <x-ad.prosody k="prosody" />
            </div>
        </article>
    </template>
    <template data-ce-tpl="vwords">
        @include('admin.content.word-item', ['where' => true])
    </template>
    <template data-ce-tpl="questions">
        <article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
            <header class="ed-item__h">
                <b class="ed-item__n">#<span data-ce-n></span></b>
                <span class="flex-1"></span>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </header>
            <div class="ad-form-grid">
                <x-ad.f k="q" :label="$L('q')" dir="ltr" wide req />
                <x-ad.f k="answer" :label="$L('answer')" dir="ltr" wide />
                <x-ad.f k="keys" :label="$L('keys')" dir="ltr" :hint="$L('keys_hint')" />
                <x-ad.f k="criteria" :label="$L('criteria')" :hint="$L('criteria_hint')" />
            </div>
        </article>
    </template>
@endif
