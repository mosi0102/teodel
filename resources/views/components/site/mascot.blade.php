@props(['color' => 'blue', 'pose' => 'plain'])

{{--
    شخصیت‌های تئودل (SVG درون‌خطی، جایگزین موقت تا فایل اصلی طرح).
    رنگ‌ها: blue | yellow | teal | orange — حالت‌ها: plain laptop headphones wave book flag globe thumbs bubbles
    برای استفاده از تصویر اصلی خودتان به <x-site.art> نگاه کنید: فایل را در public/images/landing بگذارید، خودکار جایگزین می‌شود.
--}}
@php
    $c = [
        'blue' => ['#5a5cf0', '#3a3bc4', '#8c8ef7'],
        'yellow' => ['#ffc02e', '#f29a12', '#ffe08a'],
        'teal' => ['#35d6b4', '#16a98c', '#8cf0d9'],
        'orange' => ['#ff9a30', '#ee7412', '#ffc78a'],
    ][$color] ?? ['#5a5cf0', '#3a3bc4', '#8c8ef7'];
    [$base, $dark, $light] = $c;
    $ink = '#26245e';
@endphp
<svg {{ $attributes->merge(['viewBox' => '0 0 120 140', 'fill' => 'none', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    {{-- بازوها (پشت بدن) --}}
    @if ($pose === 'wave')
        <ellipse cx="108" cy="52" rx="7" ry="15" transform="rotate(28 108 52)" fill="{{ $dark }}"/>
        <ellipse cx="13" cy="96" rx="7" ry="13" transform="rotate(8 13 96)" fill="{{ $dark }}"/>
    @elseif ($pose === 'thumbs')
        <ellipse cx="109" cy="62" rx="7" ry="14" transform="rotate(20 109 62)" fill="{{ $dark }}"/>
        <circle cx="113" cy="46" r="7.5" fill="{{ $dark }}"/><rect x="109" y="36" width="5" height="11" rx="2.5" fill="{{ $dark }}"/>
        <ellipse cx="13" cy="96" rx="7" ry="13" transform="rotate(8 13 96)" fill="{{ $dark }}"/>
    @else
        <ellipse cx="12" cy="94" rx="7" ry="14" transform="rotate(10 12 94)" fill="{{ $dark }}"/>
        <ellipse cx="108" cy="94" rx="7" ry="14" transform="rotate(-10 108 94)" fill="{{ $dark }}"/>
    @endif

    {{-- بدن --}}
    <path d="M60 10c27 0 41 23 41 52 0 30 11 49 8 61-3 12-95 12-98 0-3-12 8-31 8-61 0-29 14-52 41-52Z" fill="{{ $base }}"/>
    <path d="M19 108c-4 9-5 13-4 16 3 12 95 12 98 0 1-3 0-7-4-16-10 9-80 9-90 0Z" fill="{{ $dark }}" opacity=".16"/>
    <ellipse cx="42" cy="32" rx="14" ry="9" transform="rotate(-24 42 32)" fill="#fff" opacity=".28"/>

    {{-- صورت --}}
    <circle cx="46" cy="60" r="4.6" fill="{{ $ink }}"/><circle cx="74" cy="60" r="4.6" fill="{{ $ink }}"/>
    <circle cx="47.6" cy="58.4" r="1.5" fill="#fff"/><circle cx="75.6" cy="58.4" r="1.5" fill="#fff"/>
    <path d="M52 70q8 9 16 0" stroke="{{ $ink }}" stroke-width="3" stroke-linecap="round"/>
    <ellipse cx="36" cy="72" rx="6" ry="3.6" fill="#ff8aa8" opacity=".42"/><ellipse cx="84" cy="72" rx="6" ry="3.6" fill="#ff8aa8" opacity=".42"/>

    {{-- وسیله‌ها --}}
    @switch ($pose)
        @case ('headphones')
            <path d="M24 56C22 22 98 22 96 56" stroke="#2c2fb0" stroke-width="6" stroke-linecap="round"/>
            <rect x="16" y="48" width="14" height="26" rx="7" fill="#2c2fb0"/><rect x="90" y="48" width="14" height="26" rx="7" fill="#2c2fb0"/>
            @break
        @case ('laptop')
            <path d="M30 92h60l6 20H24Z" fill="#c9cfee"/>
            <rect x="34" y="74" width="52" height="30" rx="4" fill="#e9ecfb"/><circle cx="60" cy="89" r="4" fill="{{ $base }}" opacity=".6"/>
            @break
        @case ('book')
            <path d="M60 100 28 95v26l32 5Z" fill="#3a46cc"/><path d="M60 100 92 95v26l-32 5Z" fill="#5967f0"/>
            <path d="M36 102l18 3M36 108l18 3M66 105l18-3M66 111l18-3" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".7"/>
            @break
        @case ('flag')
            <path d="M100 92V30" stroke="#2c2fb0" stroke-width="4" stroke-linecap="round"/><path d="M100 30h-1l17 7-17 8Z" fill="#3a46cc"/>
            <rect x="82" y="92" width="26" height="22" rx="4" fill="#3a46cc"/><rect x="88" y="98" width="14" height="4" rx="2" fill="#fff" opacity=".7"/>
            @break
        @case ('globe')
            <circle cx="92" cy="100" r="19" fill="#3a46cc"/><path d="M82 94c4-5 10-4 12 0s-2 7-6 8-7 0-6-8Zm14 10c3 0 6 3 4 7-3 3-7 1-7-3 0-2 1-4 3-4Z" fill="#8c95ff"/>
            @break
        @case ('bubbles')
            <circle cx="14" cy="26" r="11" fill="#3a46cc"/><path d="m11 21 8 5-8 5Z" fill="#fff"/>
            <rect x="92" y="10" width="24" height="18" rx="6" fill="#5967f0"/><circle cx="99" cy="19" r="1.8" fill="#fff"/><circle cx="104" cy="19" r="1.8" fill="#fff"/><circle cx="109" cy="19" r="1.8" fill="#fff"/>
            <rect x="2" y="64" width="20" height="16" rx="6" fill="#8c95ff"/><circle cx="8" cy="72" r="1.6" fill="#fff"/><circle cx="12" cy="72" r="1.6" fill="#fff"/><circle cx="16" cy="72" r="1.6" fill="#fff"/>
            @break
    @endswitch

    {{-- پاها --}}
    <ellipse cx="42" cy="132" rx="14" ry="6" fill="{{ $dark }}" opacity=".35"/><ellipse cx="78" cy="132" rx="14" ry="6" fill="{{ $dark }}" opacity=".35"/>
</svg>
