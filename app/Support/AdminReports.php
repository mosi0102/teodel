<?php

namespace App\Support;

/**
 * گزارش خطا در پنل مدیر: لیست /admin/reports و جزئیات /admin/reports/{id}.
 * سمت کاربر: دکمه‌ی «گزارش خرابی» (resources/js/modules/report.js) که { description, screenshot, meta } می‌فرستد؛ قرارداد آن در README («گزارش خرابی»).
 *
 * 🔌 بک‌اند — لیست: GET /admin/reports?q=&status=&area=&priority=&sort=&page=
 *   q         جست‌وجو در کد، شرح، نشانی صفحه، نام و موبایل/ایمیل کاربر
 *   status    all | new | in_progress | resolved | rejected
 *   area      all | lesson | words | dictionary | level_test | subscription | profile | dashboard | other   (از مسیر meta.url حدس زده می‌شود: area())
 *   priority  all | low | normal | high        sort  newest (پیش‌فرض) | oldest | priority
 *   ← کنترلر query() را با Paginator واقعی عوض می‌کند. هر گزارش:
 *     id, code ('R-311')، status, priority, description، screenshot (آدرس عکس یا null — اسکرین‌شات کاربر را سرور ذخیره کند و آدرس بدهد؛
 *     نگه‌داشتن data:URL در دیتابیس سنگین است)، meta { url, locale, userAgent, viewport:'390x844', sentAt (ISO یا ثانیه) }،
 *     user { id, name, contact, color, plan }، created_at, updated_at، notes [ { id, by, text, at } ]  (یادداشت داخلی؛ کاربر نمی‌بیند)
 *
 * اقدام‌ها (صفحه‌ی جزئیات؛ آدرس‌ها روی ریشه‌ی صفحه با __ID__ = شناسه؛ همه CSRF و JSON):
 *   data-update-url  POST { status?, priority?, notify? }  ← 200 { status, priority } | 4xx { message }
 *                    notify=true یعنی با «حل شد/رد شد» به کاربر اعلان برود (فقط وقتی status عوض می‌شود).
 *   data-note-url    POST { text }  ← 200 { note:{ id, by, text, at } } | 422 { message }
 *   یا رویدادهای لغوشدنی admin:report-update { report, patch, notify, done(res), fail(msg) } و admin:report-note { report, text, done(note), fail(msg) }.
 *
 * ⚠️ اسکرین‌شات را مثل ورودی غیرقابل‌اعتماد ببینید: فقط image/jpeg|png|webp، حداکثر چند صد کیلوبایت، با نام تصادفی و Content-Type درست سرو شود.
 */
class AdminReports
{
    public const PER_PAGE = 10;

    public const STATUSES = ['new', 'in_progress', 'resolved', 'rejected'];

    public const PRIORITIES = ['low', 'normal', 'high'];

    public const AREAS = ['lesson', 'words', 'dictionary', 'level_test', 'subscription', 'profile', 'dashboard', 'other'];

    /** بخش سایت از روی مسیر صفحه */
    public static function area(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $map = ['/music' => 'lesson', '/words' => 'words', '/dictionary' => 'dictionary', '/level-test' => 'level_test', '/subscription' => 'subscription', '/profile' => 'profile', '/dashboard' => 'dashboard'];
        foreach ($map as $prefix => $area) {
            if (str_starts_with($path, $prefix)) {
                return $area;
            }
        }

        return 'other';
    }

    /** @return array{os:string,browser:string,mobile:bool} */
    public static function device(string $ua): array
    {
        $os = match (true) {
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => '—',
        };
        $browser = match (true) {
            (bool) preg_match('/Edg\//', $ua) => 'Edge',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => '—',
        };

        return ['os' => $os, 'browser' => $browser, 'mobile' => (bool) preg_match('/Android|iPhone|iPad|Mobile/i', $ua)];
    }

    /** اسکرین‌شات ساختگی (SVG) فقط برای دیدن فرانت؛ ناحیه‌ی قرمز = جای خطا */
    private static function mock(int $i, bool $mobile): string
    {
        $hue = [222, 262, 200, 340, 160, 30, 280][$i % 7];
        [$w, $h] = $mobile ? [360, 640] : [960, 600];
        $rows = '';
        for ($k = 0; $k < 6; $k++) {
            $rows .= '<rect x="24" y="'.(150 + $k * 62).'" width="'.($w - 48 - ($k % 3) * 60).'" height="14" rx="7" fill="#dfe3ee"/><rect x="24" y="'.(172 + $k * 62).'" width="'.(($w - 48) * 0.55).'" height="10" rx="5" fill="#eceff6"/>';
        }
        $bx = (int) ($w * (0.15 + ($i % 4) * 0.12));
        $by = 190 + ($i % 3) * 60;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'"><rect width="'.$w.'" height="'.$h.'" fill="#f7f8fc"/>'
            .'<rect width="'.$w.'" height="64" fill="hsl('.$hue.',70%,48%)"/><rect x="24" y="24" width="120" height="16" rx="8" fill="#fff" opacity=".85"/>'
            .'<rect x="24" y="84" width="'.($w - 48).'" height="46" rx="14" fill="#fff"/>'.$rows
            .'<rect x="'.$bx.'" y="'.$by.'" width="'.(int) ($w * 0.5).'" height="58" rx="14" fill="none" stroke="#ef4444" stroke-width="4" stroke-dasharray="10 6"/></svg>';

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }

    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $now = time();
        $h = 3600;
        $users = AdminUsers::all();
        $ua = [
            'android' => 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
            'ios' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'win' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
            'mac' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
        ];
        // [شرح، مسیر، status، priority، ua، viewport، شات دارد؟، ساعتِ پیش]
        $seed = [
            ['کاراوکه: متن با آهنگ هم‌زمان نیست و حدود دو ثانیه عقب می‌ماند', '/music/12/lesson?step=karaoke', 'new', 'high', 'android', '412x915', true, 1],
            ['ترجمه‌ی خط ۱۲ لیریک اشتباه است (معنی «grace» نادرست)', '/music/7/lesson?step=lyrics', 'new', 'normal', 'ios', '390x844', true, 7],
            ['دکمه‌ی پخش در مرحله‌ی shadowing کار نمی‌کند', '/music/3/lesson?step=shadowing', 'in_progress', 'high', 'android', '360x800', true, 20],
            ['در صفحه‌ی خرید بعد از انتخاب پلن مودال بسته نمی‌شود', '/subscription', 'new', 'high', 'win', '1366x768', true, 26],
            ['تلفظ US لغت schedule با UK یکی است', '/dictionary', 'in_progress', 'low', 'mac', '1440x900', false, 40],
            ['سؤال ۱۴ آزمون تعیین سطح دو پاسخ درست دارد', '/level-test', 'in_progress', 'normal', 'ios', '390x844', true, 52],
            ['لغت‌های ذخیره‌شده بعد از ورود از دستگاه دیگر خالی است', '/words', 'new', 'normal', 'android', '412x915', false, 60],
            ['آواتار انتخاب‌شده در پروفایل ذخیره نمی‌شود', '/profile', 'resolved', 'normal', 'win', '1920x1080', true, 100],
            ['نمودار داشبورد روی موبایل از صفحه بیرون می‌زند', '/dashboard', 'resolved', 'low', 'android', '360x800', true, 130],
            ['عنوان آهنگ در لیست موزیک نصفه نمایش داده می‌شود', '/music', 'rejected', 'low', 'ios', '375x667', true, 170],
            ['صدای مرحله‌ی شنیداری در سافاری پخش نمی‌شود', '/music/5/lesson?step=listening', 'in_progress', 'high', 'ios', '390x844', false, 200],
            ['کد تخفیف در صفحه‌ی خرید اعمال نمی‌شود', '/subscription', 'resolved', 'normal', 'mac', '1440x900', true, 260],
            ['فونت فارسی در مرحله‌ی جزئیات خوانا نیست', '/music/9/lesson?step=details', 'rejected', 'low', 'win', '1366x768', true, 320],
            ['بعد از پایان آزمون صفحه‌ی نتیجه خالی می‌ماند', '/level-test', 'new', 'normal', 'android', '393x851', true, 3],
        ];
        $out = [];
        foreach ($seed as $i => [$desc, $path, $status, $priority, $uak, $vp, $shot, $ago]) {
            $u = $users[($i * 4 + 2) % count($users)];
            $at = $now - $ago * $h - ($i % 3) * 600;
            $meta = ['url' => 'https://teodel.test'.$path, 'locale' => $i % 5 === 3 ? 'en' : 'fa', 'userAgent' => $ua[$uak], 'viewport' => $vp, 'sentAt' => date('c', $at)];
            $mobile = self::device($ua[$uak])['mobile'];
            $notes = [];
            if ($status === 'in_progress') {
                $notes[] = ['id' => 1, 'by' => 'سارا از پشتیبانی', 'text' => 'مشکل را روی دستگاه خودم هم دیدم؛ به تیم فنی منتقل شد.', 'at' => $at + 2 * $h];
            }
            if ($status === 'resolved') {
                $notes[] = ['id' => 1, 'by' => 'امیر از پشتیبانی', 'text' => 'در نسخه‌ی امروز برطرف شد.', 'at' => $at + 5 * $h];
            }
            if ($status === 'rejected') {
                $notes[] = ['id' => 1, 'by' => 'امیر از پشتیبانی', 'text' => 'قابل بازتولید نبود؛ مشکل از مرورگر کاربر بوده است.', 'at' => $at + 3 * $h];
            }
            $out[] = [
                'id' => 311 - $i * 2, 'code' => 'R-'.(311 - $i * 2), 'status' => $status, 'priority' => $priority, 'description' => $desc,
                'screenshot' => $shot ? self::mock($i, $mobile) : null, 'meta' => $meta,
                'user' => ['id' => $u['id'], 'name' => $u['name'], 'contact' => $u['contact'], 'color' => $u['color'], 'plan' => $u['plan_active']['plan'] ?? 'free'],
                'created_at' => $at, 'updated_at' => $notes ? end($notes)['at'] : $at, 'notes' => $notes,
            ];
        }

        return $cache = $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $r) {
            if ($r['id'] === $id) {
                return $r;
            }
        }

        return null;
    }

    /** گزارش قبلی/بعدی در همان ترتیب «جدیدترین» (برای پیمایش سریع) @return array{prev:?array,next:?array} */
    public static function neighbors(int $id): array
    {
        $all = self::all();
        usort($all, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);
        foreach ($all as $i => $r) {
            if ($r['id'] === $id) {
                return ['prev' => $all[$i - 1] ?? null, 'next' => $all[$i + 1] ?? null];
            }
        }

        return ['prev' => null, 'next' => null];
    }

    public static function ofUser(int $userId, int $except = 0): array
    {
        return array_values(array_filter(self::all(), fn ($r) => $r['user']['id'] === $userId && $r['id'] !== $except));
    }

    /** @return array{rows:array,total:int,page:int,pages:int,per:int,counts:array} */
    public static function query(array $p = []): array
    {
        $q = trim((string) ($p['q'] ?? ''));
        $status = (string) ($p['status'] ?? 'all');
        $area = (string) ($p['area'] ?? 'all');
        $priority = (string) ($p['priority'] ?? 'all');
        $sort = (string) ($p['sort'] ?? 'newest');
        $page = max(1, (int) ($p['page'] ?? 1));
        $all = self::all();

        $counts = ['all' => count($all)];
        foreach (self::STATUSES as $s) {
            $counts[$s] = 0;
        }
        foreach ($all as $r) {
            $counts[$r['status']]++;
        }
        $rows = array_values(array_filter($all, function ($r) use ($q, $status, $area, $priority) {
            if ($q !== '' && mb_stripos($r['code'].' '.$r['description'].' '.$r['meta']['url'].' '.$r['user']['name'].' '.$r['user']['contact'], $q) === false) {
                return false;
            }

            return ($status === 'all' || $r['status'] === $status) && ($priority === 'all' || $r['priority'] === $priority) && ($area === 'all' || self::area($r['meta']['url']) === $area);
        }));
        $rank = ['high' => 0, 'normal' => 1, 'low' => 2];
        usort($rows, fn ($a, $b) => match ($sort) {
            'oldest' => $a['created_at'] <=> $b['created_at'],
            'priority' => ($rank[$a['priority']] <=> $rank[$b['priority']]) ?: ($b['created_at'] <=> $a['created_at']),
            default => $b['created_at'] <=> $a['created_at'],
        });

        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        return ['rows' => array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => self::PER_PAGE, 'counts' => $counts];
    }
}
