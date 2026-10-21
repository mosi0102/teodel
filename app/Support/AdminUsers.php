<?php

namespace App\Support;

/**
 * مدیریت کاربران (/admin/users و /admin/users/{id}).
 *
 * 🔌 بک‌اند — لیست: GET /admin/users?q=&plan=&sort=&page=
 *   q     جست‌وجو در نام، ایمیل/موبایل و کد معرف
 *   plan  all | free | paid | expired | blocked
 *   sort  newest | score | seen
 *   ← کنترلر query() را با یک Paginator واقعی عوض می‌کند؛ ویو فقط این کلیدها را از هر کاربر می‌خواند:
 *
 *   id, name, contact (ایمیل یا موبایل), color? (رنگ آواتار), status:'active'|'blocked',
 *   score (امتیاز کل), referral_code, referred_by? ({id,name}), referrals (تعداد دعوت‌شده‌ها),
 *   registered_at, last_seen (ثانیه‌ی یونیکس یا null)،
 *   level ('A1'..'C1' یا null — میانگین آزمون‌ها),
 *   tests { vocab, grammar, listening }: هرکدام { score (۰ تا ۱۰۰), level, at } یا null (هنوز نداده)،
 *   plan_active { plan:'1m'|'6m'|'12m', started_at, ends_at, days_left } یا null،
 *   plan_prev   { plan, started_at, ended_at } یا null (آخرین اشتراکی که تمام شده)،
 *   songs_done, words_saved, streak, tickets_open
 *
 * جزئیات (show): همان کلیدها + history[] (همه‌ی خریدها: ref, plan, amount, status, at) و notes (یادداشت مدیر).
 *
 * اقدام‌ها (از صفحه‌ی جزئیات، همه با CSRF و JSON — آدرس‌ها روی ریشه‌ی صفحه: data-block-url و data-grant-url):
 *   POST data-block-url  { blocked: true|false, reason? }   ← 200 { ok:true }
 *   POST data-grant-url  { days: 1..365, reason? }          ← 200 { ok:true, ends_at }   (هدیه‌ی روز اشتراک)
 *   رویداد لغوشدنی: admin:user-action { action:'block'|'grant', user, payload, done(), fail(msg) }
 */
class AdminUsers
{
    public const PER_PAGE = 10;

    public static function all(): array
    {
        $now = time();
        $d = 86400;
        $names = [
            ['سارا کریمی', 'sara.k@example.com'], ['علی محمدی', '0912 345 6789'], ['نگار رضایی', 'negar.r@example.com'], ['رضا نوری', '0935 111 2233'],
            ['ندا فرهادی', 'neda.f@example.com'], ['مهدی حسینی', 'mahdi.h@example.com'], ['الهام صادقی', '0990 222 3344'], ['پارسا احمدی', 'parsa.a@example.com'],
            ['مریم کاظمی', 'maryam.k@example.com'], ['امیر جعفری', '0919 876 5432'], ['فاطمه موسوی', 'fatemeh.m@example.com'], ['حسین تقوی', '0937 654 3210'],
            ['زهرا اکبری', 'zahra.a@example.com'], ['کیان رحیمی', 'kian.r@example.com'], ['آرزو نظری', '0914 222 7788'], ['بهراد ملکی', 'behrad.m@example.com'],
            ['شیدا قاسمی', 'shida.g@example.com'], ['محسن زارعی', '0921 333 4455'], ['سحر یوسفی', 'sahar.y@example.com'], ['دانیال پاکزاد', 'danial.p@example.com'],
            ['لیلا عباسی', '0912 777 8899'], ['آرین شفیعی', 'arian.s@example.com'], ['نیلوفر حیدری', 'niloofar.h@example.com'], ['سینا کرمی', '0936 100 2000'],
            ['مینا باقری', 'mina.b@example.com'], ['وحید رستمی', 'vahid.r@example.com'],
        ];
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1'];
        $users = [];
        foreach ($names as $i => [$name, $contact]) {
            $id = 101 + $i;
            $hasTests = $i % 5 !== 4;
            $lvIdx = $i % 5;
            $t = fn ($off) => $hasTests && ($i + $off) % 7 !== 0 ? ['score' => 45 + (($i * 7 + $off * 13) % 50), 'level' => $levels[min(4, max(0, $lvIdx + ($off % 2) - ($off % 3 === 0 ? 1 : 0)))], 'at' => $now - (($i % 20) + $off) * $d] : null;
            $tests = ['vocab' => $t(1), 'grammar' => $t(2), 'listening' => $t(3)];
            $scores = array_filter(array_column(array_filter($tests), 'level'));
            $level = $scores ? $levels[(int) round(array_sum(array_map(fn ($l) => array_search($l, $levels), $scores)) / count($scores))] : null;

            $kind = $i % 6; // 0 رایگان، 1/2/3 پولی فعال، 4 منقضی، 5 رایگان + قبلاً پولی
            $plans = ['1m', '6m', '12m'];
            $active = null;
            $prev = null;
            if (in_array($kind, [1, 2, 3], true)) {
                $pl = $plans[$kind - 1];
                $months = ['1m' => 1, '6m' => 6, '12m' => 12][$pl];
                $start = $now - (($i * 9) % ($months * 20 + 5)) * $d;
                $active = ['plan' => $pl, 'started_at' => $start, 'ends_at' => $start + $months * 30 * $d, 'days_left' => max(1, (int) round(($start + $months * 30 * $d - $now) / $d))];
                if ($i % 2 === 0) {
                    $prev = ['plan' => '1m', 'started_at' => $start - 70 * $d, 'ended_at' => $start - 40 * $d];
                }
            } elseif ($kind >= 4) {
                $prev = ['plan' => $plans[$i % 3], 'started_at' => $now - 150 * $d, 'ended_at' => $now - (12 + $i) * $d];
            }

            $users[] = [
                'id' => $id,
                'name' => $name,
                'contact' => $contact,
                'color' => ($i * 47) % 360,
                'status' => $i === 7 ? 'blocked' : 'active',
                'score' => 120 + (($i * 211) % 2600),
                'referral_code' => 'TD'.strtoupper(substr(md5((string) $id), 0, 5)),
                'referred_by' => $i % 4 === 3 ? ['id' => 101 + ($i % 3), 'name' => $names[$i % 3][0]] : null,
                'referrals' => ($i * 3) % 7,
                'registered_at' => $now - (3 + $i * 9) * $d,
                'last_seen' => $i === 11 ? null : $now - (($i * 5400) % (40 * $d)),
                'level' => $level,
                'tests' => $tests,
                'plan_active' => $active,
                'plan_prev' => $prev,
                'songs_done' => ($i * 2) % 9,
                'words_saved' => ($i * 17) % 120,
                'streak' => ($i * 3) % 14,
                'tickets_open' => $i % 9 === 0 ? 1 : 0,
            ];
        }

        return $users;
    }

    /** وضعیت اشتراک برای فیلتر: paid | expired | free */
    public static function planState(array $u): string
    {
        return $u['plan_active'] ? 'paid' : ($u['plan_prev'] ? 'expired' : 'free');
    }

    /** @return array{rows:array,total:int,page:int,pages:int,per:int,counts:array} */
    public static function query(array $p = []): array
    {
        $q = trim((string) ($p['q'] ?? ''));
        $plan = (string) ($p['plan'] ?? 'all');
        $sort = (string) ($p['sort'] ?? 'newest');
        $page = max(1, (int) ($p['page'] ?? 1));
        $all = self::all();

        $counts = ['all' => count($all), 'free' => 0, 'paid' => 0, 'expired' => 0, 'blocked' => 0];
        foreach ($all as $u) {
            $counts[self::planState($u)]++;
            if ($u['status'] === 'blocked') {
                $counts['blocked']++;
            }
        }

        $rows = array_values(array_filter($all, function ($u) use ($q, $plan) {
            if ($q !== '' && mb_stripos($u['name'].' '.$u['contact'].' '.$u['referral_code'], $q) === false) {
                return false;
            }
            if ($plan === 'blocked') {
                return $u['status'] === 'blocked';
            }

            return $plan === 'all' || self::planState($u) === $plan;
        }));

        usort($rows, fn ($a, $b) => match ($sort) {
            'score' => $b['score'] <=> $a['score'],
            'seen' => ($b['last_seen'] ?? 0) <=> ($a['last_seen'] ?? 0),
            default => $b['registered_at'] <=> $a['registered_at'],
        });

        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        return [
            'rows' => array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => self::PER_PAGE, 'counts' => $counts,
        ];
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $u) {
            if ($u['id'] === $id) {
                $now = time();
                $d = 86400;
                $u['history'] = [];
                if ($u['plan_active']) {
                    $u['history'][] = ['ref' => 'TD-'.strtoupper(substr(md5($id.'a'), 0, 8)), 'plan' => $u['plan_active']['plan'], 'amount' => ['1m' => 429000, '6m' => 1890000, '12m' => 3290000][$u['plan_active']['plan']], 'status' => 'success', 'at' => $u['plan_active']['started_at']];
                }
                if ($u['plan_prev']) {
                    $u['history'][] = ['ref' => 'TD-'.strtoupper(substr(md5($id.'b'), 0, 8)), 'plan' => $u['plan_prev']['plan'], 'amount' => ['1m' => 429000, '6m' => 1890000, '12m' => 3290000][$u['plan_prev']['plan']], 'status' => 'success', 'at' => $u['plan_prev']['started_at']];
                }
                $u['history'][] = ['ref' => 'TD-'.strtoupper(substr(md5($id.'c'), 0, 8)), 'plan' => '6m', 'amount' => 1890000, 'status' => 'failed', 'at' => $now - 200 * $d];
                usort($u['history'], fn ($a, $b) => $b['at'] <=> $a['at']);
                $u['notes'] = '';

                return $u;
            }
        }

        return null;
    }
}
