<?php

use App\Http\Controllers\AuthPageController;
use Illuminate\Support\Facades\Route;

/*
 * ورود و ثبت‌نام — این فایل را هم مثل routes/music.php در routes/web.php require کنید.
 * میدل‌ور guest فعال است (کاربر واردشده به مقصد پیش‌فرض guest برمی‌گردد؛ در Laravel 11+ از redirectUsersTo در bootstrap/app.php).
 * چهار آدرس POST (send/verify/password/register) را بک‌اند می‌سازد؛ قرارداد: app/Support/AuthFlow.php
 */
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [AuthPageController::class, 'show'])->defaults('mode', 'login')->name('login');
    Route::get('/register', [AuthPageController::class, 'show'])->defaults('mode', 'register')->name('register');
});
