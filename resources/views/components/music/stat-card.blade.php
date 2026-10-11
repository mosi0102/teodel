@props(['label', 'value', 'icon', 'tone' => 'brand', 'href' => null, 'linkText' => null])

@php
    $tones = [
        'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'brand' => 'bg-brand-50 text-brand-700',
        'violet' => 'bg-violet-50 text-violet-600',
    ];
@endphp

<div {{ $attributes->class('card group flex items-center gap-3 p-3.5 transition-[transform,box-shadow] duration-300 hover:-translate-y-0.5 hover:shadow-lift sm:gap-4 sm:p-4') }}>
    <span class="{{ $tones[$tone] ?? $tones['brand'] }} grid size-11 shrink-0 place-items-center rounded-2xl transition-transform duration-300 group-hover:scale-110 sm:size-12">
        <x-icon :name="$icon" class="size-6" />
    </span>

    <div class="min-w-0">
        <p class="truncate text-xs text-slate-500 sm:text-sm">{{ $label }}</p>
        <p class="text-xl font-extrabold leading-tight text-brand-900 sm:text-2xl">{{ $value }}</p>

        @if ($href)
            <a href="{{ $href }}" class="group/link mt-0.5 inline-flex min-h-6 items-center gap-1 text-xs font-bold text-brand-700">
                {{ $linkText }}
                <x-icon name="arrow-end" class="size-3.5 transition-transform duration-200 rtl:-scale-x-100 group-hover/link:translate-x-0.5 rtl:group-hover/link:-translate-x-0.5" />
            </a>
        @endif
    </div>
</div>
