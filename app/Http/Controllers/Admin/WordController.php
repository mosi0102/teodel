<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDictionary;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: مدخل‌های دیکشنری را بک‌اند بدهد (قرارداد: app/Support/AdminDictionary.php).
 * نام عمداً WordController است تا با DictionaryController سایت کاربران قاطی نشود.
 */
class WordController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'dictionary', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index()
    {
        return view('admin.dictionary.index', $this->shared() + [
            'rows' => AdminDictionary::demo(),
            // 'urls' => ['bulk' => url('/admin/dictionary/bulk'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function create()
    {
        return view('admin.dictionary.edit', $this->shared() + [
            'w' => AdminDictionary::blank(),
            // 'urls' => ['save' => url('/admin/dictionary'), 'upload' => url('/admin/dictionary/upload'), 'ai' => url('/admin/ai/generate')],
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.dictionary.edit', $this->shared() + [
            'w' => AdminDictionary::find($id) ?? abort(404),
            // 'urls' => ['save' => url("/admin/dictionary/$id"), 'upload' => url('/admin/dictionary/upload'), 'delete' => url("/admin/dictionary/$id"), 'ai' => url('/admin/ai/generate')],
        ]);
    }
}
