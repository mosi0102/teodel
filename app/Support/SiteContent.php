<?php

namespace App\Support;

/**
 * ⚠️ داده‌ی صفحه‌های عمومی (نخست، سوالات، قوانین). بیشتر متن‌ها در lang/{fa,en}/site.php است.
 * اینجا فقط چیزهایی که ممکن است از پنل/تنظیمات بیایند: راه‌های ارتباطی و شبکه‌های اجتماعی.
 * بک‌اند: contacts() را از تنظیمات (پنل ادمین ← تنظیمات) بخواند؛ نظرهای واقعی را هم جایگزین lang `site.reviews.items` کند.
 */
class SiteContent
{
    public static function contacts(): array
    {
        return [
            'email' => 'info@teodel.ir',
            'telegram' => '@teodel_support',
            'telegram_url' => 'https://t.me/teodel_support',
            'instagram' => 'teodel',
            'instagram_url' => 'https://instagram.com/teodel',
        ];
    }

    /** آواتارهای نظرها: فایل‌های public/images/avatars/avatar-NN.svg */
    public static function reviewAvatars(): array
    {
        return [3, 7, 11, 5, 14, 9, 2, 12, 6];
    }
}
