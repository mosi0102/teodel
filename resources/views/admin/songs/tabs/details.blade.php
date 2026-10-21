@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
@endphp
<div class="space-y-4">
    <section class="ad-card" aria-labelledby="ed-d-t">
        <div class="ad-card__h">
            <div class="min-w-0">
                <h2 class="ad-card__t" id="ed-d-t">{{ __('adminsongs.details.interp') }}</h2>
                <p class="ad-hint !mt-0.5">{{ __('adminsongs.details.interp_hint') }}</p>
            </div>
            <button type="button" class="ad-ai" data-ed-ai="details">{!! $ic('sparkles') !!}{{ __('adminsongs.details.ai') }}</button>
        </div>
        <div class="ad-field"><textarea class="field !min-h-44" data-f="interpretation" aria-labelledby="ed-d-t"></textarea></div>
    </section>

    <section class="ad-card" aria-labelledby="ed-n-t">
        <h2 class="ad-card__t" id="ed-n-t">{{ __('adminsongs.details.notes') }}</h2>
        <p class="ad-hint !mt-0.5">{{ __('adminsongs.details.notes_hint') }}</p>
        <div class="ed-cards mt-3" data-ed-list="notes"></div>
        <p class="ad-empty" data-ed-empty="notes" hidden>{{ __('adminsongs.details.empty') }}</p>
        <button type="button" class="ed-add" data-ed-add="notes">{!! $ic('plus') !!}{{ __('adminsongs.details.add_section') }}</button>
    </section>

    <template data-ed-tpl="notes">
        <article class="ed-item" data-ed-item>
            <header class="ed-item__h">
                <b class="ed-item__n"></b>
                <button type="button" class="ed-del ms-auto" data-ed-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
            </header>
            <div class="ed-2">
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.details.s_title') }}</label><input class="field" data-k="title"></div>
                <div class="ad-field"><label class="ad-label">{{ __('adminsongs.details.s_type') }}</label>
                    <select class="field" data-k="type"><option value="literary">{{ __('adminsongs.details.literary') }}</option><option value="language">{{ __('adminsongs.details.language') }}</option></select></div>
            </div>
            <div class="ed-sub" data-ed-sublist></div>
            <button type="button" class="ed-add ed-add--s" data-ed-add-sub>{!! $ic('plus', 'size-3.5') !!}{{ __('adminsongs.details.add_item') }}</button>
        </article>
    </template>
    <template data-ed-tpl="note-item">
        <div class="ed-subitem" data-ed-subitem>
            <div class="ad-field"><label class="ad-label">{{ __('adminsongs.details.term') }}</label><input class="field !min-h-10" dir="ltr" data-sk="term" placeholder="Metaphor — استعاره"></div>
            <div class="ad-field"><label class="ad-label">{{ __('adminsongs.details.example') }}</label><input class="field !min-h-10" dir="ltr" data-sk="example"></div>
            <div class="ad-field"><label class="ad-label">{{ __('adminsongs.details.text') }}</label><textarea class="field !min-h-16" data-sk="text"></textarea></div>
            <button type="button" class="ed-del" data-ed-del-sub aria-label="{{ __('admin.common.delete') }}">{!! $ic('x', 'size-4') !!}</button>
        </div>
    </template>
</div>
