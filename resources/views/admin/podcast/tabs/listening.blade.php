{{-- اپیزود — جمله‌های دیکته (مرحله‌ی ۴). سیستم/AI پیشنهاد می‌دهد؛ ادمین فقط تأیید یا ویرایش می‌کند (کاربر انتخاب نمی‌کند) --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ce-el-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-el-t">{{ $L('dict_title') }} <span class="ad-badge" data-tone="info" data-ce-count="dictation"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('dict_lead') }}</p>
        </div>
        <button type="button" class="ad-ai" data-ce-ai="dictation_suggest" data-list="dictation">{!! $ic('sparkles') !!}{{ $L('dict_ai') }}</button>
    </div>
    <div class="ed-cards" data-ce-list="dictation"></div>
    <p class="ad-empty" data-ce-empty="dictation" hidden>{{ $L('dict_empty') }}</p>
    <button type="button" class="ed-add" data-ce-add="dictation" data-new='{"text":"","start":"","end":"","approved":true}'>{!! $ic('plus') !!}{{ $L('dict_add') }}</button>

    <template data-ce-tpl="dictation">
        <article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
            <header class="ed-item__h">
                <b class="ed-item__n">#<span data-ce-n></span></b>
                <button type="button" class="ed-range-play" data-ce-seek="start">{!! $ic('play', 'size-3') !!}{{ $L('seek') }}</button>
                <span class="flex-1"></span>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </header>
            <x-ad.f k="text" :label="$L('text')" type="textarea" dir="ltr" rows="2" />
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="ad-field">
                    <label class="ad-label">{{ $L('start') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" data-k="start" data-time inputmode="decimal" dir="ltr" placeholder="00:00.00"><button type="button" class="ed-now" data-ce-now="start" aria-label="{{ $L('now') }}">{!! $ic('clock') !!}</button></div>
                </div>
                <div class="ad-field">
                    <label class="ad-label">{{ $L('end') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" data-k="end" data-time inputmode="decimal" dir="ltr" placeholder="00:00.00"><button type="button" class="ed-now" data-ce-now="end" aria-label="{{ $L('now') }}">{!! $ic('clock') !!}</button></div>
                </div>
            </div>
            <label class="ad-switch mt-3 !p-2.5"><input type="checkbox" data-k="approved"><span class="ad-switch__t"><b>{{ $L('approved') }}</b><small>{{ $L('approved_hint') }}</small></span></label>
        </article>
    </template>
</section>
