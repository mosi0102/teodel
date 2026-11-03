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
            'canAdvanced' => true,                      // TODO(backend): پلن کاربر ویژگی advanced_level دارد؟ (در دمو: قفل؛ برای تست true کنید)
            'nextAt' => null,                            // TODO(backend): زمان باز شدن آزمون بعدی (unix ثانیه) یا null اگر قفل نیست
            'lockDays' => PlacementTest::LOCK_DAYS,
            'lockScope' => 'all',                        // all: هر ۱۵ روز یک آزمون برای همه | each: برای هر آزمون جدا
            'level' => null,                             // TODO(backend): سطح فعلی کاربر ('a1'…'c2') اگر قبلاً تعیین یا انتخاب شده
            // 'levelUrl' => route('level-test.level'),  // POST {level} ← انتخاب دستی سطح
            'count' => 15,                              // تعداد سؤال هر آزمون (می‌تواند متغیر باشد)
            // 'finishUrl' => route('level-test.finish'),
        ]);
    }
}
