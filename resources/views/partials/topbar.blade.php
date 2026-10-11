@php
    // TODO(backend): امتیاز را از کاربر واقعی بدهید
    $topPoints = $user['points'] ?? 0;
    $otherLocale = app()->getLocale() === 'fa' ? 'en' : 'fa';
@endphp

<header data-focus-hide class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 md:px-6 lg:px-8">

    {{-- موبایل: لوگو (در دسکتاپ لوگو داخل سایدبار است) --}}
    <a href="{{ url('/dashboard') }}" class="md:hidden" aria-label="Teodel">
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
