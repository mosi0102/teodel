{{-- اپیزود — بخش‌های shadowing (مرحله‌ی ۵) با لحن و ریتم (prosody) و پیش‌نمایش زنده --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ce-es-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-es-t">{{ $L('sh_title') }} <span class="ad-badge" data-tone="info" data-ce-count="shadowing"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('sh_lead') }}</p>
        </div>
        <button type="button" class="ad-ai" data-ce-ai="shadowing_suggest" data-list="shadowing">{!! $ic('sparkles') !!}{{ $L('sh_ai') }}</button>
    </div>
    <div class="space-y-3" data-ce-list="shadowing"></div>
    <p class="ad-empty" data-ce-empty="shadowing" hidden>{{ $L('sh_empty') }}</p>
    <button type="button" class="ed-add" data-ce-add="shadowing" data-new='{"start":"","end":"","text":"","prosody":""}'>{!! $ic('plus') !!}{{ $L('sh_add') }}</button>

    <template data-ce-tpl="shadowing">
        <article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
            <header class="ed-item__h">
                <b class="ed-item__n">#<span data-ce-n></span></b>
                <button type="button" class="ed-range-play" data-ce-seek="start">{!! $ic('play', 'size-3') !!}{{ $L('seek') }}</button>
                <span class="flex-1"></span>
                <button type="button" class="ad-ai ad-ai--sm" data-ce-ai-item="prosody">{!! $ic('sparkles', 'size-3.5') !!}{{ $L('sh_prosody_ai') }}</button>
                <button type="button" class="ed-del" data-ce-up aria-label="{{ __('admincontent.list.up') }}">{!! $ic('up') !!}</button>
                <button type="button" class="ed-del" data-ce-down aria-label="{{ __('admincontent.list.down') }}">{!! $ic('down') !!}</button>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </header>
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                <div class="space-y-3">
                    <x-ad.f k="text" :label="$L('sh_text')" type="textarea" dir="ltr" rows="3" :hint="$L('sh_text_hint')" />
                    <div class="grid grid-cols-2 gap-3">
                        <div class="ad-field"><label class="ad-label">{{ $L('start') }}</label>
                            <div class="ed-time"><input class="field !min-h-10 !text-xs" data-k="start" data-time inputmode="decimal" dir="ltr"><button type="button" class="ed-now" data-ce-now="start" aria-label="{{ $L('now') }}">{!! $ic('clock') !!}</button></div></div>
                        <div class="ad-field"><label class="ad-label">{{ $L('end') }}</label>
                            <div class="ed-time"><input class="field !min-h-10 !text-xs" data-k="end" data-time inputmode="decimal" dir="ltr"><button type="button" class="ed-now" data-ce-now="end" aria-label="{{ $L('now') }}">{!! $ic('clock') !!}</button></div></div>
                    </div>
                </div>
                <x-ad.prosody k="prosody" />
            </div>
        </article>
    </template>
</section>
