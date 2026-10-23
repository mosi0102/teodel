<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminBilling;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: پرداخت‌ها و پلن‌ها را بک‌اند بدهد (قرارداد: app/Support/AdminBilling.php).
 */
class BillingController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'billing', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'status', 'plan', 'range', 'sort', 'page']);

        return view('admin.billing.index', $this->shared() + [
            'p' => $p,
            'list' => AdminBilling::query($p),
            'sum' => AdminBilling::summary(),
            // 'urls' => ['action' => url('/admin/billing/__ID__/action'), 'export' => url('/admin/billing/export')],
        ]);
    }

    public function plans()
    {
        return view('admin.billing.plans', $this->shared() + [
            'plans' => AdminBilling::plans(),
            // 'urls' => ['save' => url('/admin/billing/plans')],
        ]);
    }
}
