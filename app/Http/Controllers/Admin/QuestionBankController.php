<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminPlacement;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: بانک سؤال آزمون تعیین سطح را بک‌اند بدهد (قرارداد: app/Support/AdminPlacement.php).
 * نام عمداً QuestionBankController است تا با PlacementController سایت کاربران قاطی نشود.
 */
class QuestionBankController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'placement', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        return view('admin.placement.index', $this->shared() + [
            'rows' => AdminPlacement::demo(),
            // 'urls' => ['bulk' => url('/admin/placement/bulk'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.placement.edit', $this->shared() + [
            'q' => AdminPlacement::blank((string) $request->query('section', 'vocab'), (string) $request->query('level', 'a1')),
            // 'urls' => ['save' => url('/admin/placement'), 'upload' => url('/admin/placement/upload'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.placement.edit', $this->shared() + [
            'q' => AdminPlacement::find($id) ?? abort(404),
            // 'urls' => ['save' => url("/admin/placement/$id"), 'upload' => url('/admin/placement/upload'), 'delete' => url("/admin/placement/$id"), 'ai' => url('/admin/ai/generate')],
        ]);
    }
}
