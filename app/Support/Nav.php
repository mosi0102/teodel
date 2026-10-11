<?php

namespace App\Support;

/**
 * تعریف یک‌جای آیتم‌های ناوبری (سایدبار، ناوبری پایین موبایل و منوی «بیشتر»).
 * href ها را با route های واقعی پروژه عوض کنید. soon = true یعنی «به زودی» (غیرفعال).
 */
class Nav
{
    public static function items(): array
    {
        return [
            ['key' => 'dashboard',  'icon' => 'home',            'href' => url('/dashboard'),   'active' => request()->is('dashboard*'),   'soon' => false],
            ['key' => 'music',      'icon' => 'music',           'href' => route('music.index'), 'active' => request()->routeIs('music.*'), 'soon' => false],
            ['key' => 'podcast',    'icon' => 'mic',             'href' => '#',                 'active' => false,                         'soon' => true],
            ['key' => 'video',      'icon' => 'video',           'href' => '#',                 'active' => false,                         'soon' => true],
            ['key' => 'game',       'icon' => 'gamepad',         'href' => '#',                 'active' => false,                         'soon' => true],
            ['key' => 'books',      'icon' => 'book',            'href' => '#',                 'active' => false,                         'soon' => true],
            ['key' => 'words',      'icon' => 'bookmark',        'href' => url('/words'),       'active' => request()->is('words*'),       'soon' => false],
            ['key' => 'level_test', 'icon' => 'clipboard-check', 'href' => url('/level-test'),  'active' => request()->is('level-test*'), 'soon' => false],
            ['key' => 'dictionary', 'icon' => 'languages',       'href' => url('/dictionary'),  'active' => request()->is('dictionary*'),  'soon' => false],
            ['key' => 'support',    'icon' => 'headset',         'href' => url('/support'),     'active' => request()->is('support*'),     'soon' => false],
        ];
    }

    /** آیتم‌های ناوبری پایین موبایل (۴ مورد اصلی + «بیشتر» که جدا رندر می‌شود) */
    public static function bottomKeys(): array
    {
        return ['dashboard', 'music', 'words', 'dictionary'];
    }
}
