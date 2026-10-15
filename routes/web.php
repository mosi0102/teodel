<?php

use App\Http\Controllers\MusicController;
use App\Http\Controllers\WordsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['fa', 'en'], true)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
});
Route::middleware(['web'])->group(function () {
    Route::get('/music', [MusicController::class, 'index'])->name('music.index');

    // لغات و عبارت‌های هایلایت‌شده‌ی کاربر
    Route::get('/words', [WordsController::class, 'index'])->name('words.index');

    // صفحه‌ی آموزش: /music/5 به مرحله‌ی فعلی کاربر می‌رود؛ /music/5/step/2 مرحله‌ی دوم
    Route::get('/music/{id}', [MusicController::class, 'show'])->whereNumber('id')->name('music.show');
    Route::get('/music/{id}/step/{step}', [MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');
});
