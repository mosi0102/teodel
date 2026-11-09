<?php

namespace App\Support;

/**
 * پنل مدیریت — پادکست‌ها (سری ← اپیزود). داده‌ی نمونه از app/Support/Podcast.php ساخته می‌شود.
 *
 * 🔌 قرارداد سری (series):
 *   id, status (draft|published), title, host, category, level (a|b|c), cover, is_free, recommended,
 *   desc_fa, desc_en, tags (رشته با ویرگول), accent, updated_at,
 *   episodes[] { id, number, title, duration (ثانیه), status, steps_done }   ← فقط خلاصه برای تب «اپیزودها» (ترتیب = number)
 *
 * 🔌 قرارداد اپیزود (episode) — هر تب = یک مرحله‌ی آموزش کاربر:
 *   id, series_id, number, status, title, cover, audio, duration (ثانیه، خودکار از فایل), accent, wpm, description, tags, points
 *   transcript[] { t (ثانیه), text, tr (ترجمه، اختیاری) }   ← مرحله‌ی ۲ (آپلود SRT/LRC یا پیست، یا AI)
 *   words[]      { phrase, type, level, meaning, translate, synonyms, antonyms, pdf (bool: در PDF لغات بیاید) }   ← مرحله‌ی ۳
 *   dictation[]  { text, start, end, approved (bool) }   ← مرحله‌ی ۴ (سیستم/AI پیشنهاد می‌دهد، ادمین تأیید/ویرایش)
 *   shadowing[]  { start, end, text, prosody }            ← مرحله‌ی ۵ (prosody: نشانه‌گذاری ^ _ - *کلمه* | ؛ هر خط = یک جمله)
 *   main_idea, q_play {fa,en}, q_repeat {fa,en}           ← مرحله‌ی ۱، ۶ و ۷
 *
 * ذخیره/آپلود/حذف/AI: همان قرارداد AdminSongs (resources/js/admin/content.js، بالای فایل).
 *   AI kinds: podcast_info | transcript_ai | transcript_tr | words_suggest | dictation_suggest | shadowing_suggest | prosody | main_idea
 */
class AdminPodcast
{
    public const SERIES_TABS = ['info', 'episodes'];

    public const EPISODE_TABS = ['audio', 'transcript', 'words', 'listening', 'shadowing', 'idea'];

    public const SERIES_NEED = [
        'info' => ['title', 'host', 'level', 'desc_fa'],
        'episodes' => ['episodes[]'],
    ];

    public const EPISODE_NEED = [
        'audio' => ['title', 'audio'],
        'transcript' => ['transcript[]:text'],
        'words' => ['words[]:phrase'],
        'listening' => ['dictation[]:text'],
        'shadowing' => ['shadowing[]:prosody'],
        'idea' => ['main_idea'],
    ];

    /** لیست سری‌ها برای /admin/podcast */
    public static function all(): array
    {
        return array_map(function (array $s) {
            $eps = array_map(fn ($e) => self::episode($e), $s['episodes']);
            $row = [
                'id' => $s['id'],
                'status' => $s['id'] % 5 === 0 ? 'draft' : 'published',
                'title' => $s['title'],
                'host' => $s['host'],
                'category' => $s['category'],
                'level' => $s['level'],
                'cover' => $s['cover'],
                'is_free' => $s['is_free'],
                'recommended' => $s['recommended'],
                'desc_fa' => 'یک سری دوستانه برای زبان‌آموزان: اپیزودهای کوتاه، گفتار واضح و یک آموزش کامل برای هر اپیزود.',
                'desc_en' => $s['description'],
                'tags' => implode(', ', $s['tags']),
                'accent' => $s['accent'],
                'updated_at' => $s['updated_at'],
                'total_seconds' => $s['total_seconds'],
                'episodes' => array_map(fn ($e) => [
                    'id' => $e['id'], 'number' => $e['number'], 'title' => $e['title'], 'duration' => $e['duration'],
                    'status' => $e['status'], 'steps_done' => count(array_filter(AdminContent::doneMap($e, self::EPISODE_NEED))),
                ], $eps),
            ];
            $row['done_map'] = AdminContent::doneMap($row, self::SERIES_NEED);
            $row['steps_done'] = count(array_filter($row['done_map']));

            return $row;
        }, Podcast::series());
    }

    public static function query(array $p): array
    {
        return AdminContent::query(self::all(), $p, fn ($r) => $r['title'].' '.$r['host'].' '.$r['category']);
    }

    public static function find(int $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function blank(): array
    {
        return ['id' => null, 'status' => 'draft', 'title' => '', 'host' => '', 'category' => '', 'level' => 'a', 'cover' => '',
            'is_free' => false, 'recommended' => false, 'desc_fa' => '', 'desc_en' => '', 'tags' => '', 'accent' => 'us', 'episodes' => []];
    }

    /** اپیزود کامل برای ویرایشگر */
    public static function findEpisode(int $seriesId, int $id): ?array
    {
        $ep = collect(Podcast::all())->first(fn ($e) => $e['id'] === $id && $e['series_id'] === $seriesId);

        return $ep ? self::episode($ep) : null;
    }

    public static function blankEpisode(int $seriesId, int $number): array
    {
        return ['id' => null, 'series_id' => $seriesId, 'number' => $number, 'status' => 'draft', 'title' => '', 'cover' => '', 'audio' => '',
            'duration' => 0, 'accent' => 'us', 'wpm' => '', 'description' => '', 'tags' => '', 'points' => 40,
            'transcript' => [], 'words' => [], 'dictation' => [], 'shadowing' => [], 'main_idea' => '',
            'q_play' => ['fa' => '', 'en' => ''], 'q_repeat' => ['fa' => '', 'en' => '']];
    }

    private static function episode(array $e): array
    {
        [$m, $s] = array_map('intval', explode(':', $e['duration']));
        $tr = ['سلام و به Daily English Talk خوش آمدید.', 'من آنا هستم و امروز می‌خواهم درباره‌ی روال صبحم صحبت کنم.'];

        return [
            'id' => $e['id'],
            'series_id' => $e['series_id'],
            'number' => $e['number'],
            'status' => $e['id'] % 7 === 0 ? 'draft' : 'published',
            'title' => $e['title'],
            'cover' => $e['cover'],
            'audio' => $e['audio'],
            'duration' => $m * 60 + $s,
            'accent' => $e['accent'],
            'wpm' => $e['wpm'],
            'description' => $e['description'],
            'tags' => implode(', ', $e['tags']),
            'points' => $e['points'],
            'transcript' => array_map(fn ($l, $i) => ['t' => $l['t'], 'text' => $l['text'], 'tr' => $tr[$i] ?? ''], $e['lyrics'], array_keys($e['lyrics'])),
            'words' => array_map(fn ($w, $i) => [
                'phrase' => $w['phrase'], 'type' => $w['type'], 'level' => $w['level'], 'meaning' => $w['meaning'], 'translate' => $w['translate'],
                'synonyms' => implode(', ', $w['synonyms']), 'antonyms' => implode(', ', $w['antonyms']), 'pdf' => $i < 6,
            ], $e['words'], array_keys($e['words'])),
            'dictation' => array_map(fn ($d) => ['text' => $d['text'], 'start' => $d['start'], 'end' => $d['end'], 'approved' => true], $e['listening']['sentence']),
            'shadowing' => array_map(fn ($p) => ['start' => $p['start'], 'end' => $p['end'], 'text' => $p['text'], 'prosody' => implode("\n", $p['prosody'])], $e['shadowing']),
            'main_idea' => $e['main_idea'],
            'q_play' => ['fa' => '', 'en' => ''],
            'q_repeat' => ['fa' => '', 'en' => ''],
            'updated_at' => 1730000000 + $e['id'] * 3600,
        ];
    }
}
