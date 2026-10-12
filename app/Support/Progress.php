<?php

namespace App\Support;

/**
 * فرمول پیشرفت یک آهنگ (یک‌جا، تا همه‌ی صفحه‌ها یک عدد نشان بدهند):
 *
 *   درصد = (مرحله‌های تمام‌شده + بخشی از مرحله‌ی جاری که انجام شده) ÷ تعداد کل مرحله‌ها × ۱۰۰
 *
 * مثال: ۲ مرحله تمام شده و مرحله‌ی سوم نصفه (۰.۵) → (۲ + ۰.۵) ÷ ۸ = ۳۱٪
 *
 * بک‌اند‌کار باید «completed_steps» و «current_step_fraction» را از دیتابیس بدهد.
 */
class Progress
{
    public const TOTAL_STEPS = 8;

    public static function percent(int $completedSteps, float $currentStepFraction = 0.0, int $total = self::TOTAL_STEPS): int
    {
        $completedSteps = max(0, min($completedSteps, $total));
        $fraction = $completedSteps >= $total ? 0.0 : max(0.0, min($currentStepFraction, 1.0));

        return (int) round(($completedSteps + $fraction) / $total * 100);
    }

    /** وضعیت از روی درصد: new | in_progress | done */
    public static function status(int $percent): string
    {
        return match (true) {
            $percent >= 100 => 'done',
            $percent > 0 => 'in_progress',
            default => 'new',
        };
    }
}
