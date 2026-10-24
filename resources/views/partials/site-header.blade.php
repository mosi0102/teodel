{{--
    هدر صفحه‌های عمومی: کارت شناور (دسکتاپ: لینک‌ها در خود هدر | موبایل: دکمهٔ منو ← کشوی پایین).
    لینک‌های داخل صفحه (#about …) در صفحه‌های دیگر به صفحهٔ نخست می‌روند. رفتار: resources/js/site.js
--}}
@php
    $home = url('/');
    $onHome = request()->path() === '/';
    $a = fn (string $h) => ($onHome ? '' : $home).$h;
    $other = app()->getLocale() === 'fa' ? 'en' : 'fa';
    $loggedIn = auth()->check();
    $links = [
        ['id' => 'home', 'href' => $home, 'on' => $onHome],
        ['id' => 'about', 'href' => $a('#about')],
        ['id' => 'services', 'href' => $a('#features')],
        ['id' => 'pricing', 'href' => $a('#pricing')],
        ['id' => 'faq', 'href' => url('/faq'), 'on' => request()->is('faq')],
        ['id' => 'complaint', 'href' => url('/support')],
        ['id' => 'contact', 'href' => '#contact'],
    ];
@endphp
<header class="st-head" data-st-head>
    <div class="st-head__bar" style="padding: 20px">
        <a href="{{ $home }}" class="st-logo" aria-label="{{ __('site.home_link') }}">
            <img src="{{asset('images/logo.png')}}" style="height: 40px">
        </a>

        <nav class="st-nav" aria-label="{{ __('site.menu') }}">
            @foreach ($links as $l)
                <a href="{{ $l['href'] }}" class="st-nav__a" data-st-nav="{{ $l['id'] }}"
                   @if (! empty($l['on'])) aria-current="page" @endif>{{ __('site.nav.'.$l['id']) }}</a>
            @endforeach
        </nav>

        <div class="st-head__acts">
            <a href="{{ url('/lang/'.$other) }}" hreflang="{{ $other }}" lang="{{ $other }}" class="st-lang">
                <x-icon name="globe" class="size-4"/>
                <span>{{ __('site.switch_code') }}</span>
            </a>
            @if ($loggedIn)
                <a href="{{ url('/dashboard') }}" class="st-cta st-cta--head">{{ __('site.dashboard') }}</a>
            @else
                <a href="{{ url('/login') }}" class="st-login">{{ __('site.login') }}</a>
                <a href="{{ url('/register') }}" class="st-cta st-cta--head">{{ __('site.register') }}</a>
            @endif
            <button type="button" class="st-burger" data-st-open aria-label="{{ __('site.open_menu') }}"
                    aria-expanded="false" aria-controls="st-sheet">
                <x-icon name="menu" class="size-5"/>
            </button>
        </div>
    </div>
</header>

{{-- منوی موبایل --}}
<div id="st-sheet" class="st-sheet" data-st-sheet hidden>
    <div class="st-sheet__bg" data-st-close></div>
    <div class="st-sheet__panel" role="dialog" aria-modal="true" aria-label="{{ __('site.menu') }}">
        <div class="st-sheet__top">
            <x-site.logo class="h-10 w-auto"/>
            <button type="button" class="st-burger" data-st-close aria-label="{{ __('site.close') }}">
                <x-icon name="x" class="size-5"/>
            </button>
        </div>
        <nav class="st-sheet__nav">
            @foreach ($links as $l)
                <a href="{{ $l['href'] }}" data-st-close
                   @if (! empty($l['on'])) aria-current="page" @endif>{{ __('site.nav.'.$l['id']) }}</a>
            @endforeach
        </nav>
        <div class="st-sheet__acts">
            @if ($loggedIn)
                <a href="{{ url('/dashboard') }}" class="st-cta w-full">{{ __('site.dashboard') }}</a>
            @else
                <a href="{{ url('/register') }}" class="st-cta w-full">{{ __('site.register') }}</a>
                <a href="{{ url('/login') }}" class="btn-outline w-full">{{ __('site.login') }}</a>
            @endif
            <a href="{{ url('/lang/'.$other) }}" hreflang="{{ $other }}" lang="{{ $other }}"
               class="st-lang st-lang--wide">
                <x-icon name="globe" class="size-4"/>{{ __('site.switch_to') }}</a>
        </div>
    </div>
</div>
