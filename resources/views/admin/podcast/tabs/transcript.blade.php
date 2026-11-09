{{-- اپیزود — ترانسکریپت زمان‌دار (مرحله‌ی ۲). آپلود SRT/LRC یا پیست، یا ساخت/ترجمه با AI. زمان‌ها با پخش‌کننده‌ی بالای صفحه --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ce-et-t">
    <div class="ad-card__h flex-wrap">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-et-t">{{ $L('tr_title') }} <span class="ad-badge" data-tone="info" data-ce-count="transcript"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('tr_lead') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-ce-import="transcript">{!! $ic('upload') !!}{{ $L('tr_import') }}</button>
            <button type="button" class="ad-ai" data-ce-ai="transcript_ai" data-list="transcript">{!! $ic('sparkles') !!}{{ $L('tr_ai') }}</button>
            <button type="button" class="ad-ai" data-ce-ai="transcript_tr" data-list="transcript">{!! $ic('sparkles') !!}{{ $L('tr_translate') }}</button>
        </div>
    </div>

    <div class="ed-rows" data-ce-list="transcript"></div>
    <p class="ad-empty" data-ce-empty="transcript" hidden>{{ $L('tr_empty') }}</p>
    <button type="button" class="ed-add" data-ce-add="transcript" data-new='{"t":"","text":"","tr":""}'>{!! $ic('plus') !!}{{ $L('tr_add') }}</button>

    <template data-ce-tpl="transcript">
        <div class="ed-row ce-trow" data-ce-item>
            <span class="ed-idx" data-ce-n></span>
            <div class="ed-time">
                <input class="field !min-h-9 !w-24 !px-2 !text-xs" data-k="t" data-time inputmode="decimal" dir="ltr" placeholder="00:00.00" aria-label="{{ $L('time') }}">
                <button type="button" class="ed-now" data-ce-now="t" title="{{ $L('now') }}" aria-label="{{ $L('now') }}">{!! $ic('clock') !!}</button>
                <button type="button" class="ed-now" data-ce-seek="t" title="{{ $L('seek') }}" aria-label="{{ $L('seek') }}">{!! $ic('play', 'size-3.5') !!}</button>
            </div>
            <input class="field !min-h-9 !text-xs" data-k="text" dir="ltr" placeholder="{{ $L('text') }}" aria-label="{{ $L('text') }}">
            <input class="field !min-h-9 !text-xs" data-k="tr" placeholder="{{ $L('translation') }}" aria-label="{{ $L('translation') }}">
            <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
        </div>
    </template>
</section>
