{{-- اپیزود — لغات دشوار (مرحله‌ی ۳) + انتخاب لغت‌هایی که در PDF لغات بیایند --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $W = fn ($k) => __('admincontent.words.'.$k);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ce-ew-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-ew-t">{{ $W('title') }} <span class="ad-badge" data-tone="info" data-ce-count="words"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('words_lead') }}</p>
        </div>
        <button type="button" class="ad-ai" data-ce-ai="words_suggest" data-list="words" data-mode="append">{!! $ic('sparkles') !!}{{ $W('ai') }}</button>
    </div>
    <div class="ed-cards" data-ce-list="words"></div>
    <p class="ad-empty" data-ce-empty="words" hidden>{{ $W('empty') }}</p>
    <button type="button" class="ed-add" data-ce-add="words" data-new='{"phrase":"","pdf":true}'>{!! $ic('plus') !!}{{ $W('add') }}</button>

    <template data-ce-tpl="words">
        @include('admin.content.word-item', ['pdf' => true, 'syn' => true])
    </template>
</section>
