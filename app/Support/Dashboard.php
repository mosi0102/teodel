<?php

namespace App\Support;

/**
 * داشبورد کاربر (/dashboard) — نمای کلی یادگیری در یک نگاه.
 *
 * 🔌 بک‌اند: کنترلر یک آرایه‌ی $d با این کلیدها به صفحه می‌دهد (مقدارهای نمونه در demo()):
 *   user      { name, level('A1'..'C1'), skills:{listening,reading,writing,speaking} (هرکدام 'A1'..'C1' یا null) }
 *   plan      { type: 'free'|'paid', days_left (paid), free_songs_total (مثلاً 3), free_songs_used }
 *   streak    { current, best, week:[bool×۷] }   ← week: ۷ روز آخر به ترتیب قدیمی ← امروز؛ true یعنی آن روز درس خوانده
 *   goal      { minutes (هدف روزانه), today (دقیقه‌ی امروز) }   ← گزینه‌های هدف: 10, 20, 30, 45 (data-goal-options)
 *   week      [ { at: ثانیه‌ی یونیکس, minutes } × ۷ ]  و week_prev_total (جمع هفته‌ی قبل برای مقایسه)
 *   totals    { songs_done, words_saved }
 *   resume    { song:{id,title,artist,cover?,level}, step (۱ تا ۸), steps (۸), step_key, percent (۰ تا ۱۰۰), url } | null
 *   review    { due (لغت‌های آماده‌ی مرور), url }
 *   recommended  [ { id, title, artist, cover?, level('a'|'b'|'c'), duration (ثانیه), is_free, url } ]  ← حداکثر ۳ آهنگ مناسب سطح کاربر
 *   level_test   { taken (۰ تا ۳), total: 3, url }
 *   tickets   { open, unread, url }
 *
 * رویداد هدف روزانه: $(document).on('dashboard:goal', (e, { minutes }) => …) یا data-goal-url (POST JSON { minutes } + CSRF).
 */
class Dashboard
{
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
            'plan' => ['type' => 'free', 'days_left' => 0, 'free_songs_total' => 3, 'free_songs_used' => 2],
            'streak' => ['current' => 5, 'best' => 12, 'week' => [true, false, true, true, true, true, true]],
            'goal' => ['minutes' => 20, 'today' => 12],
            'week' => array_map(fn ($i) => ['at' => $now - (6 - $i) * $day, 'minutes' => $mins[$i]], range(0, 6)),
            'week_prev_total' => 84,
            'totals' => ['songs_done' => 4, 'words_saved' => 38],
            'resume' => [
                'song' => ['id' => 2, 'title' => 'Say My Name', 'artist' => 'David Guetta', 'cover' => null, 'level' => 'a'],
                'step' => 3, 'steps' => 8, 'step_key' => 'words', 'percent' => 28,
                'url' => url('/music/2/step/3'),
            ],
            'review' => ['due' => 12, 'url' => url('/words')],
            'recommended' => [
                ['id' => 4, 'title' => 'Perfect', 'artist' => 'Ed Sheeran', 'cover' => null, 'level' => 'a', 'duration' => 263, 'is_free' => true, 'url' => url('/music/4')],
                ['id' => 5, 'title' => 'Count on Me', 'artist' => 'Bruno Mars', 'cover' => null, 'level' => 'a', 'duration' => 197, 'is_free' => false, 'url' => url('/music/5')],
                ['id' => 13, 'title' => 'Someone Like You', 'artist' => 'Adele', 'cover' => null, 'level' => 'b', 'duration' => 285, 'is_free' => false, 'url' => url('/music/13')],
            ],
            'level_test' => ['taken' => 1, 'total' => 3, 'url' => url('/level-test')],
            'tickets' => ['open' => 2, 'unread' => 1, 'url' => url('/tickets')],
        ];
    }
}
