<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminUsers;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: لیست و جزئیات کاربران را بک‌اند بدهد (قرارداد: app/Support/AdminUsers.php).
 * اقدام‌ها (مسدودسازی، هدیه‌ی روز) با آدرس‌هایی که در 'urls' می‌دهید کار می‌کنند.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.users.index', [
            'adminActive' => 'users',
            'adminBadges' => ['tickets' => 3, 'reports' => 5],
            'p' => $request->only(['q', 'plan', 'sort', 'page']),
            'list' => AdminUsers::query($request->only(['q', 'plan', 'sort', 'page'])),
        ]);
    }

    public function show(int $id)
    {
        $u = AdminUsers::find($id) ?? abort(404);

        return view('admin.users.show', [
            'adminActive' => 'users',
            'adminBadges' => ['tickets' => 3, 'reports' => 5],
            'u' => $u,
            // 'urls' => ['block' => url("/admin/users/$id/block"), 'grant' => url("/admin/users/$id/grant")],
        ]);
    }
}
