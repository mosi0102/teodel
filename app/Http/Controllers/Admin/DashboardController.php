<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDashboard;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ی داشبورد مدیر را بک‌اند بدهد (قرارداد: app/Support/AdminDashboard.php).
 * دسترسی: میدل‌ور مدیر (مثلاً 'can:admin') را روی گروه روت‌های /admin بگذارید.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $d = AdminDashboard::demo();

        return view('admin.dashboard', [
            'd' => $d,
            'adminActive' => 'dashboard',
            'adminBadges' => $d['badges'],
        ]);
    }
}
