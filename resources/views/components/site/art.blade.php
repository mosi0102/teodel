@props(['name', 'color' => 'blue', 'pose' => 'plain', 'alt' => '', 'eager' => false, 'w' => null, 'h' => null])

{{--
    تصویر شخصیت/تصویرسازی: اگر فایل اصلی را در public/images/landing بگذارید (webp، png یا svg) همان نشان داده می‌شود،
    وگرنه شخصیت SVG پیش‌فرض. نام‌ها: hero، feature-1..4، step-1..4  (مثلاً public/images/landing/hero.webp)
    برای جلوگیری از پرش چیدمان، w و h (پیکسل واقعی تصویر) را بدهید.
--}}
@php
    $src = null;
    foreach (['webp', 'png', 'svg'] as $ext) {
        if (is_file(public_path("images/landing/{$name}.{$ext}"))) {
            $src = asset("images/landing/{$name}.{$ext}");
            break;
        }
    }
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" @if ($w) width="{{ $w }}" @endif @if ($h) height="{{ $h }}" @endif
        decoding="async" @if ($eager) fetchpriority="high" @else loading="lazy" @endif {{ $attributes }}>
@else
    <x-site.mascot :color="$color" :pose="$pose" {{ $attributes }} @if ($alt) role="img" aria-label="{{ $alt }}" @endif />
@endif
