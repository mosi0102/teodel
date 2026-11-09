{{-- کتاب — فایل دانلودی (PDF/EPUB) و صوت کل کتاب (اختیاری: آپلود یا ساخت با AI) --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<div class="grid gap-4 xl:grid-cols-2">
    <section class="ad-card" aria-labelledby="ce-bf-t">
        <h2 class="ad-card__t mb-1" id="ce-bf-t">{{ $L('file_title') }}</h2>
        <p class="ad-hint mb-3">{{ $L('file_lead') }}</p>
        <x-ad.up f="file_url" type="file" :label="$L('file')" :hint="$L('file_hint')" />
    </section>

    <section class="ad-card" aria-labelledby="ce-bau-t">
        <h2 class="ad-card__t mb-1" id="ce-bau-t">{{ $L('audio_title') }}</h2>
        <p class="ad-hint mb-3">{{ $L('audio_lead') }}</p>
        <x-ad.up f="audio" type="audio" :label="$L('audio')" :hint="$L('audio_hint')">
            <button type="button" class="ad-ai ad-ai--sm" data-ce-tts="audio" data-text="chapters">{!! $ic('sparkles', 'size-3.5') !!}{{ $L('audio_ai') }}</button>
        </x-ad.up>
    </section>
</div>
