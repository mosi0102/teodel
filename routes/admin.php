<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\SongController as AdminSongController;
use App\Http\Controllers\Admin\TicketDeskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WordController;
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
    // بانک سؤال آزمون تعیین سطح
    Route::get('/placement', [QuestionBankController::class, 'index'])->name('placement.index');
    Route::get('/placement/create', [QuestionBankController::class, 'create'])->name('placement.create');
    Route::get('/placement/{id}/edit', [QuestionBankController::class, 'edit'])->whereNumber('id')->name('placement.edit');
    // مدیریت دیکشنری
    Route::get('/dictionary', [WordController::class, 'index'])->name('dictionary.index');
    Route::get('/dictionary/create', [WordController::class, 'create'])->name('dictionary.create');
    Route::get('/dictionary/{id}/edit', [WordController::class, 'edit'])->whereNumber('id')->name('dictionary.edit');
    Route::get('/kit', [AdminDashboardController::class, 'kit'])->name('kit');

    // اشتراک و پرداخت‌ها
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/plans', [BillingController::class, 'plans'])->name('billing.plans');

    // تیکت‌ها
    Route::get('/tickets', [TicketDeskController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{id}', [TicketDeskController::class, 'show'])->whereNumber('id')->name('tickets.show');
});
