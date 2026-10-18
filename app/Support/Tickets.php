<?php

namespace App\Support;

/**
 * تیکت‌های کاربر (/tickets): لیست و وضعیت، ساخت تیکت جدید، گفتگو با پشتیبانی، بستن و بازکردن دوباره‌ی تیکت.
 *
 * 🔌 بک‌اند — هر تیکت:
 *   id          شناسه (رشته یا عدد)
 *   code        کد نمایشی (مثلاً T-1042)
 *   subject     موضوع
 *   dept        technical | payment | content | account | suggestion     (فهرست: departments())
 *   status      open (در انتظار پاسخ پشتیبانی) | answered (پشتیبانی جواب داده؛ نوبت کاربر) | closed
 *   unread      true اگر پاسخ تازه‌ی پشتیبانی را هنوز ندیده
 *   created_at / updated_at   زمان (ثانیه‌ی یونیکس)
 *   messages    [ { id, from: 'user'|'support', name?, text, at, files: [ { name, url, size? } ] } ]
 *               (اختیاری در لیست؛ اگر نبود با data-detail-url بار می‌شود)
 *
 * راه‌های اتصال (هر کدام را که راحت‌ترید):
 *   ۱) آدرس‌ها روی ریشه‌ی صفحه (همه multipart/JSON با CSRF؛ جواب JSON):
 *        data-detail-url   GET   /tickets/__ID__             ← { ticket }
 *        data-create-url   POST  /tickets    (department, subject, message, files[])      ← { ticket }
 *        data-reply-url    POST  /tickets/__ID__/reply  (message, files[])                ← { message, status? }
 *        data-close-url    POST  /tickets/__ID__/close                                    ← { status: 'closed' }
 *        data-reopen-url   POST  /tickets/__ID__/reopen                                   ← { status: 'open' }
 *      خطای اعتبارسنجی: 422 با { errors: { subject: ['…'] } } یا { message: '…' }.
 *   ۲) یا جایگزین کردن توابع: Teodel.tickets.api = { create(p), reply(id, p), close(id), reopen(id), detail(id), read(id) }
 *      (هرکدام Promise برمی‌گرداند؛ ورودی‌ها و خروجی‌ها همان بالاست؛ files آرایه‌ی File است).
 * رویدادها (بعد از موفقیت): ticket:created، ticket:replied، ticket:closed، ticket:reopened، ticket:read
 * قوانین فرانت (سرور هم دوباره بررسی کند): موضوع ۳ تا ۱۲۰ نویسه، متن ۱۰ تا ۳۰۰۰، حداکثر ۳ عکس و هرکدام تا ۲ مگابایت.
 */
class Tickets
{
    public static function departments(): array
    {
        return ['technical', 'payment', 'content', 'account', 'suggestion'];
    }

    /** نمونه‌ی تیکت‌ها (فقط برای دیدن فرانت) */
    public static function demo(): array
    {
        $now = time();
        $h = 3600;

        return [
            [
                'id' => 1042, 'code' => 'T-1042', 'subject' => 'پخش صدا در مرحله‌ی shadowing قطع و وصل می‌شود',
                'dept' => 'technical', 'status' => 'answered', 'unread' => true,
                'created_at' => $now - 30 * $h, 'updated_at' => $now - 2 * $h,
                'messages' => [
                    ['id' => 1, 'from' => 'user', 'name' => '', 'text' => "سلام. در مرحله‌ی shadowing وقتی دکمه‌ی پخش را می‌زنم صدا چند ثانیه می‌آید و بعد قطع می‌شود.\nروی گوشی اندروید و با مرورگر کروم هستم.", 'at' => $now - 30 * $h, 'files' => []],
                    ['id' => 2, 'from' => 'support', 'name' => 'سارا از پشتیبانی', 'text' => "سلام، وقت بخیر.\nممنون که گزارش دادید. لطفاً یک بار صفحه را کامل ببندید و دوباره باز کنید، بعد اگر مشکل ادامه داشت بگویید اینترنت شما وای‌فای است یا داده‌ی موبایل؟", 'at' => $now - 2 * $h, 'files' => []],
                ],
            ],
            [
                'id' => 1038, 'code' => 'T-1038', 'subject' => 'تمدید اشتراک انجام شد ولی هنوز فعال نشده',
                'dept' => 'payment', 'status' => 'open', 'unread' => false,
                'created_at' => $now - 5 * $h, 'updated_at' => $now - 5 * $h,
                'messages' => [
                    ['id' => 1, 'from' => 'user', 'name' => '', 'text' => "پرداخت من موفق بود و پیامک هم آمد، اما هنوز مراحل بعد از دوم قفل است. کد پیگیری: 48213907", 'at' => $now - 5 * $h, 'files' => []],
                ],
            ],
            [
                'id' => 1021, 'code' => 'T-1021', 'subject' => 'ترجمه‌ی یکی از لغت‌های آهنگ اشتباه است',
                'dept' => 'content', 'status' => 'answered', 'unread' => false,
                'created_at' => $now - 4 * 86400, 'updated_at' => $now - 3 * 86400,
                'messages' => [
                    ['id' => 1, 'from' => 'user', 'name' => '', 'text' => "در آهنگ step by step in time، ترجمه‌ی کلمه‌ی «grace» در معنای این جمله درست نیست.", 'at' => $now - 4 * 86400, 'files' => []],
                    ['id' => 2, 'from' => 'support', 'name' => 'امیر از پشتیبانی', 'text' => "ممنون از دقتتان! بررسی کردیم و اصلاح شد. اگر مورد دیگری دیدید حتماً بگویید.", 'at' => $now - 3 * 86400, 'files' => []],
                ],
            ],
            [
                'id' => 987, 'code' => 'T-987', 'subject' => 'تغییر شماره‌ی موبایل حساب',
                'dept' => 'account', 'status' => 'closed', 'unread' => false,
                'created_at' => $now - 20 * 86400, 'updated_at' => $now - 18 * 86400,
                'messages' => [
                    ['id' => 1, 'from' => 'user', 'name' => '', 'text' => "می‌خواهم شماره‌ی موبایلم را عوض کنم ولی در پروفایل فقط نمایش داده می‌شود.", 'at' => $now - 20 * 86400, 'files' => []],
                    ['id' => 2, 'from' => 'support', 'name' => 'سارا از پشتیبانی', 'text' => "شماره‌ی موبایل به‌خاطر امنیت فقط با تأیید پشتیبانی عوض می‌شود. شماره‌ی جدید را بفرستید تا کد تأیید برایتان ارسال کنیم.", 'at' => $now - 19 * 86400, 'files' => []],
                    ['id' => 3, 'from' => 'user', 'name' => '', 'text' => "۰۹۱۲۰۰۰۰۰۰۰", 'at' => $now - 19 * 86400 + 600, 'files' => []],
                    ['id' => 4, 'from' => 'support', 'name' => 'سارا از پشتیبانی', 'text' => "انجام شد و تیکت بسته می‌شود. موفق باشید!", 'at' => $now - 18 * 86400, 'files' => []],
                ],
            ],
            [
                'id' => 954, 'code' => 'T-954', 'subject' => 'پیشنهاد: افزودن موزیک‌های بیشتر سطح B',
                'dept' => 'suggestion', 'status' => 'closed', 'unread' => false,
                'created_at' => $now - 41 * 86400, 'updated_at' => $now - 40 * 86400,
                'messages' => [
                    ['id' => 1, 'from' => 'user', 'name' => '', 'text' => "اگر آهنگ‌های سطح B بیشتری داشته باشید عالی می‌شود.", 'at' => $now - 41 * 86400, 'files' => []],
                    ['id' => 2, 'from' => 'support', 'name' => 'امیر از پشتیبانی', 'text' => "ممنون از پیشنهادتان. به تیم محتوا منتقل شد.", 'at' => $now - 40 * 86400, 'files' => []],
                ],
            ],
        ];
    }
}
