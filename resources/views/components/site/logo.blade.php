{{--
    لوگوی تئودل. اگر فایل اصلی را در public/images/logo.svg (یا .webp/.png) بگذارید همان نشان داده می‌شود؛
    وگرنه نشانهٔ ساده‌ی پیش‌فرض (آیکون + نام).
--}}
@php
    $src = null;
    foreach (['svg', 'webp', 'png'] as $ext) {
        if (is_file(public_path("images/logo.{$ext}"))) { $src = asset("images/logo.{$ext}"); break; }
    }
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ __('site.brand') }}" {{ $attributes->merge(['class' => 'h-11 w-auto']) }} width="628" height="240" decoding="async">
@else
    <span {{ $attributes->merge(['class' => 'st-wordmark']) }}>
        <span class="st-wordmark__ic"><x-icon name="music" class="size-5" /></span>
        <span class="st-wordmark__t"><b>{{ __('site.brand') }}</b><small dir="ltr">{{ __('site.brand_en') }}</small></span>
    </span>
@endif
