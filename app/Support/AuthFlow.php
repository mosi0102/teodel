<?php

namespace App\Support;

/**
 * ورود و ثبت‌نام (/login و /register — یک صفحه‌ی چندمرحله‌ای): شماره ← کد پیامکی (پیش‌فرض) یا رمز عبور ← (فقط شماره‌ی جدید) نام + رمز.
 *
 * جریان:
 *   ۱) کاربر شماره را می‌نویسد ← ارسال کد. سرور در همان پاسخ می‌گوید حساب وجود دارد یا نه.
 *   ۲) کد را وارد می‌کند. حساب موجود = ورود؛ شماره‌ی جدید = مرحله‌ی «تکمیل ثبت‌نام» (نام، نام خانوادگی، رمز، قوانین، کد معرف اختیاری).
 *   ۳) اگر پیامک نرسید، «ورود با رمز عبور» (در مرحله‌ی شماره و مرحله‌ی کد هست). «فراموشی رمز» = ورود با کد و عوض‌کردن رمز در پروفایل.
 *
 * 🔌 بک‌اند — چهار عملیات؛ هر کدام یکی از سه راه: رویداد لغوشدنی auth:{send|verify|password|register} ← Teodel.auth.api.{…} ← آدرس روی ریشه (data-*-url).
 * همه POST با JSON و CSRF؛ خطا: 422 { message?, errors:{ field:['…'] } } یا 429 { message?, retry_in: ثانیه }.
 *
 *   send      data-send-url      { phone: '09123456789', resend?: bool, purpose?: 'login'|'reset' }
 *                                ← { exists: bool, has_password: bool, sent: bool, cooldown: 60 }
 *                                sent=false یعنی پیامک نرفت (ولی exists/has_password را بدهید تا فرانت «ورود با رمز» را پیشنهاد کند).
 *                                ⚠️ این پاسخ وجود شماره را لو می‌دهد (طبق خواسته‌ی محصول)؛ حتماً نرخ‌محدودیت بگذارید (مثلاً ۵ درخواست در ۱۰ دقیقه به ازای شماره/IP).
 *   verify    data-verify-url    { phone, code, purpose? }
 *                                ← { status:'logged_in', redirect } | { status:'needs_profile', ticket }   (ticket = توکن کوتاه‌عمر که ثابت می‌کند این شماره تأیید شده)
 *                                کد غلط: 422 { errors:{ code:['…'] } }. بعد از چند تلاش غلط کد را باطل کنید.
 *   password  data-password-url  { phone, password, remember: bool } ← { redirect }   |  422 { errors:{ password:['…'] } }
 *                                پیام خطا برای «شماره‌ی بدون حساب» و «رمز غلط» یکسان باشد.
 *   register  data-register-url  { phone, ticket, first_name, last_name, password, ref?: string, terms: true } ← { redirect }
 *                                اعتبارسنجی سرور: validatePassword()/normalizePhone() همین کلاس.
 *   redirect  آدرس همان‌سایت (با «/» شروع شود). فرانت فقط مسیر داخلی را قبول می‌کند؛ سمت سرور هم safeRedirect() را بزنید.
 *
 * 🔌 صفحه (AuthPageController): data-redirect (بعد از ورود)، ?ref=CODE (کد معرف پیش‌پر)، ?redirect=/music/…
 * بدون هیچ آدرس/رویدادی «حالت دمو» فعال است (شماره‌ی پایان ۰–۴ = حساب موجود، ۵–۹ = جدید، کد 12345، رمز demo1234) — با وصل‌شدن بک‌اند خودبه‌خود خاموش می‌شود.
 */
class AuthFlow
{
    public const CODE_LENGTH = 5;

    public const RESEND_SECONDS = 60;

    public const PASSWORD_MIN = 8;

    /** شماره‌ی موبایل ایران → 09123456789 یا null (ارقام فارسی/عربی، +98، 0098، 98، بدون صفر، فاصله و خط‌تیره مجازند) */
    public static function normalizePhone(?string $v): ?string
    {
        $s = strtr((string) $v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $s = preg_replace('/[\s\-().]/u', '', $s);
        $s = preg_replace('/^(\+98|0098|98)/', '0', $s);
        if (preg_match('/^9\d{9}$/', $s)) {
            $s = '0'.$s;
        }

        return preg_match('/^09\d{9}$/', $s) ? $s : null;
    }

    /** رمز: دست‌کم PASSWORD_MIN نویسه با حرف و عدد. @return string|null کد خطا (password_short|password_weak) یا null */
    public static function validatePassword(string $p): ?string
    {
        if (mb_strlen($p) < self::PASSWORD_MIN) {
            return 'password_short';
        }
        if (! preg_match('/\p{L}/u', $p) || ! preg_match('/\d/', $p)) {
            return 'password_weak';
        }

        return null;
    }

    /** فقط مسیر داخلی (برای ?redirect= و پاسخ سرور): «/…» و نه «//…» یا آدرس کامل */
    public static function safeRedirect(?string $to, string $fallback = '/dashboard'): string
    {
        $to = (string) $to;

        return ($to !== '' && $to[0] === '/' && ! str_starts_with($to, '//') && ! str_contains($to, '\\')) ? $to : $fallback;
    }
}
