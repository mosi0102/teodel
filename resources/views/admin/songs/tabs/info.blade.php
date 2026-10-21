@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $chip = '<span class="ad-chip">'.$ic('sparkles', 'size-3').__('admin.ai.chip').'</span>';
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ed-info-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="ed-info-t">{{ __('adminsongs.info.title') }}</h2>
            <button type="button" class="ad-ai" data-ai-open data-ai-target="#ed-info-form" data-ai-seeds="title,artist" data-ai-fields="level,accent,year,genre,wpm,topic,album,desc_fa,desc_en,cover">{!! $ic('sparkles') !!}{{ __('admin.ai.btn_long') }}</button>
        </div>
        <form id="ed-info-form" class="ad-form-grid" data-ai-form="song" @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset onsubmit="return false" novalidate>
            <div class="ad-field"><label class="ad-label" for="f-title">{{ __('adminsongs.f.title') }} <span class="text-rose-500">*</span></label>
                <input id="f-title" name="title" class="field" dir="ltr" data-f="title" data-ai-field autocomplete="off"><p class="ad-error" hidden></p></div>
            <div class="ad-field"><label class="ad-label" for="f-artist">{{ __('adminsongs.f.artist') }} <span class="text-rose-500">*</span></label>
                <input id="f-artist" name="artist" class="field" dir="ltr" data-f="artist" data-ai-field autocomplete="off"><p class="ad-error" hidden></p></div>

            <div class="ad-field"><label class="ad-label" for="f-level">{{ __('adminsongs.f.level') }}{!! $chip !!}</label>
                <select id="f-level" name="level" class="field" data-f="level" data-ai-field>@foreach (['a', 'b', 'c'] as $l)<option value="{{ $l }}">{{ __('adminsongs.level.'.$l) }}</option>@endforeach</select>
                <p class="ad-hint">{{ __('adminsongs.f.level_hint') }}</p></div>
            <div class="ad-field"><label class="ad-label" for="f-accent">{{ __('adminsongs.f.accent') }}{!! $chip !!}</label>
                <select id="f-accent" name="accent" class="field" data-f="accent" data-ai-field><option value="">—</option>@foreach (['us', 'uk', 'au', 'ie'] as $a)<option value="{{ $a }}">{{ __('adminsongs.accent.'.$a) }}</option>@endforeach</select></div>

            <div class="ad-field"><label class="ad-label" for="f-year">{{ __('adminsongs.f.year') }}{!! $chip !!}</label>
                <input id="f-year" name="year" class="field" inputmode="numeric" dir="ltr" maxlength="4" data-f="year" data-ai-field></div>
            <div class="ad-field"><label class="ad-label" for="f-genre">{{ __('adminsongs.f.genre') }}{!! $chip !!}</label>
                <input id="f-genre" name="genre" class="field" dir="ltr" data-f="genre" data-ai-field></div>
            <div class="ad-field"><label class="ad-label" for="f-wpm">{{ __('adminsongs.f.wpm') }}{!! $chip !!}</label>
                <input id="f-wpm" name="wpm" class="field" inputmode="numeric" dir="ltr" data-f="wpm" data-ai-field></div>
            <div class="ad-field"><label class="ad-label" for="f-topic">{{ __('adminsongs.f.topic') }}{!! $chip !!}</label>
                <input id="f-topic" name="topic" class="field" dir="ltr" data-f="topic" data-ai-field></div>
            <div class="ad-field"><label class="ad-label" for="f-album">{{ __('adminsongs.f.album') }}{!! $chip !!}</label>
                <input id="f-album" name="album" class="field" dir="ltr" data-f="album" data-ai-field></div>
            <div class="ad-field"><label class="ad-label" for="f-points">{{ __('adminsongs.f.points') }}</label>
                <input id="f-points" name="points" class="field" inputmode="numeric" dir="ltr" data-f="points"></div>

            <div class="ad-field is-wide"><label class="ad-label" for="f-desc-fa">{{ __('adminsongs.f.desc_fa') }}{!! $chip !!}
                <button type="button" class="ad-ai ad-ai--sm" data-ai-one="desc_fa" data-ai-seeds="title,artist">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                <textarea id="f-desc-fa" name="desc_fa" class="field" data-f="desc_fa" data-ai-field></textarea></div>
            <div class="ad-field is-wide"><label class="ad-label" for="f-desc-en">{{ __('adminsongs.f.desc_en') }}{!! $chip !!}
                <button type="button" class="ad-ai ad-ai--sm" data-ai-one="desc_en" data-ai-seeds="title,artist">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                <textarea id="f-desc-en" name="desc_en" class="field" dir="ltr" data-f="desc_en" data-ai-field></textarea></div>

            <label class="ad-switch is-wide"><input type="checkbox" data-f="is_free"><span class="ad-switch__t"><b>{{ __('adminsongs.f.free') }}</b><small>{{ __('adminsongs.f.free_hint') }}</small></span></label>
        </form>
    </section>

    <section class="ad-card" aria-labelledby="ed-files-t">
        <h2 class="ad-card__t mb-3" id="ed-files-t">{{ __('adminsongs.files.title') }}</h2>

        {{-- کاور --}}
        <div class="ed-up" data-ed-up="cover" data-accept="image/jpeg,image/png,image/webp">
            <div class="ed-up__prev ed-up__prev--img" data-ed-up-prev aria-hidden="true">{!! $ic('image', 'size-6') !!}</div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-extrabold text-brand-900">{{ __('adminsongs.files.cover') }}{!! $chip !!}</p>
                <p class="ad-hint !mt-0.5">{{ __('adminsongs.files.cover_hint') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" data-ed-file>{{ __('admin.common.upload') }}</label>
                    <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-ed-up-clear hidden>{{ __('admin.common.delete') }}</button>
                </div>
                <div class="ad-field mt-2"><input name="cover" class="field !min-h-10 !text-xs" dir="ltr" placeholder="https://" data-f="cover" data-ai-field form="ed-info-form" aria-label="{{ __('adminsongs.files.cover') }} URL"></div>
                <p class="ad-error" hidden data-ed-up-err></p>
            </div>
        </div>

        {{-- فایل اصلی موزیک --}}
        <div class="ed-up mt-4 border-t border-slate-100 pt-4" data-ed-up="audio" data-accept="audio/mpeg,audio/mp4,audio/wav,audio/ogg">
            <div class="ed-up__prev" data-ed-up-prev aria-hidden="true">{!! $ic('songs', 'size-6') !!}</div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-extrabold text-brand-900">{{ __('adminsongs.files.audio') }} <span class="text-rose-500">*</span></p>
                <p class="ad-hint !mt-0.5">{{ __('adminsongs.files.audio_hint') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="audio/*" data-ed-file>{{ __('admin.common.upload') }}</label>
                    <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-ed-up-clear hidden>{{ __('admin.common.delete') }}</button>
                    <span class="text-xs font-bold text-slate-500" data-ed-up-meta dir="ltr"></span>
                </div>
                <p class="ad-error" hidden data-ed-up-err></p>
            </div>
        </div>
    </section>
</div>
