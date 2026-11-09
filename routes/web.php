<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\BugReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PodcastController as AdminPodcastController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShadowingController as AdminShadowingController;
use App\Http\Controllers\Admin\SongController as AdminSongController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TicketDeskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WordController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShadowingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WordsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teodel — مسیرهای اصلی
|--------------------------------------------------------------------------
| گروه «web» را Laravel خودکار روی این فایل اعمال می‌کند.
| ورود/ثبت‌نام در routes/auth.php و صفحه‌های عمومی (/، /faq، /terms) در routes/site.php هستند (پایین همین فایل).
| {id} و {step} همه با whereNumber محدودند. ترتیب مهم است: مسیرهای ثابت قبل از مسیرهای {id}.
*/

// زبان (fa | en): دکمه‌ی En/فا در هدر همه‌ی صفحه‌ها
Route::get('/lang/{locale}', LocaleController::class)->whereIn('locale', ['fa', 'en'])->name('lang');

/*
| پنل کاربر
| TODO(backend): ->middleware('auth') روی این گروه (فعلاً عمداً باز است تا فرانت بدون ورود دیده شود).
| TODO(backend): Route::post('/logout', …)->name('logout') — فرم «خروج» (partials/logout-dialog) به /logout POST می‌کند.
*/
Route::group([], function () {
    // حساب کاربر
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/words', [WordsController::class, 'index'])->name('words.index');
    Route::get('/dictionary', [DictionaryController::class, 'index'])->name('dictionary.index');
    Route::get('/level-test', [PlacementController::class, 'index'])->name('level-test.index');
    Route::get('/support', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/result', [SubscriptionController::class, 'result'])->name('subscription.result');

    // موزیک (۸ مرحله): /music/5 به مرحله‌ی فعلی کاربر می‌رود
    Route::get('/music', [MusicController::class, 'index'])->name('music.index');
    Route::get('/music/{id}', [MusicController::class, 'show'])->whereNumber('id')->name('music.show');
    Route::get('/music/{id}/step/{step}', [MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');

    // پادکست (سری ← اپیزود، ۷ مرحله) — series قبل از {id}
    Route::get('/podcast', [PodcastController::class, 'index'])->name('podcast.index');
    Route::get('/podcast/series/{id}', [PodcastController::class, 'series'])->whereNumber('id')->name('podcast.series');
    Route::get('/podcast/{id}', [PodcastController::class, 'show'])->whereNumber('id')->name('podcast.show');
    Route::get('/podcast/{id}/step/{step}', [PodcastController::class, 'step'])->whereNumber(['id', 'step'])->name('podcast.step');
    Route::get('/podcast/{id}/words.pdf', [PodcastController::class, 'wordsPdf'])->whereNumber('id')->name('podcast.words-pdf');

    // شدوئینگ (موضوع با نسخه‌های A1–C2، ۵ مرحله)
    Route::get('/shadowing', [ShadowingController::class, 'index'])->name('shadowing.index');
    Route::get('/shadowing/{id}', [ShadowingController::class, 'show'])->whereNumber('id')->name('shadowing.show');
    Route::get('/shadowing/{id}/step/{step}', [ShadowingController::class, 'step'])->whereNumber(['id', 'step'])->name('shadowing.step');

    // کتاب (۴ مرحله: معرفی، خواندن، مرور، نویسنده)
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{id}', [BookController::class, 'show'])->whereNumber('id')->name('books.show');
    Route::get('/books/{id}/step/{step}', [BookController::class, 'step'])->whereNumber(['id', 'step'])->name('books.step');
    Route::get('/books/{id}/download', [BookController::class, 'download'])->whereNumber('id')->name('books.download');
});

/*
| پنل مدیریت (/admin)
| TODO(backend): ->middleware(['auth', 'can:admin']) (یا نقش معادل) روی این گروه.
| POST/PUT/DELETE های ذخیره، آپلود و AI را بک‌اند اضافه می‌کند؛ قرارداد هرکدام در app/Support/Admin*.php.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // محتوا: موزیک
    Route::get('/songs', [AdminSongController::class, 'index'])->name('songs.index');
    Route::get('/songs/create', [AdminSongController::class, 'create'])->name('songs.create');
    Route::get('/songs/{id}/edit', [AdminSongController::class, 'edit'])->whereNumber('id')->name('songs.edit');

    // محتوا: پادکست (سری ← اپیزودها)
    Route::get('/podcast', [AdminPodcastController::class, 'index'])->name('podcast.index');
    Route::get('/podcast/create', [AdminPodcastController::class, 'create'])->name('podcast.create');
    Route::get('/podcast/{id}/edit', [AdminPodcastController::class, 'edit'])->whereNumber('id')->name('podcast.edit');
    Route::get('/podcast/{id}/episodes/create', [AdminPodcastController::class, 'episodeCreate'])->whereNumber('id')->name('podcast.episodes.create');
    Route::get('/podcast/{id}/episodes/{ep}/edit', [AdminPodcastController::class, 'episodeEdit'])->whereNumber(['id', 'ep'])->name('podcast.episodes.edit');

    // محتوا: Shadowing (موضوع با نسخه‌های A1–C2)
    Route::get('/shadowing', [AdminShadowingController::class, 'index'])->name('shadowing.index');
    Route::get('/shadowing/create', [AdminShadowingController::class, 'create'])->name('shadowing.create');
    Route::get('/shadowing/{id}/edit', [AdminShadowingController::class, 'edit'])->whereNumber('id')->name('shadowing.edit');

    // محتوا: کتاب‌ها
    Route::get('/books', [AdminBookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [AdminBookController::class, 'create'])->name('books.create');
    Route::get('/books/{id}/edit', [AdminBookController::class, 'edit'])->whereNumber('id')->name('books.edit');

    // محتوا: بانک سؤال آزمون تعیین سطح
    Route::get('/placement', [QuestionBankController::class, 'index'])->name('placement.index');
    Route::get('/placement/create', [QuestionBankController::class, 'create'])->name('placement.create');
    Route::get('/placement/{id}/edit', [QuestionBankController::class, 'edit'])->whereNumber('id')->name('placement.edit');

    // محتوا: دیکشنری
    Route::get('/dictionary', [WordController::class, 'index'])->name('dictionary.index');
    Route::get('/dictionary/create', [WordController::class, 'create'])->name('dictionary.create');
    Route::get('/dictionary/{id}/edit', [WordController::class, 'edit'])->whereNumber('id')->name('dictionary.edit');

    // کاربران و پرداخت‌ها
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('users.show');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/plans', [BillingController::class, 'plans'])->name('billing.plans');

    // پشتیبانی
    Route::get('/tickets', [TicketDeskController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{id}', [TicketDeskController::class, 'show'])->whereNumber('id')->name('tickets.show');
    Route::get('/reports', [BugReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [BugReportController::class, 'show'])->whereNumber('id')->name('reports.show');

    // سیستم
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    // مدیران و دسترسی‌ها — TODO(backend): فقط مدیر اصلی (role = owner)
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::get('/staff/{id}/edit', [StaffController::class, 'edit'])->whereNumber('id')->name('staff.edit');
});

// ورود و ثبت‌نام (/login و /register) — میدل‌ور guest داخل خود فایل
require __DIR__.'/auth.php';

// صفحه‌های عمومی: نخست (/)، سوالات متداول (/faq)، قوانین (/terms)
require __DIR__.'/site.php';
