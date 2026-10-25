{{--
    فونت‌های اصلی از قبل لود می‌شوند تا متن اول با فونت دیگری دیده نشود و بعد عوض نشود، و دانلود فونت هم‌زمان با CSS شروع شود.
    آدرس از manifest می‌آید (Vite::asset) تا دقیقاً همان فایلی باشد که fonts.css لود می‌کند (قبلاً آدرس اشتباه بود و دو بار دانلود می‌شد).
    فارسی: «یکان بخ» Medium (متن معمولی) و Heavy (تیترها و bold)؛ انگلیسی: Funnel Medium و Bold.
    اگر فونت‌های fonts.css را عوض کردید، اسم فایل‌ها را اینجا هم عوض کنید.
--}}
@if (app()->getLocale() === 'fa')
    <link rel="preload" href="{{ Vite::asset('public/fonts/Fa3/Yekan Bakh FA 05 Medium.woff') }}" as="font" type="font/woff" crossorigin>
    <link rel="preload" href="{{ Vite::asset('public/fonts/Fa3/Yekan Bakh FA 07 Heavy.woff') }}" as="font" type="font/woff" crossorigin>
@else
    <link rel="preload" href="{{ Vite::asset('public/fonts/enFont/FunnelDisplay-Medium.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('public/fonts/enFont/FunnelDisplay-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
@endif
