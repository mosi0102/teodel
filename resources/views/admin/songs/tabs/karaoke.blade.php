@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
@endphp
<div class="ad-grid">
    <section class="ad-card is-2">
        <h2 class="ad-card__t">{{ __('adminsongs.karaoke.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminsongs.karaoke.lead') }}</p>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach (['audio_vocal', 'audio_backing'] as $k)
                <div class="ed-up ed-up--box" data-ed-up="{{ $k }}" data-accept="audio/*">
                    <div class="ed-up__prev" data-ed-up-prev aria-hidden="true">{!! $ic('songs', 'size-6') !!}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-extrabold text-brand-900">{{ __('adminsongs.karaoke.'.$k) }} <span class="text-rose-500">*</span></p>
                        <p class="ad-hint !mt-0.5">{{ __('adminsongs.karaoke.'.$k.'_hint') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="audio/*" data-ed-file>{{ __('admin.common.upload') }}</label>
                            <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-ed-up-clear hidden>{{ __('admin.common.delete') }}</button>
                            <span class="text-xs font-bold text-slate-500" data-ed-up-meta dir="ltr"></span>
                        </div>
                        <audio controls preload="none" class="mt-2 hidden h-9 w-full" data-ed-up-audio></audio>
                        <p class="ad-error" hidden data-ed-up-err></p>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="ed-warn" data-ed-karaoke-warn hidden>{{ __('adminsongs.karaoke.mismatch') }}</p>
    </section>
    <section class="ad-card">
        <h2 class="ad-card__t mb-2">{{ __('adminsongs.play.needs') }}</h2>
        <ul class="ed-need">
            <li data-ed-need="audio_vocal">{{ __('adminsongs.karaoke.need_vocal') }}</li>
            <li data-ed-need="audio_backing">{{ __('adminsongs.karaoke.need_backing') }}</li>
            <li data-ed-need="lyrics">{{ __('adminsongs.karaoke.need_lyrics') }}</li>
        </ul>
        <p class="ad-hint mt-3">{{ __('adminsongs.karaoke.note') }}</p>
    </section>
</div>
