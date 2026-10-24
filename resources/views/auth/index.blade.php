@extends('layouts.guest')

@section('title', __('auth.title_'.$mode))

{{--
    ورود و ثبت‌نام: یک صفحه‌ی چندمرحله‌ای (شماره ← کد یا رمز ← نام و رمز برای شماره‌ی جدید).
    موبایل: صفحه‌ی اپ‌مانند با سربرگ رنگی؛ دسکتاپ: دو ستون (فرم + معرفی).
    رفتار و قرارداد بک‌اند: resources/js/auth.js و app/Support/AuthFlow.php
--}}
@php
    use App\Support\AuthFlow;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $d, string $c = 'size-5') => '<svg viewBox="0 0 24 24" class="'.$c.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $len = AuthFlow::CODE_LENGTH;
    $icons = [
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye_off' => '<path d="M17.9 17.9A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18 18 0 0 1 4.1-4.9"/><path d="M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-2.2 3.2"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><path d="m2 2 20 20"/>',
        'phone' => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
        'note' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
    ];
    $terms = str_replace(':link', '<a href="'.url('/terms').'" class="au-a" target="_blank" rel="noopener">'.e(__('auth.profile.terms_link')).'</a>', e(__('auth.profile.terms')));
@endphp

@section('content')
<div class="au" data-auth data-mode="{{ $mode }}"
    data-t='@json(__('auth'), $flags)' data-code-length="{{ $len }}" data-resend="{{ AuthFlow::RESEND_SECONDS }}"
    data-redirect="{{ $redirect ?? '/dashboard' }}" data-ref="{{ $ref ?? '' }}"
    @foreach (['send', 'verify', 'password', 'register'] as $u) @isset($urls[$u]) data-{{ $u }}-url="{{ $urls[$u] }}" @endisset @endforeach>

    <a href="#au-card" class="au-skip">{{ __('auth.skip') }}</a>

    {{-- معرفی (دسکتاپ: ستون کنار؛ موبایل: سربرگ رنگی فشرده) --}}
    <aside class="au-aside">
        <div class="au-aside__in">
            <a href="{{ url('/') }}" class="au-brand" aria-label="{{ __('auth.home') }}">
                <img src="{{asset('images/logolight.png')}}" style="height: 80px" />
            </a>
            <div class="au-aside__txt">
                <h2 class="au-aside__t">{{ __('auth.aside.title') }}</h2>
                <p class="au-aside__l">{{ __('auth.aside.lead') }}</p>
                <ul class="au-points">
                    @foreach (__('auth.aside.points') as $p)<li>{!! $ic($icons['check'], 'size-4') !!}<span>{{ $p }}</span></li>@endforeach
                </ul>
            </div>
            <div class="au-eq" aria-hidden="true">@for ($i = 0; $i < 14; $i++)<i style="--i: {{ $i }}"></i>@endfor</div>
        </div>
    </aside>

    <main class="au-main">
        <div class="au-card" id="au-card" tabindex="-1">
            <div class="au-tabs" role="tablist" aria-label="{{ __('auth.title_login') }} / {{ __('auth.title_register') }}" data-au-tabs>
                @foreach (['login', 'register'] as $m)
                    <button type="button" role="tab" id="au-tab-{{ $m }}" data-v="{{ $m }}" aria-selected="{{ $m === $mode ? 'true' : 'false' }}">{{ __('auth.mode.'.$m) }}</button>
                @endforeach
            </div>

            <ol class="au-dots" data-au-dots aria-hidden="true">
                @foreach (['phone', 'code', 'profile'] as $s)<li data-s="{{ $s }}"><i></i><span>{{ __('auth.steps.'.$s) }}</span></li>@endforeach
            </ol>

            <p class="au-demo" data-au-demo hidden>{{ __('auth.demo') }}</p>
            <p class="au-notice" role="status" aria-live="polite" data-au-notice hidden></p>

            {{-- ۱) شماره --}}
            <section class="au-step" data-au-step="phone" aria-labelledby="au-h-phone">
                <h1 class="au-h" id="au-h-phone" data-au-title>{{ __('auth.phone.title_'.$mode) }}</h1>
                <p class="au-l">{{ __('auth.phone.lead') }}</p>
                <form novalidate onsubmit="return false" data-au-form="phone">
                    <div class="au-field">
                        <label class="au-label" for="au-phone">{{ __('auth.phone.label') }}</label>
                        <div class="au-input">
                            {!! $ic($icons['phone'], 'size-5') !!}
                            <input id="au-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="{{ __('auth.phone.ph') }}" maxlength="20" data-au-phone aria-describedby="au-phone-err">
                        </div>
                        <p class="au-err" id="au-phone-err" role="alert" hidden></p>
                    </div>
                    <button type="submit" class="btn-primary au-submit" data-au-submit><span class="au-spin" aria-hidden="true"></span><span data-au-submit-t>{{ __('auth.phone.go') }}</span></button>
                    <button type="button" class="au-link au-link--c" data-au-go="password">{{ __('auth.phone.use_password') }}</button>
                </form>
            </section>

            {{-- ۲) کد پیامکی --}}
            <section class="au-step" data-au-step="code" aria-labelledby="au-h-code" hidden>
                <h1 class="au-h" id="au-h-code">{{ __('auth.code.title') }}</h1>
                <p class="au-l" data-au-code-lead></p>
                <form novalidate onsubmit="return false" data-au-form="code">
                    <div class="au-field">
                        <span class="sr-only" id="au-code-l">{{ __('auth.code.label') }}</span>
                        <div class="au-code" dir="ltr" role="group" aria-labelledby="au-code-l" data-au-code>
                            @for ($i = 1; $i <= $len; $i++)
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="{{ __('auth.code.digit', ['n' => $i]) }}" data-au-digit>
                            @endfor
                        </div>
                        <p class="au-err" role="alert" hidden data-au-code-err></p>
                    </div>
                    <p class="au-notice is-warn" hidden data-au-notsent>{{ __('auth.code.not_sent') }}</p>
                    <button type="submit" class="btn-primary au-submit" data-au-submit><span class="au-spin" aria-hidden="true"></span><span data-au-submit-t>{{ __('auth.code.go') }}</span></button>
                    <div class="au-row">
                        <button type="button" class="au-link" data-au-resend disabled></button>
                        <button type="button" class="au-link" data-au-edit>{{ __('auth.code.edit') }}</button>
                    </div>
                    <div class="au-alt">
                        <span>{{ __('auth.code.missing') }}</span>
                        <button type="button" class="au-link" data-au-go="password" data-au-pw-link>{{ __('auth.code.use_password') }}</button>
                    </div>
                </form>
            </section>

            {{-- ۳) رمز عبور --}}
            <section class="au-step" data-au-step="password" aria-labelledby="au-h-password" hidden>
                <h1 class="au-h" id="au-h-password">{{ __('auth.password.title') }}</h1>
                <p class="au-l" data-au-pw-lead></p>
                <form novalidate onsubmit="return false" data-au-form="password">
                    <div class="au-field" data-au-phone-wrap hidden>
                        <label class="au-label" for="au-phone2">{{ __('auth.phone.label') }}</label>
                        <div class="au-input">{!! $ic($icons['phone'], 'size-5') !!}<input id="au-phone2" type="tel" inputmode="tel" autocomplete="username" dir="ltr" placeholder="{{ __('auth.phone.ph') }}" maxlength="20" data-au-phone2></div>
                        <p class="au-err" role="alert" hidden data-au-phone2-err></p>
                    </div>
                    <div class="au-field">
                        <label class="au-label" for="au-pw">{{ __('auth.password.label') }}</label>
                        <div class="au-input">
                            <input id="au-pw" name="password" type="password" autocomplete="current-password" dir="ltr" data-au-pw>
                            <button type="button" class="au-eye" data-au-eye aria-pressed="false" aria-label="{{ __('auth.password.show') }}" data-show="{{ __('auth.password.show') }}" data-hide="{{ __('auth.password.hide') }}">
                                <span data-e="on">{!! $ic($icons['eye'], 'size-5') !!}</span><span data-e="off" hidden>{!! $ic($icons['eye_off'], 'size-5') !!}</span>
                            </button>
                        </div>
                        <p class="au-err" role="alert" hidden data-au-pw-err></p>
                    </div>
                    <div class="au-row">
                        <label class="au-check"><input type="checkbox" data-au-remember checked><span>{{ __('auth.password.remember') }}</span></label>
                        <button type="button" class="au-link" data-au-forgot>{{ __('auth.password.forgot') }}</button>
                    </div>
                    <p class="au-notice is-info" hidden data-au-forgot-hint>{{ __('auth.password.forgot_hint') }}</p>
                    <button type="submit" class="btn-primary au-submit" data-au-submit><span class="au-spin" aria-hidden="true"></span><span data-au-submit-t>{{ __('auth.password.go') }}</span></button>
                    <button type="button" class="au-link au-link--c" data-au-go="code">{{ __('auth.password.use_code') }}</button>
                </form>
            </section>

            {{-- ۴) تکمیل ثبت‌نام (فقط شماره‌ی جدید) --}}
            <section class="au-step" data-au-step="profile" aria-labelledby="au-h-profile" hidden>
                <h1 class="au-h" id="au-h-profile">{{ __('auth.profile.title') }}</h1>
                <p class="au-l" data-au-profile-lead></p>
                <form novalidate onsubmit="return false" data-au-form="profile">
                    <div class="au-grid">
                        <div class="au-field">
                            <label class="au-label" for="au-fn">{{ __('auth.profile.first_name') }}</label>
                            <div class="au-input"><input id="au-fn" name="first_name" autocomplete="given-name" maxlength="40" data-au-fn></div>
                            <p class="au-err" role="alert" hidden data-au-fn-err></p>
                        </div>
                        <div class="au-field">
                            <label class="au-label" for="au-ln">{{ __('auth.profile.last_name') }}</label>
                            <div class="au-input"><input id="au-ln" name="last_name" autocomplete="family-name" maxlength="60" data-au-ln></div>
                            <p class="au-err" role="alert" hidden data-au-ln-err></p>
                        </div>
                    </div>
                    <div class="au-field">
                        <label class="au-label" for="au-npw">{{ __('auth.profile.password') }}</label>
                        <div class="au-input">
                            <input id="au-npw" name="new-password" type="password" autocomplete="new-password" dir="ltr" data-au-npw aria-describedby="au-npw-h">
                            <button type="button" class="au-eye" data-au-eye aria-pressed="false" aria-label="{{ __('auth.password.show') }}" data-show="{{ __('auth.password.show') }}" data-hide="{{ __('auth.password.hide') }}">
                                <span data-e="on">{!! $ic($icons['eye'], 'size-5') !!}</span><span data-e="off" hidden>{!! $ic($icons['eye_off'], 'size-5') !!}</span>
                            </button>
                        </div>
                        <div class="au-meter" data-au-meter data-level="0" aria-hidden="true"><i></i><i></i><i></i><i></i><b data-au-meter-t></b></div>
                        <p class="au-hint" id="au-npw-h">{{ __('auth.profile.password_hint') }}</p>
                        <p class="au-err" role="alert" hidden data-au-npw-err></p>
                    </div>
                    <div class="au-field">
                        <button type="button" class="au-link" data-au-ref-open aria-expanded="false">{{ __('auth.profile.ref') }}</button>
                        <div data-au-ref-box hidden>
                            <label class="au-label" for="au-ref">{{ __('auth.profile.ref_label') }}</label>
                            <div class="au-input"><input id="au-ref" name="ref" dir="ltr" autocomplete="off" maxlength="32" data-au-ref value="{{ $ref ?? '' }}"></div>
                            <p class="au-hint">{{ __('auth.profile.ref_hint') }}</p>
                        </div>
                    </div>
                    <div class="au-field">
                        <label class="au-check"><input type="checkbox" data-au-terms><span>{!! $terms !!}</span></label>
                        <p class="au-err" role="alert" hidden data-au-terms-err></p>
                    </div>
                    <p class="au-err" role="alert" hidden data-au-form-err></p>
                    <button type="submit" class="btn-primary au-submit" data-au-submit><span class="au-spin" aria-hidden="true"></span><span data-au-submit-t>{{ __('auth.profile.go') }}</span></button>
                </form>
            </section>

            {{-- پایان --}}
            <section class="au-step au-done" data-au-step="done" aria-live="polite" hidden>
                <span class="au-done__ic">{!! $ic($icons['check'], 'size-8') !!}</span>
                <h1 class="au-h">{{ __('auth.done.title') }}</h1>
                <p class="au-l" data-au-done-l>{{ __('auth.done.lead') }}</p>
            </section>
        </div>
    </main>
</div>
@endsection
