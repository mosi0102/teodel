<?php

namespace App\Http\Controllers;

use App\Support\Dashboard;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ی داشبورد را بک‌اند بدهد (قرارداد: app/Support/Dashboard.php).
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index', [
            'd' => Dashboard::demo(),
            // 'goalUrl' => route('dashboard.goal'),   // POST { minutes }
        ]);
    }
}
