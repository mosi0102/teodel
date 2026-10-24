@php
    $locale = app()->getLocale();
    $dir = $locale === 'fa' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title') · {{ __('auth.brand') }}</title>
    @vite(['resources/css/fonts.css', 'resources/css/app.css', 'resources/css/auth.css', 'resources/css/custom.css', 'resources/js/auth.js'])
</head>
<body class="min-h-full bg-white text-slate-700 antialiased">
    @yield('content')
    @stack('modals')
</body>
</html>
