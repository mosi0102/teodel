<?php

namespace App\Support;

/**
 * ⚠️ داده‌ی نمونه‌ی بخش «Shadowing» (فقط برای نمایش فرانت‌اند؛ بک‌اند‌کار جایگزین می‌کند).
 *
 * ساختار: هر «موضوع» (topic) چند «نسخه» (version) دارد، یکی برای هر سطح A1…C2 با یک متن منسجم.
 * صفحه‌ی آموزش با «نسخه» کار می‌کند (شکل آن مثل «song» موزیک است تا از همان اجزای آموزش استفاده شود):
 *   id, topic_id, level (a1…c2), title, artist(=عنوان موضوع), cover, audio, lyrics[], words[], sentences[], questions[],
 *   duration, words_count, completed_steps, current_step_fraction, is_free, free_steps, total_steps
 *
 * جمله‌ها: sentences[i] = ['n' => 1, 'text' => '...', 'audio' => ''] ← audio خالی = صدای مرورگر (TTS) در دمو؛
 *   بک‌اند فایل صوتی هر جمله (TTS هوش مصنوعی یا آپلود ادمین) را می‌دهد.
 * سوال‌ها: questions[i] = ['q' => 'What ...?', 'answer' => 'پاسخ نمونه', 'keys' => ['teacher', 'school']]
 *   بررسی پاسخ صوتی با هوش مصنوعی انجام می‌شود (در دمو: تشخیص گفتار مرورگر + کلیدواژه‌ها).
 */
class ShadowingTopics
{
    public const STEPS = ['listen', 'record', 'practice', 'text', 'questions'];
    public const FIRST_ID = 2001;
    public const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
    public const FREE_TOPICS = 3;

    /** لغت‌های دشوار: phrase => [type, level, meaning, translate] */
    private const BANK = [
        'years old' => ['phrase', 'A1', 'Used to say how old someone is.', 'ساله'],
        'free time' => ['noun phrase', 'A2', 'The time when you are not working or studying.', 'اوقات فراغت'],
        'nice to meet you' => ['phrase', 'A2', 'A polite thing to say when you meet someone for the first time.', 'از آشنایی با شما خوشحالم'],
        'introduce myself' => ['phrase', 'B1', 'To tell people who you are when you meet them.', 'خودم را معرفی کنم'],
        'grew up' => ['phrasal verb', 'B1', 'Became an adult; spent your childhood in a place.', 'بزرگ شدم'],
        'drives' => ['verb', 'B2', 'Gives someone the energy and reason to keep going.', 'انگیزه می‌دهد؛ به پیش می‌راند'],
        'escape' => ['verb', 'B2', 'To get away from a place or a situation you want to leave.', 'فرار کردن؛ پناه بردن'],
        'sum myself up' => ['phrasal verb', 'C1', 'To describe yourself briefly and clearly.', 'خودم را در یک جمله توصیف کنم'],
        'take pride in' => ['idiom', 'C1', 'To feel pleased and satisfied about something you do well.', 'به چیزی افتخار کردن'],
        'temperament' => ['noun', 'C2', 'The natural way a person thinks, feels and behaves.', 'خلق‌وخو؛ سرشت'],
        'demystifying' => ['verb', 'C2', 'Making something difficult easier to understand.', 'ابهام‌زدایی کردن؛ ساده کردن'],

        "o'clock" => ['adverb', 'A1', 'Used after a number to say the exact hour.', 'ساعت (تمام)'],
        'tiring' => ['adjective', 'A2', 'Making you feel you need rest.', 'خسته‌کننده'],
        'one day' => ['phrase', 'A2', 'At some time in the future.', 'یک روز؛ روزی'],
        'deadlines' => ['noun', 'B1', 'The latest time by which a task must be finished.', 'ضرب‌الاجل‌ها'],
        'promotion' => ['noun', 'B1', 'A move to a more important job in the same company.', 'ارتقای شغلی'],
        'workload' => ['noun', 'B2', 'The amount of work a person has to do.', 'حجم کار'],
        'rewarding' => ['adjective', 'B2', 'Giving you satisfaction because it is worth doing.', 'ارزشمند؛ رضایت‌بخش'],
        'autonomy' => ['noun', 'C1', 'The freedom to make your own decisions.', 'استقلال عمل'],
        'the buck stops with me' => ['idiom', 'C1', 'I am the one who is finally responsible.', 'مسئولیت نهایی با من است'],
        'unconventional' => ['adjective', 'C2', 'Different from what is usual or traditional.', 'غیرمعمول؛ نامتعارف'],
        'beneath the surface' => ['idiom', 'C2', 'Hidden below what you can easily see.', 'زیر پوست ماجرا'],

        'wake up' => ['phrasal verb', 'A1', 'To stop sleeping.', 'بیدار شدن'],
        'wash my face' => ['phrase', 'A1', 'To clean my face with water.', 'صورتم را شستن'],
        'get up' => ['phrasal verb', 'A2', 'To leave your bed after sleeping.', 'از رختخواب بلند شدن'],
        'usually' => ['adverb', 'A2', 'Most of the time; in most cases.', 'معمولاً'],
        'skip' => ['verb', 'B1', 'To not do something you normally do.', 'جا انداختن؛ رد کردن'],
        'bedtime routine' => ['noun phrase', 'B1', 'The things you do every night before you sleep.', 'روال قبل از خواب'],
        'non-negotiable' => ['adjective', 'B2', 'Not open to discussion or change.', 'غیرقابل مذاکره؛ ثابت'],
        'wind down' => ['phrasal verb', 'B2', 'To relax after a busy or stressful time.', 'آرام گرفتن؛ استراحت کردن'],
        'scaffolding' => ['noun', 'C1', 'A structure that supports something while it is being built.', 'داربست؛ چارچوب حمایتی'],
        'spontaneity' => ['noun', 'C1', 'Doing things naturally, without planning them.', 'بی‌برنامگی؛ خودجوشی'],
        'friction' => ['noun', 'C2', 'Anything that makes an action harder or slower.', 'اصطکاک؛ مانع'],
        'compound' => ['verb', 'C2', 'To grow bigger over time as small effects add up.', 'روی هم انباشته شدن'],

        'stayed' => ['verb (past)', 'A2', 'Lived in a place for a short time.', 'ماندیم؛ اقامت کردیم'],
        'local' => ['adjective', 'A2', 'Belonging to the place you are visiting.', 'محلی'],
        'abroad' => ['adverb', 'B1', 'In or to a foreign country.', 'خارج از کشور'],
        'watching the world go by' => ['idiom', 'B1', 'Sitting quietly and enjoying looking at people passing.', 'تماشای رفت‌وآمد مردم'],
        'itinerary' => ['noun', 'C1', 'A detailed plan of a journey.', 'برنامه‌ی سفر'],
        'backfires' => ['verb', 'C1', 'Has the opposite result to what you wanted.', 'نتیجه‌ی عکس می‌دهد'],

        'how much' => ['phrase', 'A1', 'Used to ask the price of something.', 'چقدر؛ قیمتش چنده'],
        'discount' => ['noun', 'A2', 'A reduction in the usual price.', 'تخفیف'],
        'seller' => ['noun', 'A2', 'A person who sells things.', 'فروشنده'],
        'on sale' => ['phrase', 'B1', 'Being sold at a lower price than usual.', 'حراج'],
        'refund' => ['noun', 'B1', 'Money that is given back to you.', 'بازپرداخت؛ پس‌دادن پول'],
    ];

    /**
     * topics[key] = ['category', 'free' => bool, 'levels' => [lvl => [text, [words], [questions…]]]]
     * سوال‌ها: 'سوال|پاسخ نمونه|کلیدواژه,کلیدواژه'
     */
    private const TOPICS = [
        'introductions' => ['category' => 'Everyday', 'levels' => [
            'a1' => ['Hello! My name is Sara. I am from Iran. I am twenty-five years old. I like music and tea.', ['years old'],
                ['What is her name?|Her name is Sara.|sara', 'Where is she from?|She is from Iran.|iran']],
            'a2' => ['Hi, my name is Sara and I live in Tehran. I work in a small bank. I have one brother and one sister. In my free time, I read books. Nice to meet you!', ['free time', 'nice to meet you'],
                ['Where does she work?|She works in a small bank.|bank', 'What does she do in her free time?|She reads books.|read,books']],
            'b1' => ["Let me introduce myself. My name is Sara, and I grew up in Shiraz. I moved to Tehran three years ago to study economics. These days I work at a bank, and I really enjoy it. Outside work, I like hiking with friends.", ['introduce myself', 'grew up'],
                ['Why did she move to Tehran?|She moved there to study economics.|study,economics', 'What does she like doing outside work?|She likes hiking with friends.|hiking,friends']],
            'b2' => ["First of all, thank you for having me. I'm Sara, an economist who was born in Shiraz but has spent most of her adult life in Tehran. I've been working at a bank for about three years now. What drives me is helping ordinary people manage their money wisely. When I'm not working, I escape to the mountains.", ['drives', 'escape'],
                ['What motivates her at work?|Helping ordinary people manage their money.|help,money', 'What does she do to relax?|She escapes to the mountains.|mountains']],
            'c1' => ["It's a pleasure to meet you all. I'm Sara, and, if I had to sum myself up, I'd say I'm a curious person who happens to work in finance. I grew up in Shiraz, surrounded by poetry and gardens, which probably explains my love of language. Professionally, I specialise in personal finance, and I take pride in making complex ideas feel simple.", ['sum myself up', 'take pride in'],
                ['How does she describe herself?|As a curious person who works in finance.|curious,finance', 'What does she take pride in?|Making complex ideas feel simple.|complex,simple']],
            'c2' => ["Allow me to introduce myself properly. I'm Sara, a financial analyst by trade and a lifelong student of language by temperament. Having been raised in Shiraz, where poetry is woven into everyday conversation, I've always been drawn to the nuances of how people express themselves. Nowadays I channel that fascination into demystifying money for people who find it intimidating.", ['temperament', 'demystifying'],
                ['Why is she interested in language?|She grew up in Shiraz where poetry is part of everyday talk.|shiraz,poetry', 'What does she do with that interest today?|She makes money easier to understand for people.|money,understand']],
        ]],
        'job' => ['category' => 'Work', 'levels' => [
            'a1' => ["I am a teacher. I work in a school. I start at eight o'clock. I like my students. My job is good.", ["o'clock"],
                ['What is her job?|She is a teacher.|teacher', 'When does she start?|She starts at eight o\'clock.|eight']],
            'a2' => ['I work as a nurse in a big hospital. I usually start at seven in the morning. I help sick people and talk to their families. The work is tiring, but I love it. I want to be a doctor one day.', ['tiring', 'one day'],
                ['Where does she work?|She works in a big hospital.|hospital', 'What does she want to be one day?|She wants to be a doctor.|doctor']],
            'b1' => ["I've been working as a graphic designer for four years. I design websites and posters for small companies. Sometimes the deadlines are stressful, but I enjoy being creative. Last month I got a promotion, which made me really proud.", ['deadlines', 'promotion'],
                ['What does she design?|Websites and posters for small companies.|websites,posters', 'What happened last month?|She got a promotion.|promotion']],
            'b2' => ["I'm a project manager at a software company, which basically means I keep everyone on schedule. My day is a mix of meetings, emails and solving unexpected problems. Although the workload can be overwhelming, I find it rewarding to see a project come together. I'm hoping to take on more responsibility next year.", ['workload', 'rewarding'],
                ['What is his day like?|A mix of meetings, emails and solving problems.|meetings,emails,problems', 'What does he hope for next year?|To take on more responsibility.|responsibility']],
            'c1' => ["I run a small consultancy that helps start-ups find their footing. It's demanding work, as no two clients face quite the same challenges. What I value most is the autonomy, although it does mean the buck stops with me. Looking ahead, I'd like to build a team rather than keep doing everything on my own.", ['autonomy', 'the buck stops with me'],
                ['What does he value most?|The autonomy.|autonomy,freedom', 'What would he like to do in the future?|Build a team.|team']],
            'c2' => ["My career has been, for lack of a better word, unconventional. I began in journalism, drifted into policy research, and eventually landed in consultancy. Each move taught me something I could never have learnt by climbing a single ladder. If there's a common thread, it's a restless need to understand how things really work beneath the surface.", ['unconventional', 'beneath the surface'],
                ['Where did the career begin?|In journalism.|journalism', 'What is the common thread?|A need to understand how things really work.|understand,work']],
        ]],
        'routine' => ['category' => 'Everyday', 'levels' => [
            'a1' => ['I wake up at six. I wash my face. I eat breakfast. I go to work by bus. I go to bed at ten.', ['wake up', 'wash my face'],
                ['When does he wake up?|He wakes up at six.|six', 'How does he go to work?|He goes by bus.|bus']],
            'a2' => ['I get up at half past six every day. First, I make a cup of tea and read the news. Then I take the bus to my office. After work, I cook dinner and watch a film. I usually go to sleep around eleven.', ['get up', 'usually'],
                ['What does she drink in the morning?|She drinks tea.|tea', 'What does she do after work?|She cooks dinner and watches a film.|cook,dinner,film']],
            'b1' => ["On weekdays I wake up early because I like having a quiet hour to myself. I go for a short walk, then I have breakfast before heading to work. In the evenings I try to cook something healthy instead of ordering food. I never skip my bedtime routine, as sleep is really important to me.", ['skip', 'bedtime routine'],
                ['Why does he wake up early?|He likes having a quiet hour to himself.|quiet,hour', 'Why does he keep a bedtime routine?|Because sleep is important to him.|sleep,important']],
            'b2' => ["My mornings are non-negotiable: a glass of water, ten minutes of stretching and a proper breakfast. Once I get to the office, I tackle the hardest tasks first, while my mind is still fresh. By evening I'm usually exhausted, so I wind down with a book. Having a steady routine keeps me surprisingly calm.", ['non-negotiable', 'wind down'],
                ['What does she do first at the office?|She does the hardest tasks first.|hardest,tasks', 'How does she relax in the evening?|She reads a book.|book']],
            'c1' => ["I've come to rely on routine as a kind of scaffolding for the day. It spares me from making countless small decisions, leaving my energy for the things that matter. That said, I deliberately leave Sundays unplanned, because spontaneity needs room to breathe. Striking that balance has taken years of trial and error.", ['scaffolding', 'spontaneity'],
                ['What does routine save him from?|Making countless small decisions.|decisions', 'Why are Sundays unplanned?|Because spontaneity needs room.|spontaneity,room']],
            'c2' => ["There's a certain quiet dignity in a well-ordered day, though I'd hesitate to call myself disciplined. My habits are less about willpower than about removing friction: the running shoes by the door, the phone charging in another room. Over time these small arrangements compound into something resembling a life. Still, I make a point of breaking the pattern now and then, lest it harden into rigidity.", ['friction', 'compound'],
                ['What are his habits really about?|Removing friction, not willpower.|friction,willpower', 'Why does he break the pattern sometimes?|So it does not become rigid.|rigid,rigidity']],
        ]],
        'travel' => ['category' => 'Travel', 'levels' => [
            'a2' => ['Last summer I visited Istanbul with my sister. We stayed in a small hotel near the sea. Every day we walked around the old city and ate local food. The people were very kind to us. I want to go back next year.', ['stayed', 'local'],
                ['Who did she travel with?|She travelled with her sister.|sister', 'Where did they stay?|In a small hotel near the sea.|hotel,sea']],
            'b1' => ["I'd never travelled abroad on my own until last year, when I flew to Italy for a week. At first I was nervous about getting lost, but everyone I met was surprisingly helpful. My favourite memory is sitting in a tiny café in Rome, watching the world go by. It taught me that travelling alone is easier than I'd imagined.", ['abroad', 'watching the world go by'],
                ['Where did she fly to?|She flew to Italy.|italy', 'What did the trip teach her?|That travelling alone is easier than she imagined.|alone,easier']],
            'c1' => ["What I look for when travelling isn't so much sights as atmosphere: the sounds of a market at dawn, the rhythm of a neighbourhood that has nothing to do with tourism. I tend to wander without a fixed itinerary, which occasionally backfires but often leads to the best moments. Returning home, I find I notice my own city with fresher eyes.", ['itinerary', 'backfires'],
                ['What does he look for when travelling?|Atmosphere rather than sights.|atmosphere', 'What happens when he returns home?|He notices his own city with fresher eyes.|city,fresher']],
        ]],
        'shopping' => ['category' => 'Everyday', 'levels' => [
            'a1' => ['I go to the shop. I want some bread and milk. How much is this? It is two dollars. Thank you, goodbye.', ['how much'],
                ['What does he want?|Bread and milk.|bread,milk', 'How much is it?|It is two dollars.|two,dollars']],
            'a2' => ['Yesterday I went to the market to buy fruit. The apples were fresh, but the oranges were too expensive. I asked the seller for a discount, and he smiled and said yes. I bought a kilo of apples and some bananas. I paid and went home happy.', ['discount', 'seller'],
                ['Why did she not buy oranges?|They were too expensive.|expensive', 'What did she buy?|Apples and bananas.|apples,bananas']],
            'b1' => ["I usually make a list before I go shopping, otherwise I end up buying things I don't need. Last week I spotted a jacket on sale and couldn't resist, even though I already own three. When I got home, I realised it didn't fit properly, so I took it back the next day. Luckily, the shop gave me a full refund without any fuss.", ['on sale', 'refund'],
                ['Why does she make a list?|To avoid buying things she does not need.|list,need', 'What happened with the jacket?|It did not fit so she returned it and got a refund.|refund,fit']],
        ]],
    ];

    private const TITLES = ['introductions' => 'Introducing myself', 'job' => 'My job', 'routine' => 'My daily routine', 'travel' => 'A trip abroad', 'shopping' => 'Going shopping'];

    /** نمونه‌ی پیشرفت (id نسخه => [مرحله‌ی کامل‌شده, کسر مرحله‌ی بعد]) */
    private const PROGRESS = [
        2001 => [5, 0.0], 2002 => [5, 0.0], 2003 => [2, 0.5], 2007 => [1, 0.4], 2008 => [3, 0.3], 2014 => [5, 0.0],
    ];

    public static function fmt(int $sec): string
    {
        $m = intdiv($sec, 60);
        $s = $sec % 60;

        return $m > 0 ? __('shadowing.dur.ms', ['m' => num($m), 's' => num($s)]) : __('shadowing.dur.s', ['s' => num($s)]);
    }

    public static function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $i => $t) {
            $out[] = ['n' => $i + 1, 'text' => $t, 'audio' => ''];
        }

        return $out;
    }

    private static function word(string $phrase): array
    {
        [$type, $level, $meaning, $translate] = self::BANK[$phrase];

        return ['phrase' => $phrase, 'type' => $type, 'level' => $level, 'ipa' => ['uk' => '', 'us' => ''],
            'meaning' => $meaning, 'synonyms' => [], 'antonyms' => [], 'translate' => $translate];
    }

    private static function question(string $raw): array
    {
        [$q, $a, $k] = explode('|', $raw);

        return ['q' => $q, 'answer' => $a, 'keys' => array_values(array_filter(array_map('trim', explode(',', $k))))];
    }

    /** همه‌ی موضوع‌ها (هر کدام با لیست نسخه‌هایش، به ترتیب سطح) */
    public static function topics(): array
    {
        $out = [];
        $tid = 0;
        $vid = self::FIRST_ID;
        $levelNames = array_flip(self::LEVELS);
        foreach (self::TOPICS as $key => $t) {
            $tid++;
            $free = $tid <= self::FREE_TOPICS;
            $versions = [];
            foreach (self::LEVELS as $lvl) {
                if (! isset($t['levels'][$lvl])) {
                    continue;
                }
                [$text, $words, $questions] = $t['levels'][$lvl];
                $sentences = self::sentences($text);
                $wordCount = str_word_count($text);
                $duration = (int) round($wordCount / 2.1) + count($sentences) * 3;
                [$completed, $fraction] = self::PROGRESS[$vid] ?? [0, 0.0];
                $total = count(self::STEPS);
                $percent = Progress::percent($completed, (float) $fraction, $total);
                $lyrics = [];
                $tt = 1.0;
                foreach ($sentences as $s) {
                    $lyrics[] = ['t' => $tt, 'text' => $s['text']];
                    $tt += 3.5;
                }
                $title = self::TITLES[$key];
                $versions[] = [
                    'id' => $vid,
                    'topic_id' => $tid,
                    'topic' => $key,
                    'level' => $lvl,
                    'title' => $title,
                    'artist' => strtoupper($lvl),
                    'cover' => '',
                    'audio' => '',
                    'vocals' => [],
                    'lyrics' => $lyrics,
                    'words' => array_map([self::class, 'word'], $words),
                    'listening' => ['easy' => [], 'hard' => [], 'sentence' => []],
                    'sentences' => $sentences,
                    'questions' => array_map([self::class, 'question'], $questions),
                    'words_count' => $wordCount,
                    'duration' => $duration,
                    'completed_steps' => $completed,
                    'current_step_fraction' => (float) $fraction,
                    'progress' => $percent,
                    'status' => Progress::status($percent),
                    'is_free' => $free,
                    'free_steps' => 2,
                    'total_steps' => $total,
                    'order' => $levelNames[$lvl],
                ];
                $vid++;
            }

            $done = count(array_filter($versions, fn ($v) => $v['status'] === 'done'));
            $started = count(array_filter($versions, fn ($v) => $v['status'] !== 'new'));
            $out[] = [
                'id' => $tid,
                'key' => $key,
                'title' => self::TITLES[$key],
                'category' => $t['category'],
                'is_free' => $free,
                'versions' => $versions,
                'levels' => array_column($versions, 'level'),
                'versions_done' => $done,
                'started' => $started > 0,
                'status' => $done === count($versions) ? 'done' : ($started > 0 ? 'in_progress' : 'new'),
                'min_seconds' => min(array_column($versions, 'duration')),
                'max_seconds' => max(array_column($versions, 'duration')),
                'sentences_min' => min(array_map(fn ($v) => count($v['sentences']), $versions)),
                'sentences_max' => max(array_map(fn ($v) => count($v['sentences']), $versions)),
                'updated_at' => 1730000000 + $tid * 86400,
            ];
        }

        return $out;
    }

    /** همه‌ی نسخه‌ها (برای صفحه‌ی آموزش) */
    public static function all(): array
    {
        return array_merge(...array_column(self::topics(), 'versions'));
    }
}
