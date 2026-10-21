@extends('layouts.admin')

@php
    use App\Support\AdminNav;
    $isNew = empty($song['id']);
    $urls = $urls ?? [];
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $jsonFlags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    // ⚠️ داخل @json ویرگول نگذارید: بلید آن را تکه‌تکه می‌کند
    $qDefaults = ['fa' => __('adminsongs.q_default', [], 'fa'), 'en' => __('adminsongs.q_default', [], 'en')];
@endphp

@section('title', $isNew ? __('adminsongs.new') : $song['title'])

{{--
    ویرایشگر موزیک: اطلاعات + هشت مرحله‌ی آموزش (هرکدام یک تب). همه‌ی داده‌ها داخل data-song (JSON) هستند و رفتار در resources/js/admin/song.js است.
    قرارداد ذخیره/آپلود/AI: app/Support/AdminSongs.php و README.
--}}
@section('content')
<div data-ed
    data-song='@json($song, $jsonFlags)'
    data-t='@json(__('adminsongs.js'), $jsonFlags)'
    data-defaults='@json($qDefaults, $jsonFlags)'
    data-list-url="{{ url('/admin/songs') }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset
    @isset($urls['upload']) data-upload-url="{{ $urls['upload'] }}" @endisset
    @isset($urls['delete']) data-delete-url="{{ $urls['delete'] }}" @endisset
    @isset($urls['preview']) data-preview-url="{{ $urls['preview'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div class="min-w-0">
            <a href="{{ url('/admin/songs') }}" class="mb-1 inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-brand-700">{!! $ic('arrow', 'size-3.5 rtl:rotate-0 ltr:rotate-180') !!}{{ __('adminsongs.back') }}</a>
            <h1 class="ad-h1 truncate" data-ed-title dir="auto">{{ $isNew ? __('adminsongs.new') : $song['title'] }}</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-ed-status data-tone="{{ $song['status'] === 'published' ? 'ok' : '' }}">{{ __('admin.common.'.$song['status']) }}</span>
                <span class="text-xs font-bold text-slate-500" data-ed-progress></span>
                <span class="text-xs text-slate-400" data-ed-dirty hidden>{{ __('adminsongs.unsaved') }}</span>
            </p>
        </div>
        <div class="ad-acts">
            @if (! $isNew)
                <button type="button" class="btn-ghost !min-h-10 !text-xs !text-rose-600" data-ed-delete-open>{{ __('admin.common.delete') }}</button>
                @isset($urls['preview'])<a href="{{ $urls['preview'] }}" target="_blank" rel="noopener" class="btn-outline !min-h-10 !text-xs">{!! $ic('external') !!}{{ __('adminsongs.preview') }}</a>@endisset
            @endif
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-ed-save="draft">{{ __('adminsongs.save_draft') }}</button>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-ed-publish-open>{{ __('adminsongs.publish') }}</button>
        </div>
    </div>

    <div class="ed-nav">
        <div class="ed-tabs" role="tablist" aria-label="{{ __('adminsongs.tabs_label') }}">
            @foreach (\App\Support\AdminSongs::TABS as $i => $k)
                <button type="button" role="tab" class="ed-tab" id="ed-t-{{ $k }}" aria-controls="ed-p-{{ $k }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? 0 : -1 }}" data-ed-tab="{{ $k }}">
                    <i class="ed-dot" data-ed-dot="{{ $k }}" aria-hidden="true"></i>
                    @if ($k !== 'info')<em class="ed-n">{{ $i }}</em>@endif
                    <span>{{ __('adminsongs.tabs.'.$k) }}</span>
                </button>
            @endforeach
        </div>

        {{-- پخش‌کننده‌ی مشترک: برای تایم‌گیری (لیریک، لغت، شنیداری، شدوینگ) --}}
        <div class="ed-player" data-ed-player hidden>
            <audio preload="metadata" data-ed-audio></audio>
            <button type="button" class="ed-pbtn" data-ed-play aria-label="{{ __('adminsongs.player.play') }}">
                <svg class="ed-i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                <svg class="ed-i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
            </button>
            <button type="button" class="ed-pbtn ed-pbtn--s" data-ed-seek="-5" aria-label="{{ __('adminsongs.player.back') }}">−۵</button>
            <button type="button" class="ed-pbtn ed-pbtn--s" data-ed-seek="5" aria-label="{{ __('adminsongs.player.fwd') }}">+۵</button>
            <input type="range" min="0" max="100" step="0.1" value="0" class="ed-range" data-ed-range aria-label="{{ __('adminsongs.player.seek') }}">
            <output class="ed-clock" dir="ltr" data-ed-clock>00:00.00</output>
            <select class="ed-speed" data-ed-speed aria-label="{{ __('adminsongs.player.speed') }}">
                @foreach ([0.5, 0.75, 1, 1.25] as $r)<option value="{{ $r }}" @selected($r == 1)>{{ $r }}×</option>@endforeach
            </select>
        </div>
        <p class="ed-noaudio" data-ed-noaudio hidden>{{ __('adminsongs.player.none') }}</p>
    </div>

    <div class="ed-panels">
        @foreach (\App\Support\AdminSongs::TABS as $i => $k)
            <section class="ed-panel" role="tabpanel" id="ed-p-{{ $k }}" aria-labelledby="ed-t-{{ $k }}" data-ed-panel="{{ $k }}" @if ($i !== 0) hidden @endif>
                @include('admin.songs.tabs.'.($k === 'repeat' ? 'play' : $k), ['step' => $k])
            </section>
        @endforeach
    </div>

    <div class="ed-savebar" data-ed-savebar>
        <span class="text-xs font-bold text-slate-500" data-ed-msg role="status" aria-live="polite"></span>
        <button type="button" class="btn-primary !min-h-10 !text-xs" data-ed-save="auto">{{ __('adminsongs.save') }}</button>
    </div>
</div>
@include('admin.partials.ai-dialog')
@endsection

@push('modals')
{{-- چک‌لیست انتشار --}}
<div id="ed-publish" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ed-pub-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ed-pub-t" class="text-lg font-extrabold text-brand-900">{{ __('adminsongs.pub.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500" data-ed-pub-lead></p>
        <ul class="ed-check" data-ed-pub-list></ul>
        <p class="sb-err" role="alert" hidden data-ed-pub-err></p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-ed-save="published">{{ __('adminsongs.pub.go') }}</button>
        </div>
    </div>
</div>

{{-- چسباندن LRC --}}
<div id="ed-lrc" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ed-lrc-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-lg">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ed-lrc-t" class="text-lg font-extrabold text-brand-900">{{ __('adminsongs.lyrics.paste_t') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminsongs.lyrics.paste_lead') }}</p>
        <textarea class="field mt-3 !min-h-48 font-mono !text-xs" dir="ltr" data-ed-lrc-text placeholder="[00:21.53]Mm&#10;[00:24.65]I don't run like I did back then"></textarea>
        <label class="mt-3 flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-brand-700" data-ed-lrc-keep checked> {{ __('adminsongs.lyrics.keep_tr') }}</label>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-ed-lrc-apply>{{ __('adminsongs.lyrics.paste_go') }}</button>
        </div>
    </div>
</div>

{{-- حذف --}}
<div id="ed-delete" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="ed-del-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ed-del-t" class="text-lg font-extrabold text-brand-900">{{ __('adminsongs.del.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('adminsongs.del.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-ed-delete>{{ __('adminsongs.del.go') }}</button>
        </div>
    </div>
</div>
@endpush
