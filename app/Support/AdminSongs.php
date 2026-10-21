<?php

namespace App\Support;

/**
 * مدیریت آموزش موزیک (/admin/songs، /admin/songs/create، /admin/songs/{id}/edit).
 *
 * 🔌 بک‌اند — مدل یک موزیک (همان کلیدهایی که سمت کاربر در $song می‌خواند؛ نمونه: find(1)):
 *   id, status ('draft'|'published'), title, artist, cover, level ('a'|'b'|'c')، is_free (bool)، points
 *   accent (us|uk|au|ie)، year، genre، wpm، topic، album، duration (ثانیه)، desc_fa، desc_en
 *   audio            فایل موزیک (مرحله‌ی ۱، ۲، ۴، ۵، ۷)
 *   audio_vocal / audio_backing   دو فایل هم‌طول برای کارائوکه (مرحله‌ی ۶)
 *   q_play / q_repeat  { fa, en }  متن پرسش «چقدر متوجه شدی؟» در مرحله‌ی ۱ و ۷ (خالی = متن پیش‌فرض سایت)
 *   lyrics           [ { t:ثانیه, text, tr:ترجمه‌ی فارسی (اختیاری) } ]  ← مرحله‌ی ۲ (سمت کاربر: Lrc::forSong)
 *   words            [ { phrase,type,level,ipa:{uk,us},audio:{uk,us},meaning,synonyms[],antonyms[],translate } ]  ← مرحله‌ی ۳ (Words)
 *   listening        { easy:[{start,end,text,answer}], hard:[…] }  ← مرحله‌ی ۴ (Listening)
 *   shadowing        [ {start,end,text} ]  و  shadowing_repeats (پیش‌فرض ۳)  ← مرحله‌ی ۵ (Shadowing)
 *   interpretation   رشته (پاراگراف‌ها با خط خالی)، notes [ {title,type:'literary'|'language',items:[{term,example,text}]} ]  ← مرحله‌ی ۸ (Details)
 *
 * ذخیره (فرم ویرایشگر): ریشه‌ی صفحه  data-save-url (POST برای ساخت، PUT برای ویرایش — JSON کامل همین مدل + header CSRF)
 *   ← 200 { id, status, url? }    یا    422 { message, errors:{ 'title':['…'] } }
 *   «ذخیره‌ی پیش‌نویس» status=draft و «انتشار» status=published می‌فرستد (انتشار فقط وقتی مجاز است که کنترل‌های سمت سرور پاس شوند).
 * آپلود فایل: data-upload-url  (POST multipart: file, kind = cover|audio|audio_vocal|audio_backing)
 *   ← 200 { url, duration? (ثانیه، برای فایل‌های صوتی) }
 * حذف: data-delete-url (DELETE).
 * یا به‌جای آدرس‌ها:  رویداد لغوشدنی admin:song-save / admin:song-upload / admin:song-delete  یا  Teodel.admin.song.api.{save,upload,remove}
 *
 * کامل بودن هر مرحله (completeness) در همین کلاس تعریف شده تا بک‌اند هم همان قانون را برای «انتشار» استفاده کند.
 */
class AdminSongs
{
    /** ترتیب تب‌ها: اطلاعات + هشت مرحله‌ی آموزش */
    public const TABS = ['info', 'play', 'lyrics', 'words', 'listening', 'shadowing', 'karaoke', 'repeat', 'details'];

    public const DEMO_LRC = [
        [21.5, 'Mm', 'ممم'],
        [24.6, "I don't run like I did back then", 'مثل قدیم نمی‌دوم'],
        [27.4, 'Takes a breath just to start again', 'برای شروع دوباره نفسی می‌گیرم'],
        [30.5, 'Morning light finds lines on my face', 'نور صبح خط‌هایی روی صورتم پیدا می‌کند'],
        [33.3, "But I've learned to move at a kind of pace", 'اما یاد گرفته‌ام با یک آهنگ مخصوص حرکت کنم'],
        [41.9, "Plans I made didn't all come true", 'همه‌ی نقشه‌هایم به واقعیت نپیوستند'],
        [44.8, 'Some roads bent, some outgrew', 'بعضی راه‌ها خم شدند، بعضی کوچک شدند'],
        [59.1, "We're just walking this road, step by step in time", 'ما فقط این جاده را قدم‌به‌قدم و به‌موقع می‌رویم'],
        [65.1, 'Little sparks in a grand design', 'جرقه‌های کوچک در یک طرح بزرگ'],
        [71.2, 'Every year brings a clearer sight', 'هر سال دیدی روشن‌تر می‌آورد'],
        [76.8, "There's a quiet grace in another sunrise", 'در هر طلوع تازه لطفی آرام هست'],
        [82.7, "No need to race what we can't control", 'لازم نیست با چیزی که کنترلش نداریم مسابقه بدهیم'],
    ];

    public static function levelLabel(string $l): string
    {
        return strtoupper($l);
    }

    /** موزیک خالی برای «موزیک جدید» */
    public static function blank(): array
    {
        return [
            'id' => null, 'status' => 'draft', 'title' => '', 'artist' => '', 'cover' => '', 'level' => 'a', 'is_free' => false, 'points' => 50,
            'accent' => '', 'year' => '', 'genre' => '', 'wpm' => '', 'topic' => '', 'album' => '', 'duration' => 0, 'desc_fa' => '', 'desc_en' => '',
            'audio' => '', 'audio_vocal' => '', 'audio_backing' => '',
            'q_play' => ['fa' => '', 'en' => ''], 'q_repeat' => ['fa' => '', 'en' => ''],
            'lyrics' => [], 'words' => [], 'listening' => ['easy' => [], 'hard' => []],
            'shadowing' => [], 'shadowing_repeats' => 3, 'interpretation' => '', 'notes' => [],
            'updated_at' => null,
        ];
    }

    /** یک موزیک کامل (فقط دمو) */
    private static function full(): array
    {
        $lines = array_map(fn ($r) => ['t' => $r[0], 'text' => $r[1], 'tr' => $r[2]], self::DEMO_LRC);

        return array_merge(self::blank(), [
            'id' => 1, 'status' => 'published', 'title' => 'Step by Step in Time', 'artist' => 'Demo Artist', 'level' => 'a', 'is_free' => true, 'points' => 50,
            'accent' => 'us', 'year' => 2023, 'genre' => 'Folk', 'wpm' => 96, 'topic' => 'Life', 'album' => 'Sample Album', 'duration' => 214,
            'desc_fa' => 'آهنگی آرام درباره‌ی گذر زمان و پذیرفتن آهنگ زندگی.', 'desc_en' => 'A calm song about time, change and finding your own pace.',
            'audio' => 'https://example.com/audio/step-by-step.mp3', 'audio_vocal' => 'https://example.com/audio/step-vocal.mp3', 'audio_backing' => 'https://example.com/audio/step-backing.mp3',
            'cover' => '',
            'lyrics' => $lines,
            'words' => [
                ['phrase' => 'step by step', 'type' => 'idiom', 'level' => 'A2', 'ipa' => ['uk' => '/ˌstep baɪ ˈstep/', 'us' => '/ˌstep baɪ ˈstep/'],
                    'meaning' => 'Slowly and gradually, completing one stage at a time.', 'synonyms' => ['gradually', 'bit by bit'], 'antonyms' => ['suddenly'], 'translate' => 'قدم‌به‌قدم؛ به‌تدریج'],
                ['phrase' => 'pace', 'type' => 'noun', 'level' => 'B1', 'ipa' => ['uk' => '/peɪs/', 'us' => '/peɪs/'],
                    'meaning' => 'The speed at which someone or something moves.', 'synonyms' => ['speed', 'tempo'], 'antonyms' => [], 'translate' => 'سرعت؛ آهنگ حرکت'],
            ],
            'listening' => [
                'easy' => [['start' => 24.4, 'end' => 27.3, 'text' => "I don't run like I did back then", 'answer' => 'run']],
                'hard' => [['start' => 33.0, 'end' => 38.5, 'text' => "But I've learned to move at a kind of pace", 'answer' => 'a kind of pace']],
            ],
            'shadowing' => [['start' => 24.4, 'end' => 30.4, 'text' => "I don't run like I did back then\nTakes a breath just to start again"]],
            'interpretation' => "این آهنگ درباره‌ی پذیرفتن گذر زمان است.\n\nخواننده یاد گرفته که سرعت زندگی را خودش تعیین کند.",
            'notes' => [['title' => 'آرایه‌های ادبی', 'type' => 'literary', 'items' => [['term' => 'Metaphor — استعاره', 'example' => 'Little sparks in a grand design', 'text' => 'آدم‌ها به جرقه‌های کوچک در یک طرح بزرگ تشبیه شده‌اند.']]]],
            'updated_at' => time() - 3600 * 5,
        ]);
    }

    /** وضعیت کامل بودن هر مرحله: ['play'=>bool, …] (info هم هست ولی در شمارش «از ۸» نمی‌آید) */
    public static function completeness(array $s): array
    {
        $timed = $s['lyrics'] ? count(array_filter($s['lyrics'], fn ($l) => isset($l['t']) && $l['t'] !== '' && trim((string) ($l['text'] ?? '')) !== '')) === count($s['lyrics']) : false;
        $words = array_filter($s['words'], fn ($w) => trim((string) ($w['phrase'] ?? '')) !== '' && trim((string) ($w['translate'] ?? '')) !== '');

        return [
            'info' => trim((string) $s['title']) !== '' && trim((string) $s['artist']) !== '' && ! empty($s['level']) && ! empty($s['cover']),
            'play' => ! empty($s['audio']),
            'lyrics' => $timed,
            'words' => count($words) > 0,
            'listening' => count($s['listening']['easy'] ?? []) > 0 && count($s['listening']['hard'] ?? []) > 0,
            'shadowing' => count($s['shadowing']) > 0,
            'karaoke' => ! empty($s['audio_vocal']) && ! empty($s['audio_backing']),
            'repeat' => ! empty($s['audio']),
            'details' => trim((string) $s['interpretation']) !== '' || count($s['notes']) > 0,
        ];
    }

    /** چند مرحله از ۸ کامل است */
    public static function done(array $s): int
    {
        $c = self::completeness($s);
        unset($c['info']);

        return count(array_filter($c));
    }

    /** @return array<int,array> لیست دمو (۲۶ موزیک) — حذف فیلدهای سنگین نیاز نیست؛ بک‌اند فقط سطرهای لازم را بدهد */
    public static function all(): array
    {
        static $cache = null;
        if ($cache) {
            return $cache;
        }
        $catalog = [
            ['Perfect', 'Ed Sheeran', 'a'], ['Count on Me', 'Bruno Mars', 'a'], ['Let It Be', 'The Beatles', 'a'], ['Yellow', 'Coldplay', 'a'], ['Riptide', 'Vance Joy', 'a'],
            ['Counting Stars', 'OneRepublic', 'b'], ['Someone Like You', 'Adele', 'b'], ['Photograph', 'Ed Sheeran', 'b'], ['Stay', 'Rihanna', 'b'], ['Hello', 'Adele', 'b'], ['Radioactive', 'Imagine Dragons', 'b'],
            ['Bohemian Rhapsody', 'Queen', 'c'], ['Hotel California', 'Eagles', 'c'], ['Imagine', 'John Lennon', 'c'], ['Hallelujah', 'Leonard Cohen', 'c'], ['Fix You', 'Coldplay', 'c'],
        ];
        $now = time();
        $out = [self::full()];
        foreach ($catalog as $i => [$title, $artist, $level]) {
            $id = $i + 2;
            $s = self::blank();
            $s['id'] = $id;
            $s['title'] = $title;
            $s['artist'] = $artist;
            $s['level'] = $level;
            $s['is_free'] = $id <= 3;
            $s['duration'] = 150 + ($id * 17) % 120;
            $s['cover'] = $id % 4 === 0 ? '' : 'https://example.com/c/'.$id.'.jpg';
            $s['audio'] = $id % 7 === 0 ? '' : 'https://example.com/a/'.$id.'.mp3';
            $stage = $id % 9; // چند بخش پر شده باشد
            if ($stage >= 2) {
                $s['lyrics'] = array_map(fn ($r) => ['t' => $r[0], 'text' => $r[1], 'tr' => $stage >= 4 ? $r[2] : ''], array_slice(self::DEMO_LRC, 0, 8));
            }
            if ($stage >= 3) {
                $s['words'] = [['phrase' => 'pace', 'type' => 'noun', 'level' => 'B1', 'ipa' => ['uk' => '', 'us' => ''], 'meaning' => 'speed', 'synonyms' => [], 'antonyms' => [], 'translate' => 'سرعت']];
            }
            if ($stage >= 5) {
                $s['listening'] = ['easy' => [['start' => 24, 'end' => 27, 'text' => 'I run', 'answer' => 'run']], 'hard' => [['start' => 33, 'end' => 38, 'text' => 'a kind of pace', 'answer' => 'kind of']]];
                $s['shadowing'] = [['start' => 24, 'end' => 30, 'text' => 'I run']];
            }
            if ($stage >= 6) {
                $s['audio_vocal'] = 'https://example.com/v/'.$id.'.mp3';
                $s['audio_backing'] = 'https://example.com/b/'.$id.'.mp3';
            }
            if ($stage >= 7) {
                $s['interpretation'] = 'تفسیر نمونه';
            }
            $s['status'] = $stage >= 6 ? 'published' : 'draft';
            $s['updated_at'] = $now - ($id * 7 + 3) * 3600;
            $out[] = $s;
        }

        return $cache = $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $s) {
            if ($s['id'] === $id) {
                return $s;
            }
        }

        return null;
    }

    /**
     * لیست با جست‌وجو و فیلتر: GET ?q=&level=all|a|b|c&status=all|published|draft&access=all|free|paid&sort=newest|title|progress&page=
     *
     * @return array{rows:array,total:int,page:int,pages:int,per:int,counts:array}
     */
    public static function query(array $p = []): array
    {
        $q = trim((string) ($p['q'] ?? ''));
        $level = (string) ($p['level'] ?? 'all');
        $status = (string) ($p['status'] ?? 'all');
        $sort = (string) ($p['sort'] ?? 'newest');
        $page = max(1, (int) ($p['page'] ?? 1));
        $all = self::all();

        $counts = ['all' => count($all), 'published' => 0, 'draft' => 0];
        foreach ($all as $s) {
            $counts[$s['status']]++;
        }

        $rows = array_values(array_filter($all, function ($s) use ($q, $level, $status) {
            if ($q !== '' && mb_stripos($s['title'].' '.$s['artist'], $q) === false) {
                return false;
            }

            return ($level === 'all' || $s['level'] === $level) && ($status === 'all' || $s['status'] === $status);
        }));

        usort($rows, fn ($a, $b) => match ($sort) {
            'title' => strcasecmp($a['title'], $b['title']),
            'progress' => self::done($b) <=> self::done($a),
            default => ($b['updated_at'] ?? 0) <=> ($a['updated_at'] ?? 0),
        });

        $per = 12;
        $total = count($rows);
        $pages = max(1, (int) ceil($total / $per));
        $page = min($page, $pages);

        return [
            'rows' => array_map(fn ($s) => $s + ['steps_done' => self::done($s), 'done_map' => self::completeness($s)], array_slice($rows, ($page - 1) * $per, $per)),
            'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per, 'counts' => $counts,
        ];
    }
}
