<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminBooks;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: کتاب‌ها (قرارداد: app/Support/AdminBooks.php).
 */
class BookController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'books', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'level', 'status', 'sort', 'page']);

        return view('admin.books.index', $this->shared() + ['p' => $p, 'list' => AdminBooks::query($p)]);
    }

    public function create()
    {
        return view('admin.books.edit', $this->shared() + ['model' => AdminBooks::blank()]);
    }

    public function edit(int $id)
    {
        return view('admin.books.edit', $this->shared() + [
            'model' => AdminBooks::find($id) ?? abort(404),
            // 'urls' => ['save' => …, 'delete' => …, 'upload' => …, 'ai' => …, 'preview' => url("/books/$id")],
        ]);
    }
}
