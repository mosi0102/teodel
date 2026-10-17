<?php

namespace App\Http\Controllers;

use App\Support\Dictionary;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: Dictionary::demo() را با داده‌ی واقعی عوض کنید (قرارداد و دو حالت لیست کوچک/بزرگ: app/Support/Dictionary.php).
 * حالت لیست بزرگ: 'endpoint' => route('dictionary.search') بدهید و 'words' را خالی بگذارید.
 */
class DictionaryController extends Controller
{
    public function index()
    {
        $words = Dictionary::demo();

        return view('dictionary.index', [
            'words' => $words,
            'letters' => Dictionary::letters($words),
            'savedKeys' => [],          // کلیدهای (حروف کوچک) لغت‌هایی که کاربر ذخیره کرده
            // 'endpoint' => route('dictionary.search'),
        ]);
    }
}
