<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminPodcast;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: لیست سری‌ها و اپیزودها را بک‌اند بدهد (قرارداد: app/Support/AdminPodcast.php).
 * ذخیره/آپلود/حذف/AI با آدرس‌هایی کار می‌کند که در 'urls' می‌دهید (بدون آن‌ها «حالت دمو» در مرورگر ذخیره می‌کند).
 */
class PodcastController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'podcast', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'level', 'status', 'sort', 'page']);

        return view('admin.podcast.index', $this->shared() + ['p' => $p, 'list' => AdminPodcast::query($p)]);
    }

    public function create()
    {
        return view('admin.podcast.edit', $this->shared() + [
            'model' => AdminPodcast::blank(),
            // 'urls' => ['save' => url('/admin/podcast'), 'upload' => url('/admin/upload'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.podcast.edit', $this->shared() + [
            'model' => AdminPodcast::find($id) ?? abort(404),
            // 'urls' => ['save' => url("/admin/podcast/$id"), 'delete' => url("/admin/podcast/$id"), 'upload' => …, 'ai' => …, 'preview' => url("/podcast/series/$id")],
        ]);
    }

    public function episodeCreate(int $id)
    {
        $series = AdminPodcast::find($id) ?? abort(404);

        return view('admin.podcast.episode', $this->shared() + [
            'series' => $series,
            'model' => AdminPodcast::blankEpisode($id, count($series['episodes']) + 1),
        ]);
    }

    public function episodeEdit(int $id, int $ep)
    {
        return view('admin.podcast.episode', $this->shared() + [
            'series' => AdminPodcast::find($id) ?? abort(404),
            'model' => AdminPodcast::findEpisode($id, $ep) ?? abort(404),
            // 'urls' => ['save' => url("/admin/podcast/$id/episodes/$ep"), 'delete' => …, 'upload' => …, 'ai' => …, 'preview' => url("/podcast/$ep")],
        ]);
    }
}
