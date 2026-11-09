<?php

namespace App\Support;

/**
 * ابزار مشترک ویرایشگرهای محتوای پنل مدیریت (پادکست، شدوئینگ، کتاب) — همان الگوی AdminSongs.
 *
 * «قانون کامل‌بودن» هر تب (برای نقطه‌ی سبز تب‌ها، نوار پیشرفت لیست و چک‌لیست «انتشار»):
 *   'title'         مقدار پر باشد
 *   'episodes[]'    آرایه خالی نباشد
 *   'chapters[]:text'  آرایه خالی نباشد و همه‌ی آیتم‌ها کلید text را داشته باشند
 *   '@versions'     قانون ویژه (در کلاس هر بخش و در resources/js/admin/content.js)
 * همین قانون‌ها با data-need به جاوااسکریپت داده می‌شوند تا هر دو یک نتیجه بدهند.
 */
class AdminContent
{
    public static function get(array $m, string $path)
    {
        foreach (explode('.', $path) as $k) {
            if (! is_array($m) || ! array_key_exists($k, $m)) {
                return null;
            }
            $m = $m[$k];
        }

        return $m;
    }

    public static function filled($v): bool
    {
        return is_array($v) ? count($v) > 0 : trim((string) $v) !== '';
    }

    /** @param array<string,array<int,string>> $need  تب ← قانون‌ها */
    public static function doneMap(array $model, array $need, array $special = []): array
    {
        $out = [];
        foreach ($need as $tab => $rules) {
            $ok = true;
            foreach ($rules as $r) {
                if (str_starts_with($r, '@')) {
                    $ok = $ok && (isset($special[$r]) ? (bool) $special[$r]($model) : true);
                } elseif (preg_match('/^(.+)\[\](?::(\w+))?$/', $r, $m)) {
                    $arr = self::get($model, $m[1]);
                    $ok = $ok && is_array($arr) && count($arr) > 0
                        && (! isset($m[2]) || collect($arr)->every(fn ($i) => self::filled($i[$m[2]] ?? '')));
                } else {
                    $ok = $ok && self::filled(self::get($model, $r));
                }
            }
            $out[$tab] = $ok;
        }

        return $out;
    }

    /**
     * فیلتر + مرتب‌سازی + صفحه‌بندی لیست‌های ادمین (q, level, status, sort, page).
     * @param  callable(array):string  $haystack
     */
    public static function query(array $rows, array $p, callable $haystack, int $per = 12): array
    {
        $q = mb_strtolower(trim((string) ($p['q'] ?? '')));
        $level = $p['level'] ?? 'all';
        $status = $p['status'] ?? 'all';
        $sort = $p['sort'] ?? 'newest';

        $all = collect($rows);
        $counts = ['all' => $all->count(), 'published' => $all->where('status', 'published')->count(), 'draft' => $all->where('status', 'draft')->count()];
        $list = $all
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($haystack($r)), $q)))
            ->when($level !== 'all', fn ($c) => $c->where('level', $level))
            ->when($status !== 'all', fn ($c) => $c->where('status', $status));
        $list = match ($sort) {
            'title' => $list->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            'progress' => $list->sortByDesc('steps_done'),
            default => $list->sortByDesc('updated_at'),
        };

        $total = $list->count();
        $pages = max(1, (int) ceil($total / $per));
        $page = min($pages, max(1, (int) ($p['page'] ?? 1)));

        return ['rows' => $list->values()->forPage($page, $per)->values()->all(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'counts' => $counts];
    }
}
