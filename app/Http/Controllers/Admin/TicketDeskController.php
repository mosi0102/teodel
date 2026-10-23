<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminTickets;
use Illuminate\Http\Request;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: تیکت‌ها را بک‌اند بدهد (قرارداد: app/Support/AdminTickets.php).
 * نام عمداً TicketDeskController است تا با TicketController سایت کاربران قاطی نشود.
 */
class TicketDeskController extends Controller
{
    private function shared(): array
    {
        return ['adminActive' => 'tickets', 'adminBadges' => ['tickets' => 3, 'reports' => 5]];
    }

    public function index(Request $request)
    {
        $p = $request->only(['q', 'status', 'dept', 'user', 'sort', 'page']);

        return view('admin.tickets.index', $this->shared() + ['p' => $p, 'list' => AdminTickets::query($p)]);
    }

    public function show(int $id)
    {
        $t = AdminTickets::find($id) ?? abort(404);

        return view('admin.tickets.show', $this->shared() + [
            't' => $t,
            'others' => AdminTickets::ofUser($t['user']['id'], $t['id']),
            'canned' => AdminTickets::canned(),
            // 'urls' => ['reply' => url('/admin/tickets/__ID__/reply'), 'close' => url('/admin/tickets/__ID__/close'),
            //            'reopen' => url('/admin/tickets/__ID__/reopen'), 'ai' => url('/admin/ai/generate')],
        ]);
    }
}
