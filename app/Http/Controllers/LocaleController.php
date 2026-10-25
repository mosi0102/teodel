<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * تغییر زبان سایت: /lang/fa  یا  /lang/en
 * زبان در session و کوکی `locale` ذخیره می‌شود و App\Http\Middleware\SetLocale (داخل گروه web) آن را اعمال می‌کند.
 * بعد از تغییر، کاربر به همان صفحه‌ای که بود برمی‌گردد (فقط آدرس‌های همین سایت).
 */
class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        session()->put('locale', $locale);

        $back = url()->previous();
        $own = str_starts_with($back, url('/'));

        // کوکی هم می‌گذاریم چون صفحه‌های خطا (مثلاً ۴۰۴) از میدلور web و session رد نمی‌شوند و فقط کوکی را می‌بینند
        return redirect()->to($own ? $back : url('/'))
            ->withCookie(cookie('locale', $locale, 60 * 24 * 365, '/', null, null, false));
    }
}
