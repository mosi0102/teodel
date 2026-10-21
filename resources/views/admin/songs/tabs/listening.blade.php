@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
@endphp
<div class="space-y-4">
    @foreach (['easy', 'hard'] as $mode)
        <section class="ad-card" aria-labelledby="ed-ls-{{ $mode }}">
            <div class="ad-card__h">
                <div class="min-w-0">
                    <h2 class="ad-card__t" id="ed-ls-{{ $mode }}">{{ __('adminsongs.listening.'.$mode.'_t') }}</h2>
                    <p class="ad-hint !mt-0.5">{{ __('adminsongs.listening.'.$mode.'_lead') }}</p>
                </div>
                <button type="button" class="ad-ai ad-ai--sm" data-ed-ai="listening_suggest" data-mode="{{ $mode }}">{!! $ic('sparkles', 'size-3.5') !!}{{ __('adminsongs.listening.ai') }}</button>
            </div>
            <div class="ed-cards" data-ed-list="listening.{{ $mode }}"></div>
            <p class="ad-empty" data-ed-empty="listening.{{ $mode }}" hidden>{{ __('adminsongs.listening.empty') }}</p>
            <button type="button" class="ed-add" data-ed-add="listening.{{ $mode }}">{!! $ic('plus') !!}{{ __('adminsongs.listening.add') }}</button>
        </section>
    @endforeach

    <template data-ed-tpl="clip">
        <article class="ed-item" data-ed-item>
            <header class="ed-item__h">
                <b class="ed-item__n"></b>
                <button type="button" class="ed-range-play" data-ed-range-play>{!! $ic('arrow', 'size-3.5') !!}{{ __('adminsongs.clip.play') }}</button>
                <button type="button" class="ed-del ms-auto" data-ed-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
            </header>
            <div class="ad-field"><label class="ad-label">{{ __('adminsongs.clip.pick') }}</label>
                <select class="field" data-ed-pick-line><option value="">—</option></select></div>
            <div class="ed-2 mt-3">
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.clip.start') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" dir="ltr" inputmode="decimal" data-k="start" data-time placeholder="00:00.00"><button type="button" class="ed-now" data-ed-now title="{{ __('adminsongs.now') }}" aria-label="{{ __('adminsongs.now') }}">{!! $ic('clock', 'size-3.5') !!}</button></div></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.clip.end') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" dir="ltr" inputmode="decimal" data-k="end" data-time placeholder="00:00.00"><button type="button" class="ed-now" data-ed-now title="{{ __('adminsongs.now') }}" aria-label="{{ __('adminsongs.now') }}">{!! $ic('clock', 'size-3.5') !!}</button></div></div>
            </div>
            <div class="ad-field mt-3"><label class="ad-label">{{ __('adminsongs.clip.text') }}</label><input class="field" dir="ltr" data-k="text"></div>
            <div class="ad-field mt-3"><label class="ad-label" data-ed-ans-l>{{ __('adminsongs.clip.answer') }}</label>
                <input class="field" dir="ltr" data-k="answer" data-ed-answer list="" autocomplete="off">
                <div class="ed-words" data-ed-words hidden></div>
                <p class="ad-error" hidden></p></div>
            <p class="ed-blank" dir="ltr" data-ed-blank aria-label="{{ __('adminsongs.clip.preview') }}"></p>
        </article>
    </template>
</div>
