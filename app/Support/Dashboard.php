<?php

namespace App\Support;

/**
 * داشبورد کاربر (/dashboard) — نمای کلی یادگیری در هر چهار بخش (موزیک، پادکست، شدوئینگ، کتاب).
 *
 * 🔌 بک‌اند: کنترلر یک آرایه‌ی $d با این کلیدها به صفحه می‌دهد (مقدارهای نمونه در demo()):
 *   user      { name, level('A1'..'C1'), skills:{listening,reading,writing,speaking} (هرکدام 'A1'..'C1' یا null) }
 *   plan      { type: 'free'|'paid', days_left (paid), free:[ { kind, used, total } ] }   ← از هر بخش چند آموزش کاملاً رایگان باز شده
 *   streak    { current, best, week:[bool×۷] }   ← week: ۷ روز آخر به ترتیب قدیمی ← امروز؛ true یعنی آن روز درس خوانده
 *   goal      { minutes (هدف روزانه), today (دقیقه‌ی امروز) }   ← گزینه‌های هدف: 10, 20, 30, 45 (data-goal-options)
 *   week      [ { at: ثانیه‌ی یونیکس, minutes } × ۷ ]  و week_prev_total (جمع هفته‌ی قبل برای مقایسه)
 *   totals    { done:{music,podcast,shadowing,books}, words_saved, sentences_saved }
 *   resume    [ { kind, id, title, subtitle, cover?, step, steps, step_key, percent (۰ تا ۱۰۰), url } ]
 *             ← آموزش‌های نیمه‌کاره، جدیدترین اول (حداکثر ۴)؛ اولی در سربرگ و بقیه در «ادامه‌ی بقیه» نشان داده می‌شوند
 *             step_key: کلید مرحله در همان بخش (music: play…details | podcast: play…details | shadowing: listen…questions | book: intro…author)
 *   review    { due (لغت‌های آماده‌ی مرور), url }
 *   recommended  [ { kind, id, title, subtitle, cover?, level('a'|'b'|'c'|'A2'…), minutes, is_free, url } ]  ← از همه‌ی بخش‌ها، مناسب سطح کاربر
 *   level_test   { taken (۰ تا ۳), total: 3, url }
 *   tickets   { open, unread, url }
 *   sections  [ { key: music|podcast|shadowing|books, done, started, total, url } ]  ← چهار مسیر یادگیری (کارت‌های میانبر با پیشرفت)
 *   tip_english  { show: bool, switch_url, dismiss_url? } | null   ← پیشنهاد استفاده از سایت به انگلیسی؛ بک‌اند بعد از «اولین آموزش تمام‌شده» show=true می‌کند
 *                (تا وقتی کاربر «دیگر نشان نده» نزده). فقط وقتی زبان فعلی فارسی است نمایش داده می‌شود.
 *
 * رویداد هدف روزانه: $(document).on('dashboard:goal', (e, { minutes }) => …) یا data-goal-url (POST JSON { minutes } + CSRF).
 */
class Dashboard
{
    /** آیکون و فایل متن مرحله‌های هر بخش */
    public const KINDS = [
        'music' => ['icon' => 'music', 'lang' => 'lesson', 'nav' => 'music'],
        'podcast' => ['icon' => 'mic', 'lang' => 'podcast_lesson', 'nav' => 'podcast'],
        'shadowing' => ['icon' => 'repeat', 'lang' => 'shadowing_lesson', 'nav' => 'shadowing'],
        'book' => ['icon' => 'book', 'lang' => 'book_lesson', 'nav' => 'books'],
    ];

    /** برچسب مرحله‌ی یک بخش (از فایل متن همان بخش) */
    public static function stepLabel(string $kind, string $key): string
    {
        $file = self::KINDS[$kind]['lang'] ?? 'lesson';
        $v = trans($file.'.steps.'.$key.'.label');

        return is_string($v) && $v !== $file.'.steps.'.$key.'.label' ? $v : $key;
    }

    public static function demo(): array
    {
        $now = time();
        $day = 86400;
        $mins = [18, 0, 25, 12, 30, 8, 12]; // آخرین مورد = امروز

        return [
            'user' => [
                'name' => 'مهدی',
                'level' => 'A2',
                'skills' => ['listening' => 'B1', 'reading' => 'A1', 'writing' => 'A2', 'speaking' => null],
            ],
            'plan' => ['type' => 'free', 'days_left' => 0, 'free' => [
                ['kind' => 'music', 'used' => 2, 'total' => 3],
                ['kind' => 'podcast', 'used' => 1, 'total' => 3],
                ['kind' => 'shadowing', 'used' => 1, 'total' => 3],
                ['kind' => 'book', 'used' => 1, 'total' => 3],
            ]],
            'streak' => ['current' => 5, 'best' => 12, 'week' => [true, false, true, true, true, true, true]],
            'goal' => ['minutes' => 20, 'today' => 12],
            'week' => array_map(fn ($i) => ['at' => $now - (6 - $i) * $day, 'minutes' => $mins[$i]], range(0, 6)),
            'week_prev_total' => 84,
            'totals' => ['done' => ['music' => 4, 'podcast' => 3, 'shadowing' => 1, 'books' => 0], 'words_saved' => 38, 'sentences_saved' => 12],
            'tip_english' => ['show' => true, 'switch_url' => url('/lang/en'), 'dismiss_url' => null],
            'resume' => [
                ['kind' => 'podcast', 'id' => 1002, 'title' => 'Ordering Food', 'subtitle' => 'Daily English Talk', 'cover' => '/covers/111.jpg',
                    'step' => 4, 'steps' => 7, 'step_key' => 'listening', 'percent' => 50, 'url' => url('/podcast/1002')],
                ['kind' => 'book', 'id' => 3001, 'title' => 'The Lighthouse Keeper', 'subtitle' => 'Clara Wynn', 'cover' => null,
                    'step' => 2, 'steps' => 4, 'step_key' => 'read', 'percent' => 33, 'url' => url('/books/3001')],
                ['kind' => 'music', 'id' => 2, 'title' => 'Say My Name', 'subtitle' => 'David Guetta', 'cover' => null,
                    'step' => 3, 'steps' => 8, 'step_key' => 'words', 'percent' => 28, 'url' => url('/music/2/step/3')],
                ['kind' => 'shadowing', 'id' => 2002, 'title' => 'Introducing myself', 'subtitle' => 'A2', 'cover' => null,
                    'step' => 2, 'steps' => 5, 'step_key' => 'record', 'percent' => 30, 'url' => url('/shadowing/2002')],
            ],
            'review' => ['due' => 12, 'url' => url('/words')],
            'recommended' => [
                ['kind' => 'music', 'id' => 4, 'title' => 'Perfect', 'subtitle' => 'Ed Sheeran', 'cover' => null, 'level' => 'A', 'minutes' => 4, 'is_free' => true, 'url' => url('/music/4')],
                ['kind' => 'podcast', 'id' => 4, 'title' => 'Around the Town', 'subtitle' => 'Daniel Reed', 'cover' => '/covers/111.jpg', 'level' => 'A', 'minutes' => 5, 'is_free' => false, 'url' => url('/podcast/series/4')],
                ['kind' => 'shadowing', 'id' => 2008, 'title' => 'My daily routine', 'subtitle' => 'A2', 'cover' => null, 'level' => 'A2', 'minutes' => 2, 'is_free' => true, 'url' => url('/shadowing')],
                ['kind' => 'book', 'id' => 3002, 'title' => 'City of Small Things', 'subtitle' => 'Omar Haddad', 'cover' => null, 'level' => 'A2', 'minutes' => 3, 'is_free' => true, 'url' => url('/books/3002')],
            ],
            'level_test' => ['taken' => 1, 'total' => 3, 'url' => url('/level-test')],
            'tickets' => ['open' => 2, 'unread' => 1, 'url' => url('/support')],
            'sections' => [
                ['key' => 'music', 'done' => 4, 'started' => 2, 'total' => 24, 'url' => url('/music')],
                ['key' => 'podcast', 'done' => 3, 'started' => 2, 'total' => 49, 'url' => url('/podcast')],
                ['key' => 'shadowing', 'done' => 1, 'started' => 3, 'total' => 18, 'url' => url('/shadowing')],
                ['key' => 'books', 'done' => 0, 'started' => 1, 'total' => 6, 'url' => url('/books')],
            ],
        ];
    }
}
