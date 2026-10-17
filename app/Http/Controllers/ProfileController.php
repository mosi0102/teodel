<?php

namespace App\Http\Controllers;

use App\Support\Profile;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: Profile::demo() را با داده‌ی واقعی عوض کنید (قرارداد: app/Support/Profile.php).
 * ذخیره‌ی فرم: یا به رویداد `profile:submit` گوش بدهید یا آدرس را در `data-endpoint` فرم بگذارید (README).
 */
class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', Profile::demo());
    }
}
