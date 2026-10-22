@extends('layouts.admin')

@php
    use App\Support\AdminNav;
    use App\Support\AdminPlacement;
    $isNew = empty($q['id']);
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $chip = '<span class="ad-chip">'.$ic('sparkles', 'size-3').__('admin.ai.chip').'</span>';
    $letters = ['A', 'B', 'C', 'D'];
    // ⚠️ داخل @json ویرگول نگذارید (بلید تکه‌تکه‌اش می‌کند)؛ مقدارها را اینجا بسازید
    $defaults = ['fa' => AdminPlacement::FA, 'title' => AdminPlacement::TITLES];
    $range = ['a1' => [80, 95], 'a2' => [65, 85], 'b1' => [50, 72], 'b2' => [38, 60], 'c1' => [25, 48]];
@endphp

@section('title', $isNew ? __('adminplacement.new_title') : __('adminplacement.edit_title').' #'.$q['id'])

{{--
    ویرایشگر یک سؤال: فرم + پیش‌نمایش زنده + بررسی. همه‌ی مقدارها داخل data-q (JSON)؛ رفتار: resources/js/admin/placement.js
    قرارداد ذخیره/آپلود/AI: app/Support/AdminPlacement.php و README.
--}}
@section('content')
<div data-bk-edit
    data-q='@json($q, $flags)'
    data-t='@json(__('adminplacement'), $flags)'
    data-defaults='@json($defaults, $flags)'
    data-range='@json($range)'
    data-list-url="{{ url('/admin/placement') }}"
    data-edit-url="{{ url('/admin/placement/__ID__/edit') }}"
    data-new-url="{{ url('/admin/placement/create') }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset
    @isset($urls['upload']) data-upload-url="{{ $urls['upload'] }}" @endisset
    @isset($urls['delete']) data-delete-url="{{ $urls['delete'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div class="min-w-0">
            <a href="{{ url('/admin/placement') }}" class="mb-1 inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-brand-700">{!! $ic('arrow', 'size-3.5 rtl:rotate-0 ltr:rotate-180') !!}{{ __('adminplacement.back') }}</a>
            <h1 class="ad-h1">{{ $isNew ? __('adminplacement.new_title') : __('adminplacement.edit_title') }} @unless ($isNew)<span class="text-slate-400" dir="ltr">#{{ $q['id'] }}</span>@endunless</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-bk-status data-tone="{{ $q['status'] === 'published' ? 'ok' : '' }}">{{ __('adminplacement.status_label.'.$q['status']) }}</span>
                <span class="text-xs text-slate-400" data-bk-dirty hidden>{{ __('adminplacement.unsaved') }}</span>
                <span class="text-xs font-bold text-slate-500" data-bk-msg role="status" aria-live="polite"></span>
            </p>
        </div>
        <div class="ad-acts">
            @unless ($isNew)<button type="button" class="btn-ghost !min-h-10 !text-xs !text-rose-600" data-bk-delete-open>{{ __('admin.common.delete') }}</button>@endunless
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-bk-save="draft">{{ __('adminplacement.save_draft') }}</button>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-bk-save="published">{{ __('adminplacement.publish') }}</button>
        </div>
    </div>

    <div class="ad-grid">
        <section class="ad-card is-2" aria-labelledby="bk-form-t">
            <div class="ad-card__h">
                <h2 class="ad-card__t" id="bk-form-t">{{ __('adminplacement.edit_title') }}</h2>
                <button type="button" class="ad-ai" data-ai-open data-ai-target="#bk-form" data-ai-seeds="section,level,topic" data-ai-fields="q,o1,o2,o3,o4,answer,explain" data-bk-ai-all>{!! $ic('sparkles') !!}{{ __('admin.ai.btn_long') }}</button>
            </div>

            <form id="bk-form" class="ad-form-grid" data-ai-form="question" @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset onsubmit="return false" novalidate>
                <div class="ad-field"><label class="ad-label" for="bk-section">{{ __('adminplacement.f.section') }}</label>
                    <select id="bk-section" name="section" class="field" data-f="section" data-ai-field>@foreach (AdminPlacement::SECTIONS as $s)<option value="{{ $s }}">{{ __('adminplacement.section.'.$s) }}</option>@endforeach</select></div>
                <div class="ad-field"><label class="ad-label" for="bk-level">{{ __('adminplacement.f.level') }}</label>
                    <select id="bk-level" name="level" class="field" data-f="level" data-ai-field>@foreach (AdminPlacement::LEVELS as $l)<option value="{{ $l }}">{{ __('adminplacement.level.'.$l) }} · {{ __('adminplacement.level_name.'.$l) }}</option>@endforeach</select>
                    <p class="ad-hint">{{ __('adminplacement.f.level_hint') }}</p></div>

                <div class="ad-field is-wide"><label class="ad-label" for="bk-topic">{{ __('adminplacement.f.topic') }}</label>
                    <input id="bk-topic" name="topic" class="field" dir="auto" placeholder="{{ __('adminplacement.f.topic_ph') }}" data-ai-field autocomplete="off">
                    <p class="ad-hint">{{ __('adminplacement.f.topic_hint') }}</p></div>

                <div class="ad-field is-wide"><label class="ad-label" for="bk-fa">{{ __('adminplacement.f.fa') }}</label>
                    <input id="bk-fa" name="fa" class="field" data-f="fa" data-ai-field autocomplete="off"></div>

                <div class="ad-field is-wide" data-bk-only="listening" hidden>
                    <label class="ad-label" for="bk-transcript"><span>{{ __('adminplacement.f.transcript') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="transcript" data-ai-seeds="section,level,topic">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <textarea id="bk-transcript" name="transcript" class="field" dir="ltr" data-f="transcript" data-ai-field></textarea>
                    <p class="ad-hint">{{ __('adminplacement.f.transcript_hint') }}</p><p class="ad-error" hidden></p>
                </div>

                <div class="ad-field is-wide"><label class="ad-label" for="bk-q"><span>{{ __('adminplacement.f.q') }} <span class="text-rose-500">*</span>{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="q" data-ai-seeds="section,level,topic">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <textarea id="bk-q" name="q" class="field !min-h-20" dir="ltr" data-f="q" data-ai-field></textarea>
                    <p class="ad-hint" data-bk-qhint>{{ __('adminplacement.f.q_hint_blank') }}</p><p class="ad-error" hidden></p></div>

                <div class="ad-field is-wide" data-bk-opts-box>
                    <p class="ad-label !mb-1"><span>{{ __('adminplacement.f.options') }} <span class="text-rose-500">*</span></span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="o1,o2,o3,o4,answer" data-ai-seeds="section,level,q">{!! $ic('sparkles', 'size-3.5') !!}{{ __('adminplacement.ai.field_options') }}</button></p>
                    <p class="ad-hint !mt-0 mb-2">{{ __('adminplacement.f.options_hint') }}</p>
                    <div class="bk-opts" role="radiogroup" aria-label="{{ __('adminplacement.f.correct') }}" data-bk-opts>
                        @foreach ($letters as $i => $L)
                            <div class="bk-opt ad-field" data-i="{{ $i }}">
                                <label class="bk-opt__pick" title="{{ __('adminplacement.f.correct') }}">
                                    <input type="radio" name="answer_pick" value="{{ $i }}" class="sr-only" aria-label="{{ __('adminplacement.f.correct') }} {{ $L }}">
                                    <i aria-hidden="true">{!! $ic('check', 'size-3.5') !!}</i>
                                </label>
                                <span class="bk-opt__l" aria-hidden="true">{{ $L }}</span>
                                <label class="sr-only ad-label" for="bk-o{{ $i + 1 }}">{{ __('adminplacement.f.option', ['n' => $L]) }}</label>
                                <input id="bk-o{{ $i + 1 }}" name="o{{ $i + 1 }}" class="field" dir="ltr" placeholder="{{ __('adminplacement.f.option', ['n' => $L]) }}" data-f="o{{ $i + 1 }}" data-ai-field autocomplete="off">
                            </div>
                        @endforeach
                    </div>
                    <div class="ad-field" hidden><label class="ad-label">{{ __('adminplacement.f.correct') }}</label><input type="hidden" name="answer" data-f="answer" data-ai-field></div>
                    <p class="ad-error" hidden data-bk-opts-err></p>
                </div>

                <div class="ad-field is-wide"><label class="ad-label" for="bk-explain"><span>{{ __('adminplacement.f.explain') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="explain" data-ai-seeds="section,level,q">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <textarea id="bk-explain" name="explain" class="field !min-h-16" data-f="explain" data-ai-field></textarea>
                    <p class="ad-hint">{{ __('adminplacement.f.explain_hint') }}</p></div>

                {{-- فایل صوتی (فقط شنیداری) --}}
                <div class="ad-field is-wide" data-bk-only="listening" hidden>
                    <div class="ed-up ed-up--box" data-bk-up>
                        <div class="ed-up__prev" aria-hidden="true">{!! $ic('songs', 'size-6') !!}</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-extrabold text-brand-900">{{ __('adminplacement.f.audio') }}{!! $chip !!}</p>
                            <p class="ad-hint !mt-0.5">{{ __('adminplacement.f.audio_hint') }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="audio/*" data-bk-file>{{ __('admin.common.upload') }}</label>
                                <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-bk-up-clear hidden>{{ __('admin.common.delete') }}</button>
                            </div>
                            <div class="ad-field mt-2"><input name="audio" class="field !min-h-10 !text-xs" dir="ltr" placeholder="https://" data-f="audio" data-ai-field aria-label="{{ __('adminplacement.f.audio') }} URL"></div>
                            <audio class="mt-2 hidden w-full" controls preload="none" data-bk-audio></audio>
                            <p class="ad-error" hidden data-bk-up-err></p>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <div class="grid content-start gap-4 xl:gap-5">
            {{-- پیش‌نمایش --}}
            <section class="ad-card" aria-labelledby="bk-pv-t">
                <h2 class="ad-card__t mb-3" id="bk-pv-t">{{ __('adminplacement.pv.title') }}</h2>
                <div class="bk-pv" data-bk-pv>
                    <div class="bk-pv__top"><b data-pv="title"></b><span class="ad-badge" data-tone="info" data-pv="level"></span></div>
                    <p class="bk-pv__fa" data-pv="fa"></p>
                    <button type="button" class="bk-pv__play" data-bk-play hidden>{!! $ic('songs', 'size-4') !!}<span data-bk-play-t>{{ __('adminplacement.pv.play') }}</span></button>
                    <p class="bk-pv__q" dir="ltr" data-pv="q"></p>
                    <ul class="bk-pv__opts" dir="ltr" data-pv="opts"></ul>
                </div>
                <p class="ad-hint mt-2">{{ __('adminplacement.pv.correct') }}: <b class="text-emerald-600" data-pv="ans"></b></p>
            </section>

            {{-- بررسی --}}
            <section class="ad-card" aria-labelledby="bk-ck-t">
                <h2 class="ad-card__t mb-2" id="bk-ck-t">{{ __('adminplacement.ck.title') }}</h2>
                <ul class="bk-ck" data-bk-ck aria-live="polite"></ul>
            </section>

            {{-- آمار --}}
            @unless ($isNew)
            <section class="ad-card" aria-labelledby="bk-st-t" data-bk-stats>
                <h2 class="ad-card__t mb-2" id="bk-st-t">{{ __('adminplacement.st.title') }}</h2>
                <div data-bk-stats-body></div>
            </section>
            @endunless
        </div>
    </div>

    <div class="ed-savebar" data-bk-savebar>
        <span class="text-xs font-bold text-slate-500" data-bk-msg2 role="status"></span>
        <div class="flex items-center gap-2">
            @if ($isNew)<button type="button" class="btn-outline !min-h-10 !text-xs" data-bk-save="next">{{ __('adminplacement.save_next') }}</button>@endif
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-bk-save="auto">{{ __('adminplacement.save') }}</button>
        </div>
    </div>
</div>
@include('admin.partials.ai-dialog')
@endsection

@push('modals')
<div id="bk-delete" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="bk-del-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="bk-del-t" class="text-lg font-extrabold text-brand-900">{{ __('adminplacement.del.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('adminplacement.del.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-bk-delete>{{ __('adminplacement.del.go') }}</button>
        </div>
    </div>
</div>
@endpush
