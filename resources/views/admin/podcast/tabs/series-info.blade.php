@php
    $L = fn ($k, $r = []) => __('admincontent.podcast.'.$k, $r);
    $F = fn ($k) => __('admincontent.f.'.$k);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ce-pi-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="ce-pi-t">{{ $L('info_title') }}</h2>
            <button type="button" class="ad-ai" data-ce-ai="podcast_info" data-fields="category,desc_fa,desc_en,tags">{!! $ic('sparkles') !!}{{ __('admincontent.ai.fill') }}</button>
        </div>
        <div class="ad-form-grid">
            <x-ad.f f="title" :label="$F('title')" dir="ltr" req />
            <x-ad.f f="host" :label="$L('host')" dir="ltr" req />
            <x-ad.f f="category" :label="$F('category')" dir="ltr" ai />
            <x-ad.f f="level" :label="$F('level')" type="select" :options="['a' => __('admincontent.level.a'), 'b' => __('admincontent.level.b'), 'c' => __('admincontent.level.c')]" req />
            <x-ad.f f="accent" :label="$F('accent')" type="select" :options="__('admincontent.accent')" />
            <x-ad.f f="tags" :label="$F('tags')" dir="ltr" :hint="$F('tags_hint')" ai />
            <x-ad.f f="desc_fa" :label="$F('desc_fa')" type="textarea" wide req ai />
            <x-ad.f f="desc_en" :label="$F('desc_en')" type="textarea" dir="ltr" wide ai />
            <label class="ad-switch"><input type="checkbox" data-f="is_free"><span class="ad-switch__t"><b>{{ $F('free') }}</b><small>{{ $L('free_hint') }}</small></span></label>
            <label class="ad-switch"><input type="checkbox" data-f="recommended"><span class="ad-switch__t"><b>{{ $F('recommended') }}</b><small>{{ $L('reco_hint') }}</small></span></label>
        </div>
    </section>

    <section class="ad-card" aria-labelledby="ce-pf-t">
        <h2 class="ad-card__t mb-3" id="ce-pf-t">{{ $F('files') }}</h2>
        <x-ad.up f="cover" type="image" :label="$F('cover')" :hint="$F('cover_hint')" />
    </section>
</div>
