<?php

namespace App\Support;

/**
 * تنظیمات سایت در پنل مدیر (/admin/settings): یک صفحه با چند بخش و یک دکمه‌ی «ذخیره‌ی تغییرات».
 *
 * 🔌 بک‌اند — مدل (کلیدها به‌صورت «گروه.کلید»؛ همان ساختار current()):
 *   general     site_name_fa, site_name_en, support_email, default_locale ('fa'|'en')، maintenance (bool)، maintenance_msg
 *   access      free_songs (۰–۲۰: تعداد موزیک کاملاً رایگان)، free_steps (۱–۸: چند مرحله‌ی اول برای بقیه‌ی موزیک‌های کاربر رایگان؛ ۸ = همه)
 *   level_test  questions_per_section (۵–۵۰ و **مضرب ۵**: آزمون از هر ۵ سطح مساوی سؤال برمی‌دارد)، time_limit_min (۰ = بدون محدودیت)، retake_days (۰ = هر وقت)
 *   ai          enabled (bool)، model، batch_max (۵–۳۰)، key_set (bool؛ فقط خواندنی — کلید واقعی هرگز به مرورگر نمی‌رود)
 *   payments    gateway (zarinpal|idpay|zibal|nextpay)، sandbox (bool)، merchant_set (bool؛ فقط خواندنی)
 *   notify      email، new_ticket, new_report, failed_payment (bool)
 *   referral    inviter_points (۰–۱۰۰۰)، invitee_days (۰–۳۰)
 *
 * ذخیره: PUT data-save-url  JSON { settings:{ 'general.site_name_fa':'…', … همه‌ی کلیدها … }, changed:['general.site_name_fa', …] }
 *   • رازها (ai.key, payments.merchant) فقط وقتی مدیر مقدار تازه تایپ کرده در settings می‌آیند؛ نبودنشان یعنی «بدون تغییر».
 *   ← 200 { ok:true } | 422 { message, errors:{ 'general.support_email':['…'] } }
 *   یا رویداد لغوشدنی admin:settings-save { settings, changed, done(), fail(msg, errors) }.
 * قانون اعتبار (validate) را سمت سرور هم اعمال کنید؛ همان قانون resources/js/admin/settings.js است.
 *
 * ⚠️ نگهداری (maintenance): وقتی روشن است، سایت کاربران باید صفحه‌ی «در دست نگهداری» با maintenance_msg نشان دهد و فقط مدیرها وارد شوند.
 * ⚠️ کلید AI و مرچنت درگاه را رمزنگاری‌شده نگه دارید و در لاگ‌ها ننویسید.
 */
class AdminSettings
{
    public const GATEWAYS = ['zarinpal', 'idpay', 'zibal', 'nextpay'];

    public const LOCALES = ['fa', 'en'];

    public static function current(): array
    {
        return [
            'general' => ['site_name_fa' => 'تئودل', 'site_name_en' => 'Teodel', 'support_email' => 'support@teodel.test', 'default_locale' => 'fa', 'maintenance' => false, 'maintenance_msg' => ''],
            'access' => ['free_songs' => 3, 'free_steps' => 2],
            'level_test' => ['questions_per_section' => 15, 'time_limit_min' => 0, 'retake_days' => 0],
            'ai' => ['enabled' => true, 'model' => 'claude-sonnet-5-5', 'batch_max' => 20, 'key_set' => true],
            'payments' => ['gateway' => 'zarinpal', 'sandbox' => true, 'merchant_set' => false],
            'notify' => ['email' => 'admin@teodel.test', 'new_ticket' => true, 'new_report' => true, 'failed_payment' => false],
            'referral' => ['inviter_points' => 50, 'invitee_days' => 3],
        ];
    }

    /** کمترین تعداد سؤال «منتشرشده» در هر سطح برای هر بخش (برای هشدار تنظیم سؤال‌های آزمون) @return array<string,int> */
    public static function bankCoverage(): array
    {
        $out = [];
        foreach (AdminPlacement::coverage() as $section => $levels) {
            $out[$section] = min($levels);
        }

        return $out;
    }
}
