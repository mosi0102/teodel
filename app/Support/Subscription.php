<?php

namespace App\Support;

/**
 * خرید اشتراک (/subscription) و نتیجه‌ی پرداخت (/subscription/result).
 *
 * 🔌 بک‌اند — هر پلن (plans()):
 *   id           free | 1m | 6m | 12m   (کلید ثابت؛ متن‌ها در lang/*\/subscription.php)
 *   months       مدت (۰ = رایگان)
 *   price        قیمت نهایی (تومان)
 *   old_price    قیمت قبل از تخفیف (اختیاری؛ برای خط‌خورده و درصد تخفیف)
 *   recommended  true برای پلن «پیشنهادی»
 *   features     کلیدهای ویژگی‌ها (متن‌شان در lang: subscription.features.*)
 *
 * ۱) پلن فعلی کاربر — current():  { plan: 'free'|'1m'|'6m'|'12m', ends_at (ثانیه‌ی یونیکس یا null), days_left }
 *
 * ۲) شروع پرداخت (بعد از «تأیید» در مودال) — ریشه‌ی صفحه: data-checkout-url
 *      POST  JSON { plan: '6m' }  + CSRF
 *      ← 200 { redirect: 'https://درگاه…' }       فرانت کاربر را به درگاه می‌برد
 *      ← 4xx { message: '…' }                     پیام داخل مودال نشان داده می‌شود
 *    (یا رویداد لغوشدنی subscription:checkout یا جایگزین کردن Teodel.subscription.api.checkout — توضیح در README)
 *    مبلغ را همیشه سرور از روی پلن حساب کند؛ هرچه از کلاینت بیاید فقط شناسه‌ی پلن است.
 *
 * ۳) برگشت از درگاه: بک‌اند تراکنش را verify می‌کند و کاربر را به GET /subscription/result می‌فرستد.
 *    کنترلر باید $result را به ویو بدهد (نمونه: demoResult()):
 *      status    success | failed | cancelled
 *      plan      شناسه‌ی پلن
 *      amount    مبلغ پرداخت‌شده (تومان)
 *      ref       کد پیگیری سایت            (در success و گاهی failed)
 *      bank_ref  شماره‌ی مرجع بانک (اختیاری)
 *      paid_at   زمان پرداخت (ثانیه‌ی یونیکس)
 *      ends_at   پایان اشتراک (ثانیه‌ی یونیکس) — فقط success
 *      message   دلیل خطا از بانک/درگاه (اختیاری — فقط failed)
 *    در حالت دمو: /subscription/result?status=success|failed|cancelled
 */
class Subscription
{
    public static function plans(): array
    {
        $paid = ['full_exercises', 'full_reports', 'advanced_level', 'planning', 'ai'];

        return [
            ['id' => 'free', 'months' => 0, 'price' => 0, 'old_price' => null, 'recommended' => false, 'features' => ['free_exercises', 'free_songs', 'level_test', 'free_reports']],
            ['id' => '1m', 'months' => 1, 'price' => 429000, 'old_price' => null, 'recommended' => false, 'features' => $paid],
            ['id' => '6m', 'months' => 6, 'price' => 1890000, 'old_price' => 2574000, 'recommended' => true, 'features' => $paid],
            ['id' => '12m', 'months' => 12, 'price' => 3290000, 'old_price' => 5148000, 'recommended' => false, 'features' => $paid],
        ];
    }

    public static function current(): array
    {
        return ['plan' => 'free', 'ends_at' => null, 'days_left' => 0];
    }

    public static function find(string $id): ?array
    {
        foreach (self::plans() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }

    /** نمونه‌ی نتیجه‌ی پرداخت (فقط برای دیدن فرانت) */
    public static function demoResult(string $status = 'success', string $plan = '6m'): array
    {
        $status = in_array($status, ['success', 'failed', 'cancelled'], true) ? $status : 'success';
        $p = self::find($plan) ?? self::find('6m');
        $now = time();

        return [
            'status' => $status,
            'plan' => $p['id'],
            'amount' => $p['price'],
            'ref' => $status === 'success' ? 'TD-7F3K9Q2M' : null,
            'bank_ref' => $status === 'success' ? '712408553901' : null,
            'paid_at' => $now,
            'ends_at' => $status === 'success' ? $now + $p['months'] * 30 * 86400 : null,
            'message' => $status === 'failed' ? 'تراکنش توسط بانک تأیید نشد.' : null,
        ];
    }
}
