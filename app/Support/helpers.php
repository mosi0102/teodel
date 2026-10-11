<?php

if (! function_exists('num')) {
    /**
     * اعداد را بر اساس زبان فعلی نمایش می‌دهد (فارسی: ۰۱۲۳۴۵۶۷۸۹).
     * در composer.json زیر autoload.files اضافه کنید: "app/Support/helpers.php"
     */
    function num(int|float|string|null $value): string
    {
        $value = (string) $value;

        if (app()->getLocale() !== 'fa') {
            return $value;
        }

        return strtr($value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
