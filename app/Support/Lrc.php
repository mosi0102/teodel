<?php

namespace App\Support;

/**
 * لیریک زمان‌دار (LRC) → آرایه برای صفحه‌ی آموزش.
 *
 * بک‌اند می‌تواند در $song['lyrics'] یکی از این دو را بدهد:
 *   ۱) رشته‌ی LRC:  "[00:21.53]Mm\n[00:24.65]I don't run like I did back then ..."
 *   ۲) آرایه:       [['t' => 21.53, 'text' => 'Mm', 'tr' => 'ترجمه (اختیاری)'], ...]
 * اگر چیزی نداد، متن نمونه‌ی «Step by Step in Time» نشان داده می‌شود (فقط برای تست فرانت).
 */
class Lrc
{
    /** لیریک نمونه برای تست — وقتی بک‌اند وصل شد لازم نیست */
    private const DEMO = <<<'LRC'
[00:21.53]Mm
[00:24.65]I don't run like I did back then
[00:27.44]Takes a breath just to start again
[00:30.51]Morning light finds lines on my face
[00:33.35]But I've learned to move at a kind of pace
[00:41.99]Plans I made didn't all come true
[00:44.84]Some roads bent, some outgrew
[00:48.06]Still, I found in the changing view
[00:51.45]What stays with you is what you choose
[00:59.10]We're just walking this road, step by step in time
[01:05.12]Little sparks in a grand design
[01:11.26]Every year brings a clearer sight
[01:16.87]There's a quiet grace in another sunrise
[01:22.76]No need to race what we can't control
[01:25.86]Every mile shapes the heart and soul
[01:29.20]I believe as the days unfold
[01:32.67]There's a kind of strength in growing old
[01:46.13]Mm-mm
[01:57.70]There was a time I chased every mile
[02:01.37]Now I linger just to rest a while
[02:04.25]Windows down with a slower song
[02:07.12]Let the moment carry me alone
[02:09.70]Used to think the best was gone too fast
[02:12.55]Like joy was tied to a fading past
[02:15.41]But the truth has a way of showing through
[02:18.67]Some of the best comes later on for you
[02:26.11]We're just walking this road, step by step in time
[02:33.26]Little sparks in a grand design
[02:39.58]Every year brings a softer view
[02:45.49]Of the things that really matter to you
[02:51.17]Through the turns and the highs and lows
[02:54.00]There's a peace that the heart can know
[02:56.93]And I see as the days unfold
[03:00.21]There's a quiet gift in growing old
[03:09.61]I won't forget where I've been before
[03:15.78]Every lesson opened up a door
[03:20.91]Time moves on, it won't slow down
[03:26.35]But there's beauty waiting all around
[03:34.35]We're just walking this road, step by step in time
[03:40.85]Little sparks in a grand design
[03:46.85]And the more that I live and learn
[03:52.59]The more I see what's truly earned
[03:57.73]No need to feel what I can't control
[04:00.82]Every day shapes the heart and soul
[04:04.24]Now I know as the days unfold
[04:07.99]There's nothing wrong with growing old
[04:18.93]Mm-mm
LRC;

    /**
     * خط‌های لیریک این موزیک: [['t' => ثانیه، 'end' => پایان خواندن، 'text' => ..., 'tr' => ...], ...]
     */
    public static function forSong(array $song): array
    {
        $raw = $song['lyrics'] ?? self::DEMO;
        $lines = is_string($raw) ? self::parse($raw) : self::normalize($raw);

        foreach ($lines as $i => $line) {
            $lines[$i]['end'] = self::sungEnd($line, $lines[$i + 1]['t'] ?? null);
        }

        return $lines;
    }

    /** رشته‌ی LRC → خط‌ها (چند زمان در یک خط هم پشتیبانی می‌شود) */
    public static function parse(string $lrc): array
    {
        $out = [];

        foreach (preg_split('/\R/u', $lrc) as $row) {
            if (! preg_match_all('/\[(\d{1,3}):(\d{2})(?:[.:](\d{1,3}))?\]/u', $row, $m, PREG_SET_ORDER)) {
                continue; // خط‌های اطلاعاتی مثل [ar:...] یا خالی
            }
            $text = trim(preg_replace('/\[[^\]]*\]/u', '', $row));
            if ($text === '') {
                continue;
            }
            foreach ($m as $t) {
                $frac = isset($t[3]) ? (float) ('0.' . $t[3]) : 0.0;
                $out[] = ['t' => (int) $t[1] * 60 + (int) $t[2] + $frac, 'text' => $text];
            }
        }

        return self::normalize($out);
    }

    /** مرتب‌سازی و پاک‌سازی آرایه‌ی ورودی */
    private static function normalize(array $lines): array
    {
        $clean = [];
        foreach ($lines as $l) {
            if (! isset($l['t'], $l['text']) || trim((string) $l['text']) === '') {
                continue;
            }
            $clean[] = ['t' => (float) $l['t'], 'text' => trim((string) $l['text'])] + (isset($l['tr']) ? ['tr' => (string) $l['tr']] : []);
        }
        usort($clean, fn ($a, $b) => $a['t'] <=> $b['t']);

        return $clean;
    }

    /** پایان تقریبی خواندن یک خط (حداکثر تا شروع خط بعد) */
    private static function sungEnd(array $line, ?float $nextT): float
    {
        $len = max(1.2, mb_strlen($line['text']) * 0.08);
        $end = $line['t'] + $len;

        return round($nextT !== null ? min($end, $nextT) : $end, 2);
    }

    /** بخش‌هایی که خواننده می‌خواند [[شروع، پایان], ...] برای نشانه‌ی روی نوار پخش‌کننده */
    public static function vocals(array $lines): array
    {
        $segs = [];
        foreach ($lines as $l) {
            $last = count($segs) - 1;
            if ($last >= 0 && $l['t'] - $segs[$last][1] < 3.0) {
                $segs[$last][1] = $l['end']; // خط‌های نزدیک به هم یک بخش می‌شوند
            } else {
                $segs[] = [round($l['t'], 2), $l['end']];
            }
        }

        return $segs;
    }
}
