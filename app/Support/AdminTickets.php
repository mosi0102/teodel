<?php

namespace App\Support;

/**
 * تیکت‌ها در پنل مدیر: لیست /admin/tickets و گفتگو /admin/tickets/{id}. سمت کاربر: app/Support/Tickets.php (همان status/dept).
 *
 * 🔌 بک‌اند — لیست: GET /admin/tickets?q=&status=&dept=&user=&sort=&page=
 *   q       جست‌وجو در کد، موضوع، نام و موبایل/ایمیل کاربر      user  فقط تیکت‌های این کاربر (از صفحه‌ی کاربر)
 *   status  all | open (منتظر پاسخ پشتیبانی) | answered (نوبت کاربر) | closed
 *   dept    all | technical | payment | content | account | suggestion
 *   sort    waiting (پیش‌فرض: قدیمی‌ترین «منتظر پاسخ» اول) | newest
 *   ← کنترلر query() را با Paginator واقعی عوض می‌کند. هر تیکت:
 *     id, code, subject, dept, status, user { id, name, contact, color, plan:'free'|'1m'|'6m'|'12m', level? }، created_at, updated_at
 *     messages [ { id, from:'user'|'support'|'note', name?, text, at, files:[{name,url,size?}] } ]   ('note' = یادداشت داخلی؛ کاربر نمی‌بیند)
 *
 * اقدام‌ها (صفحه‌ی گفتگو؛ آدرس‌ها روی ریشه‌ی صفحه با __ID__ = شناسه‌ی تیکت؛ همه CSRF؛ جواب JSON):
 *   data-reply-url   POST multipart { message, files[], internal:0|1, close:0|1 } ← { message:{…همان مدل پیام…}, status }
 *                    internal=1 یعنی یادداشت داخلی (وضعیت تیکت عوض نمی‌شود)؛ پاسخ عادی وضعیت را answered می‌کند؛ close=1 هم می‌بندد.
 *   data-close-url   POST ← { status:'closed' }        data-reopen-url  POST ← { status:'open' }
 *   خطای اعتبارسنجی: 422 { message, errors:{ message:['…'] } }.
 *   یا رویدادهای لغوشدنی admin:ticket-reply { ticket, payload, done(message,status), fail(msg) } و admin:ticket-status { ticket, to, done(), fail(msg) }.
 *   قوانین فرانت (سرور هم بررسی کند): پیام ۱ تا ۳۰۰۰ نویسه، حداکثر ۳ فایل، هر کدام تا ۲ مگابایت.
 *
 * پاسخ‌های آماده: canned() — [ {id,title,body} ] (بک‌اند می‌تواند از جدول خودش بدهد؛ {name} در متن با نام کاربر جایگزین می‌شود).
 * AI: kind 'ticket_reply' seeds { subject, last_message } fields ['reply'] ← { fields:{ reply:'…' }, missing:[] } (پیش‌نویس پاسخ؛ مدیر حتماً می‌خواند و ارسال می‌کند).
 */
class AdminTickets
{
    public const PER_PAGE = 10;

    public const DEPTS = ['technical', 'payment', 'content', 'account', 'suggestion'];

    public const STATUSES = ['open', 'answered', 'closed'];

    public static function canned(): array
    {
        return [
            ['id' => 'thanks', 'title' => 'سلام و تشکر', 'body' => "سلام {name} عزیز، وقت بخیر.\nممنون که با ما در تماس بودید. موضوع را بررسی می‌کنیم و نتیجه را همین‌جا به شما اطلاع می‌دهیم."],
            ['id' => 'need_info', 'title' => 'درخواست اطلاعات بیشتر', 'body' => "سلام {name} عزیز.\nبرای بررسی دقیق‌تر لطفاً یک اسکرین‌شات از مشکل، نوع دستگاه و مرورگر خود را برایمان بفرستید."],
            ['id' => 'payment_ok', 'title' => 'پرداخت بررسی شد', 'body' => "سلام {name} عزیز.\nپرداخت شما را بررسی کردیم و اشتراک شما فعال شد. اگر مراحل هنوز قفل است یک بار از حساب خارج و دوباره وارد شوید."],
            ['id' => 'fixed', 'title' => 'مشکل برطرف شد', 'body' => "سلام {name} عزیز.\nمشکل گزارش‌شده برطرف شد. ممنون از دقت شما؛ اگر دوباره دیدید همین‌جا خبر بدهید."],
            ['id' => 'suggestion', 'title' => 'پیشنهاد ثبت شد', 'body' => "سلام {name} عزیز.\nمرسی از پیشنهادتان؛ برای بررسی به تیم محتوا منتقل شد."],
        ];
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
        $planOf = fn ($u) => $u['plan_active']['plan'] ?? 'free';
        // [subject, dept, status, پیام کاربر, پاسخ پشتیبانی|null, ساعتِ پیش از آخرین فعالیت]
        $seed = [
            ['پخش صدا در مرحله‌ی shadowing قطع و وصل می‌شود', 'technical', 'answered', "سلام. در مرحله‌ی shadowing وقتی دکمه‌ی پخش را می‌زنم صدا چند ثانیه می‌آید و قطع می‌شود. گوشی من سامسونگ و مرورگر کروم است.", 'سلام، ممنون از گزارش. لطفاً یک بار کش مرورگر را پاک کنید و دوباره امتحان کنید؛ اگر ادامه داشت خبر بدهید.', 2],
            ['تمدید اشتراک انجام شد ولی هنوز فعال نشده', 'payment', 'open', "پرداخت من موفق بود و پیامک بانک هم آمد، اما هنوز مراحل بعد از دوم قفل است. کد پیگیری: TD-4CAB002C", null, 5],
            ['ترجمه‌ی یکی از لغت‌های آهنگ اشتباه است', 'content', 'answered', "در آهنگ step by step in time ترجمه‌ی کلمه‌ی «grace» در معنای این جمله درست نیست.", 'ممنون از دقتتان! بررسی کردیم و اصلاح شد.', 72],
            ['تغییر شماره‌ی موبایل حساب', 'account', 'closed', "می‌خواهم شماره‌ی موبایلم را عوض کنم ولی در پروفایل فقط نمایش داده می‌شود.", 'انجام شد و تیکت بسته می‌شود. موفق باشید!', 430],
            ['پیشنهاد: افزودن موزیک‌های بیشتر سطح B', 'suggestion', 'closed', 'اگر آهنگ‌های سطح B بیشتری داشته باشید عالی می‌شود.', 'ممنون از پیشنهادتان. به تیم محتوا منتقل شد.', 960],
            ['کد تخفیف اعمال نمی‌شود', 'payment', 'open', 'در صفحه‌ی خرید کد تخفیف دوستم را زدم ولی قیمت عوض نشد.', null, 28],
            ['نمره‌ی آزمون تعیین سطح اشتباه ثبت شده', 'technical', 'open', 'آزمون را کامل دادم ولی سطح من A1 نشان می‌دهد در حالی که بیشتر جواب‌ها را درست داده بودم.', null, 9],
            ['فایل صوتی یکی از آهنگ‌ها پخش نمی‌شود', 'technical', 'answered', 'آهنگ Yesterday در مرحله‌ی کارائوکه هیچ صدایی ندارد.', 'مشکل از فایل صوتی بود و تعویض شد. لطفاً صفحه را تازه کنید.', 40],
            ['حذف حساب کاربری', 'account', 'closed', 'می‌خواهم حسابم را حذف کنم.', 'حساب شما طبق درخواست حذف شد.', 1500],
            ['لغت‌های ذخیره‌شده‌ام پاک شده‌اند', 'technical', 'open', 'بعد از ورود از گوشی دیگر لیست لغت‌هایم خالی است.', null, 52],
            ['سؤال درباره‌ی مدت اعتبار اشتراک شش‌ماهه', 'payment', 'answered', 'اشتراک ۶ ماهه دقیقاً از چه روزی حساب می‌شود؟', 'از لحظه‌ی پرداخت؛ تاریخ پایان را در صفحه‌ی اشتراک می‌بینید.', 18],
            ['پیشنهاد حالت تاریک', 'suggestion', 'open', 'لطفاً حالت تاریک هم اضافه کنید.', null, 120],
            ['تلفظ لغت schedule در دیکشنری اشتباه است', 'content', 'open', 'تلفظ US لغت «schedule» در دیکشنری با تلفظ UK یکی است.', null, 14],
        ];
        $out = [];
        foreach ($seed as $i => [$subject, $dept, $status, $text, $reply, $ago]) {
            $u = $users[($i * 3 + 1) % count($users)];
            $last = $now - $ago * $h;
            $first = $last - ($reply ? 2 * $h : 0);
            $msgs = [['id' => 1, 'from' => 'user', 'name' => '', 'text' => $text, 'at' => $first, 'files' => $i % 4 === 1 ? [['name' => 'screenshot.png', 'url' => 'https://example.com/f/'.$i.'.png', 'size' => 184320]] : []]];
            if ($reply) {
                $msgs[] = ['id' => 2, 'from' => 'support', 'name' => $i % 2 ? 'امیر از پشتیبانی' : 'سارا از پشتیبانی', 'text' => $reply, 'at' => $last, 'files' => []];
            }
            if ($i === 2) {
                $msgs[] = ['id' => 3, 'from' => 'note', 'name' => 'سارا از پشتیبانی', 'text' => 'ترجمه‌ی لغت در دیکشنری اصلاح شد؛ به تیم محتوا هم خبر دادم.', 'at' => $last + 600, 'files' => []];
            }
            $out[] = [
                'id' => 1100 - $i * 7, 'code' => 'T-'.(1100 - $i * 7), 'subject' => $subject, 'dept' => $dept, 'status' => $status,
                'user' => ['id' => $u['id'], 'name' => $u['name'], 'contact' => $u['contact'], 'color' => $u['color'], 'plan' => $planOf($u), 'level' => $u['level']],
                'created_at' => $first, 'updated_at' => $last, 'messages' => $msgs,
            ];
        }

        return $cache = $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $t) {
            if ($t['id'] === $id) {
                return $t;
            }
        }

        return null;
    }

    /** تیکت‌های دیگر همین کاربر (برای ستون کناری) */
    public static function ofUser(int $userId, int $except = 0): array
    {
        return array_values(array_filter(self::all(), fn ($t) => $t['user']['id'] === $userId && $t['id'] !== $except));
    }

    /** @return array{rows:array,total:int,page:int,pages:int,per:int,counts:array} */
    public static function query(array $p = []): array
    {
        $q = trim((string) ($p['q'] ?? ''));
        $status = (string) ($p['status'] ?? 'all');
        $dept = (string) ($p['dept'] ?? 'all');
        $user = (int) ($p['user'] ?? 0);
        $sort = (string) ($p['sort'] ?? 'waiting');
        $page = max(1, (int) ($p['page'] ?? 1));
        $all = self::all();

        $counts = ['all' => count($all), 'open' => 0, 'answered' => 0, 'closed' => 0];
        foreach ($all as $t) {
            $counts[$t['status']]++;
        }
        $rows = array_values(array_filter($all, function ($t) use ($q, $status, $dept, $user) {
            if ($q !== '' && mb_stripos($t['code'].' '.$t['subject'].' '.$t['user']['name'].' '.$t['user']['contact'], $q) === false) {
                return false;
            }

            return ($status === 'all' || $t['status'] === $status) && ($dept === 'all' || $t['dept'] === $dept) && (! $user || $t['user']['id'] === $user);
        }));
        // «منتظر پاسخ» ها اول و قدیمی‌ترین‌شان بالاتر؛ بعد بقیه جدیدترین اول
        usort($rows, function ($a, $b) use ($sort) {
            if ($sort === 'waiting') {
                $wa = $a['status'] === 'open' ? 0 : 1;
                $wb = $b['status'] === 'open' ? 0 : 1;
                if ($wa !== $wb) {
                    return $wa <=> $wb;
                }
                if ($wa === 0) {
                    return $a['updated_at'] <=> $b['updated_at'];
                }
            }

            return $b['updated_at'] <=> $a['updated_at'];
        });

        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        return ['rows' => array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => self::PER_PAGE, 'counts' => $counts];
    }
}
