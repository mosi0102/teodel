<?php

namespace App\Support;

/**
 * بخش‌های مرحله‌ی ۵ (shadowing): هر بخش = تکه‌ای از موزیک که کاربر می‌شنود و بعد تکرار می‌کند (حداقل ۳ بار).
 *
 * 🔌 بک‌اند در $song['shadowing'] می‌دهد:
 *   [ ['start' => 24.4, 'end' => 30.6, 'text' => "I don't run like I did back then\nTakes a breath just to start again"], ... ]
 *   start / end = شروع و پایان بخش (ثانیه) | text = لیریک همان بخش (هر خط در یک سطر)
 * تعداد تکرارِ هدف: $song['shadowing_repeats'] (پیش‌فرض ۳).
 * اگر چیزی نرسد، چند بخش نمونه از لیریک ساخته می‌شود.
 */
class Shadowing
{
    public const TARGET = 3;

    /** نمونه برای تست فرانت: شماره‌ی خط‌های لیریک در هر بخش */
    private const DEMO = [[1, 2], [3, 4], [9, 10], [12, 13]];

    public static function target(array $song): int
    {
        return max(1, (int) ($song['shadowing_repeats'] ?? self::TARGET));
    }

    public static function forSong(array $song): array
    {
        $raw = $song['shadowing'] ?? self::demo($song);
        $out = [];

        foreach (array_values($raw) as $i => $p) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($p['text'] ?? '')))));
            if (! $lines || ! isset($p['start'], $p['end']) || $p['end'] <= $p['start']) {
                continue;
            }
            $out[] = [
                'id' => 'sh-' . (count($out) + 1),
                'n' => count($out) + 1,
                'start' => round((float) $p['start'], 2),
                'end' => round((float) $p['end'], 2),
                'lines' => $lines,
            ];
        }

        return $out;
    }

    private static function demo(array $song): array
    {
        $lines = Lrc::forSong($song);
        $raw = [];

        foreach (self::DEMO as $idx) {
            $group = array_values(array_filter(array_map(fn ($i) => $lines[$i] ?? null, $idx)));
            if (! $group) {
                continue;
            }
            $last = end($group);
            $next = $lines[end($idx) + 1]['t'] ?? null;
            $end = $last['end'] + 0.4;
            $raw[] = [
                'start' => max(0, $group[0]['t'] - 0.3),
                'end' => $next !== null ? min($end, $next) : $end,
                'text' => implode("\n", array_column($group, 'text')),
            ];
        }

        return $raw;
    }
}
