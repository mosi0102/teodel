<?php

namespace App\Http\Controllers;

use App\Support\AuthFlow;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط نمایش فرانت‌اند ورود/ثبت‌نام. منطق واقعی (ارسال پیامک، تأیید کد، ساخت کاربر، نشست) را بک‌اند می‌نویسد؛
 * قرارداد چهار آدرس: app/Support/AuthFlow.php. نام عمداً AuthPageController است تا با کنترلرهای احراز هویت لاراول قاطی نشود.
 */
class AuthPageController extends Controller
{
    public function show(Request $request, string $mode = 'login')
    {
        return view('auth.index', [
            'mode' => $mode === 'register' ? 'register' : 'login',
            'redirect' => AuthFlow::safeRedirect($request->query('redirect')),
            'ref' => preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->query('ref', '')),
            // بعد از ساخت بک‌اند، آدرس‌ها را این‌جا بدهید (بدون آن «حالت دمو» فعال است):
            // 'urls' => ['send' => url('/auth/send'), 'verify' => url('/auth/verify'), 'password' => url('/auth/password'), 'register' => url('/auth/register')],
        ]);
    }
}
