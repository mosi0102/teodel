<?php

namespace App\Support;

/**
 * قرارداد داده‌ی صفحه‌ی پروفایل (/profile).
 *
 * ⚠️ فقط برای نمایش فرانت‌اند: demo() را با داده‌ی واقعیِ کاربر وارد‌شده عوض کنید.
 *
 * profile (در ویو با نام $profile؛ نه $user، چون سایدبار از $user استفاده می‌کند):
 *   first_name, last_name, mobile, email|null, gender: male|female|other|null,
 *   birthday: 'YYYY-MM-DD' (میلادی) | null   ← تبدیل به شمسی را خودِ فرانت انجام می‌دهد،
 *   avatar: id آواتار فعلی, has_password: bool, referral_code, days_left,
 *   skills: { listening, reading, writing, speaking } (مثلاً 'A2')
 *
 * avatars: لیست آواتارهایی که سایت پیشنهاد می‌دهد { id, src, locked }
 *   locked=true یعنی هنوز از «دستاوردها» آزاد نشده (فاز بعد)؛ کاربر نمی‌تواند انتخابش کند.
 *   کاربر آواتار شخصی خودش را نمی‌تواند آپلود کند.
 */
class Profile
{
    public static function demo(): array
    {
        return [
            'profile' => [
                'first_name' => 'مهدی',
                'last_name' => 'پوربشیری',
                'mobile' => '09123456789',
                'email' => null,
                'gender' => null,
                'birthday' => null,
                'avatar' => 2,
                'has_password' => true,
                'referral_code' => 'Teo156389',
                'days_left' => 0,
                'skills' => ['listening' => 'A3', 'reading' => 'A1', 'writing' => 'A2', 'speaking' => 'A2'],
            ],
            'avatars' => self::avatars(),
        ];
    }

    /** آواتارهای نمونه (pixel-art ساخته‌شده برای تست). فایل‌های public/images/avatars/avatar-XX.svg را با آواتارهای اصلی عوض کنید. */
    public static function avatars(): array
    {
        return collect(range(1, 15))->map(fn ($i) => [
            'id' => $i,
            'src' => asset(sprintf('images/avatars/avatar-%02d.svg', $i)),
            'locked' => false,
        ])->all();
    }
}
