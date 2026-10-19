<?php

namespace App\Http\Controllers;

use App\Support\Subscription;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: پلن‌ها، پلن فعلی و نتیجه‌ی پرداخت را از بک‌اند بدهید (قرارداد: app/Support/Subscription.php).
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        return view('subscription.index', [
            'plans' => Subscription::plans(),
            'current' => Subscription::current(),
            // آدرس شروع پرداخت (اگر ندهید حالت دمو کار می‌کند و مستقیم به صفحه‌ی نتیجه می‌رود):
            // 'urls' => ['checkout' => url('/subscription/checkout')],
        ]);
    }

    public function result(Request $request)
    {
        return view('subscription.result', [
            'result' => Subscription::demoResult((string) $request->query('status', 'success'), (string) $request->query('plan', '6m')),
            'plans' => Subscription::plans(),
        ]);
    }
}
