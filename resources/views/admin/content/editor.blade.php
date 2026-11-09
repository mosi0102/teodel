@extends('layouts.admin')

{{--
    قالب مشترک ویرایشگرهای محتوا (پادکست، اپیزود، Shadowing، کتاب). صفحه‌ی هر بخش این‌ها را می‌دهد:
      $kind   podcast | podcast_episode | shadowing | book
      $model  داده (قرارداد: app/Support/Admin*.php)        $need  قانون‌های کامل‌بودن هر تب
      $tabs   [key => label]  $tabView  پیشوند view تب‌ها (مثلاً 'admin.books.tabs.')
      $listUrl, $editUrl (:id), $back (متن لینک بازگشت), $newTitle, $player (پخش‌کننده‌ی تایم‌گیری؟), $crumb (اختیاری)
      $urls   ['save','upload','delete','ai','preview'] — نبودن = حالت دمو (localStorage)
    رفتار: resources/js/admin/content.js
--}}
@php
    use App\Support\AdminNav;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $isNew = empty($model['id']);
    $urls = $urls ?? [];
    $jf = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $js = __('admincontent.js');
    foreach ($tabs as $k => $l) {
        $js['tab_'.$k] = $l;
    }
    $player = $player ?? false;
@endphp

@section('title', $isNew ? $newTitle : ($model['title'] ?: $newTitle))

@section('content')
<div data-ce data-kind="{{ $kind }}"
    data-model='@json($model, $jf)'
    data-need='@json($need, $jf)'
    data-t='@json($js, $jf)'
    data-list-url="{{ $listUrl }}" data-edit-url="{{ $editUrl }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset
    @isset($urls['upload']) data-upload-url="{{ $urls['upload'] }}" @endisset
    @isset($urls['delete']) data-delete-url="{{ $urls['delete'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div class="min-w-0">
            <nav class="mb-1 flex flex-wrap items-center gap-1 text-xs font-bold text-slate-400" aria-label="{{ __('admincontent.crumb') }}">
                <a href="{{ $backUrl ?? $listUrl }}" class="inline-flex items-center gap-1 hover:text-brand-700">{!! $ic('arrow', 'size-3.5 ltr:rotate-180') !!}{{ $back }}</a>
                @isset($crumb)<span aria-hidden="true">/</span><a href="{{ $crumb['url'] }}" class="truncate hover:text-brand-700" dir="auto">{{ $crumb['label'] }}</a>@endisset
            </nav>
            <h1 class="ad-h1 truncate" data-ce-title dir="auto">{{ $isNew ? $newTitle : $model['title'] }}</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-ce-status data-tone="{{ $model['status'] === 'published' ? 'ok' : '' }}">{{ __('admin.common.'.$model['status']) }}</span>
                <span class="text-xs font-bold text-slate-500" data-ce-progress></span>
                <span class="text-xs text-slate-400" data-ce-dirty hidden>{{ __('admincontent.unsaved') }}</span>
            </p>
        </div>
        <div class="ad-acts">
            @if (! $isNew)
                <button type="button" class="btn-ghost !min-h-10 !text-xs !text-rose-600" data-ce-delete-open>{{ __('admin.common.delete') }}</button>
                @isset($urls['preview'])<a href="{{ $urls['preview'] }}" target="_blank" rel="noopener" class="btn-outline !min-h-10 !text-xs">{!! $ic('external') !!}{{ __('admincontent.preview') }}</a>@endisset
            @endif
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-ce-save="draft">{{ __('admincontent.save_draft') }}</button>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-ce-publish-open>{{ __('admincontent.publish') }}</button>
        </div>
    </div>

    <div class="ed-nav">
        <div class="ed-tabs" role="tablist" aria-label="{{ __('admincontent.tabs_label') }}">
            @foreach ($tabs as $k => $label)
                <button type="button" role="tab" class="ed-tab" id="ce-t-{{ $k }}" aria-controls="ce-p-{{ $k }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}" data-ce-tab="{{ $k }}">
                    <i class="ed-dot" data-ce-dot="{{ $k }}" data-s="todo" aria-hidden="true"></i>
                    @if (! $loop->first && ($numbered ?? true))<em class="ed-n">{{ num($loop->index) }}</em>@endif
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>

        @if ($player)
            {{-- پخش‌کننده‌ی مشترک برای تایم‌گیری (ترانسکریپت، دیکته، shadowing) --}}
            <div class="ce-player" data-ce-player hidden>
                <audio controls preload="metadata" data-ce-audio class="w-full"></audio>
            </div>
            <p class="ed-noaudio" data-ce-noaudio hidden>{{ __('admincontent.player.none') }}</p>
        @endif
    </div>

    <div class="ed-panels">
        @foreach ($tabs as $k => $label)
            <section class="ed-panel" role="tabpanel" id="ce-p-{{ $k }}" aria-labelledby="ce-t-{{ $k }}" data-ce-panel="{{ $k }}" @if (! $loop->first) hidden @endif>
                @include(($tabMap[$k] ?? $tabView.$k), ['tab' => $k])
            </section>
        @endforeach
    </div>

    <div class="ed-savebar">
        <span class="text-xs font-bold text-slate-500" data-ce-msg role="status" aria-live="polite"></span>
        <button type="button" class="btn-primary !min-h-10 !text-xs" data-ce-save="auto">{{ __('admincontent.save') }}</button>
    </div>
</div>
@endsection

@push('modals')
<div id="ce-publish" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ce-pub-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ce-pub-t" class="text-lg font-extrabold text-brand-900">{{ __('admincontent.pub.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500" data-ce-pub-lead></p>
        <ul class="ed-check" data-ce-pub-list></ul>
        <p class="sb-err" role="alert" hidden data-ce-pub-err></p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-ce-save="published">{{ __('admincontent.pub.go') }}</button>
        </div>
    </div>
</div>

<div id="ce-delete" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="ce-del-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ce-del-t" class="text-lg font-extrabold text-brand-900">{{ __('admincontent.del.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('admincontent.del.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-ce-delete>{{ __('admincontent.del.go') }}</button>
        </div>
    </div>
</div>

<div id="ce-import" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ce-imp-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-lg">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ce-imp-t" class="text-lg font-extrabold text-brand-900">{{ __('admincontent.import.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('admincontent.import.lead') }}</p>
        <label class="btn-outline mt-3 !min-h-10 cursor-pointer !text-xs">
            <input type="file" class="sr-only" accept=".srt,.lrc,.txt,text/plain" data-ce-import-file>{!! \App\Support\AdminNav::icon('upload', 'size-4') !!}{{ __('admincontent.import.file') }}
        </label>
        <textarea class="field mt-3 !min-h-48 font-mono !text-xs" dir="ltr" data-ce-import-text
            placeholder="[00:06.00]Hello and welcome…&#10;— {{ __('admincontent.import.or') }} —&#10;1&#10;00:00:06,000 --> 00:00:09,500&#10;Hello and welcome…"></textarea>
        <label class="mt-3 flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-brand-700" data-ce-import-keep checked> {{ __('admincontent.import.keep') }}</label>
        <p class="ad-error mt-2" hidden data-ce-import-err>{{ __('admincontent.import.err') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-ce-import-go>{{ __('admincontent.import.go') }}</button>
        </div>
    </div>
</div>
@endpush
