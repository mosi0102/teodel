{{--
    قالب مشترک صفحه‌های خطا. عمداً مستقل است: به دیتابیس، session و کاربر وارد‌شده وابسته نیست، چون خودِ خطا ممکن است از همان‌ها آمده باشد.
    فقط CSS سایت (و فونت) را لود می‌کند. متن‌ها: lang/{fa,en}/errors.php
--}}
@php
    // صفحه‌ی خطا (مخصوصاً ۴۰۴) از میدلور web رد نشده؛ پس زبان را از کوکی `locale` می‌خوانیم (LocaleController می‌گذارد)
    $cookieLocale = request()->cookie('locale');
    if (in_array($cookieLocale, ['fa', 'en'], true)) {
        app()->setLocale($cookieLocale);
    }
    $locale = in_array(app()->getLocale(), ['fa', 'en'], true) ? app()->getLocale() : 'fa';
    $other = $locale === 'fa' ? 'en' : 'fa';
    $code = (string) ($code ?? '500');
    $poses = ['404' => ['yellow', 'globe'], '500' => ['orange', 'laptop'], '403' => ['blue', 'plain'], '419' => ['teal', 'book'], '429' => ['yellow', 'plain'], '503' => ['teal', 'headphones']];
    [$col, $pose] = $poses[$code] ?? ['blue', 'plain'];
    $digits = fn ($s) => $locale === 'fa' ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $reloadable = in_array($code, ['500', '419', '429', '503'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4f5fa">
    <title>{{ __('errors.'.$code.'.t') }} · {{ __('site.brand') }}</title>
    @vite(['resources/css/fonts.css', 'resources/css/app.css', 'resources/css/site.css', 'resources/css/custom.css'])
</head>
<body class="st-body">
    <main class="er" id="main">
        <a href="{{ url('/') }}" class="er__logo" aria-label="{{ __('site.home_link') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ __('site.brand') }}" width="104" height="40" style="height: 40px">
        </a>

        <section class="er__card" aria-labelledby="er-t">
            <div class="er__art">
                <span class="er__code" aria-hidden="true">{{ $digits($code) }}</span>
                <x-site.mascot :color="$col" :pose="$pose" class="er__mascot" />
            </div>
            <h1 id="er-t" class="er__t">{{ __('errors.'.$code.'.t') }}</h1>
            <p class="er__d">{{ __('errors.'.$code.'.d') }}</p>

            <div class="er__acts">
                @if ($reloadable)
                    <a href="{{ url()->current() }}" class="st-cta">{{ __('errors.reload') }}</a>
                    <a href="{{ url('/') }}" class="btn-outline">{{ __('errors.home') }}</a>
                @else
                    <a href="{{ url('/') }}" class="st-cta">{{ __('errors.home') }}</a>
                    <a href="{{ url()->previous() === url()->current() ? url('/') : url()->previous() }}" class="btn-outline">{{ __('errors.back') }}</a>
                @endif
            </div>
            @if (in_array($code, ['500', '403', '419'], true))
                <a href="{{ url('/support') }}" class="st-link">{{ __('errors.support') }}</a>
            @endif
        </section>

        <p class="er__foot">
            <span>{{ __('errors.code') }}: <bdi dir="ltr">{{ $code }}</bdi></span>
            <a href="{{ url('/lang/'.$other) }}" hreflang="{{ $other }}" lang="{{ $other }}">{{ __('errors.other_lang') }}</a>
        </p>
    </main>
</body>
</html>
