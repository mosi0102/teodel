<?php

namespace App\Support;

/**
 * اشتراک و پرداخت‌ها در پنل مدیر: /admin/billing (پرداخت‌ها) و /admin/billing/plans (قیمت پلن‌ها).
 *
 * 🔌 بک‌اند — پرداخت‌ها: GET /admin/billing?q=&status=&plan=&range=&sort=&page=
 *   q       جست‌وجو در کد پیگیری، شماره‌ی مرجع بانک، نام و موبایل/ایمیل کاربر
 *   status  all | success | failed | cancelled | pending | refunded
 *   plan    all | 1m | 6m | 12m
 *   range   all | today | 7d | 30d
 *   sort    newest | amount
 *   ← کنترلر query() را با Paginator واقعی عوض می‌کند. هر پرداخت:
 *     id, ref (کد پیگیری سایت)، user { id, name, contact, color? }, plan ('1m'|'6m'|'12m')، amount (تومان)،
 *     status, gateway (نام درگاه)، bank_ref (اختیاری)، message (دلیل خطا؛ فقط failed)، at (ثانیه‌ی یونیکس)،
 *     ends_at (پایان اشتراک؛ فقط success/refunded)، events [ { at, key: created|redirected|verified|failed|cancelled|refunded, note? } ]
 *   summary(): { revenue_month, prev_month, success, failed, cancelled, pending, fail_rate (درصد) } برای کارت‌های بالا.
 *
 * اقدام‌ها (دیالوگ جزئیات؛ همه با CSRF و JSON؛ آدرس روی ریشه‌ی صفحه: data-action-url با __ID__):
 *   POST { action: 'verify'|'refund', reason }  ← 200 { ok:true, status }  | 4xx { message }
 *     verify  پرداختِ pending/failed که پول از کاربر کم شده را دستی «موفق» می‌کند و اشتراک را فعال می‌کند (سرور باید حتماً از بانک استعلام بگیرد).
 *     refund  پرداخت موفق را «بازپرداخت‌شده» می‌کند و اشتراک را لغو می‌کند.
 *   یا رویداد لغوشدنی admin:payment-action { action, payment, payload, done(status), fail(msg) }.
 * خروجی CSV: اگر data-export-url بدهید دکمه به آن آدرس (با همان فیلترها) می‌رود؛ وگرنه فقط ردیف‌های همین صفحه را CSV می‌کند.
 *
 * 🔌 پلن‌ها: GET /admin/billing/plans ← plans() با کلیدهای Subscription::plans() + active (bool) + subscribers (تعداد فعال)
 *   ذخیره: PUT data-save-url { plans:[ { id, price, old_price|null, recommended, active } ] }  ← 200 { ok:true } | 422 { errors, message }
 *   پلن free ثابت است. قیمت نهایی را هم سرور حساب می‌کند؛ checkout کاربر همیشه از جدول پلن‌ها قیمت می‌گیرد.
 */
class AdminBilling
{
    public const PER_PAGE = 10;

    public const STATUSES = ['success', 'failed', 'cancelled', 'pending', 'refunded'];

    public const PAID_PLANS = ['1m', '6m', '12m'];

    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $now = time();
        $users = AdminUsers::all();
        $months = ['1m' => 1, '6m' => 6, '12m' => 12];
        $out = [];
        for ($i = 0; $i < 46; $i++) {
            $u = $users[($i * 5) % count($users)];
            $plan = self::PAID_PLANS[($i * 7 + intdiv($i, 3)) % 3];
            $price = Subscription::find($plan)['price'];
            // بیشتر موفق؛ چند ناموفق/انصراف/در انتظار/بازپرداخت
            $status = match (true) {
                $i % 11 === 4 => 'failed',
                $i % 13 === 6 => 'cancelled',
                $i === 1 || $i === 17 => 'pending',
                $i === 9 || $i === 30 => 'refunded',
                default => 'success',
            };
            $at = $now - ($i * 6 * 3600 + ($i % 5) * 1800 + 900);
            $ref = 'TD-'.strtoupper(substr(md5('pay'.$i), 0, 8));
            $bank = $status === 'cancelled' ? null : (string) (730000000 + $i * 9137);
            $events = [['at' => $at - 60, 'key' => 'created'], ['at' => $at - 40, 'key' => 'redirected']];
            if ($status === 'success') {
                $events[] = ['at' => $at, 'key' => 'verified'];
            } elseif ($status === 'refunded') {
                $events[] = ['at' => $at, 'key' => 'verified'];
                $events[] = ['at' => $at + 86400, 'key' => 'refunded', 'note' => 'درخواست کاربر؛ پرداخت تکراری'];
            } elseif ($status === 'failed') {
                $events[] = ['at' => $at, 'key' => 'failed'];
            } elseif ($status === 'cancelled') {
                $events[] = ['at' => $at, 'key' => 'cancelled'];
            }
            $out[] = [
                'id' => 5000 + $i,
                'ref' => $ref,
                'user' => ['id' => $u['id'], 'name' => $u['name'], 'contact' => $u['contact'], 'color' => $u['color']],
                'plan' => $plan,
                'amount' => $price,
                'status' => $status,
                'gateway' => 'زرین‌پال',
                'bank_ref' => $bank,
                'message' => $status === 'failed' ? 'موجودی حساب کافی نیست' : null,
                'at' => $at,
                'ends_at' => in_array($status, ['success', 'refunded'], true) ? $at + $months[$plan] * 30 * 86400 : null,
                'events' => $events,
            ];
        }

        return $cache = $out;
    }

    /** @return array{rows:array,total:int,page:int,pages:int,per:int,counts:array} */
    public static function query(array $p = []): array
    {
        $q = trim((string) ($p['q'] ?? ''));
        $status = (string) ($p['status'] ?? 'all');
        $plan = (string) ($p['plan'] ?? 'all');
        $range = (string) ($p['range'] ?? 'all');
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

        $since = match ($range) {
            'today' => strtotime('today'),
            '7d' => time() - 7 * 86400,
            '30d' => time() - 30 * 86400,
            default => 0,
        };
        $rows = array_values(array_filter($all, function ($r) use ($q, $status, $plan, $since) {
            if ($q !== '' && mb_stripos($r['ref'].' '.($r['bank_ref'] ?? '').' '.$r['user']['name'].' '.$r['user']['contact'], $q) === false) {
                return false;
            }

            return ($status === 'all' || $r['status'] === $status) && ($plan === 'all' || $r['plan'] === $plan) && $r['at'] >= $since;
        }));
        usort($rows, fn ($a, $b) => $sort === 'amount' ? $b['amount'] <=> $a['amount'] : $b['at'] <=> $a['at']);

        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        return [
            'rows' => array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => self::PER_PAGE, 'counts' => $counts,
        ];
    }

    public static function summary(): array
    {
        $all = self::all();
        $now = time();
        $sum = ['revenue_month' => 0, 'prev_month' => 0, 'success' => 0, 'failed' => 0, 'cancelled' => 0, 'pending' => 0, 'fail_rate' => 0];
        foreach ($all as $r) {
            if ($r['status'] === 'success') {
                $sum['success']++;
                if ($r['at'] >= $now - 30 * 86400) {
                    $sum['revenue_month'] += $r['amount'];
                } elseif ($r['at'] >= $now - 60 * 86400) {
                    $sum['prev_month'] += $r['amount'];
                }
            } elseif (isset($sum[$r['status']])) {
                $sum[$r['status']]++;
            }
        }
        $tried = $sum['success'] + $sum['failed'];
        $sum['fail_rate'] = $tried ? (int) round($sum['failed'] / $tried * 100) : 0;

        return $sum;
    }

    /** پلن‌ها برای صفحه‌ی قیمت‌گذاری (همان plans() کاربر + فعال بودن و تعداد مشترک فعال) */
    public static function plans(): array
    {
        $subs = ['1m' => 318, '6m' => 224, '12m' => 70];

        return array_map(fn ($p) => $p + ['active' => true, 'subscribers' => $subs[$p['id']] ?? 0], Subscription::plans());
    }
}
