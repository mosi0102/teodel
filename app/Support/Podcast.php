<?php

namespace App\Support;

/**
 * داده‌ی نمونه‌ی پادکست‌ها (فقط برای تست فرانت). شکل هر اپیزود همان شکل «آهنگ» است تا کامپوننت‌ها و ماژول‌های
 * مرحله‌های موزیک عیناً استفاده شوند (پخش‌کننده، متن همزمان، لغات، listening، shadowing، امتیازدهی).
 *
 * تفاوت‌ها نسبت به موزیک:
 *   artist  = نام کانال پادکست | album = نام سری/کانال | genre = دسته‌بندی (مثلاً Lifestyle)
 *   lyrics  = ترنسکریپت زمان‌دار (رشته‌ی LRC یا آرایه‌ی [['t'=>ثانیه,'text'=>…]]) — همان قرارداد Lrc
 *   listening['sentence'] = جمله‌های مرحله‌ی ۴ که «سیستم» انتخاب می‌کند (کاربر انتخاب نمی‌کند)
 *   shadowing = بخش‌های مرحله‌ی ۵ (سیستم انتخاب می‌کند)
 *   host, description, tags[], words_count = برای مرحله‌ی «اطلاعات پادکست» (مرحله‌ی ۷)
 *   total_steps = ۷ (موزیک ۸ مرحله دارد؛ پادکست karaoke ندارد)
 *
 * شناسه‌ها از ۱۰۰۱ شروع می‌شوند تا در دمو (localStorage پیشرفت) با شناسه‌ی موزیک‌ها قاطی نشوند.
 * بک‌اند: شناسه‌ی واقعی خودش را می‌دهد.
 */
class Podcast
{
    public const STEPS = ['play', 'lyrics', 'words', 'listening', 'shadowing', 'repeat', 'details'];

    public const FIRST_ID = 1001;

    /** ترنسکریپت نمونه (ثانیه => جمله) */
    private const TRANSCRIPT = [
        [6.0, 'Hello and welcome to Daily English Talk.'],
        [10.5, "I'm Anna, and today I want to talk about my morning routine."],
        [16.0, 'I usually wake up at six o’clock, even on weekends.'],
        [21.5, 'First, I drink a big glass of water, because it helps me wake up.'],
        [27.5, 'Then I go for a short walk around my neighborhood.'],
        [33.0, 'The streets are quiet at that time, and the air feels fresh.'],
        [39.0, 'After that, I take a shower and get dressed for work.'],
        [44.5, 'For breakfast, I always have toast with jam and a cup of tea.'],
        [51.0, 'I never skip breakfast because I get hungry very quickly.'],
        [57.0, 'Some people check their phones first thing in the morning, but I try not to.'],
        [64.5, 'I think it’s better to start the day slowly.'],
        [70.0, 'On busy days, I make a plan in my notebook.'],
        [75.5, 'I write down three important things that I need to finish.'],
        [82.0, 'This simple habit keeps me focused and relaxed.'],
        [88.0, 'My brother, on the other hand, hates mornings.'],
        [93.5, 'He sets five alarms and still arrives late.'],
        [99.0, 'So, what about you? What does your morning look like?'],
        [105.0, 'Do you prefer to get up early, or do you stay in bed as long as possible?'],
        [112.0, 'Try one small change tomorrow, like drinking water or taking a walk.'],
        [118.5, 'You might be surprised at how much better you feel.'],
        [124.0, 'Thanks for listening, and see you in the next episode.'],
        [129.0, 'Goodbye!'],
    ];

    /** جمله‌های مرحله‌ی ۴ (شماره‌ی خط در ترنسکریپت، ۰-پایه) و بخش‌های مرحله‌ی ۵ */
    private const DICTATION = [2, 3, 6, 8, 13];
    private const SHADOWING = [[1, 2], [4, 5], [9, 10], [13], [18, 19]];

    private const WORDS = [
        ['phrase' => 'routine', 'type' => 'noun', 'level' => 'A2', 'ipa' => ['uk' => '/ruːˈtiːn/', 'us' => '/ruːˈtiːn/'],
            'meaning' => 'The things you do regularly, in the same order, every day or week.', 'synonyms' => ['habit', 'schedule'], 'antonyms' => [], 'translate' => 'روال؛ برنامه‌ی روزمره'],
        ['phrase' => 'neighborhood', 'type' => 'noun', 'level' => 'B1', 'ipa' => ['uk' => '/ˈneɪbəhʊd/', 'us' => '/ˈneɪbərhʊd/'],
            'meaning' => 'The area around where you live, and the people who live there.', 'synonyms' => ['area', 'district'], 'antonyms' => [], 'translate' => 'محله؛ همسایگی'],
        ['phrase' => 'get dressed', 'type' => 'phrasal verb', 'level' => 'A2', 'ipa' => ['uk' => '/ɡet drest/', 'us' => '/ɡet drest/'],
            'meaning' => 'To put on your clothes.', 'synonyms' => ['put on clothes'], 'antonyms' => ['undress'], 'translate' => 'لباس پوشیدن'],
        ['phrase' => 'skip', 'type' => 'verb', 'level' => 'B1', 'ipa' => ['uk' => '/skɪp/', 'us' => '/skɪp/'],
            'meaning' => 'To not do or have something that you normally do or have.', 'synonyms' => ['miss', 'omit'], 'antonyms' => ['attend'], 'translate' => 'جا انداختن؛ رد کردن'],
        ['phrase' => 'first thing in the morning', 'type' => 'idiom', 'level' => 'B1', 'ipa' => ['uk' => '', 'us' => ''],
            'meaning' => 'As the very first activity of the day, right after waking up.', 'synonyms' => ['as soon as you wake up'], 'antonyms' => [], 'translate' => 'اولین کار صبح؛ همان صبح زود'],
        ['phrase' => 'focused', 'type' => 'adjective', 'level' => 'B1', 'ipa' => ['uk' => '/ˈfəʊkəst/', 'us' => '/ˈfoʊkəst/'],
            'meaning' => 'Giving all your attention to one thing.', 'synonyms' => ['concentrated', 'attentive'], 'antonyms' => ['distracted'], 'translate' => 'متمرکز'],
        ['phrase' => 'on the other hand', 'type' => 'phrase', 'level' => 'B1', 'ipa' => ['uk' => '', 'us' => ''],
            'meaning' => 'Used to introduce a different or opposite point of view.', 'synonyms' => ['in contrast', 'however'], 'antonyms' => [], 'translate' => 'از طرف دیگر؛ در مقابل'],
        ['phrase' => 'fresh', 'type' => 'adjective', 'level' => 'A2', 'ipa' => ['uk' => '/freʃ/', 'us' => '/freʃ/'],
            'meaning' => 'Clean, cool and pleasant; not old or stale.', 'synonyms' => ['clean', 'crisp'], 'antonyms' => ['stale'], 'translate' => 'تازه؛ خنک و دلپذیر'],
    ];

    /** مرحله‌ی اول/آخر هر اپیزود برای فرانت: ترنسکریپت به شکل آرایه‌ی Lrc */
    public static function transcript(): array
    {
        return array_map(fn ($r) => ['t' => $r[0], 'text' => $r[1]], self::TRANSCRIPT);
    }

    private static function endOf(int $i): float
    {
        $t = self::TRANSCRIPT;
        $len = max(1.5, mb_strlen($t[$i][1]) * 0.07 + 0.6);
        $next = $t[$i + 1][0] ?? null;

        return round($next !== null ? min($t[$i][0] + $len, $next) : $t[$i][0] + $len, 2);
    }

    public static function sentences(): array
    {
        return array_map(function (int $i) {
            $text = self::TRANSCRIPT[$i][1];

            return [
                'start' => max(0, self::TRANSCRIPT[$i][0] - 0.3),
                'end' => self::endOf($i) + 0.3,
                'text' => $text,
                'answer' => rtrim($text, ".!?"),
            ];
        }, self::DICTATION);
    }

    public static function shadowing(): array
    {
        return array_map(function (array $idx) {
            $last = end($idx);

            return [
                'start' => max(0, self::TRANSCRIPT[$idx[0]][0] - 0.3),
                'end' => self::endOf($last) + 0.3,
                'text' => implode("\n", array_map(fn ($i) => self::TRANSCRIPT[$i][1], $idx)),
                'prosody' => array_map(fn ($i) => self::PROSODY[$i] ?? self::TRANSCRIPT[$i][1], $idx),
            ];
        }, self::SHADOWING);
    }

    /** لحن/ریتم جمله‌های مرحله‌ی shadowing (کلید = شماره‌ی خط ترنسکریپت). نشانه‌ها در Shadowing::prosody توضیح داده شده */
    private const PROSODY = [
        1 => "I'm *Anna*^| and today I want to talk about my *morning* routine_",
        2 => "I *usually* wake up at *six* o’clock-| even on *weekends*_",
        4 => "Then I go for a short *walk*-| around my neighborhood_",
        5 => "The streets are *quiet* at that time^| and the air feels *fresh*_",
        9 => "Some people check their *phones*-| first thing in the morning^| but I try *not* to_",
        10 => "I think it’s *better*-| to start the day *slowly*_",
        13 => "This simple *habit*-| keeps me *focused*-| and *relaxed*_",
        18 => "Try one small *change* tomorrow^| like *drinking* water^| or taking a *walk*_",
        19 => "You might be *surprised*-| at how much *better* you *feel*_",
    ];

    /** Main idea رسمی پادکست (ادمین یا AI می‌نویسد؛ در مرحله‌ی آخر کنار نوشته‌ی کاربر نشان داده می‌شود) */
    private const MAIN_IDEA = 'Anna explains her calm morning routine (water, a short walk, a real breakfast and a simple plan) and says that starting the day slowly keeps her focused.';

    /**
     * سری‌های پادکست: هر سری چند اپیزود دارد. هر اپیزود آموزش ۷ مرحله‌ای خودش را دارد.
     * [عنوان، کانال/سازنده، دسته، سطح، پیشنهادی؟، مدت هر اپیزود (دقیقه:ثانیه)]
     */
    private const SERIES = [
        ['Daily English Talk', 'Anna Clarke', 'Lifestyle', 'a', true, ['04:10', '05:30', '03:45', '06:20', '04:50']],
        ['Easy Stories', 'James Miller', 'Stories', 'a', false, ['07:15', '06:40', '08:05', '05:55']],
        ['First Steps in English', 'Sara Lopez', 'Education', 'a', false, ['03:20', '03:50', '04:15']],
        ['Around the Town', 'Daniel Reed', 'Travel', 'a', false, ['05:05', '04:30', '05:45', '06:10', '04:20', '05:00']],
        ['Work & Life', 'Maya Collins', 'Career', 'b', true, ['12:30', '10:45', '14:10', '11:25']],
        ['Tech Today', 'Ryan Walker', 'Technology', 'b', false, ['09:40', '08:55', '10:20', '09:10', '11:00']],
        ['Culture Corner', 'Lena Park', 'Culture', 'b', false, ['13:15', '12:05', '14:40']],
        ['Healthy Minds', 'Omar Hassan', 'Health', 'b', false, ['08:30', '09:15', '07:50', '10:05']],
        ['Mind Matters', 'Dr. Emily Stone', 'Psychology', 'c', true, ['22:10', '19:45', '24:30', '21:05']],
        ['The Big Debate', 'Chris Bennett', 'Society', 'c', false, ['28:00', '31:20', '26:40']],
        ['Science Explained', 'Nora Fischer', 'Science', 'c', false, ['18:35', '20:10', '17:50', '19:25', '21:15']],
        ['History Untold', 'Victor Hale', 'History', 'c', false, ['25:20', '27:45', '24:05']],
    ];

    private const EPISODE_TITLES = ['My Morning Routine', 'Ordering Food', 'A Day at School', 'Meet My Family', 'At the Supermarket', 'My Favourite Hobby'];

    private const MIN_FREE_SERIES = 3;

    private static function seconds(string $d): int
    {
        [$m, $s] = array_map('intval', explode(':', $d));

        return $m * 60 + $s;
    }

    /** ثانیه → «۲۴ دقیقه» یا «۱ ساعت و ۲۰ دقیقه» (به زبان کاربر) */
    public static function fmt(int $sec): string
    {
        $m = max(1, (int) round($sec / 60));
        if ($m < 60) {
            return __('podcast.dur.min', ['n' => num($m)]);
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return $r ? __('podcast.dur.hm', ['h' => num($h), 'm' => num($r)]) : __('podcast.dur.h', ['h' => num($h)]);
    }

    /** لیست سری‌ها (هر سری با اپیزودهایش) */
    public static function series(): array
    {
        $total = count(self::STEPS);
        $epId = self::FIRST_ID - 1;
        $out = [];

        foreach (self::SERIES as $si => [$title, $host, $category, $level, $recommended, $durations]) {
            $sid = $si + 1;
            $eps = [];
            $secSum = 0;
            $doneCount = 0;

            foreach ($durations as $ei => $dur) {
                $epId++;
                $n = $ei + 1;
                $completed = match (true) {
                    $sid === 1 && $n === 1 => $total,
                    $sid === 1 && $n === 2 => 3,
                    $sid === 5 && $n === 1 => $total,
                    $sid === 5 && $n === 2 => $total,
                    $sid === 9 && $n === 1 => 1,
                    default => 0,
                };
                $fraction = ($completed > 0 && $completed < $total) ? 0.5 : 0.0;
                $percent = Progress::percent($completed, $fraction, $total);
                $doneCount += $completed >= $total ? 1 : 0;
                $secSum += self::seconds($dur);
                $title2 = self::EPISODE_TITLES[($sid + $ei) % count(self::EPISODE_TITLES)];

                $eps[] = [
                    'id' => $epId,
                    'series_id' => $sid,
                    'number' => $n,
                    'title' => $title2,
                    'artist' => $title,
                    'cover' => '/covers/111.jpg',
                    'audio' => '/songs/Benny Rivers – Step by Step in Time.mp3',
                    'vocals' => [],
                    'lyrics' => self::transcript(),
                    'words' => self::WORDS,
                    'listening' => ['easy' => [], 'hard' => [], 'sentence' => self::sentences()],
                    'shadowing' => self::shadowing(),
                    'main_idea' => self::MAIN_IDEA,
                    'level' => $level,
                    'accent' => ['us', 'uk', 'au', 'ie'][($sid + $n) % 4],
                    'year' => 2024,
                    'genre' => $category,
                    'wpm' => 110 + (($sid * 13 + $n * 11) % 70),
                    'topic' => $category,
                    'album' => $title,
                    'host' => $host,
                    'description' => 'A short, clear conversation about everyday life. Listen first, then follow the transcript and practise the key sentences.',
                    'tags' => [$category, 'Conversation', strtoupper($level) . ' level'],
                    'words_count' => 420 + (($sid * 37 + $n * 53) % 900),
                    'duration' => $dur,
                    'points' => 40,
                    'play_score' => $completed >= 1 ? 3 + ($n % 4) : null,
                    'play_note' => $completed >= 1 ? 'She talks about her morning routine and how she starts the day slowly.' : '',
                    'repeat_score' => $completed >= 6 ? 8 : null,
                    'completed_steps' => $completed,
                    'current_step_fraction' => $fraction,
                    'progress' => $percent,
                    'status' => Progress::status($percent),
                    'is_free' => $sid <= self::MIN_FREE_SERIES,
                    'free_steps' => 2,
                    'total_steps' => $total,
                ];
            }

            $out[] = [
                'id' => $sid,
                'title' => $title,
                'host' => $host,
                'category' => $category,
                'level' => $level,
                'cover' => '/covers/111.jpg',
                'description' => 'A friendly series for English learners: short episodes, clear speech and a full lesson for every episode.',
                'tags' => [$category, strtoupper($level) . ' level'],
                'recommended' => $recommended,
                'is_free' => $sid <= self::MIN_FREE_SERIES,
                'episodes' => $eps,
                'episodes_count' => count($eps),
                'episodes_done' => $doneCount,
                'total_seconds' => $secSum,
                'avg_seconds' => (int) round($secSum / max(1, count($eps))),
                'min_seconds' => min(array_map([self::class, 'seconds'], $durations)),
                'max_seconds' => max(array_map([self::class, 'seconds'], $durations)),
                'accent' => ['us', 'uk', 'au', 'ie'][$sid % 4],
                'wpm' => 110 + (($sid * 13) % 70),
                'progress' => (int) round($doneCount / max(1, count($eps)) * 100),
                'updated_at' => 1730000000 + $sid * 86400,
            ];
        }

        return $out;
    }

    /** همه‌ی اپیزودها (برای صفحه‌ی آموزش) */
    public static function all(): array
    {
        return array_merge(...array_column(self::series(), 'episodes'));
    }
}
