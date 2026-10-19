<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WordsController;
use Illuminate\Support\Facades\Route;

/*
 * این خطوط را در routes/web.php خودتان اضافه کنید (یا این فایل را require کنید).
 * مسیرهای دیگر (words, logout, lang ...) در پروژه‌ی خودتان موجود است؛
 * Nav.php و لایه‌ها فقط با url() به آن‌ها لینک می‌دهند.
 */
Route::middleware(['web'])->group(function () {
    Route::get('/music', [MusicController::class, 'index'])->name('music.index');

    // داشبورد کاربر (نمای کلی یادگیری)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // پروفایل کاربر (مشاهده / تکمیل / ویرایش + انتخاب آواتار)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    // دیکشنری (بدون سطح‌بندی، الفبایی، جمع‌وجور)
    Route::get('/dictionary', [DictionaryController::class, 'index'])->name('dictionary.index');

    // آزمون تعیین سطح (واژگان، گرامر، درک شنیداری)
    Route::get('/level-test', [PlacementController::class, 'index'])->name('level-test.index');

    // تیکت‌های پشتیبانی (لیست، تیکت جدید، گفتگو)
    Route::get('/support', [TicketController::class, 'index'])->name('tickets.index');

    // لغات و عبارت‌های هایلایت‌شده‌ی کاربر
    Route::get('/words', [WordsController::class, 'index'])->name('words.index');

    // صفحه‌ی آموزش: /music/5 به مرحله‌ی فعلی کاربر می‌رود؛ /music/5/step/2 مرحله‌ی دوم
    Route::get('/music/{id}', [MusicController::class, 'show'])->whereNumber('id')->name('music.show');
    Route::get('/music/{id}/step/{step}', [MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');
});
