<?php

use App\Http\Controllers\SitePageController;
use Illuminate\Support\Facades\Route;

/*
 * صفحه‌های عمومی سایت (نخست، بعداً سوالات متداول و قوانین).
 * این فایل را در routes/web.php require کنید. اگر مسیر '/' از قبل در پروژه‌تان تعریف شده، آن را حذف کنید.
 */
Route::middleware(['web'])->group(function () {
    Route::get('/', [SitePageController::class, 'home'])->name('home');
    Route::get('/faq', [SitePageController::class, 'faq'])->name('faq');
    Route::get('/terms', [SitePageController::class, 'terms'])->name('terms');
});
