<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SongController as AdminSongController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

/*
 * پنل مدیریت — این فایل را هم مثل routes/music.php در routes/web.php require کنید.
 * 🔌 حتماً میدل‌ور احراز هویت/نقش مدیر را اضافه کنید: ->middleware(['web', 'auth', 'can:admin'])
 * ⚠️ کنترلرهای مدیر عمداً با نام مستعار Admin… import شده‌اند تا با DashboardController/UserController سایت کاربران قاطی نشوند.
 * بخش‌های بعدی پنل (songs, placement, dictionary, users, billing, tickets, reports, settings)
 * در فازهای بعد همین‌جا اضافه می‌شوند؛ تا آن موقع لینک‌هایشان در منو 404 می‌دهد.
 */
Route::middleware(['web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('users.show');
    Route::get('/songs', [AdminSongController::class, 'index'])->name('songs.index');
    Route::get('/songs/create', [AdminSongController::class, 'create'])->name('songs.create');
    Route::get('/songs/{id}/edit', [AdminSongController::class, 'edit'])->whereNumber('id')->name('songs.edit');
    Route::get('/kit', [AdminDashboardController::class, 'kit'])->name('kit');
});
