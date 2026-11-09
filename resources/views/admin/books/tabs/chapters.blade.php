{{-- کتاب — فصل‌ها: افزودن/ترتیب/حذف، متن (پاراگراف‌ها با یک خط خالی)، زمان خواندن خودکار، خلاصه‌ی فصل (AI)، صوت اختیاری (آپلود یا AI) --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ce-bch-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-bch-t">{{ $L('ch_title') }} <span class="ad-badge" data-tone="info" data-ce-count="chapters"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('ch_lead') }}</p>
        </div>
    </div>
    <div class="space-y-3" data-ce-list="chapters"></div>
    <p class="ad-empty" data-ce-empty="chapters" hidden>{{ $L('ch_empty') }}</p>
    <button type="button" class="ed-add" data-ce-add="chapters" data-new='{"title":"","text":"","summary_fa":"","summary_en":"","audio":"","minutes":1}'>{!! $ic('plus') !!}{{ $L('ch_add') }}</button>

    <template data-ce-tpl="chapters">
        <article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
            <header class="ed-item__h">
                <b class="ed-item__n">{{ $L('ch') }} <span data-ce-n></span></b>
                <span class="ad-badge">{!! $ic('clock', 'size-3') !!} <span data-ce-text="minutes" data-fmt="num"></span> {{ $L('min') }}</span>
                <span class="flex-1"></span>
                <button type="button" class="ad-ai ad-ai--sm" data-ce-ai-item="chapter_summary">{!! $ic('sparkles', 'size-3.5') !!}{{ $L('ch_sum_ai') }}</button>
                <button type="button" class="ed-del" data-ce-up aria-label="{{ __('admincontent.list.up') }}">{!! $ic('up') !!}</button>
                <button type="button" class="ed-del" data-ce-down aria-label="{{ __('admincontent.list.down') }}">{!! $ic('down') !!}</button>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </header>
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <div class="space-y-3">
                    <x-ad.f k="title" :label="$L('ch_name')" dir="ltr" req />
                    <x-ad.f k="text" :label="$L('ch_text')" type="textarea" dir="ltr" rows="10" :hint="$L('ch_text_hint')" class="ce-chtext" />
                </div>
                <div class="space-y-3">
                    <x-ad.f k="summary_fa" :label="$L('ch_sum_fa')" type="textarea" rows="3" />
                    <x-ad.f k="summary_en" :label="$L('ch_sum_en')" type="textarea" dir="ltr" rows="3" />
                    <x-ad.up k="audio" type="audio" :label="$L('ch_audio')" :hint="$L('ch_audio_hint')" />
                </div>
            </div>
        </article>
    </template>
</section>
