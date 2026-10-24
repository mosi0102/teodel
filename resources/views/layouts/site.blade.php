@php
    $locale = app()->getLocale();
    $dir = $locale === 'fa' ? 'rtl' : 'ltr';
    $other = $locale === 'fa' ? 'en' : 'fa';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f4f5fa">
    <title>@yield('title', __('site.meta.home_t'))</title>
    <meta name="description" content="@yield('description', __('site.meta.home_d'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('site.brand') }}">
    <meta property="og:title" content="@yield('title', __('site.meta.home_t'))">
    <meta property="og:description" content="@yield('description', __('site.meta.home_d'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <script>document.documentElement.classList.add('st-js')</script>
    @stack('head')
    @vite(['resources/css/fonts.css', 'resources/css/app.css', 'resources/css/site.css', 'resources/css/custom.css', 'resources/js/site.js'])
</head>
<body class="st-body" data-st-page="@yield('page', 'inner')">
    <a href="#main" class="st-skip">{{ __('site.skip') }}</a>

    @include('partials.site-header')

    <main id="main" class="st-main" tabindex="-1">
        @yield('content')
    </main>

    @include('partials.site-footer')
    @stack('modals')
</body>
</html>
