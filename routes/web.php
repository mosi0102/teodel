<?php

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
Route::get('/music', [\App\Http\Controllers\MusicController::class, 'index'])->name('music.index');
Route::get('/music/{id}', [\App\Http\Controllers\MusicController::class, 'show'])->whereNumber('id')->name('music.show');
Route::get('/music/{id}/step/{step}', [\App\Http\Controllers\MusicController::class, 'step'])->whereNumber(['id', 'step'])->name('music.step');
