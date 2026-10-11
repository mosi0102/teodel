# صفحه‌ی لیست موزیک (Teodel) — فرانت‌اند

Laravel Blade + Tailwind CSS 3 + jQuery. دو زبانه (فارسی RTL / انگلیسی LTR)، موبایل‌محور و هم‌وزن برای دسکتاپ.
فقط فرانت است؛ هر جا داده یا ارسال لازم است یک نقطه‌ی اتصال خالی گذاشته شده.

## نصب در پروژه‌ی لاراول

```bash
npm i -D tailwindcss@3 postcss autoprefixer
npm i jquery html2canvas vazirmatn @fontsource-variable/inter
```

1. پوشه‌های `resources/`, `lang/`, `app/Support/`, `public/images/` را در پروژه کپی کنید.
   `tailwind.config.js`, `postcss.config.js`, `vite.config.js` را با فایل‌های موجود ادغام کنید.
2. در `composer.json` زیر `autoload.files` اضافه کنید: `"app/Support/helpers.php"` و بعد `composer dump-autoload`
   (تابع `num()` اعداد را در زبان فارسی فارسی‌نویس می‌کند).
3. محتوای `routes/music.php` را در `routes/web.php` بگذارید. `MusicController` فعلاً داده‌ی نمونه دارد.
4. `npm run dev` / `npm run build`.

> مسیر فونت فارسی `vazirmatn/Vazirmatn-Variable-font-face.css` در `resources/js/app.js` است.
> اگر نسخه‌ی نصب‌شده نام فایل دیگری داشت، همان‌جا اصلاحش کنید.

## ساختار

| فایل | کار |
|---|---|
| `resources/views/layouts/app.blade.php` | لایه‌ی اصلی (جهت و زبان از `app()->getLocale()`) |
| `resources/views/partials/*` | سایدبار، هدر، ناوبری پایین موبایل، دیالوگ گزارش خرابی |
| `resources/views/music/index.blade.php` | صفحه‌ی لیست موزیک |
| `resources/views/components/*` | `x-icon`, `x-page-tools`, `x-music.song-card`, `x-music.stat-card` |
| `resources/js/modules/*` | `dialogs`, `focus-mode`, `music-list`, `report` (jQuery) |
| `lang/{fa,en}/{ui,music}.php` | همه‌ی متن‌ها |
| `app/Support/Nav.php` | تعریف یک‌جای منو (href ها را عوض کنید) |

## قرارداد داده (برای بک‌اند)

متغیرهای ویو `music.index`:

| متغیر | نوع |
|---|---|
| `$songs` | `LengthAwarePaginator` (۹ مورد در هر صفحه) |
| `$level` | `a` \| `b` \| `c` |
| `$filters` | `{ q, filter: all\|new\|in_progress\|done\|free, sort: default\|newest\|points\|duration\|title }` |
| `$subscribed` | bool |
| `$stats` | `{ completed, in_progress, points, highlights }` |
| `$user` | `{ name, days_left, points, level, skills: {listening, reading, writing, speaking} }` |

هر آیتم `$songs`:

```json
{
  "id": 1, "title": "Say My Name", "artist": "David Guetta", "cover": "https://…/cover.webp",
  "level": "a", "duration": "02:32", "points": 50,
  "progress": 75, "status": "in_progress",
  "is_free": true, "free_steps": 2, "total_steps": 8
}
```

- `status`: `new` | `in_progress` | `done`
- `is_free`: یکی از ۳ آهنگ کاملاً رایگان. برای بقیه، کاربر رایگان فقط `free_steps` مرحله‌ی اول را می‌بیند.
- فیلتر، جستجو، مرتب‌سازی و صفحه‌بندی سمت سرور و با query string (`?level=&q=&filter=&sort=&page=`) است.
- «نمایش بیشتر» موبایل همان خروجی HTML صفحه‌ی بعد را با fetch می‌گیرد؛ API جدا لازم نیست.

## گزارش خرابی

`resources/js/modules/report.js` اسکرین‌شات بخش قابل‌مشاهده‌ی صفحه را می‌گیرد (html2canvas فقط هنگام کلیک لود می‌شود)
و payload زیر را می‌سازد. ارسال دو راه دارد:

1. رویداد: `$(document).on('report:submit', (e, payload) => {...})`
2. آدرس: مقدار `data-endpoint` روی `#report-form` در `partials/report-dialog.blade.php` (POST + JSON + CSRF)

```json
{ "description": "…", "screenshot": "data:image/jpeg;base64,… | null",
  "meta": { "url": "…", "locale": "fa", "userAgent": "…", "viewport": "390x844", "sentAt": "ISO-8601" } }
```

نکته: کاورها باید از همان دامنه یا با CORS درست سرو شوند، وگرنه در اسکرین‌شات خالی می‌افتند.

## حالت تمرکز

عناصری که `data-focus-hide` دارند در حالت تمرکز مخفی می‌شوند. انتخاب کاربر در `localStorage` (`teodel:focus`) می‌ماند.
در صفحات بعدی فقط `<x-page-tools />` را در هدر صفحه بگذارید و `data-focus-hide` را روی بخش‌های اضافی.

## وضعیت تست

PHP با `php -l` و JS با `node --check` بررسی شده. **بیلد Vite و نمایش در مرورگر انجام نشده** (در محیط ساخت، رجیستری npm در دسترس نبود)؛
پیش از استفاده یک بار `npm run build` و بازبینی چشمی در موبایل/دسکتاپ، هر دو زبان لازم است.
