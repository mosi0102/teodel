@extends('layouts.admin')

@section('title', __('admindictionary.title'))

{{--
    مدیریت دیکشنری: آمار پوشش (تصویر/صدا/کامل)، فیلتر، نوار الفبا، جدول (موبایل: کارت) و ساخت گروهی با AI.
    فیلتر و صفحه‌بندی سمت مرورگرند؛ رفتار: resources/js/admin/dictionary.js — قرارداد داده: app/Support/AdminDictionary.php
--}}
@php
    use App\Support\AdminDictionary;
    use App\Support\AdminNav;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $letters = range('A', 'Z');
@endphp

@section('content')
<div data-dc-list
    data-rows='@json($rows, $flags)'
    data-t='@json(__('admindictionary'), $flags)'
    data-edit-url="{{ url('/admin/dictionary/__ID__/edit') }}"
    data-new-url="{{ url('/admin/dictionary/create') }}"
    @isset($urls['bulk']) data-bulk-url="{{ $urls['bulk'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('admindictionary.title') }}</h1>
            <p class="ad-sub">{{ __('admindictionary.sub') }}</p>
        </div>
        <div class="ad-acts">
            <button type="button" class="ad-ai" data-dc-batch-open>{!! $ic('sparkles') !!}{{ __('admindictionary.batch') }}</button>
            <a href="{{ url('/admin/dictionary/create') }}" class="btn-primary !min-h-10 !text-xs">{!! $ic('plus') !!}{{ __('admindictionary.new') }}</a>
        </div>
    </div>

    {{-- آمار پوشش: روی کارت‌های تصویر/صدا/کامل بزنید تا لیست فیلتر شود --}}
    <div class="dc-stats">
        <div class="dc-stat" data-dc-stat="total">
            <span class="dc-stat__l">{{ __('admindictionary.stat.total') }}</span>
            <b class="dc-stat__v" data-v>0</b>
            <small class="dc-stat__s" data-s></small>
        </div>
        @foreach (['image' => 'noimage', 'audio' => 'noaudio', 'complete' => 'incomplete'] as $k => $go)
            <button type="button" class="dc-stat is-link" data-dc-stat="{{ $k }}" data-go="{{ $go }}">
                <span class="dc-stat__l">{{ __('admindictionary.stat.'.$k) }}</span>
                <b class="dc-stat__v" data-v>0</b>
                <small class="dc-stat__s" data-s></small>
                <i class="dc-bar" aria-hidden="true"></i>
            </button>
        @endforeach
    </div>

    {{-- فیلترها --}}
    <div class="ad-tools mt-4" role="search">
        <div class="ad-tools__q"><input type="search" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('admindictionary.search_ph') }}" aria-label="{{ __('admindictionary.search_ph') }}" autocomplete="off" data-dc-q></div>
        <label class="sr-only" for="dc-type">{{ __('admindictionary.col.type') }}</label>
        <select id="dc-type" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-dc-type>
            <option value="all">{{ __('admindictionary.type_all') }}</option>
            @foreach (AdminDictionary::TYPES as $ty)<option value="{{ $ty }}">{{ __('admindictionary.type.'.$ty) }}</option>@endforeach
        </select>
        <label class="sr-only" for="dc-status">{{ __('admindictionary.col.status') }}</label>
        <select id="dc-status" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-dc-status>
            @foreach (['all', 'published', 'draft'] as $s)<option value="{{ $s }}">{{ __('admindictionary.status.'.$s) }}</option>@endforeach
        </select>
        <label class="sr-only" for="dc-sort">{{ __('admindictionary.sort.label') }}</label>
        <select id="dc-sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-dc-sort>
            @foreach (['newest', 'az', 'least'] as $o)<option value="{{ $o }}">{{ __('admindictionary.sort.'.$o) }}</option>@endforeach
        </select>
    </div>
    <div class="ad-filters">
        <nav class="ad-chips" aria-label="{{ __('admindictionary.col.done') }}" data-dc-quick>
            @foreach (['all', 'noimage', 'noaudio', 'incomplete', 'draft'] as $q)
                <button type="button" class="chip-btn shrink-0" data-v="{{ $q }}" @if ($q === 'all') aria-current="true" @endif>{{ __('admindictionary.quick.'.$q) }} <b class="ms-1 tabular-nums" data-n></b></button>
            @endforeach
        </nav>
        <nav class="dc-letters" aria-label="{{ __('admindictionary.letters') }}" data-dc-letters dir="ltr">
            <button type="button" data-v="all" aria-current="true">{{ __('admindictionary.letter_all') }}</button>
            @foreach ($letters as $L)<button type="button" data-v="{{ $L }}">{{ $L }}</button>@endforeach
            <button type="button" data-v="#">#</button>
        </nav>
    </div>

    <section class="ad-card !p-3 md:!p-5">
        <div class="ad-tbl-wrap" data-dc-table>
            <table class="ad-tbl">
                <thead><tr>
                    <th>{{ __('admindictionary.col.phrase') }}</th><th>{{ __('admindictionary.col.type') }}</th><th>{{ __('admindictionary.col.translate') }}</th>
                    <th>{{ __('admindictionary.col.done') }}</th><th>{{ __('admindictionary.col.status') }}</th><th>{{ __('admindictionary.col.updated') }}</th>
                </tr></thead>
                <tbody data-dc-rows></tbody>
            </table>
        </div>
        <div class="ad-empty" data-dc-empty hidden>
            <span>{{ __('admindictionary.empty') }}</span>
            <button type="button" class="btn-outline !min-h-9 !text-xs" data-dc-clear>{{ __('admindictionary.clear') }}</button>
        </div>
        <div class="ad-pager" data-dc-pager>
            <span data-dc-count></span>
            <div>
                <button type="button" class="btn-outline" data-dc-prev>{{ __('admindictionary.prev') }}</button>
                <button type="button" class="btn-outline" data-dc-next>{{ __('admindictionary.next') }}</button>
            </div>
        </div>
    </section>
</div>
@endsection

@push('modals')
{{-- ساخت گروهی با AI --}}
<div id="dc-batch" class="dialog ad-ai-dlg" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dc-batch-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-2xl">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start gap-3.5">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-50 text-violet-600">{!! $ic('sparkles', 'size-5') !!}</span>
            <div class="min-w-0">
                <h2 id="dc-batch-t" class="text-lg font-extrabold text-brand-900">{{ __('admindictionary.bt.title') }}</h2>
                <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('admindictionary.bt.lead') }}</p>
            </div>
        </div>

        <div class="ad-form-grid mt-4">
            <div class="ad-field is-wide"><label class="ad-label" for="dc-bt-words">{{ __('admindictionary.bt.words') }}</label>
                <textarea id="dc-bt-words" class="field !min-h-28" dir="ltr" placeholder="{{ __('admindictionary.bt.words_ph') }}" data-bt="words"></textarea>
                <p class="ad-hint">{{ __('admindictionary.bt.hint', ['n' => 30]) }}</p></div>
            <div class="ad-field"><label class="ad-label" for="dc-bt-type">{{ __('admindictionary.bt.type') }}</label>
                <select id="dc-bt-type" class="field" data-bt="type"><option value="">{{ __('admindictionary.bt.type_any') }}</option>@foreach (AdminDictionary::TYPES as $ty)<option value="{{ $ty }}">{{ __('admindictionary.type.'.$ty) }}</option>@endforeach</select></div>
        </div>

        <p class="sb-err mt-3" role="alert" hidden data-bt-err></p>
        <div class="bk-bt-bar" data-bt-bar hidden>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-brand-700" data-bt-all checked> {{ __('admindictionary.bt.pick_all') }}</label>
            <span class="text-xs font-bold text-slate-400" data-bt-sum></span>
        </div>
        <ul class="bk-bt-list" data-bt-list hidden></ul>
        <p class="ad-ai-miss" data-bt-note hidden>{{ __('admindictionary.bt.note') }}</p>

        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-outline" data-bt-gen>
                <span class="ad-ai-spin !border-brand-200 !border-t-brand-700" data-bt-spin aria-hidden="true"></span>
                <span data-bt-gen-t>{{ __('admindictionary.bt.go') }}</span>
            </button>
            <button type="button" class="btn-primary" data-bt-add hidden></button>
        </div>
    </div>
</div>
@endpush
