<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use App\Support\Subscription;

/**
 * ⚠️ فقط نمایش فرانت‌اند صفحه‌های عمومی. پلن‌ها از همان Subscription::plans() می‌آیند
 * (یک منبع قیمت برای صفحه‌ی نخست و صفحه‌ی خرید اشتراک).
 */
class SitePageController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'plans' => Subscription::plans(),
            'contacts' => SiteContent::contacts(),
            'loggedIn' => auth()->check(),
        ]);
    }

    public function faq()
    {
        return view('site.faq');
    }

    public function terms()
    {
        return view('site.terms');
    }
}
