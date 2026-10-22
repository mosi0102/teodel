@extends('layouts.admin')

@php
    use App\Support\AdminDictionary;
    use App\Support\AdminNav;
    $isNew = empty($w['id']);
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $chip = '<span class="ad-chip">'.$ic('sparkles', 'size-3').__('admin.ai.chip').'</span>';
    // ⚠️ داخل @json ویرگول نگذارید (بلید تکه‌تکه‌اش می‌کند)؛ مقدارها را اینجا بسازید
    // کلید لغت‌های موجود برای هشدار «تکراری»؛ سرور باید همین را با 422 هم رد کند (برای ده‌ها هزار لغت از این روش به‌جای API استفاده نکنید)
    $taken = array_map(fn ($r) => ['id' => $r['id'], 'k' => AdminDictionary::key($r['phrase'], $r['type'])], AdminDictionary::demo());
@endphp

@section('title', $isNew ? __('admindictionary.new_title') : __('admindictionary.edit_title').' #'.$w['id'])

{{--
    ویرایشگر یک لغت: فرم + پیش‌نمایش زنده + بررسی. همه‌ی مقدارها داخل data-w (JSON)؛ رفتار: resources/js/admin/dictionary.js
    قرارداد ذخیره/آپلود/AI: app/Support/AdminDictionary.php و README.
--}}
@section('content')
<div data-dc-edit
    data-w='@json($w, $flags)'
    data-t='@json(__('admindictionary'), $flags)'
    data-taken='@json($taken, $flags)'
    data-list-url="{{ url('/admin/dictionary') }}"
    data-edit-url="{{ url('/admin/dictionary/__ID__/edit') }}"
    data-new-url="{{ url('/admin/dictionary/create') }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset
    @isset($urls['upload']) data-upload-url="{{ $urls['upload'] }}" @endisset
    @isset($urls['delete']) data-delete-url="{{ $urls['delete'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <div class="ad-head">
        <div class="min-w-0">
            <a href="{{ url('/admin/dictionary') }}" class="mb-1 inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-brand-700">{!! $ic('arrow', 'size-3.5 rtl:rotate-0 ltr:rotate-180') !!}{{ __('admindictionary.back') }}</a>
            <h1 class="ad-h1">{{ $isNew ? __('admindictionary.new_title') : __('admindictionary.edit_title') }} @unless ($isNew)<span class="text-slate-400" dir="ltr">#{{ $w['id'] }}</span>@endunless</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-dc-status data-tone="{{ $w['status'] === 'published' ? 'ok' : '' }}">{{ __('admindictionary.status_label.'.$w['status']) }}</span>
                <span class="text-xs text-slate-400" data-dc-dirty hidden>{{ __('admindictionary.unsaved') }}</span>
                <span class="text-xs font-bold text-slate-500" data-dc-msg role="status" aria-live="polite"></span>
            </p>
        </div>
        <div class="ad-acts">
            @unless ($isNew)<button type="button" class="btn-ghost !min-h-10 !text-xs !text-rose-600" data-dc-delete-open>{{ __('admin.common.delete') }}</button>@endunless
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-dc-save="draft">{{ __('admindictionary.save_draft') }}</button>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-dc-save="published">{{ __('admindictionary.publish') }}</button>
        </div>
    </div>

    <div class="ad-grid">
        <section class="ad-card is-2" aria-labelledby="dc-form-t">
            <div class="ad-card__h">
                <h2 class="ad-card__t" id="dc-form-t">{{ __('admindictionary.edit_title') }}</h2>
                <button type="button" class="ad-ai" data-ai-open data-ai-target="#dc-form" data-ai-seeds="phrase,type" data-ai-fields="type,translate,meaning,example,example_tr,ipa_uk,ipa_us,synonyms,antonyms,image,audio_uk,audio_us">{!! $ic('sparkles') !!}{{ __('admin.ai.btn_long') }}</button>
            </div>

            <form id="dc-form" class="ad-form-grid" data-ai-form="word" @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset onsubmit="return false" novalidate>
                <div class="ad-field"><label class="ad-label" for="dc-phrase"><span>{{ __('admindictionary.f.phrase') }} <span class="text-rose-500">*</span></span></label>
                    <input id="dc-phrase" name="phrase" class="field" dir="ltr" data-f="phrase" data-ai-field autocomplete="off" spellcheck="false">
                    <p class="ad-hint">{{ __('admindictionary.f.phrase_hint') }}</p><p class="ad-error" hidden></p></div>
                <div class="ad-field"><label class="ad-label" for="dc-type"><span>{{ __('admindictionary.f.type') }} <span class="text-rose-500">*</span>{!! $chip !!}</span></label>
                    <select id="dc-type" name="type" class="field" data-f="type" data-ai-field><option value="">{{ __('admindictionary.f.type_ph') }}</option>@foreach (AdminDictionary::TYPES as $ty)<option value="{{ $ty }}">{{ __('admindictionary.type.'.$ty) }}</option>@endforeach</select>
                    <p class="ad-error" hidden></p></div>

                <div class="ad-field is-wide"><label class="ad-label" for="dc-translate"><span>{{ __('admindictionary.f.translate') }} <span class="text-rose-500">*</span>{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="translate" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <input id="dc-translate" name="translate" class="field" dir="rtl" data-f="translate" data-ai-field autocomplete="off">
                    <p class="ad-hint">{{ __('admindictionary.f.translate_hint') }}</p><p class="ad-error" hidden></p></div>

                <div class="ad-field is-wide"><label class="ad-label" for="dc-meaning"><span>{{ __('admindictionary.f.meaning') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="meaning" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <textarea id="dc-meaning" name="meaning" class="field !min-h-16" dir="ltr" data-f="meaning" data-ai-field></textarea>
                    <p class="ad-hint">{{ __('admindictionary.f.meaning_hint') }}</p></div>

                <div class="ad-field is-wide"><label class="ad-label" for="dc-example"><span>{{ __('admindictionary.f.example') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="example,example_tr" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <textarea id="dc-example" name="example" class="field !min-h-16" dir="ltr" data-f="example" data-ai-field></textarea></div>
                <div class="ad-field is-wide"><label class="ad-label" for="dc-example-tr"><span>{{ __('admindictionary.f.example_tr') }}{!! $chip !!}</span></label>
                    <textarea id="dc-example-tr" name="example_tr" class="field !min-h-16" dir="rtl" data-f="example_tr" data-ai-field></textarea></div>

                {{-- تلفظ --}}
                <div class="ad-field is-wide">
                    <p class="ad-label !mb-1"><span>{{ __('admindictionary.f.ipa') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="ipa_uk,ipa_us" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></p>
                    <div class="ad-form-grid !gap-3">
                        <div class="ad-field"><label class="ad-label" for="dc-ipa-uk">{{ __('admindictionary.f.ipa_uk') }}</label>
                            <input id="dc-ipa-uk" name="ipa_uk" class="field" dir="ltr" placeholder="/…/" data-f="ipa_uk" data-ai-field autocomplete="off" spellcheck="false"></div>
                        <div class="ad-field"><label class="ad-label" for="dc-ipa-us">{{ __('admindictionary.f.ipa_us') }}</label>
                            <input id="dc-ipa-us" name="ipa_us" class="field" dir="ltr" placeholder="/…/" data-f="ipa_us" data-ai-field autocomplete="off" spellcheck="false"></div>
                    </div>
                </div>

                {{-- فایل‌های صوتی (AI نمی‌سازد؛ دستی) --}}
                <div class="ad-field is-wide">
                    <p class="ad-label !mb-0"><span>{{ __('admindictionary.f.audio') }}</span></p>
                    <p class="ad-hint !mt-0 mb-2">{{ __('admindictionary.f.audio_hint') }}</p>
                    <div class="ad-form-grid !gap-3">
                        @foreach (['uk', 'us'] as $a)
                            <div class="ad-field">
                                <div class="ed-up ed-up--box dc-aud" data-dc-up="audio_{{ $a }}">
                                    <button type="button" class="dc-play" data-dc-play="{{ $a }}" aria-label="{{ __('admindictionary.up.play') }} {{ strtoupper($a) }}">{!! $ic('songs', 'size-4') !!}</button>
                                    <div class="min-w-0 flex-1">
                                        <label class="ad-label !mb-1" for="dc-audio-{{ $a }}">{{ __('admindictionary.f.audio_'.$a) }}</label>
                                        <input id="dc-audio-{{ $a }}" name="audio_{{ $a }}" class="field !min-h-10 !text-xs" dir="ltr" placeholder="https://" data-f="audio_{{ $a }}" data-ai-field autocomplete="off">
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="audio/*" data-dc-file="audio_{{ $a }}">{{ __('admindictionary.up.choose') }}</label>
                                            <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-dc-clear="audio_{{ $a }}" hidden>{{ __('admindictionary.up.remove') }}</button>
                                        </div>
                                        <p class="ad-error" hidden data-dc-up-err></p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- تصویر لغت --}}
                <div class="ad-field is-wide">
                    <div class="ed-up ed-up--box" data-dc-up="image">
                        <div class="ed-up__prev ed-up__prev--img" data-dc-img-prev aria-hidden="true">{!! $ic('image', 'size-6') !!}</div>
                        <div class="min-w-0 flex-1">
                            <p class="ad-label !mb-0"><span>{{ __('admindictionary.f.image') }}{!! $chip !!}</span></p>
                            <p class="ad-hint !mt-0.5">{{ __('admindictionary.f.image_hint') }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="btn-outline !min-h-9 !px-3 !text-xs cursor-pointer"><input type="file" class="sr-only" accept="image/*" data-dc-file="image">{{ __('admindictionary.up.choose') }}</label>
                                <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-dc-clear="image" hidden>{{ __('admindictionary.up.remove') }}</button>
                            </div>
                            <input type="text" name="image" class="field mt-2 !min-h-10 !text-xs" dir="ltr" placeholder="https://" data-f="image" data-ai-field aria-label="{{ __('admindictionary.f.image') }} URL" autocomplete="off">
                            <p class="ad-error" hidden data-dc-up-err></p>
                        </div>
                    </div>
                </div>

                <div class="ad-field"><label class="ad-label" for="dc-syn"><span>{{ __('admindictionary.f.synonyms') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="synonyms" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <input id="dc-syn" name="synonyms" class="field" dir="ltr" data-f="synonyms" data-ai-field autocomplete="off">
                    <p class="ad-hint">{{ __('admindictionary.f.list_hint') }}</p></div>
                <div class="ad-field"><label class="ad-label" for="dc-ant"><span>{{ __('admindictionary.f.antonyms') }}{!! $chip !!}</span>
                        <button type="button" class="ad-ai ad-ai--sm" data-ai-one="antonyms" data-ai-seeds="phrase,type">{!! $ic('sparkles', 'size-3.5') !!}{{ __('admin.ai.btn') }}</button></label>
                    <input id="dc-ant" name="antonyms" class="field" dir="ltr" data-f="antonyms" data-ai-field autocomplete="off">
                    <p class="ad-hint">{{ __('admindictionary.f.list_hint') }}</p></div>
            </form>
        </section>

        <div class="grid content-start gap-4 xl:gap-5">
            {{-- پیش‌نمایش --}}
            <section class="ad-card" aria-labelledby="dc-pv-t">
                <h2 class="ad-card__t mb-3" id="dc-pv-t">{{ __('admindictionary.pv.title') }}</h2>
                <div class="dc-pv" data-dc-pv>
                    <div class="dc-pv__top">
                        <div data-pv="img"></div>
                        <div class="min-w-0">
                            <p class="dc-pv__phrase" dir="ltr" data-pv="phrase"></p>
                            <span class="ad-badge" data-tone="info" data-pv="type" hidden></span>
                        </div>
                    </div>
                    @foreach (['uk', 'us'] as $a)
                        <div class="dc-pv__ipa" data-pv-ipa="{{ $a }}" dir="ltr" hidden>
                            <button type="button" class="dc-play dc-play--sm" data-dc-play="{{ $a }}" aria-label="{{ __('admindictionary.up.play') }} {{ strtoupper($a) }}">{!! $ic('songs', 'size-3.5') !!}</button>
                            <b>{{ __('admindictionary.pv.'.$a) }}</b><span data-pv="ipa"></span>
                        </div>
                    @endforeach
                    <p class="dc-pv__tr" data-pv="translate" dir="rtl" hidden></p>
                    <p class="dc-pv__meaning" dir="ltr" data-pv="meaning" hidden></p>
                    <div class="dc-pv__ex" data-pv="example" hidden>
                        <p dir="ltr" data-pv="ex"></p>
                        <p class="text-slate-400" dir="rtl" data-pv="extr" hidden></p>
                    </div>
                    <div class="dc-pv__chips" data-pv="syn" hidden dir="ltr"><b>{{ __('admindictionary.pv.syn') }}</b><ul></ul></div>
                    <div class="dc-pv__chips is-ant" data-pv="ant" hidden dir="ltr"><b>{{ __('admindictionary.pv.ant') }}</b><ul></ul></div>
                </div>
            </section>

            {{-- بررسی + کامل بودن --}}
            <section class="ad-card" aria-labelledby="dc-ck-t">
                <h2 class="ad-card__t mb-2" id="dc-ck-t">{{ __('admindictionary.ck.title') }}</h2>
                <div class="dc-segs dc-segs--lg" data-dc-segs aria-hidden="true"></div>
                <p class="ad-hint mb-3" data-dc-done></p>
                <ul class="bk-ck" data-dc-ck aria-live="polite"></ul>
            </section>
        </div>
    </div>

    <div class="ed-savebar" data-dc-savebar>
        <span class="text-xs font-bold text-slate-500" data-dc-msg2 role="status"></span>
        <div class="flex items-center gap-2">
            @if ($isNew)<button type="button" class="btn-outline !min-h-10 !text-xs" data-dc-save="next">{{ __('admindictionary.save_next') }}</button>@endif
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-dc-save="auto">{{ __('admindictionary.save') }}</button>
        </div>
    </div>
</div>
@include('admin.partials.ai-dialog')
@endsection

@push('modals')
<div id="dc-delete" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="dc-del-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="dc-del-t" class="text-lg font-extrabold text-brand-900">{{ __('admindictionary.del.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('admindictionary.del.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-dc-delete>{{ __('admindictionary.del.go') }}</button>
        </div>
    </div>
</div>
@endpush
