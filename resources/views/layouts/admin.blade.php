@php
    $locale = app()->getLocale();
    $dir = $locale === 'fa' ? 'rtl' : 'ltr';
    $adminActive = $adminActive ?? 'dashboard';
    $adminBadges = $adminBadges ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('admin.title')) · {{ __('admin.brand') }}</title>
    @include('partials.font-preload')
    @vite(['resources/css/fonts.css', 'resources/css/app.css', 'resources/css/admin.css', 'resources/css/custom.css', 'resources/js/admin.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-700 antialiased">
    <a href="#ad-main" class="ad-skip">{{ __('admin.skip') }}</a>

    @include('admin.partials.sidebar')
    <div class="ad-scrim" data-ad-close aria-hidden="true"></div>

    <div class="ad-main">
        @include('admin.partials.topbar')
        <main id="ad-main" class="ad-page" tabindex="-1">
            @yield('content')
        </main>
    </div>

    @include('admin.partials.bottom-nav')
    @include('partials.logout-dialog')
    @stack('modals')
    <div class="pf-toast" role="status" aria-live="polite" data-ad-toast style="display:none"></div>
</body>
</html>
