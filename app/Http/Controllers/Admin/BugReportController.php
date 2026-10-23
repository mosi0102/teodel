<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminReports;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: گزارش‌های خطا را بک‌اند بدهد (قرارداد: app/Support/AdminReports.php).
 * نام عمداً BugReportController است تا با هر کنترلر گزارش سمت کاربران قاطی نشود.
 */
class BugReportController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'reports', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'status', 'area', 'priority', 'sort', 'page']);

        return view('admin.reports.index', $this->shared() + ['p' => $p, 'list' => AdminReports::query($p)]);
    }

    public function show(int $id)
    {
        $r = AdminReports::find($id) ?? abort(404);

        return view('admin.reports.show', $this->shared() + [
            'r' => $r,
            'nav' => AdminReports::neighbors($id),
            'others' => AdminReports::ofUser($r['user']['id'], $r['id']),
            // 'urls' => ['update' => url('/admin/reports/__ID__/update'), 'note' => url('/admin/reports/__ID__/note')],
        ]);
    }
}
