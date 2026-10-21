@extends('layouts.admin')

@section('title', __('admin.kit.title'))

{{-- راهنمای اجزای پنل (فقط برای توسعه) — فرم، دکمه‌ی AI، جدول، نشانه‌ها. در production می‌توانید حذفش کنید. --}}
@php use App\Support\AdminNav; @endphp
@section('content')
<div class="ad-head">
    <div><h1 class="ad-h1">{{ __('admin.kit.title') }}</h1><p class="ad-sub">{{ __('admin.kit.lead') }}</p></div>
</div>

<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="k-form">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="k-form">{{ __('admin.kit.form') }}</h2>
            <button type="button" class="ad-ai" data-ai-open data-ai-target="#kit-form" data-ai-seeds="title,artist" data-ai-fields="level,genre,desc_fa,desc_en,cover">{!! AdminNav::icon('sparkles') !!}{{ __('admin.ai.btn_long') }}</button>
        </div>
        <form id="kit-form" class="ad-form-grid" data-ai-form="song" onsubmit="return false">
            <div class="ad-field"><label class="ad-label" for="f-title">{{ __('admin.kit.f_title') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label><input id="f-title" name="title" class="field" data-ai-field value="Perfect"></div>
            <div class="ad-field"><label class="ad-label" for="f-artist">{{ __('admin.kit.f_artist') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label><input id="f-artist" name="artist" class="field" data-ai-field value="Ed Sheeran"></div>
            <div class="ad-field"><label class="ad-label" for="f-level">{{ __('admin.kit.f_level') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label>
                <select id="f-level" name="level" class="field" data-ai-field><option value="">—</option>@foreach (['A1','A2','B1','B2','C1'] as $l)<option>{{ $l }}</option>@endforeach</select></div>
            <div class="ad-field"><label class="ad-label" for="f-genre">Genre<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label><input id="f-genre" name="genre" class="field" data-ai-field></div>
            <div class="ad-field is-wide"><label class="ad-label" for="f-desc">{{ __('admin.kit.f_desc') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span>
                <button type="button" class="ad-ai ad-ai--sm" data-ai-one="desc_fa" data-ai-seeds="title,artist">{!! AdminNav::icon('sparkles') !!}{{ __('admin.ai.btn') }}</button></label>
                <textarea id="f-desc" name="desc_fa" class="field" data-ai-field></textarea></div>
            <div class="ad-field is-wide"><label class="ad-label" for="f-desc-en">{{ __('admin.kit.f_desc_en') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label><textarea id="f-desc-en" name="desc_en" class="field" dir="ltr" data-ai-field></textarea></div>
            <div class="ad-field is-wide"><label class="ad-label" for="f-cover">{{ __('admin.kit.f_cover') }}<span class="ad-chip">{{ __('admin.ai.chip') }}</span></label><input id="f-cover" name="cover" class="field" data-ai-field placeholder="https://"></div>
        </form>
    </section>

    <section class="ad-card" aria-labelledby="k-badges">
        <h2 class="ad-card__t mb-3" id="k-badges">{{ __('admin.kit.badges') }}</h2>
        <div class="flex flex-wrap gap-2">
            <span class="ad-badge">{{ __('admin.common.draft') }}</span>
            <span class="ad-badge" data-tone="ok">{{ __('admin.common.published') }}</span>
            <span class="ad-badge" data-tone="warn">{{ __('admin.status.pending') }}</span>
            <span class="ad-badge" data-tone="bad">{{ __('admin.status.failed') }}</span>
            <span class="ad-badge" data-tone="info">A2</span>
            <span class="ad-badge" data-tone="ai">{!! AdminNav::icon('sparkles', 'size-3') !!}AI</span>
        </div>
        <p class="ad-hint mt-4">{{ __('admin.ai.review') }}</p>
    </section>
</div>
@include('admin.partials.ai-dialog')
@endsection
