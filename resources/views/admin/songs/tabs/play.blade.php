@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $k = $step === 'repeat' ? 'q_repeat' : 'q_play';
@endphp
<div class="ad-grid">
    <section class="ad-card is-2">
        <h2 class="ad-card__t">{{ __('adminsongs.'.$step.'.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminsongs.'.$step.'.lead') }}</p>

        <div class="mt-4 rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-extrabold text-slate-500">{{ __('adminsongs.play.vocals') }}</p>
            <div class="ed-vocals" data-ed-vocals role="img" aria-label="{{ __('adminsongs.play.vocals') }}"></div>
            <p class="ad-hint" data-ed-vocals-hint>{{ __('adminsongs.play.vocals_hint') }}</p>
        </div>

        <div class="ad-form-grid mt-4">
            <div class="ad-field"><label class="ad-label" for="f-{{ $k }}-fa">{{ __('adminsongs.play.q_fa') }}</label>
                <textarea id="f-{{ $k }}-fa" class="field !min-h-20" data-f="{{ $k }}.fa" data-ed-q="fa" data-ed-qdef="{{ $k }}"></textarea></div>
            <div class="ad-field"><label class="ad-label" for="f-{{ $k }}-en">{{ __('adminsongs.play.q_en') }}</label>
                <textarea id="f-{{ $k }}-en" class="field !min-h-20" dir="ltr" data-f="{{ $k }}.en" data-ed-q="en" data-ed-qdef="{{ $k }}"></textarea></div>
        </div>
        <p class="ad-hint">{{ __('adminsongs.play.q_hint') }}</p>

        <div class="mt-4">
            <p class="text-xs font-extrabold text-slate-500">{{ __('adminsongs.play.prev') }}</p>
            <div class="ed-rate" aria-hidden="true">
                <p class="ed-rate__q" data-ed-q-prev="{{ $k }}"></p>
                <div class="ed-rate__row">@foreach (range(1, 10) as $n)<span>{{ $n }}</span>@endforeach</div>
            </div>
        </div>
    </section>

    <section class="ad-card">
        <h2 class="ad-card__t mb-2">{{ __('adminsongs.play.needs') }}</h2>
        <ul class="ed-need">
            <li data-ed-need="audio">{{ __('adminsongs.play.need_audio') }}</li>
            <li data-ed-need="lyrics">{{ __('adminsongs.play.need_lyrics') }}</li>
        </ul>
        @if ($step === 'repeat')<p class="ad-hint mt-3">{{ __('adminsongs.repeat.compare') }}</p>@endif
    </section>
</div>
