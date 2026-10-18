<?php

namespace App\Http\Controllers;

use App\Support\PlacementTest;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: بانک سؤال و نتیجه‌ها را از بک‌اند بدهید (قرارداد: app/Support/PlacementTest.php).
 */
class PlacementController extends Controller
{
    public function index()
    {
        return view('placement.index', [
            'sections' => PlacementTest::sections(),
            'bank' => PlacementTest::demoBank(),        // یا به‌جای آن 'startUrl' => route('level-test.start')
            'results' => PlacementTest::demoResults(),  // آخرین نمره‌ی هر بخش
            'count' => 15,                              // تعداد سؤال هر آزمون (می‌تواند متغیر باشد)
            // 'finishUrl' => route('level-test.finish'),
        ]);
    }
}
