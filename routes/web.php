<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WordsController;
use App\Http\Controllers\Admin\SongController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SongController as AdminSongController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\WordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\TicketDeskController;

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

    // خرید اشتراک (لیست پلن‌ها + مودال تأیید) و نتیجه‌ی پرداخت
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/result', [SubscriptionController::class, 'result'])->name('subscription.result');

    // لغات و عبارت‌های هایلایت‌شده‌ی کاربر
    Route::get('/words', [WordsController::class, 'index'])->name('words.index');

    // صفحه‌ی آموزش: /music/5 به مرحله‌ی فعلی کاربر می‌رود؛ /music/5/step/2 مرحله‌ی دوم
    Route::get('/music/{id}', [MusicController::class, 'show'])->whereNumber('id')->name('music.show');
    Route::get('/music/{id}/step/{step}', [MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');
});

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

