<?php

namespace App\Http\Controllers;

use App\Support\SavedWords;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: SavedWords::demo() را با لیست واقعیِ کاربر عوض کنید (قرارداد داده: app/Support/SavedWords.php).
 * فیلتر، جست‌وجو و مرتب‌سازی در مرورگر انجام می‌شود؛ اگر لیست خیلی بزرگ شد، بک‌اند می‌تواند صفحه‌بندی کند.
 */
class WordsController extends Controller
{
    public function index()
    {
        return view('words.index', [
            'words' => SavedWords::demo(),
            'sentences' => SavedWords::sentences(), // جمله‌های محبوب (کتاب‌ها)
            // 'viewUrl' => route('words.view'),  // POST {id} ← ثبت هر بار دیدن لغت (بعد از SavedWords::LEARN_AT بار، «یاد گرفته‌ام» می‌شود)
        ]);
    }
}
