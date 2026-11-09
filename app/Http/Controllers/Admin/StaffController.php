<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminStaff;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: مدیران پنل و دسترسی‌هایشان (قرارداد: app/Support/AdminStaff.php).
 * TODO(backend): همه‌ی این route ها فقط برای مدیر اصلی (role = owner) باشد.
 * ذخیره/بازنشانی رمز/حذف با آدرس‌هایی که در 'urls' می‌دهید؛ بدون آن‌ها «حالت دمو» در مرورگر ذخیره می‌کند.
 */
class StaffController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'staff', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'role']);

        return view('admin.staff.index', $this->shared() + ['p' => $p, 'list' => AdminStaff::query($p)]);
    }

    public function create()
    {
        return view('admin.staff.edit', $this->shared() + [
            'member' => AdminStaff::blank(),
            // 'urls' => ['save' => url('/admin/staff')],
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.staff.edit', $this->shared() + [
            'member' => AdminStaff::find($id) ?? abort(404),
            // 'urls' => ['save' => url("/admin/staff/$id"), 'reset' => url("/admin/staff/$id/password"), 'delete' => url("/admin/staff/$id")],
        ]);
    }
}
