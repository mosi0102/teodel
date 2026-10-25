<?php

namespace App\Support;

/**
 * ناوبری پنل مدیریت (سایدبار دسکتاپ، ریل تبلت، منوی کشویی و نوار پایین موبایل).
 *
 * 🔌 بک‌اند:
 *  - $badges: عددهای کنار آیتم‌ها، مثلاً ['tickets' => 3, 'reports' => 5] (۰ یا نبودن = بدون نشان).
 *  - دسترسی نقش‌ها (مثلاً فقط «پشتیبان» تیکت ببیند): آیتم‌هایی که نباید دیده شوند را از groups() حذف کنید
 *    یا $hide = ['billing', 'settings'] بدهید.
 *  - آیتم فعال از روی بخش دوم آدرس (/admin/{key}) تشخیص داده می‌شود؛ یا $adminActive را از کنترلر بدهید.
 */
class AdminNav
{
    /** آیکون‌ها: محتوای داخلی SVG (۲۴×۲۴، stroke) */
    public const ICONS = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'songs' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'placement' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
        'dictionary' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M9 7h6"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'billing' => '<rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20M6 15h3"/>',
        'tickets' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'reports' => '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4M12 17h.01"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'sparkles' => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 3v4M21 5h-4M5 17v4M7 19H3"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'up' => '<path d="m6 15 6-6 6 6"/>',
        'down' => '<path d="m6 9 6 6 6-6"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];

    /** آیکون آماده‌ی SVG */
    public static function icon(string $name, string $class = 'size-5'): string
    {
        return '<svg viewBox="0 0 24 24" class="'.$class.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.(self::ICONS[$name] ?? '').'</svg>';
    }

    /**
     * @param  array<string,int>  $badges
     * @param  array<int,string>  $hide  کلید آیتم‌هایی که برای این نقش دیده نشوند
     * @return array<int,array{key:string,items:array<int,array{key:string,href:string,icon:string,badge:int}>}>
     */
    public static function groups(array $badges = [], array $hide = []): array
    {
        $g = [
            'overview' => ['dashboard'],
            'content' => ['songs', 'placement', 'dictionary'],
            'people' => ['users', 'billing'],
            'support' => ['tickets', 'reports'],
            'system' => ['settings'],
        ];
        $out = [];
        foreach ($g as $gk => $keys) {
            $items = [];
            foreach ($keys as $k) {
                if (in_array($k, $hide, true)) {
                    continue;
                }
                $items[] = [
                    'key' => $k,
                    'href' => url($k === 'dashboard' ? '/admin' : '/admin/'.$k),
                    'icon' => $k,
                    'badge' => (int) ($badges[$k] ?? 0),
                ];
            }
            if ($items) {
                $out[] = ['key' => $gk, 'items' => $items];
            }
        }

        return $out;
    }

    /** آیتم‌های نوار پایین موبایل (۴ مورد + «بیشتر») */
    public static function bottom(): array
    {
        return ['dashboard', 'songs', 'users', 'tickets'];
    }
}
