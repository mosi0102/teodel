<?php

use App\Http\Controllers\MusicController;
use Illuminate\Support\Facades\Route;

/*
 * این خطوط را در routes/web.php خودتان اضافه کنید (یا این فایل را require کنید).
 * مسیرهای دیگر (dashboard, words, logout, lang ...) در پروژه‌ی خودتان موجود است؛
 * Nav.php و لایه‌ها فقط با url() به آن‌ها لینک می‌دهند.
 */
Route::middleware(['web'])->group(function () {
    Route::get('/music', [MusicController::class, 'index'])->name('music.index');
    // Route::get('/music/{id}', ...)->name('music.show');  // صفحه‌ی آموزش (مراحل ۱ تا ۸)
});
