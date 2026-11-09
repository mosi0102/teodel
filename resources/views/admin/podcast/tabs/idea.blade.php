{{-- اپیزود — Main idea رسمی (مرحله‌ی ۷، کنار نوشته‌ی کاربر) + متن پرسش «چقدر متوجه شدی؟» (مرحله‌ی ۱ و ۶) --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ce-ei-t">
        <div class="ad-card__h">
            <div class="min-w-0">
                <h2 class="ad-card__t" id="ce-ei-t">{{ $L('idea_title') }}</h2>
                <p class="ad-hint !mt-0.5">{{ $L('idea_lead') }}</p>
            </div>
            <button type="button" class="ad-ai" data-ce-ai="main_idea" data-fields="main_idea">{!! $ic('sparkles') !!}{{ $L('idea_ai') }}</button>
        </div>
        <x-ad.f f="main_idea" :label="$L('idea')" type="textarea" dir="ltr" req ai />
    </section>

    <section class="ad-card" aria-labelledby="ce-eq-t">
        <h2 class="ad-card__t mb-1" id="ce-eq-t">{{ $L('q_title') }}</h2>
        <p class="ad-hint mb-3">{{ $L('q_lead') }}</p>
        <div class="space-y-3">
            <x-ad.f f="q_play.fa" :label="$L('q_play_fa')" :ph="$L('q_default')" />
            <x-ad.f f="q_play.en" :label="$L('q_play_en')" dir="ltr" :ph="$L('q_ph_en')" />
            <x-ad.f f="q_repeat.fa" :label="$L('q_repeat_fa')" :ph="$L('q_default')" />
            <x-ad.f f="q_repeat.en" :label="$L('q_repeat_en')" dir="ltr" :ph="$L('q_ph_en')" />
        </div>
    </section>
</div>
