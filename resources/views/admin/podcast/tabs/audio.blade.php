{{-- اپیزود — صوت و اطلاعات (مرحله‌ی ۱ «play» و مرحله‌ی ۷ «details») --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $F = fn ($k) => __('admincontent.f.'.$k);
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ce-ea-t">
        <h2 class="ad-card__t mb-3" id="ce-ea-t">{{ $L('info_title') }}</h2>
        <div class="ad-form-grid">
            <x-ad.f f="title" :label="$F('title')" dir="ltr" req />
            <x-ad.f f="number" :label="$L('number')" type="number" />
            <x-ad.f f="accent" :label="$F('accent')" type="select" :options="__('admincontent.accent')" />
            <x-ad.f f="wpm" :label="$L('wpm')" type="number" :hint="$L('wpm_hint')" />
            <x-ad.f f="points" :label="$L('points')" type="number" />
            <x-ad.f f="tags" :label="$F('tags')" dir="ltr" :hint="$F('tags_hint')" />
            <x-ad.f f="description" :label="$L('description')" type="textarea" dir="ltr" wide />
        </div>
    </section>

    <section class="ad-card space-y-4" aria-labelledby="ce-ef-t">
        <h2 class="ad-card__t" id="ce-ef-t">{{ $F('files') }}</h2>
        <x-ad.up f="audio" type="audio" :label="$L('audio')" :hint="$L('audio_hint')" duration="duration" req />
        <p class="text-xs font-bold text-slate-500">{{ $L('duration') }}: <b class="tabular-nums" dir="ltr" data-ce-dur></b></p>
        <div class="border-t border-slate-100 pt-4"><x-ad.up f="cover" type="image" :label="$F('cover')" :hint="$L('cover_hint')" /></div>
    </section>
</div>
