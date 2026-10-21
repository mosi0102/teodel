<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSongs;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: لیست و جزئیات موزیک‌ها را بک‌اند بدهد (قرارداد: app/Support/AdminSongs.php).
 * ذخیره/آپلود/حذف با آدرس‌هایی کار می‌کند که در 'urls' می‌دهید (بدون آن‌ها «حالت دمو» در مرورگر ذخیره می‌کند).
 */
class SongController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'songs', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'level', 'status', 'sort', 'page']);

        return view('admin.songs.index', $this->shared() + ['p' => $p, 'list' => AdminSongs::query($p)]);
    }

    public function create()
    {
        return view('admin.songs.edit', $this->shared() + [
            'song' => AdminSongs::blank(),
            // 'urls' => ['save' => url('/admin/songs'), 'upload' => url('/admin/songs/upload'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.songs.edit', $this->shared() + [
            'song' => AdminSongs::find($id) ?? abort(404),
            // 'urls' => ['save' => url("/admin/songs/$id"), 'upload' => url('/admin/songs/upload'), 'delete' => url("/admin/songs/$id"), 'ai' => url('/admin/ai/generate'), 'preview' => url("/music/$id")],
        ]);
    }
}
