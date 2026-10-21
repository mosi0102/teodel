@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
@endphp
<section class="ad-card" aria-labelledby="ed-sh-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ed-sh-t">{{ __('adminsongs.shadowing.title') }}</h2>
            <p class="ad-hint !mt-0.5">{{ __('adminsongs.shadowing.lead') }}</p>
        </div>
        <button type="button" class="ad-ai" data-ed-ai="shadowing_suggest">{!! $ic('sparkles') !!}{{ __('adminsongs.shadowing.ai') }}</button>
    </div>
    <div class="ad-field max-w-48">
        <label class="ad-label" for="f-repeats">{{ __('adminsongs.shadowing.repeats') }}</label>
        <input id="f-repeats" class="field" inputmode="numeric" dir="ltr" data-f="shadowing_repeats" maxlength="2">
    </div>
    <div class="ed-cards mt-4" data-ed-list="shadowing"></div>
    <p class="ad-empty" data-ed-empty="shadowing" hidden>{{ __('adminsongs.shadowing.empty') }}</p>
    <button type="button" class="ed-add" data-ed-add="shadowing">{!! $ic('plus') !!}{{ __('adminsongs.shadowing.add') }}</button>

    <template data-ed-tpl="shadowing">
        <article class="ed-item" data-ed-item>
            <header class="ed-item__h">
                <b class="ed-item__n"></b>
                <button type="button" class="ed-range-play" data-ed-range-play>{!! $ic('arrow', 'size-3.5') !!}{{ __('adminsongs.clip.play') }}</button>
                <button type="button" class="ed-del ms-auto" data-ed-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
            </header>
            <div class="ed-2">
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.shadowing.from') }}</label><select class="field" data-ed-from-line><option value="">—</option></select></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.shadowing.to') }}</label><select class="field" data-ed-to-line><option value="">—</option></select></div>
            </div>
            <div class="ed-2 mt-3">
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.clip.start') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" dir="ltr" inputmode="decimal" data-k="start" data-time placeholder="00:00.00"><button type="button" class="ed-now" data-ed-now title="{{ __('adminsongs.now') }}" aria-label="{{ __('adminsongs.now') }}">{!! $ic('clock', 'size-3.5') !!}</button></div></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.clip.end') }}</label>
                    <div class="ed-time"><input class="field !min-h-10 !text-xs" dir="ltr" inputmode="decimal" data-k="end" data-time placeholder="00:00.00"><button type="button" class="ed-now" data-ed-now title="{{ __('adminsongs.now') }}" aria-label="{{ __('adminsongs.now') }}">{!! $ic('clock', 'size-3.5') !!}</button></div></div>
            </div>
            <div class="ad-field mt-3"><label class="ad-label">{{ __('adminsongs.shadowing.text') }}</label><textarea class="field !min-h-20" dir="ltr" data-k="text"></textarea><p class="ad-error" hidden></p></div>
        </article>
    </template>
</section>
