<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSettings;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: تنظیمات را بک‌اند بدهد/ذخیره کند (قرارداد: app/Support/AdminSettings.php).
 */
class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'adminActive' => 'settings', 'adminBadges' => ['tickets' => 3, 'reports' => 5],
            's' => AdminSettings::current(),
            'cover' => AdminSettings::bankCoverage(),
            // 'urls' => ['save' => url('/admin/settings')],
        ]);
    }
}
