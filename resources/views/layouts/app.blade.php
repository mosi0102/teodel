@php
    $locale = app()->getLocale();
    $dir = $locale === 'fa' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2f3a9e">
    <title>@yield('title', config('app.name')) | {{ config('app.name', 'Teodel') }}</title>

    {{-- حالت تمرکز قبل از رندر اعمال می‌شود تا صفحه پرش نکند --}}
    <script>try{if(localStorage.getItem('teodel:focus')==='1')document.documentElement.classList.add('focus-mode')}catch(e){}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#main" class="sr-only z-[60] rounded-lg bg-white px-4 py-2 font-bold text-brand-700 shadow-lift focus:not-sr-only focus:fixed focus:start-4 focus:top-4">
        {{ __('ui.skip') }}
    </a>

    @include('partials.sidebar')

    <div class="app-shell">
        @include('partials.topbar')

        <main id="main" class="px-4 pb-28 pt-4 md:px-6 md:pb-10 md:pt-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @include('partials.bottom-nav')

    {{-- دیالوگ‌های سراسری (گزارش خرابی در هر صفحه در دسترس است) --}}
    @include('partials.report-dialog')
    @stack('modals')
</body>
</html>
