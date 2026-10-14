<?php

namespace App\Support;

/**
 * تمرین‌های مرحله‌ی ۴ (listening): دو حالت «آسان» و «سخت».
 *
 *  - آسان: بخشی از موزیک پخش می‌شود و متنش با «یک» کلمه‌ی خالی نشان داده می‌شود؛ کاربر همان کلمه را می‌نویسد.
 *  - سخت: یک عبارت یا چند کلمه‌ی پشت‌سرهم خالی است؛ کاربر باید بشنود و کلمه‌ها را بنویسد.
 *
 * 🔌 بک‌اند در $song['listening'] می‌دهد (هر تمرین = یک بخش از موزیک):
 *   [
 *     'easy' => [ ['start' => 24.4, 'end' => 28.1, 'text' => "I don't run like I did back then", 'answer' => 'run'] , ...],
 *     'hard' => [ ['start' => 33.0, 'end' => 39.0, 'text' => 'But I\'ve learned to move at a kind of pace', 'answer' => 'a kind of pace'] , ...],
 *   ]
 *   start / end = زمان شروع و پایان بخش (ثانیه) | text = متن کامل آن بخش | answer = همان کلمه/عبارتی که باید خالی شود
 *   (answer باید دقیقاً داخل text باشد؛ اگر چند بار آمده، 'occurrence' => 2 بدهید). اگر چیزی نرسد، تمرین‌های نمونه از لیریک ساخته می‌شود.
 */
class Listening
{
    /** نمونه برای تست فرانت: [شماره‌ی خط لیریک، پاسخ] */
    private const DEMO = [
        'easy' => [[1, 'run'], [3, 'light'], [11, 'clearer'], [14, 'heart']],
        'hard' => [[4, 'a kind of pace'], [9, 'step by step in time'], [12, 'quiet grace in another sunrise'], [13, "what we can't control"]],
    ];

    public static function forSong(array $song): array
    {
        $raw = $song['listening'] ?? self::demo($song);
        $out = ['easy' => [], 'hard' => []];

        foreach (['easy', 'hard'] as $mode) {
            foreach (array_values($raw[$mode] ?? []) as $i => $ex) {
                $built = self::build($mode, $i + 1, $ex);
                if ($built) {
                    $out[$mode][] = $built;
                }
            }
        }

        return $out;
    }

    /** تمرین‌های نمونه از لیریک (زمان پایان = تقریبی) */
    private static function demo(array $song): array
    {
        $lines = Lrc::forSong($song);
        $raw = ['easy' => [], 'hard' => []];

        foreach (self::DEMO as $mode => $items) {
            foreach ($items as [$idx, $answer]) {
                if (! isset($lines[$idx])) {
                    continue;
                }
                $l = $lines[$idx];
                $next = $lines[$idx + 1]['t'] ?? null;
                $end = $l['t'] + max(3.0, mb_strlen($l['text']) * 0.13 + 1.0);
                $raw[$mode][] = [
                    'start' => max(0, $l['t'] - 0.3),
                    'end' => round($next !== null ? min($end, $next) : $end, 2),
                    'text' => $l['text'],
                    'answer' => $answer,
                ];
            }
        }

        return $raw;
    }

    private static function build(string $mode, int $n, array $ex): ?array
    {
        $text = trim((string) ($ex['text'] ?? ''));
        $answer = trim((string) ($ex['answer'] ?? ''));
        if ($text === '' || $answer === '' || ! isset($ex['start'], $ex['end'])) {
            return null;
        }

        // جای پاسخ داخل متن (حروف بزرگ/کوچک مهم نیست، مرز کلمه رعایت می‌شود)
        $re = "/(?<![\p{L}\p{N}'’])" . preg_replace('/\s+/u', '\s+', preg_quote($answer, '/')) . "(?![\p{L}\p{N}'’])/iu";
        if (! preg_match_all($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            return null; // پاسخ داخل متن نیست؛ تمرین نادیده گرفته می‌شود
        }
        [$matched, $off] = $m[0][max(0, (int) ($ex['occurrence'] ?? 1) - 1)] ?? $m[0][0];

        return [
            'id' => $mode . '-' . $n,
            'mode' => $mode,
            'n' => $n,
            'start' => (float) $ex['start'],
            'end' => (float) $ex['end'],
            'before' => substr($text, 0, $off),
            'after' => substr($text, $off + strlen($matched)),
            'answers' => preg_split('/\s+/u', trim($matched)), // هر کلمه یک جای خالی
        ];
    }
}
