{{-- کتاب — خلاصه (مرحله‌ی ۱ کاربر) و نویسنده (مرحله‌ی ۴) --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<div class="grid gap-4 xl:grid-cols-2">
    <section class="ad-card" aria-labelledby="ce-bs-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="ce-bs-t">{{ $L('summary_title') }}</h2>
            <button type="button" class="ad-ai" data-ce-ai="book_summary" data-fields="summary_fa,summary_en">{!! $ic('sparkles') !!}{{ __('admincontent.ai.write') }}</button>
        </div>
        <div class="space-y-3">
            <x-ad.f f="summary_fa" :label="$L('summary_fa')" type="textarea" rows="5" req ai />
            <x-ad.f f="summary_en" :label="$L('summary_en')" type="textarea" dir="ltr" rows="5" ai />
        </div>
    </section>

    <section class="ad-card" aria-labelledby="ce-ba-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="ce-ba-t">{{ $L('author_title') }}</h2>
            <button type="button" class="ad-ai" data-ce-ai="author_bio" data-fields="author_bio_fa,author_bio_en">{!! $ic('sparkles') !!}{{ __('admincontent.ai.write') }}</button>
        </div>
        <div class="ad-form-grid">
            <x-ad.f f="author_born" :label="$L('born')" type="year" />
            <x-ad.f f="author_country" :label="$L('country')" dir="ltr" />
            <x-ad.f f="author_works" :label="$L('works')" dir="ltr" wide :hint="__('admincontent.f.tags_hint')" />
            <x-ad.f f="author_bio_fa" :label="$L('bio_fa')" type="textarea" wide req ai />
            <x-ad.f f="author_bio_en" :label="$L('bio_en')" type="textarea" dir="ltr" wide ai />
        </div>
    </section>
</div>
