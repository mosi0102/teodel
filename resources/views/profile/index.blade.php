@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/profile.js')
@endpush

@section('title', __('profile.title'))

{{--
    پروفایل: مشاهده + تکمیل + ویرایش اطلاعات، و انتخاب آواتار از بین آواتارهای پیشنهادیِ سایت (آپلود آواتار شخصی ممکن نیست).
    رفتار: resources/js/modules/profile.js — داده: app/Support/Profile.php ($profile, $avatars از کنترلر).
    ذخیره: رویداد `profile:submit` یا آدرس data-endpoint روی فرم (README).
--}}
@php
    $skills = ['listening', 'reading', 'writing', 'speaking'];
    $cur = collect($avatars)->firstWhere('id', $profile['avatar']) ?? $avatars[0] ?? null;
    $curSrc = $cur['src'] ?? '';
@endphp
@section('content')
<div class="page-wrap" data-pf
    data-user='@json($profile, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-months='@json(__('profile.months'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-t-complete="{{ __('profile.complete') }}" data-t-complete-done="{{ __('profile.complete_done') }}"
    data-t-saved="{{ __('profile.saved') }}" data-t-copied="{{ __('profile.side.copied') }}" data-t-copy-failed="{{ __('profile.side.copy_failed') }}"
    data-t-unsaved="{{ __('profile.unsaved') }}" data-t-failed="{{ __('profile.errors.failed') }}"
    data-t-show-pass="{{ __('profile.show_password') }}" data-t-hide-pass="{{ __('profile.hide_password') }}"
    data-t-copy="{{ __('profile.side.copy') }}"
    data-e-required="{{ __('profile.errors.required') }}" data-e-mobile="{{ __('profile.errors.mobile') }}" data-e-email="{{ __('profile.errors.email') }}"
    data-e-birthday="{{ __('profile.errors.birthday') }}" data-e-password="{{ __('profile.errors.password') }}" data-e-confirm="{{ __('profile.errors.confirm') }}">

    {{-- عنوان + ابزارها --}}
    <header class="mb-5 flex flex-col gap-3 md:mb-6 md:flex-row md:items-start md:justify-between md:gap-6">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-extrabold text-brand-900 md:text-2xl">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </span>
                {{ __('profile.title') }}
            </h1>
            <p data-focus-hide class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">{{ __('profile.subtitle') }}</p>
        </div>

        <x-page-tools class="shrink-0 flex-wrap" />
    </header>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-6">

        {{-- کارت کاربر (موبایل: اول) — دسکتاپ: سمت «پایان» --}}
        <aside class="pf-side order-1 lg:order-2 lg:row-span-2">
            <div class="pf-side__top">
                <button type="button" class="pf-avatar" data-pf-avatar-open aria-label="{{ __('profile.side.change_avatar') }}" title="{{ __('profile.side.change_avatar') }}">
                    <img src="{{ $curSrc }}" alt="" width="96" height="96" data-pf-avatar-img data-user-avatar>
                    <span class="pf-avatar__edit" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    </span>
                </button>

                <h2 class="mt-3 text-lg font-extrabold text-white" data-pf-name>{{ $profile['first_name'] }} {{ $profile['last_name'] }}</h2>
                <p class="mt-1 text-sm font-medium text-white/80">
                    {{ __('profile.side.days_left') }}: <b class="font-extrabold text-white" dir="ltr">{{ num($profile['days_left']) }}</b>
                </p>
                <a href="{{ url('/subscription') }}" class="pf-buy">{{ __('profile.side.buy') }}</a>

                <div class="pf-code">
                    <div class="min-w-0 flex-1 text-center">
                        <p class="text-[11px] font-bold text-white/70">{{ __('profile.side.code') }}</p>
                        <p class="mt-0.5 select-all truncate text-lg font-extrabold tracking-wider text-white" dir="ltr" data-pf-code>{{ $profile['referral_code'] }}</p>
                    </div>
                    <span class="w-px self-stretch bg-white/25" aria-hidden="true"></span>
                    <button type="button" class="pf-copy" data-pf-copy>
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                        <span data-pf-copy-label>{{ __('profile.side.copy') }}</span>
                    </button>
                </div>
            </div>

            <ul class="pf-skills" data-focus-hide>
                @foreach ($skills as $sk)
                    <li class="pf-skill">
                        <b dir="ltr">{{ strtoupper($profile['skills'][$sk] ?? '—') }}</b>
                        <span>{{ __('profile.skills.' . $sk) }}</span>
                    </li>
                @endforeach
            </ul>
        </aside>

        {{-- فرم --}}
        <form class="card order-2 p-4 md:p-6 lg:order-1" id="pf-form" data-pf-form novalidate autocomplete="on"
            @isset($endpoint) data-endpoint="{{ $endpoint }}" @endisset>
            @csrf
            <input type="hidden" name="avatar" value="{{ $profile['avatar'] }}" data-pf-avatar-input>
            <input type="hidden" name="birthday" value="{{ $profile['birthday'] }}" data-pf-birthday>

            {{-- میزان تکمیل‌بودن (زنده) --}}
            <div class="pf-meter" data-pf-meter>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-bold text-brand-900" data-pf-meter-text aria-live="polite"></p>
                </div>
                <div class="pf-meter__bar"><i data-pf-meter-bar></i></div>
            </div>

            <div class="mt-5 grid gap-x-4 gap-y-5 sm:grid-cols-2">
                <div>
                    <label class="pf-label" for="pf-first">{{ __('profile.fields.first_name') }} <abbr class="pf-req" title="*">*</abbr></label>
                    <input class="field min-h-12" id="pf-first" name="first_name" type="text" required autocomplete="given-name" value="{{ $profile['first_name'] }}" placeholder="{{ __('profile.placeholders.first_name') }}" data-pf-f>
                    <p class="pf-err" data-pf-err="first_name" role="alert"></p>
                </div>
                <div>
                    <label class="pf-label" for="pf-last">{{ __('profile.fields.last_name') }} <abbr class="pf-req" title="*">*</abbr></label>
                    <input class="field min-h-12" id="pf-last" name="last_name" type="text" required autocomplete="family-name" value="{{ $profile['last_name'] }}" placeholder="{{ __('profile.placeholders.last_name') }}" data-pf-f>
                    <p class="pf-err" data-pf-err="last_name" role="alert"></p>
                </div>

                <div>
                    <label class="pf-label" for="pf-mobile">{{ __('profile.fields.mobile') }} <abbr class="pf-req" title="*">*</abbr></label>
                    <input class="field min-h-12 rtl:text-right" id="pf-mobile" name="mobile" type="tel" inputmode="numeric" required autocomplete="tel" dir="ltr" maxlength="11" value="{{ $profile['mobile'] }}" placeholder="{{ __('profile.placeholders.mobile') }}" data-pf-f>
                    <p class="pf-err" data-pf-err="mobile" role="alert"></p>
                </div>
                <div>
                    <label class="pf-label" for="pf-email">{{ __('profile.fields.email') }}</label>
                    <input class="field min-h-12 rtl:text-right" id="pf-email" name="email" type="email" inputmode="email" autocomplete="email" dir="ltr" value="{{ $profile['email'] }}" placeholder="{{ __('profile.placeholders.email') }}" data-pf-f>
                    <p class="pf-err" data-pf-err="email" role="alert"></p>
                </div>

                <div>
                    <label class="pf-label" for="pf-gender">{{ __('profile.fields.gender') }}</label>
                    <select class="field min-h-12" id="pf-gender" name="gender" data-pf-f>
                        <option value="">{{ __('profile.placeholders.gender') }}</option>
                        @foreach (['male', 'female', 'other'] as $g)
                            <option value="{{ $g }}" @selected(($profile['gender'] ?? null) === $g)>{{ __('profile.gender.' . $g) }}</option>
                        @endforeach
                    </select>
                    <p class="pf-err" data-pf-err="gender" role="alert"></p>
                </div>
                <div>
                    <span class="pf-label" id="pf-bd-label">{{ __('profile.fields.birthday') }}</span>
                    {{-- روز / ماه / سال؛ در فارسی شمسی و در انگلیسی میلادی (ساخته‌شده در profile.js)، مقدار میلادی در input پنهانِ birthday --}}
                    <div class="grid grid-cols-[1fr_1.6fr_1.2fr] gap-2" role="group" aria-labelledby="pf-bd-label" data-pf-date
                        data-ph-day="{{ __('profile.placeholders.day') }}" data-ph-month="{{ __('profile.placeholders.month') }}" data-ph-year="{{ __('profile.placeholders.year') }}">
                        <select class="field min-h-12 !px-3" data-pf-d="d" aria-label="{{ __('profile.placeholders.day') }}"></select>
                        <select class="field min-h-12 !px-3" data-pf-d="m" aria-label="{{ __('profile.placeholders.month') }}"></select>
                        <select class="field min-h-12 !px-3" data-pf-d="y" aria-label="{{ __('profile.placeholders.year') }}"></select>
                    </div>
                    <p class="pf-err" data-pf-err="birthday" role="alert"></p>
                </div>

                <div>
                    <label class="pf-label" for="pf-pass">{{ $profile['has_password'] ? __('profile.fields.password_new') : __('profile.fields.password') }} @unless ($profile['has_password'])<abbr class="pf-req" title="*">*</abbr>@endunless</label>
                    <div class="relative">
                        <input class="field min-h-12 pe-12" id="pf-pass" name="password" type="password" autocomplete="new-password" minlength="8" @unless ($profile['has_password']) required @endunless
                            placeholder="{{ $profile['has_password'] ? __('profile.placeholders.password_new') : __('profile.placeholders.password') }}" data-pf-f data-pf-pass>
                        <button type="button" class="pf-eye" data-pf-eye="#pf-pass" aria-label="{{ __('profile.show_password') }}" aria-pressed="false">
                            <x-icon name="eye" class="size-5 pf-eye__on" /><x-icon name="eye-off" class="size-5 pf-eye__off" />
                        </button>
                    </div>
                    <p class="pf-err" data-pf-err="password" role="alert"></p>
                    @if ($profile['has_password'])
                        <p class="mt-1.5 text-xs text-slate-400">{{ __('profile.hint.password_keep') }}</p>
                    @endif
                </div>
                <div>
                    <label class="pf-label" for="pf-pass2">{{ __('profile.fields.password_confirm') }} @unless ($profile['has_password'])<abbr class="pf-req" title="*">*</abbr>@endunless</label>
                    <div class="relative">
                        <input class="field min-h-12 pe-12" id="pf-pass2" name="password_confirmation" type="password" autocomplete="new-password" @unless ($profile['has_password']) required @endunless
                            placeholder="{{ __('profile.placeholders.password_confirm') }}" data-pf-f data-pf-pass>
                        <button type="button" class="pf-eye" data-pf-eye="#pf-pass2" aria-label="{{ __('profile.show_password') }}" aria-pressed="false">
                            <x-icon name="eye" class="size-5 pf-eye__on" /><x-icon name="eye-off" class="size-5 pf-eye__off" />
                        </button>
                    </div>
                    <p class="pf-err" data-pf-err="password_confirmation" role="alert"></p>
                </div>
            </div>

            <p class="pf-err mt-4" data-pf-err="_form" role="alert"></p>

            <div class="mt-6">
                <button type="submit" class="btn-primary w-full sm:w-auto sm:min-w-44" data-pf-submit>{{ __('profile.submit') }}</button>
            </div>
        </form>

        {{-- بنر معرفی --}}
        <section class="pf-banner order-3 lg:order-3" data-focus-hide>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-extrabold text-brand-900 md:text-xl">{{ __('profile.banner.title') }}</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('profile.banner.text') }}</p>
            </div>
            <button type="button" class="pf-ticket" data-pf-copy-ticket title="{{ __('profile.side.copy') }}">{{ __('profile.banner.ticket') }}</button>
        </section>
    </div>

    <div class="pf-toast" data-pf-toast role="status" aria-live="polite" hidden></div>
</div>
@endsection

@push('modals')
{{-- انتخاب آواتار: فقط از لیست پیشنهادیِ سایت؛ آواتار قفل‌دار بعداً از «دستاوردها» آزاد می‌شود (فاز بعد) --}}
<div id="avatar-dialog" class="dialog" data-dialog data-pf-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pf-av-title">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-[34rem]">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="pf-av-title" class="text-lg font-extrabold text-brand-900">{{ __('profile.avatar.title') }}</h2>
        <p class="mt-3 text-sm leading-7 text-slate-600">
            {{ __('profile.avatar.text') }}<br>
            {{ __('profile.avatar.more_before') }}
            <a href="{{ url('/achievements') }}" class="font-bold text-brand-700 underline-offset-4 hover:underline">{{ __('profile.avatar.more_link') }}</a>
            {{ __('profile.avatar.more_after') }}
        </p>

        <div class="pf-avatars" role="radiogroup" aria-labelledby="pf-av-title" data-pf-avatars>
            @foreach ($avatars as $av)
                <label class="pf-av" @if ($av['locked']) title="{{ __('profile.avatar.locked') }}" @endif>
                    <input type="radio" name="pf-avatar" value="{{ $av['id'] }}" data-src="{{ $av['src'] }}" class="peer sr-only" @checked($av['id'] === $profile['avatar']) @disabled($av['locked'])
                        aria-label="{{ __('profile.avatar.option', ['n' => num($loop->iteration)]) }}">
                    <img src="{{ $av['src'] }}" alt="" width="96" height="96" loading="lazy" class="pf-av__img">
                    @if ($av['locked'])
                        <span class="pf-av__lock" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>
                    @endif
                </label>
            @endforeach
        </div>

        <button type="button" class="btn-primary mt-6 w-full sm:w-auto sm:min-w-40" data-pf-avatar-confirm>{{ __('profile.avatar.confirm') }}</button>
    </div>
</div>
@endpush
