@php
    $L = fn ($k, $r = []) => __('admincontent.shadowing.'.$k, $r);
    $F = fn ($k) => __('admincontent.f.'.$k);
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ce-si-t">
        <h2 class="ad-card__t mb-3" id="ce-si-t">{{ $L('info_title') }}</h2>
        <div class="ad-form-grid">
            <x-ad.f f="title" :label="$F('title')" dir="ltr" req />
            <x-ad.f f="category" :label="$F('category')" dir="ltr" req />
            <x-ad.f f="repeats" :label="$L('repeats')" type="number" :hint="$L('repeats_hint')" />
            <div></div>
            <x-ad.f f="desc_fa" :label="$F('desc_fa')" type="textarea" wide />
            <x-ad.f f="desc_en" :label="$F('desc_en')" type="textarea" dir="ltr" wide />
            <label class="ad-switch is-wide"><input type="checkbox" data-f="is_free"><span class="ad-switch__t"><b>{{ $F('free') }}</b><small>{{ $L('free_hint') }}</small></span></label>
        </div>
    </section>

    <section class="ad-card space-y-4" aria-labelledby="ce-sv-t">
        <h2 class="ad-card__t" id="ce-sv-t">{{ $L('versions_title') }}</h2>
        <p class="ad-hint !mt-1">{{ $L('versions_lead') }}</p>
        <div class="grid grid-cols-3 gap-2">
            @foreach (\App\Support\AdminShadowing::LEVELS as $l)
                <label class="ce-vchip"><input type="checkbox" data-f="versions.{{ $l }}.enabled"><span dir="ltr">{{ strtoupper($l) }}</span></label>
            @endforeach
        </div>
        <div class="border-t border-slate-100 pt-4"><x-ad.up f="cover" type="image" :label="$F('cover')" :hint="$F('cover_hint')" /></div>
    </section>
</div>
