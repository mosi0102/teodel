@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $chip = '<span class="ad-chip">'.$ic('sparkles', 'size-3').__('admin.ai.chip').'</span>';
@endphp
<section class="ad-card" aria-labelledby="ed-w-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ed-w-t">{{ __('adminsongs.words.title') }}</h2>
            <p class="ad-hint !mt-0.5">{{ __('adminsongs.words.lead') }}</p>
        </div>
        <button type="button" class="ad-ai" data-ed-ai="words_suggest">{!! $ic('sparkles') !!}{{ __('adminsongs.words.ai') }}</button>
    </div>
    <div class="ed-cards" data-ed-list="words"></div>
    <p class="ad-empty" data-ed-empty="words" hidden>{{ __('adminsongs.words.empty') }}</p>
    <button type="button" class="ed-add" data-ed-add="words">{!! $ic('plus') !!}{{ __('adminsongs.words.add') }}</button>

    <template data-ed-tpl="words">
        <article class="ed-item" data-ed-item>
            <header class="ed-item__h">
                <b class="ed-item__n"></b>
                <span class="ed-flag" data-ed-inlyrics></span>
                <button type="button" class="ad-ai ad-ai--sm ms-auto" data-ed-ai-item="word_fill">{!! $ic('sparkles', 'size-3.5') !!}{{ __('adminsongs.words.fill') }}</button>
                <button type="button" class="ed-del" data-ed-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
            </header>
            <div class="ad-form-grid">
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.phrase') }} <span class="text-rose-500">*</span></label><input class="field" dir="ltr" data-k="phrase"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.type') }}{!! $chip !!}</label><input class="field" dir="ltr" data-k="type" placeholder="noun / verb / idiom…"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.level') }}{!! $chip !!}</label>
                    <select class="field" data-k="level"><option value="">—</option>@foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $l)<option>{{ $l }}</option>@endforeach</select></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.translate') }} <span class="text-rose-500">*</span>{!! $chip !!}</label><input class="field" data-k="translate"></div>
                <div class="ad-field"><label class="ad-label">IPA · UK{!! $chip !!}</label><input class="field" dir="ltr" data-k="ipa.uk" placeholder="/…/"></div>
                <div class="ad-field"><label class="ad-label">IPA · US{!! $chip !!}</label><input class="field" dir="ltr" data-k="ipa.us" placeholder="/…/"></div>
                <div class="ad-field is-wide"><label class="ad-label">{{ __('adminsongs.words.meaning') }}{!! $chip !!}</label><textarea class="field !min-h-20" dir="ltr" data-k="meaning"></textarea></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.syn') }}{!! $chip !!}</label><input class="field" dir="ltr" data-k="synonyms" data-list placeholder="a, b, c"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.ant') }}{!! $chip !!}</label><input class="field" dir="ltr" data-k="antonyms" data-list placeholder="a, b, c"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.audio_uk') }}</label><input class="field" dir="ltr" data-k="audio.uk" placeholder="https://"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.words.audio_us') }}</label><input class="field" dir="ltr" data-k="audio.us" placeholder="https://"></div>
            </div>
            <p class="ad-hint">{{ __('adminsongs.words.audio_hint') }}</p>
        </article>
    </template>
</section>
