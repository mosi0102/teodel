@php
    // TODO(backend): امتیاز را از کاربر واقعی بدهید
    $topPoints = $user['points'] ?? 0;
    $otherLocale = app()->getLocale() === 'fa' ? 'en' : 'fa';
@endphp

<header data-focus-hide class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 md:px-6 lg:px-8">

    {{-- دکمه‌ی بستن/باز کردن سایدبار (فقط تبلت و دسکتاپ؛ موبایل ناوبری پایین دارد) --}}
    <button type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true"
        data-label-close="{{ __('ui.sidebar_close') }}" data-label-open="{{ __('ui.sidebar_open') }}"
        class="hidden size-11 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 transition duration-200 hover:bg-brand-50 hover:text-brand-700 active:scale-95 md:grid"
        aria-label="{{ __('ui.sidebar_close') }}" title="{{ __('ui.sidebar_close') }}">
        <x-icon name="panel" class="size-5 rtl:-scale-x-100" />
    </button>

    {{-- لوگو: موبایل همیشه | تبلت و دسکتاپ فقط وقتی سایدبار بسته است (وگرنه لوگو داخل سایدبار است) --}}
    <a href="{{ url('/dashboard') }}" class="topbar-logo md:hidden" aria-label="Teodel">
        <img src="{{ asset('images/logo.svg') }}" alt="Teodel" width="110" height="32" class="h-8 w-auto">
    </a>

    <div class="ms-auto flex items-center gap-2 md:gap-3">
        {{-- امتیاز --}}
        <div class="flex h-11 items-center gap-2 rounded-full bg-slate-100 ps-1.5 pe-4">
            <span class="grid size-8 place-items-center rounded-full bg-amber-100 text-amber-600">
                <x-icon name="award" class="size-4" />
            </span>
            <span class="leading-tight">
                <span class="block text-[11px] text-slate-500">{{ __('ui.points') }}</span>
                <span class="block text-sm font-extrabold text-brand-900">{{ num($topPoints) }}</span>
            </span>
        </div>

        {{-- تغییر زبان --}}
        <a href="{{ url('/lang/' . $otherLocale) }}" hreflang="{{ $otherLocale }}" class="chip-btn h-11" aria-label="{{ __('ui.switch_lang') }}">
            <x-icon name="globe" class="size-5" />
            <span class="font-bold">{{ __('ui.switch_to') }}</span>
        </a>

        {{-- خروج (در موبایل داخل منوی «بیشتر» است) --}}
        <form method="POST" action="{{ url('/logout') }}" class="hidden md:block">
            @csrf
            <button type="submit" class="grid size-11 place-items-center rounded-full bg-rose-50 text-rose-500 transition duration-200 hover:bg-rose-100 active:scale-95" aria-label="{{ __('ui.logout') }}" title="{{ __('ui.logout') }}">
                <x-icon name="power" class="size-5" />
            </button>
        </form>
    </div>
</header>
