<?php

namespace App\Http\Controllers;

use App\Support\Tickets;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: تیکت‌های کاربر را از بک‌اند بدهید (قرارداد و آدرس‌ها: app/Support/Tickets.php).
 */
class TicketController extends Controller
{
    public function index()
    {
        return view('tickets.index', [
            'tickets' => Tickets::demo(),
            'departments' => Tickets::departments(),
            // آدرس‌ها (اگر ندهید، حالت دمو با localStorage کار می‌کند):
            // 'urls' => ['detail' => url('/tickets/__ID__'), 'create' => url('/tickets'), 'reply' => url('/tickets/__ID__/reply'),
            //            'close' => url('/tickets/__ID__/close'), 'reopen' => url('/tickets/__ID__/reopen')],
        ]);
    }
}
