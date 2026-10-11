@php
    $allItems = \App\Support\Nav::items();
    $bottomKeys = \App\Support\Nav::bottomKeys();
    $bottomItems = collect($allItems)->whereIn('key', $bottomKeys)->values();
    $moreItems = collect($allItems)->whereNotIn('key', $bottomKeys)->values();
    $otherLocale = app()->getLocale() === 'fa' ? 'en' : 'fa';
@endphp

{{-- ناوبری پایین (فقط موبایل) --}}
<nav data-focus-hide class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white md:hidden" style="padding-bottom: env(safe-area-inset-bottom)" aria-label="{{ __('ui.menu') }}">
    <div class="grid grid-cols-5">
        @foreach ($bottomItems as $item)
            <a href="{{ $item['href'] }}" class="bottom-nav-item" @if ($item['active']) aria-current="page" @endif>
                <x-icon :name="$item['icon']" class="size-6" />
                <span class="max-w-full truncate px-1">{{ __('ui.nav_short.' . $item['key']) }}</span>
            </a>
        @endforeach

        <button type="button" class="bottom-nav-item" data-dialog-open="#more-dialog" aria-haspopup="dialog">
            <x-icon name="more" class="size-6" />
            <span>{{ __('ui.nav.more') }}</span>
        </button>
    </div>
</nav>

{{-- منوی «بیشتر» (Bottom Sheet) --}}
<div id="more-dialog" class="dialog md:hidden" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-label="{{ __('ui.nav.more') }}">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel">
        <div class="dialog-handle"></div>

        <div class="grid grid-cols-2 gap-2.5">
            @foreach ($moreItems as $item)
                @php $label = __('ui.nav.' . $item['key']); @endphp

                @if ($item['soon'])
                    <div class="flex min-h-14 items-center gap-3 rounded-2xl bg-slate-50 px-3.5 text-sm font-medium text-slate-400" aria-disabled="true">
                        <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                        <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                        <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ __('ui.soon') }}</span>
                    </div>
                @else
                    <a href="{{ $item['href'] }}" class="flex min-h-14 items-center gap-3 rounded-2xl bg-slate-50 px-3.5 text-sm font-semibold text-slate-700 transition active:scale-95" @if ($item['active']) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" class="size-5 shrink-0 text-brand-700" />
                        <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2.5">
            <a href="{{ url('/lang/' . $otherLocale) }}" hreflang="{{ $otherLocale }}" class="btn-ghost">
                <x-icon name="globe" class="size-5" />
                {{ __('ui.switch_to') }}
            </a>
            <form method="POST" action="{{ url('/logout') }}">
                @csrf
                <button type="submit" class="btn-ghost w-full !bg-rose-50 !text-rose-600">
                    <x-icon name="power" class="size-5" />
                    {{ __('ui.logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
