@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ed-ly-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ed-ly-t">{{ __('adminsongs.lyrics.title') }}</h2>
            <p class="ad-hint !mt-0.5">{{ __('adminsongs.lyrics.lead') }}</p>
        </div>
    </div>

    <div class="ed-bar">
        <button type="button" class="btn-outline !min-h-9 !px-3 !text-xs" data-ed-lrc-open>{{ __('adminsongs.lyrics.paste') }}</button>
        <button type="button" class="btn-outline !min-h-9 !px-3 !text-xs" data-ed-lrc-copy>{!! $ic('copy', 'size-3.5') !!}{{ __('adminsongs.lyrics.copy') }}</button>
        <button type="button" class="btn-outline !min-h-9 !px-3 !text-xs" data-ed-tap-toggle aria-pressed="false">{!! $ic('clock', 'size-3.5') !!}{{ __('adminsongs.lyrics.tap') }}</button>
        <button type="button" class="ad-ai ad-ai--sm ms-auto" data-ed-ai="lyrics_tr">{!! $ic('sparkles', 'size-3.5') !!}{{ __('adminsongs.lyrics.ai') }}</button>
    </div>

    <div class="ed-tap" data-ed-tap hidden>
        <p class="text-xs font-bold leading-6 text-slate-600">{{ __('adminsongs.lyrics.tap_help') }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <button type="button" class="btn-primary !min-h-11 !px-6" data-ed-tap-mark>{{ __('adminsongs.lyrics.mark') }} <kbd class="ed-kbd">Space</kbd></button>
            <button type="button" class="btn-ghost !min-h-11 !px-3 !text-xs" data-ed-tap-undo>{{ __('adminsongs.lyrics.undo') }}</button>
            <span class="text-xs font-bold text-slate-500" data-ed-tap-pos></span>
        </div>
    </div>

    <ol class="ed-rows ed-lyrics" data-ed-list="lyrics" aria-live="off"></ol>
    <p class="ad-empty" data-ed-empty="lyrics" hidden>{{ __('adminsongs.lyrics.empty') }}</p>
    <button type="button" class="ed-add" data-ed-add="lyrics">{!! $ic('plus') !!}{{ __('adminsongs.lyrics.add') }}</button>

    <template data-ed-tpl="lyrics">
        <li class="ed-row" data-ed-item>
            <span class="ed-idx" aria-hidden="true"></span>
            <div class="ed-time">
                <input class="field !min-h-10 !text-xs" dir="ltr" inputmode="decimal" data-k="t" data-time placeholder="00:00.00" aria-label="{{ __('adminsongs.time') }}">
                <button type="button" class="ed-now" data-ed-now title="{{ __('adminsongs.now') }}" aria-label="{{ __('adminsongs.now') }}">{!! $ic('clock', 'size-3.5') !!}</button>
            </div>
            <input class="field !min-h-10" dir="ltr" data-k="text" placeholder="{{ __('adminsongs.lyrics.text') }}" aria-label="{{ __('adminsongs.lyrics.text') }}">
            <input class="field !min-h-10" data-k="tr" placeholder="{{ __('adminsongs.lyrics.tr') }}" aria-label="{{ __('adminsongs.lyrics.tr') }}">
            <button type="button" class="ed-del" data-ed-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
        </li>
    </template>
</section>
