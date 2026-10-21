<?php

namespace App\Support;

/**
 * داشبورد مدیریت (/admin) — نمای کلی سایت.
 *
 * 🔌 بک‌اند: کنترلر آرایه‌ی $d را با این کلیدها می‌دهد (نمونه: demo()):
 *   admin      { name }
 *   kpi        { users:{total,new_week,delta}, subs:{active,delta}, revenue:{month,prev_month,delta}, tickets:{open,unanswered} }
 *              ← delta: درصد تغییر نسبت به دوره‌ی قبل (می‌تواند منفی یا null باشد)
 *   series     { revenue:[{at,amount}×۳۰], signups:[{at,count}×۳۰] }   ← ۳۰ روز اخیر، قدیمی ← امروز
 *   plan_mix   [ { plan:'free'|'1m'|'6m'|'12m', count } ]
 *   attention  { tickets:[{id,subject,user,at,url}], reports:[{id,title,user,at,url}], ai_review:{count,url} }
 *   transactions [ { ref,user,plan,amount,status:'success'|'failed'|'cancelled'|'pending',at } ]
 *   top_songs  [ { id,title,artist,cover?,plays,completion (۰ تا ۱۰۰),url } ]
 *   health     { songs:{published,drafts,incomplete}, questions:{A1:n,…,C1:n, min_per_level}, words:{total,no_image} }
 *   badges     { tickets:n, reports:n }  ← عددهای کنار منوی سایدبار
 */
class AdminDashboard
{
    public static function demo(): array
    {
        $now = time();
        $day = 86400;
        $rev = [310, 420, 0, 560, 380, 940, 690, 520, 1180, 760, 430, 880, 1240, 610, 480, 790, 1020, 340, 560, 1480, 920, 730, 410, 860, 1310, 690, 540, 1090, 1260, 980];
        $sig = [6, 9, 4, 11, 7, 15, 12, 8, 19, 10, 7, 13, 21, 9, 8, 12, 17, 6, 9, 24, 14, 11, 7, 13, 20, 10, 9, 16, 22, 18];
        $series = ['revenue' => [], 'signups' => []];
        foreach (range(0, 29) as $i) {
            $at = $now - (29 - $i) * $day;
            $series['revenue'][] = ['at' => $at, 'amount' => $rev[$i] * 1000];
            $series['signups'][] = ['at' => $at, 'count' => $sig[$i]];
        }

        return [
            'admin' => ['name' => 'مدیر'],
            'badges' => ['tickets' => 3, 'reports' => 5],
            'kpi' => [
                'users' => ['total' => 4286, 'new_week' => 94, 'delta' => 12.4],
                'subs' => ['active' => 612, 'delta' => 5.1],
                'revenue' => ['month' => 18_450_000, 'prev_month' => 15_920_000, 'delta' => 15.9],
                'tickets' => ['open' => 8, 'unanswered' => 3],
            ],
            'series' => $series,
            'plan_mix' => [
                ['plan' => 'free', 'count' => 3674],
                ['plan' => '1m', 'count' => 318],
                ['plan' => '6m', 'count' => 224],
                ['plan' => '12m', 'count' => 70],
            ],
            'attention' => [
                'tickets' => [
                    ['id' => 1042, 'subject' => 'پرداخت انجام شد ولی اشتراک فعال نشد', 'user' => 'سارا ک.', 'at' => $now - 1800, 'url' => '/admin/tickets/1042'],
                    ['id' => 1041, 'subject' => 'سؤال درباره‌ی تمدید اشتراک', 'user' => 'علی م.', 'at' => $now - 7200, 'url' => '/admin/tickets/1041'],
                    ['id' => 1038, 'subject' => 'صدای مرحله‌ی شنیداری قطع می‌شود', 'user' => 'نگار ر.', 'at' => $now - 19000, 'url' => '/admin/tickets/1038'],
                ],
                'reports' => [
                    ['id' => 311, 'title' => 'کاراوکه: متن با آهنگ هم‌زمان نیست', 'user' => 'مهدی ح.', 'at' => $now - 3600, 'url' => '/admin/reports/311'],
                    ['id' => 309, 'title' => 'ترجمه‌ی خط ۱۲ اشتباه است', 'user' => 'ندا ف.', 'at' => $now - 26000, 'url' => '/admin/reports/309'],
                ],
                'ai_review' => ['count' => 7, 'url' => '/admin/songs?review=1'],
            ],
            'transactions' => [
                ['ref' => 'TD-7F3K9Q2M', 'user' => 'سارا کریمی', 'plan' => '6m', 'amount' => 1_890_000, 'status' => 'success', 'at' => $now - 900],
                ['ref' => 'TD-2B8L4N7X', 'user' => 'علی محمدی', 'plan' => '1m', 'amount' => 429_000, 'status' => 'success', 'at' => $now - 5400],
                ['ref' => 'TD-9C1D6R5P', 'user' => 'نگار رضایی', 'plan' => '12m', 'amount' => 3_290_000, 'status' => 'failed', 'at' => $now - 9800],
                ['ref' => 'TD-4H7T2V8K', 'user' => 'رضا نوری', 'plan' => '6m', 'amount' => 1_890_000, 'status' => 'success', 'at' => $now - 21000],
                ['ref' => 'TD-6J3W9Z1B', 'user' => 'ندا فرهادی', 'plan' => '1m', 'amount' => 429_000, 'status' => 'cancelled', 'at' => $now - 40000],
            ],
            'top_songs' => [
                ['id' => 3, 'title' => 'Perfect', 'artist' => 'Ed Sheeran', 'cover' => null, 'plays' => 2140, 'completion' => 68, 'url' => '/admin/songs/3'],
                ['id' => 7, 'title' => 'Say My Name', 'artist' => 'David Guetta', 'cover' => null, 'plays' => 1760, 'completion' => 54, 'url' => '/admin/songs/7'],
                ['id' => 9, 'title' => 'Count on Me', 'artist' => 'Bruno Mars', 'cover' => null, 'plays' => 1495, 'completion' => 71, 'url' => '/admin/songs/9'],
                ['id' => 12, 'title' => 'Someone Like You', 'artist' => 'Adele', 'cover' => null, 'plays' => 1203, 'completion' => 49, 'url' => '/admin/songs/12'],
            ],
            'health' => [
                'songs' => ['published' => 38, 'drafts' => 6, 'incomplete' => 4],
                'questions' => ['A1' => 46, 'A2' => 42, 'B1' => 31, 'B2' => 18, 'C1' => 9, 'min_per_level' => 30],
                'words' => ['total' => 1240, 'no_image' => 903],
            ],
        ];
    }
}
