@extends('layouts.admin')

@section('title', __('adminsettings.title'))

{{--
    تنظیمات: بخش‌ها با ناوبری کناری (موبایل: چیپ‌های افقی)، یک نوار «ذخیره‌ی تغییرات» که با ویرایش ظاهر می‌شود.
    مقدارها و قرارداد ذخیره: app/Support/AdminSettings.php — رفتار: resources/js/admin/settings.js
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminSettings;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $groups = ['general', 'access', 'level_test', 'ai', 'payments', 'notify', 'referral'];
    $t = fn (string $k, array $r = []) => __('adminsettings.'.$k, $r);
    // ⚠️ داخل @json ویرگول نگذارید (بلید تکه‌تکه‌اش می‌کند)؛ مقدارها را اینجا بسازید
    $trans = ['v' => __('adminsettings.v'), 'bar' => __('adminsettings.bar'), 'errors' => __('adminsettings.errors'), 'cover' => __('adminsettings.cover'), 'secret' => __('adminsettings.secret'), 'sections' => __('adminplacement.section')];
    $numbers = [
        'access' => [['free_songs', 0, 20, 1], ['free_steps', 1, 8, 1]],
        'level_test' => [['questions_per_section', 5, 50, 5], ['time_limit_min', 0, 120, 1], ['retake_days', 0, 90, 1]],
        'referral' => [['inviter_points', 0, 1000, 1], ['invitee_days', 0, 30, 1]],
    ];
    $switches = ['notify' => ['new_ticket', 'new_report', 'failed_payment']];
@endphp

@section('content')
<div data-st data-s='@json($s, $flags)' data-t='@json($trans, $flags)' data-cover='@json($cover, $flags)' data-bank-url="{{ url('/admin/placement') }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset>

    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ $t('title') }}</h1>
            <p class="ad-sub">{{ $t('sub') }}</p>
        </div>
    </div>

    <div class="st-layout">
        <nav class="st-nav" aria-label="{{ $t('title') }}" data-st-nav>
            @foreach ($groups as $g)<a href="#st-{{ $g }}" @if ($loop->first) aria-current="true" @endif>{{ $t('nav.'.$g) }}</a>@endforeach
        </nav>

        <form class="st-form" onsubmit="return false" novalidate data-st-form>
            {{-- عمومی --}}
            <section class="ad-card st-sec" id="st-general" aria-labelledby="st-general-t">
                <h2 class="ad-card__t" id="st-general-t">{{ $t('sec.general.t') }}</h2>
                <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.general.d') }}</p>
                <div class="ad-form-grid">
                    <div class="ad-field"><label class="ad-label" for="st-site-fa">{{ $t('f.general.site_name_fa') }}</label>
                        <input id="st-site-fa" class="field" data-k="general.site_name_fa" maxlength="80" autocomplete="off"><p class="ad-hint">{{ $t('h.general.site_name_fa') }}</p><p class="ad-error" hidden></p></div>
                    <div class="ad-field"><label class="ad-label" for="st-site-en">{{ $t('f.general.site_name_en') }}</label>
                        <input id="st-site-en" class="field" dir="ltr" data-k="general.site_name_en" maxlength="80" autocomplete="off"><p class="ad-hint">{{ $t('h.general.site_name_en') }}</p><p class="ad-error" hidden></p></div>
                    <div class="ad-field"><label class="ad-label" for="st-email">{{ $t('f.general.support_email') }}</label>
                        <input id="st-email" type="email" class="field" dir="ltr" data-k="general.support_email" autocomplete="off"><p class="ad-hint">{{ $t('h.general.support_email') }}</p><p class="ad-error" hidden></p></div>
                    <div class="ad-field"><label class="ad-label" for="st-locale">{{ $t('f.general.default_locale') }}</label>
                        <select id="st-locale" class="field" data-k="general.default_locale">@foreach (AdminSettings::LOCALES as $l)<option value="{{ $l }}">{{ $t('locale.'.$l) }}</option>@endforeach</select>
                        <p class="ad-hint">{{ $t('h.general.default_locale') }}</p></div>
                    <div class="ad-field is-wide"><label class="ad-switch st-danger"><input type="checkbox" data-k="general.maintenance" data-st-maint><span class="ad-switch__t"><b>{{ $t('f.general.maintenance') }}</b><small>{{ $t('h.general.maintenance') }}</small></span></label></div>
                    <div class="ad-field is-wide" data-st-maint-msg><label class="ad-label" for="st-maint-msg">{{ $t('f.general.maintenance_msg') }}</label>
                        <textarea id="st-maint-msg" class="field !min-h-16" dir="auto" data-k="general.maintenance_msg" maxlength="300"></textarea><p class="ad-hint">{{ $t('h.general.maintenance_msg') }}</p><p class="ad-error" hidden></p></div>
                </div>
            </section>

            {{-- دسترسی رایگان / آزمون / دعوت: فیلدهای عددی --}}
            @foreach (['access', 'level_test'] as $g)
                <section class="ad-card st-sec" id="st-{{ $g }}" aria-labelledby="st-{{ $g }}-t">
                    <h2 class="ad-card__t" id="st-{{ $g }}-t">{{ $t('sec.'.$g.'.t') }}</h2>
                    <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.'.$g.'.d') }}</p>
                    <div class="ad-form-grid">
                        @foreach ($numbers[$g] as [$k, $min, $max, $step])
                            <div class="ad-field"><label class="ad-label" for="st-{{ $g }}-{{ $k }}">{{ $t('f.'.$g.'.'.$k) }}</label>
                                <input id="st-{{ $g }}-{{ $k }}" type="number" class="field" dir="ltr" inputmode="numeric" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" data-k="{{ $g }}.{{ $k }}" data-min="{{ $min }}" data-max="{{ $max }}" data-step="{{ $step }}">
                                <p class="ad-hint">{{ $t('h.'.$g.'.'.$k) }}</p><p class="ad-error" hidden></p></div>
                        @endforeach
                    </div>
                    @if ($g === 'level_test')<p class="st-cover" data-st-cover hidden></p>@endif
                </section>
            @endforeach

            {{-- هوش مصنوعی --}}
            <section class="ad-card st-sec" id="st-ai" aria-labelledby="st-ai-t">
                <h2 class="ad-card__t" id="st-ai-t">{{ $t('sec.ai.t') }}</h2>
                <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.ai.d') }}</p>
                <div class="ad-form-grid">
                    <div class="ad-field is-wide"><label class="ad-switch"><input type="checkbox" data-k="ai.enabled"><span class="ad-switch__t"><b>{{ $t('f.ai.enabled') }}</b><small>{{ $t('h.ai.enabled') }}</small></span></label></div>
                    <div class="ad-field"><label class="ad-label" for="st-ai-model">{{ $t('f.ai.model') }}</label>
                        <input id="st-ai-model" class="field" dir="ltr" data-k="ai.model" autocomplete="off"><p class="ad-hint">{{ $t('h.ai.model') }}</p><p class="ad-error" hidden></p></div>
                    <div class="ad-field"><label class="ad-label" for="st-ai-batch">{{ $t('f.ai.batch_max') }}</label>
                        <input id="st-ai-batch" type="number" class="field" dir="ltr" inputmode="numeric" min="5" max="30" data-k="ai.batch_max" data-min="5" data-max="30" data-step="1"><p class="ad-hint">{{ $t('h.ai.batch_max') }}</p><p class="ad-error" hidden></p></div>
                    <div class="ad-field is-wide"><label class="ad-label" for="st-ai-key"><span>{{ $t('f.ai.key') }}</span><span class="ad-badge" data-st-secret-badge="ai.key_set"></span></label>
                        <input id="st-ai-key" type="password" class="field" dir="ltr" data-k="ai.key" data-secret="ai.key_set" autocomplete="new-password" spellcheck="false"><p class="ad-hint">{{ $t('h.ai.key') }}</p></div>
                </div>
            </section>

            {{-- درگاه پرداخت --}}
            <section class="ad-card st-sec" id="st-payments" aria-labelledby="st-payments-t">
                <h2 class="ad-card__t" id="st-payments-t">{{ $t('sec.payments.t') }}</h2>
                <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.payments.d') }}</p>
                <div class="ad-form-grid">
                    <div class="ad-field"><label class="ad-label" for="st-gw">{{ $t('f.payments.gateway') }}</label>
                        <select id="st-gw" class="field" data-k="payments.gateway">@foreach (AdminSettings::GATEWAYS as $g)<option value="{{ $g }}">{{ $t('gateway.'.$g) }}</option>@endforeach</select>
                        <p class="ad-hint">{{ $t('h.payments.gateway') }}</p></div>
                    <div class="ad-field"><label class="ad-label" for="st-merchant"><span>{{ $t('f.payments.merchant') }}</span><span class="ad-badge" data-st-secret-badge="payments.merchant_set"></span></label>
                        <input id="st-merchant" type="password" class="field" dir="ltr" data-k="payments.merchant" data-secret="payments.merchant_set" autocomplete="new-password" spellcheck="false"><p class="ad-hint">{{ $t('h.payments.merchant') }}</p></div>
                    <div class="ad-field is-wide"><label class="ad-switch"><input type="checkbox" data-k="payments.sandbox"><span class="ad-switch__t"><b>{{ $t('f.payments.sandbox') }}</b><small>{{ $t('h.payments.sandbox') }}</small></span></label></div>
                </div>
            </section>

            {{-- اعلان‌ها --}}
            <section class="ad-card st-sec" id="st-notify" aria-labelledby="st-notify-t">
                <h2 class="ad-card__t" id="st-notify-t">{{ $t('sec.notify.t') }}</h2>
                <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.notify.d') }}</p>
                <div class="ad-form-grid">
                    <div class="ad-field is-wide"><label class="ad-label" for="st-notify-email">{{ $t('f.notify.email') }}</label>
                        <input id="st-notify-email" type="email" class="field" dir="ltr" data-k="notify.email" autocomplete="off"><p class="ad-hint">{{ $t('h.notify.email') }}</p><p class="ad-error" hidden></p></div>
                    @foreach ($switches['notify'] as $k)
                        <div class="ad-field is-wide"><label class="ad-switch"><input type="checkbox" data-k="notify.{{ $k }}"><span class="ad-switch__t"><b>{{ $t('f.notify.'.$k) }}</b><small>{{ $t('h.notify.'.$k) }}</small></span></label></div>
                    @endforeach
                </div>
            </section>

            {{-- دعوت دوستان --}}
            <section class="ad-card st-sec" id="st-referral" aria-labelledby="st-referral-t">
                <h2 class="ad-card__t" id="st-referral-t">{{ $t('sec.referral.t') }}</h2>
                <p class="ad-hint !mt-0.5 mb-4">{{ $t('sec.referral.d') }}</p>
                <div class="ad-form-grid">
                    @foreach ($numbers['referral'] as [$k, $min, $max, $step])
                        <div class="ad-field"><label class="ad-label" for="st-referral-{{ $k }}">{{ $t('f.referral.'.$k) }}</label>
                            <input id="st-referral-{{ $k }}" type="number" class="field" dir="ltr" inputmode="numeric" min="{{ $min }}" max="{{ $max }}" data-k="referral.{{ $k }}" data-min="{{ $min }}" data-max="{{ $max }}" data-step="{{ $step }}">
                            <p class="ad-hint">{{ $t('h.referral.'.$k) }}</p><p class="ad-error" hidden></p></div>
                    @endforeach
                </div>
            </section>
        </form>
    </div>

    <div class="ed-savebar" data-st-bar hidden>
        <span class="text-xs font-bold text-slate-500" data-st-msg role="status" aria-live="polite">{{ $t('bar.unsaved') }}</span>
        <div class="flex items-center gap-2">
            <button type="button" class="btn-ghost !min-h-10 !text-xs" data-st-discard>{{ $t('bar.discard') }}</button>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-st-save><span data-st-save-t>{{ $t('bar.save') }}</span></button>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div id="st-maint" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="st-maint-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="st-maint-t" class="text-lg font-extrabold text-brand-900">{{ $t('maint.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $t('maint.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close data-st-maint-cancel>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-st-maint-ok>{{ $t('maint.go') }}</button>
        </div>
    </div>
</div>
@endpush
