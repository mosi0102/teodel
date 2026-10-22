@extends('layouts.admin')

@section('title', __('adminplacement.title'))

{{--
    بانک سؤال آزمون تعیین سطح: ماتریس پوشش (بخش × سطح)، فیلتر/جست‌وجو، جدول (موبایل: کارت) و ساخت گروهی با AI.
    فیلتر و صفحه‌بندی سمت مرورگرند؛ رفتار: resources/js/admin/placement.js — قرارداد داده: app/Support/AdminPlacement.php
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminPlacement;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $lvLabel = fn ($l) => __('adminplacement.level.'.$l).' · '.__('adminplacement.level_name.'.$l);
@endphp

@section('content')
<div data-bk-list
    data-rows='@json($rows, $flags)'
    data-t='@json(__('adminplacement'), $flags)'
    data-edit-url="{{ url('/admin/placement/__ID__/edit') }}"
    data-new-url="{{ url('/admin/placement/create') }}"
    data-min="{{ AdminPlacement::MIN_PER_LEVEL }}" data-good="{{ AdminPlacement::GOOD_PER_LEVEL }}"
    @isset($urls['bulk']) data-bulk-url="{{ $urls['bulk'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminplacement.title') }}</h1>
            <p class="ad-sub">{{ __('adminplacement.sub') }}</p>
        </div>
        <div class="ad-acts">
            <button type="button" class="ad-ai" data-bk-batch-open>{!! $ic('sparkles') !!}{{ __('adminplacement.batch') }}</button>
            <a href="{{ url('/admin/placement/create') }}" class="btn-primary !min-h-10 !text-xs">{!! $ic('plus') !!}{{ __('adminplacement.new') }}</a>
        </div>
    </div>

    {{-- ماتریس پوشش --}}
    <section class="ad-card bk-cov-card" aria-labelledby="bk-cov-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="bk-cov-t">{{ __('adminplacement.cov.title') }}</h2>
        </div>
        <p class="ad-hint !mt-0 mb-3">{{ __('adminplacement.cov.lead') }}</p>
        <div class="bk-cov-wrap">
            <table class="bk-cov" data-bk-cov>
                <thead>
                    <tr>
                        <th scope="col"><span class="sr-only">{{ __('adminplacement.col.section') }}</span></th>
                        @foreach (AdminPlacement::LEVELS as $l)<th scope="col">{{ __('adminplacement.level.'.$l) }}<small>{{ __('adminplacement.level_name.'.$l) }}</small></th>@endforeach
                        <th scope="col">{{ __('adminplacement.cov.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (AdminPlacement::SECTIONS as $s)
                        <tr data-sec="{{ $s }}">
                            <th scope="row">{{ __('adminplacement.section.'.$s) }}</th>
                            @foreach (AdminPlacement::LEVELS as $l)<td><button type="button" class="bk-cov__c" data-sec="{{ $s }}" data-lv="{{ $l }}" data-tone="">0</button></td>@endforeach
                            <td class="bk-cov__t" data-tot>0</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <ul class="bk-legend" aria-hidden="true">
            <li data-tone="bad">{{ __('adminplacement.cov.bad', ['n' => AdminPlacement::MIN_PER_LEVEL]) }}</li>
            <li data-tone="warn">{{ __('adminplacement.cov.warn', ['n' => AdminPlacement::GOOD_PER_LEVEL]) }}</li>
            <li data-tone="ok">{{ __('adminplacement.cov.ok') }}</li>
        </ul>
    </section>

    {{-- فیلترها --}}
    <div class="ad-tools mt-4" role="search">
        <div class="ad-tools__q"><input type="search" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminplacement.search_ph') }}" aria-label="{{ __('adminplacement.search_ph') }}" autocomplete="off" data-bk-q></div>
        <label class="sr-only" for="bk-status">{{ __('adminplacement.col.status') }}</label>
        <select id="bk-status" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-bk-status>
            @foreach (['all', 'published', 'draft'] as $s)<option value="{{ $s }}">{{ __('adminplacement.status.'.$s) }}</option>@endforeach
        </select>
        <label class="sr-only" for="bk-sort">{{ __('adminplacement.sort.label') }}</label>
        <select id="bk-sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-bk-sort>
            @foreach (['newest', 'level', 'hard'] as $o)<option value="{{ $o }}">{{ __('adminplacement.sort.'.$o) }}</option>@endforeach
        </select>
    </div>
    <div class="ad-filters">
        <nav class="ad-chips" aria-label="{{ __('adminplacement.col.section') }}" data-bk-secs>
            <button type="button" class="chip-btn shrink-0" data-v="all" aria-current="true">{{ __('admin.common.all') }} <b class="ms-1 tabular-nums" data-n></b></button>
            @foreach (AdminPlacement::SECTIONS as $s)
                <button type="button" class="chip-btn shrink-0" data-v="{{ $s }}">{{ __('adminplacement.section.'.$s) }} <b class="ms-1 tabular-nums" data-n></b></button>
            @endforeach
        </nav>
        <nav class="ad-chips" aria-label="{{ __('adminplacement.col.level') }}" data-bk-lvs>
            <button type="button" class="chip-btn shrink-0" data-v="all" aria-current="true">{{ __('admin.common.all') }}</button>
            @foreach (AdminPlacement::LEVELS as $l)
                <button type="button" class="chip-btn shrink-0" data-v="{{ $l }}">{{ __('adminplacement.level.'.$l) }}</button>
            @endforeach
        </nav>
    </div>

    <section class="ad-card !p-3 md:!p-5">
        <div class="ad-tbl-wrap" data-bk-table>
            <table class="ad-tbl">
                <thead><tr>
                    <th>{{ __('adminplacement.col.q') }}</th><th>{{ __('adminplacement.col.section') }}</th><th>{{ __('adminplacement.col.level') }}</th>
                    <th>{{ __('adminplacement.col.answer') }}</th><th>{{ __('adminplacement.col.stats') }}</th><th>{{ __('adminplacement.col.status') }}</th><th>{{ __('adminplacement.col.updated') }}</th>
                </tr></thead>
                <tbody data-bk-rows></tbody>
            </table>
        </div>
        <div class="ad-empty" data-bk-empty hidden>
            <span>{{ __('adminplacement.empty') }}</span>
            <button type="button" class="btn-outline !min-h-9 !text-xs" data-bk-clear>{{ __('adminplacement.clear') }}</button>
        </div>
        <div class="ad-pager" data-bk-pager>
            <span data-bk-count></span>
            <div>
                <button type="button" class="btn-outline" data-bk-prev>{{ __('adminplacement.prev') }}</button>
                <button type="button" class="btn-outline" data-bk-next>{{ __('adminplacement.next') }}</button>
            </div>
        </div>
    </section>
</div>
@endsection

@push('modals')
{{-- ساخت گروهی با AI --}}
<div id="bk-batch" class="dialog ad-ai-dlg" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bk-batch-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-2xl">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start gap-3.5">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-50 text-violet-600">{!! $ic('sparkles', 'size-5') !!}</span>
            <div class="min-w-0">
                <h2 id="bk-batch-t" class="text-lg font-extrabold text-brand-900">{{ __('adminplacement.bt.title') }}</h2>
                <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminplacement.bt.lead') }}</p>
            </div>
        </div>

        <div class="ad-form-grid mt-4">
            <div class="ad-field"><label class="ad-label" for="bt-section">{{ __('adminplacement.bt.section') }}</label>
                <select id="bt-section" class="field" data-bt="section">@foreach (AdminPlacement::SECTIONS as $s)<option value="{{ $s }}">{{ __('adminplacement.section.'.$s) }}</option>@endforeach</select></div>
            <div class="ad-field"><label class="ad-label" for="bt-level">{{ __('adminplacement.bt.level') }}</label>
                <select id="bt-level" class="field" data-bt="level"><option value="all">{{ __('adminplacement.bt.level_all') }}</option>@foreach (AdminPlacement::LEVELS as $l)<option value="{{ $l }}">{{ $lvLabel($l) }}</option>@endforeach</select></div>
            <div class="ad-field"><label class="ad-label" for="bt-count">{{ __('adminplacement.bt.count') }}</label>
                <input id="bt-count" type="number" min="1" max="20" value="10" class="field" dir="ltr" inputmode="numeric" data-bt="count"></div>
            <div class="ad-field"><label class="ad-label" for="bt-topic">{{ __('adminplacement.bt.topic') }}</label>
                <input id="bt-topic" class="field" dir="auto" placeholder="{{ __('adminplacement.bt.topic_ph') }}" data-bt="topic" autocomplete="off"></div>
        </div>

        <p class="sb-err mt-3" role="alert" hidden data-bt-err></p>
        <div class="bk-bt-bar" data-bt-bar hidden>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-brand-700" data-bt-all checked> {{ __('adminplacement.bt.pick_all') }}</label>
            <span class="text-xs font-bold text-slate-400" data-bt-sum></span>
        </div>
        <ul class="bk-bt-list" data-bt-list hidden></ul>
        <p class="ad-ai-miss" data-bt-note hidden>{{ __('adminplacement.bt.audio_note') }}</p>

        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-outline" data-bt-gen>
                <span class="ad-ai-spin !border-brand-200 !border-t-brand-700" data-bt-spin aria-hidden="true"></span>
                <span data-bt-gen-t>{{ __('adminplacement.bt.go') }}</span>
            </button>
            <button type="button" class="btn-primary" data-bt-add hidden></button>
        </div>
    </div>
</div>
@endpush
