<?php

namespace App\Support;

/**
 * پنل مدیریت — مدیران و دسترسی‌ها (/admin/staff).
 *
 * «مدیر اصلی» (role = owner) به همه‌چیز دسترسی دارد، قابل حذف/محدودکردن نیست و فقط او این صفحه را می‌بیند.
 * مدیر اصلی برای هر ادمین جدید شماره‌ی موبایل، رمز پیش‌فرض و سطح دسترسی هر بخش را تعیین می‌کند.
 *
 * 🔌 قرارداد یک ادمین:
 *   id, name, phone ('09xxxxxxxxx'), role (owner|manager|content|support|finance|viewer|custom),
 *   permissions { بخش: none|view|edit|full }   ← بخش‌ها: SECTIONS (owner همیشه همه full)
 *   status (active|disabled), must_change_password (bool: در اولین ورود رمز را عوض کند),
 *   last_login (timestamp|null), created_at, created_by (نام)
 *
 * سطح‌ها:  none = بخش در منو نیست | view = فقط دیدن | edit = ساخت و ویرایش | full = ویرایش + انتشار/حذف/کارهای حساس (مثل بازپرداخت)
 *
 * 🔌 بک‌اند:
 *   - رمز فقط هش‌شده ذخیره شود؛ رمز پیش‌فرض فقط یک بار در پاسخ ساخت نشان داده می‌شود (فرانت هم دیگر نمایشش نمی‌دهد).
 *   - ذخیره:  data-save-url  POST (جدید) / PUT (ویرایش) { name, phone, password?, role, permissions, status, must_change_password, send_sms }
 *             ← { id } | 422 { message, errors: { phone: ['این شماره قبلاً ثبت شده'] } }
 *   - بازنشانی رمز: data-reset-url POST { password, must_change_password: true, send_sms } ← { ok }
 *   - حذف:   data-delete-url DELETE ← { ok }
 *   - رویدادهای لغوشدنی: admin:staff-save | admin:staff-reset | admin:staff-delete  ({ payload, done, fail })
 *   - دسترسی واقعی سمت سرور: Gate/Policy با AdminStaff::can($perms, 'billing', 'edit')؛ منوی سایدبار: $adminHide = AdminStaff::hidden($perms)
 *   - route های /admin/staff فقط برای role = owner.
 */
class AdminStaff
{
    /** بخش‌های پنل که دسترسی‌شان تعیین می‌شود (به ترتیب منو). staff عمداً نیست: فقط مدیر اصلی */
    public const SECTIONS = ['dashboard', 'songs', 'podcast', 'shadowing', 'books', 'placement', 'dictionary', 'users', 'billing', 'tickets', 'reports', 'settings'];

    public const LEVELS = ['none', 'view', 'edit', 'full'];

    /** نقش‌های آماده (انتخاب نقش، جدول دسترسی را پر می‌کند؛ تغییر دستی جدول = «سفارشی») */
    public static function presets(): array
    {
        $all = fn (string $lvl) => array_fill_keys(self::SECTIONS, $lvl);
        $only = fn (array $map) => array_merge($all('none'), $map);

        return [
            'manager' => $all('full'),
            'content' => $only(['dashboard' => 'view', 'songs' => 'full', 'podcast' => 'full', 'shadowing' => 'full', 'books' => 'full', 'placement' => 'full', 'dictionary' => 'full', 'reports' => 'view']),
            'support' => $only(['dashboard' => 'view', 'users' => 'view', 'billing' => 'view', 'tickets' => 'full', 'reports' => 'full']),
            'finance' => $only(['dashboard' => 'view', 'users' => 'view', 'billing' => 'full']),
            'viewer' => array_merge($all('view'), ['settings' => 'none']),
        ];
    }

    public static function can(array $perms, string $section, string $need = 'view'): bool
    {
        $have = array_search($perms[$section] ?? 'none', self::LEVELS, true);

        return $have !== false && $have >= array_search($need, self::LEVELS, true);
    }

    /** بخش‌هایی که باید از منوی این ادمین پنهان شوند (برای AdminNav::groups) */
    public static function hidden(array $perms): array
    {
        return array_values(array_filter(self::SECTIONS, fn ($s) => ! self::can($perms, $s)));
    }

    /** ۰۹۱۲… از ورودی‌هایی مثل «+98 912 …» یا رقم فارسی؛ null = نامعتبر */
    public static function normalizePhone(string $raw): ?string
    {
        $d = preg_replace('/\D/', '', strtr($raw, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));
        if (str_starts_with($d, '98')) {
            $d = '0'.substr($d, 2);
        } elseif (str_starts_with($d, '9') && strlen($d) === 10) {
            $d = '0'.$d;
        }

        return preg_match('/^09\d{9}$/', $d) ? $d : null;
    }

    public static function all(): array
    {
        $now = time();
        $p = self::presets();
        $rows = [
            ['id' => 1, 'name' => 'مدیر', 'phone' => '09120000000', 'role' => 'owner', 'permissions' => array_fill_keys(self::SECTIONS, 'full'), 'status' => 'active', 'must_change_password' => false, 'last_login' => $now - 300, 'created_at' => $now - 86400 * 200, 'created_by' => null],
            ['id' => 2, 'name' => 'سارا کریمی', 'phone' => '09123456789', 'role' => 'support', 'permissions' => $p['support'], 'status' => 'active', 'must_change_password' => false, 'last_login' => $now - 3600 * 3, 'created_at' => $now - 86400 * 90, 'created_by' => 'مدیر'],
            ['id' => 3, 'name' => 'امیر رضایی', 'phone' => '09351234567', 'role' => 'support', 'permissions' => $p['support'], 'status' => 'active', 'must_change_password' => false, 'last_login' => $now - 86400, 'created_at' => $now - 86400 * 60, 'created_by' => 'مدیر'],
            ['id' => 4, 'name' => 'نگار احمدی', 'phone' => '09191112233', 'role' => 'content', 'permissions' => $p['content'], 'status' => 'active', 'must_change_password' => true, 'last_login' => null, 'created_at' => $now - 86400 * 2, 'created_by' => 'مدیر'],
            ['id' => 5, 'name' => 'رضا نوری', 'phone' => '09031239876', 'role' => 'custom', 'permissions' => array_merge($p['finance'], ['users' => 'edit', 'reports' => 'view']), 'status' => 'disabled', 'must_change_password' => false, 'last_login' => $now - 86400 * 40, 'created_at' => $now - 86400 * 120, 'created_by' => 'مدیر'],
        ];

        return $rows;
    }

    public static function query(array $p): array
    {
        $q = mb_strtolower(trim((string) ($p['q'] ?? '')));
        $role = $p['role'] ?? 'all';
        $all = collect(self::all());
        $counts = ['all' => $all->count()] + collect(['owner', 'manager', 'content', 'support', 'finance', 'viewer', 'custom'])->mapWithKeys(fn ($r) => [$r => $all->where('role', $r)->count()])->all();
        $rows = $all
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['phone']), $q)))
            ->when($role !== 'all', fn ($c) => $c->where('role', $role))
            ->values()->all();

        return ['rows' => $rows, 'total' => count($rows), 'counts' => $counts];
    }

    public static function find(int $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function blank(): array
    {
        return ['id' => null, 'name' => '', 'phone' => '', 'role' => 'content', 'permissions' => self::presets()['content'], 'status' => 'active', 'must_change_password' => true, 'last_login' => null, 'created_at' => null, 'created_by' => null];
    }
}
