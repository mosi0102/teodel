<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminShadowing;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: موضوع‌های Shadowing (قرارداد: app/Support/AdminShadowing.php).
 */
class ShadowingController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'shadowing', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'level', 'status', 'sort', 'page']);

        return view('admin.shadowing.index', $this->shared() + ['p' => $p, 'list' => AdminShadowing::query($p)]);
    }

    public function create()
    {
        return view('admin.shadowing.edit', $this->shared() + ['model' => AdminShadowing::blank()]);
    }

    public function edit(int $id)
    {
        return view('admin.shadowing.edit', $this->shared() + [
            'model' => AdminShadowing::find($id) ?? abort(404),
            // 'urls' => ['save' => …, 'delete' => …, 'upload' => …, 'ai' => …, 'preview' => url('/shadowing')],
        ]);
    }
}
