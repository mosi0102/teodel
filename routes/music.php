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

    // صفحه‌ی آموزش: /music/5 به مرحله‌ی فعلی کاربر می‌رود؛ /music/5/step/2 مرحله‌ی دوم
    Route::get('/music/{id}', [MusicController::class, 'show'])->whereNumber('id')->name('music.show');
    Route::get('/music/{id}/step/{step}', [MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');
});
