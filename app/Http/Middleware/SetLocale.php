<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // اول session، بعد کوکی (بعد از بسته‌شدن مرورگر session عوض می‌شود ولی کوکی می‌ماند)، بعد زبان پیش‌فرض
        $locale = session('locale') ?? $request->cookie('locale') ?? config('app.locale');

        if (in_array($locale, ['fa', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}