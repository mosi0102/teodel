# راهنمای کامل پروژه‌ی Teodel (تئودل)

> این فایل برای دو نفر نوشته شده: **۱) هوش مصنوعی‌ای که کار فرانت را ادامه می‌دهد** و **۲) برنامه‌نویس بک‌اند** که باید پروژه را به دیتابیس و سرویس‌ها وصل کند.
> کارهای باقیمانده در فایل جداگانه‌ی `TEODEL-REMAINING-TASKS.md` است.
> آخرین وضعیت (۱۸ آبان ۱۴۰۵): سمت کاربر هر چهار بخش (موزیک، پادکست، شدوئینگ، کتاب) و پنل ادمین هر چهار بخش آماده است؛ نمایش لحن (prosody) بازطراحی شد، داشبوردها به‌روز شد و «به‌زودی» از هر دو پنل حذف شد. فقط «بازی» (game) در دامنه نیست. چک‌لیست اتصال بک‌اند: `TEODEL-BACKEND-CHECKLIST.md`.

---

## ۱. پروژه چیست؟

سایت آموزش زبان انگلیسی **Teodel** با یادگیری از طریق **موزیک، پادکست، شدوئینگ و کتاب**.

- دو زبانه: **فارسی (RTL)** و **انگلیسی (LTR)**؛ پیش‌فرض فارسی. کاربر از دکمه‌ی `En/فا` در هدر زبان را عوض می‌کند (`/lang/{fa|en}`).
- **موبایل‌محور و شبیه اپلیکیشن**، با نسخه‌ی دسکتاپ هم‌وزن (نوار پایین موبایل، سایدبار دسکتاپ).
- دو پنل: **پنل کاربر** و **پنل مدیریت** (`/admin`).
- **فقط فرانت‌اند ساخته شده است.** داده‌ها نمونه (mock) هستند و هر جا ذخیره یا خواندن واقعی لازم است، یک «نقطه‌ی اتصال» (hook) مستند شده است. بک‌اند را برنامه‌نویس دیگری می‌نویسد.

### سطح‌ها
- موزیک: A / B / C (سه سطح).
- پادکست: A / B / C.
- شدوئینگ: هر موضوع نسخه‌هایی از **A1 تا C2** دارد.
- کتاب: سطح A (مثلاً A2) به بالا طبق فرم کتاب‌ها.
- دیکشنری: A1–C2.

---

## ۲. استک فنی

| لایه | ابزار |
|---|---|
| فریم‌ورک | Laravel (Blade)؛ نسخه‌ی ۱۰ به بالا |
| CSS | **Tailwind CSS 3** (`@apply` + کلاس‌های سفارشی) |
| JS | **jQuery 3** + ماژول‌های ES (بدون فریم‌ورک SPA) |
| بیلد | **Vite 5** + `laravel-vite-plugin` (چند entry) |
| اسکرین‌شات گزارش خطا | `html2canvas` (lazy) |
| فونت | یکان بخ (فارسی)، Funnel Display (انگلیسی)، فایل‌ها در `public/fonts` |

نصب:
```bash
npm i -D tailwindcss@3 postcss autoprefixer
npm i jquery html2canvas
composer dump-autoload   # بعد از اضافه‌کردن "app/Support/helpers.php" به autoload.files
npm run dev   # یا npm run build
```

`vite.config.js` چند entry دارد: `fonts.css, app.css, admin.css, auth.css, site.css, custom.css` و `resources/js/{app,admin,auth,site}.js` و `resources/js/pages/*.js`.
**هر صفحه‌ی جدید که JS مخصوص خود دارد باید در `vite.config.js` هم اضافه شود** (مثلاً `pages/lesson.js` برای صفحه‌های آموزش).

---

## ۳. قوانین ثابت کار (حتماً رعایت شود)

1. **`resources/css/custom.css` مال صاحب پروژه است**؛ آخر از همه لود می‌شود. هیچ‌وقت بازنویسی یا خراب نشود. رنگ اصلی با `--brand-*` و فونت با `--font-fa` / `--font-en` همان‌جا تعریف می‌شود.
2. تحویل کار: **فقط فایل‌های جدید یا تغییرکرده** در یک زیپ به نام `teodel-update-N.zip` (N یک‌به‌یک زیاد می‌شود؛ آخرین: ۵۲). مسیر داخل زیپ همان مسیر پروژه است.
3. اگر چیزی مبهم است **قبل از ساختن بپرس** (کوتاه، چندگزینه‌ای). اگر روشن است، شروع کن.
4. توضیح‌ها **کوتاه و ساده** (کاربر فارسی‌زبان است).
5. UI/UX را هر جا بهتر می‌شود بهتر کن، ولی ساختار و سبک بقیه‌ی سایت را حفظ کن.
6. کد تمیز، سریع، سازگار با همه‌ی مرورگرها (Safari/iOS، Firefox، Chrome).
7. انیمیشن فقط **سبک و CSS**، و همیشه با `prefers-reduced-motion` خاموش شود.
8. **هیچ برچسب «به‌زودی / Soon» نشان داده نشود** (نه در پنل کاربر، نه ادمین). بخشی که هنوز ساخته نشده اصلاً در منو نیست.
9. سایدبار: آیتم «ویدیو/فیلم» حذف و «Shadowing» جایگزین شده است.
10. تمام متن‌های UI در `lang/{fa,en}/*.php` باشد (هیچ متن سخت‌کد در Blade نباشد). هر کلید در **هر دو زبان** باشد.
11. اعداد فارسی: تابع `num()` در `app/Support/helpers.php` (در زبان فارسی عدد فارسی می‌نویسد).
12. هر تغییر در Blade/JS/CSS باید با تست مرورگر (Chromium/Playwright، fa و en، موبایل و دسکتاپ، بدون overflow و بدون خطای JS) بررسی شود.

### نکته‌های فنی Tailwind 3 که قبلاً مشکل ساختند
- در یک کلاس سفارشی که `@apply` دارد، **نام کلاس‌های utility را به‌عنوان selector استفاده نکن** (خطای circular dependency).
- هر کلاس پیشوند اختصاصی دارد تا تداخل نکند: `pc-*` (پادکست)، `sh-*` و `shc-*` (شدوئینگ)، `bk-*` (کتاب)، `pr-*` (prosody/آهنگ‌خوانی)، `ad-*` (ادمین).
- `hoverOnlyWhenSupported` روشن است (hover روی موبایل نمی‌چسبد).

---

## ۴. ساختار پوشه‌ها

```
app/
  Http/Controllers/            کنترلرهای کاربر (Music, Podcast, Shadowing, Dashboard, Profile, Dictionary,
                               Placement, Ticket, Subscription, Words, Locale, AuthPage, SitePage)
  Http/Controllers/Admin/      Dashboard, Song, User, QuestionBank, Word, Billing, TicketDesk, BugReport, Settings
  Support/                     «قرارداد داده» + داده‌ی نمونه‌ی هر بخش (جای جایگزینی با دیتابیس) ← مهم‌ترین پوشه برای بک‌اند
  Http/Middleware/SetLocale.php
lang/{fa,en}/                  همه‌ی متن‌ها (یک فایل برای هر بخش)
resources/
  css/ app.css admin.css auth.css site.css fonts.css custom.css
  js/modules/                  ماژول‌های jQuery کاربر
  js/admin/                    ماژول‌های ادمین
  js/pages/                    entry هر صفحه (فقط import ماژول‌ها)
  views/layouts/               app (کاربر)، admin، guest (ورود)، site (عمومی)
  views/partials/              sidebar, topbar, bottom-nav, report-dialog, logout-dialog
  views/components/            x-icon, x-page-tools, x-music.*, x-podcast.*, x-shadowing.*, x-lesson.*, x-site.*
  views/{music,podcast,shadowing,...}/   صفحه‌های هر بخش
  views/admin/...              صفحه‌های ادمین
routes/ web.php  admin.php  auth.php  site.php  music.php (نمونه)
public/ fonts/ images/ covers/ songs/
```

### اتصال صفحه به Vite
هر صفحه‌ی کاربر یک entry دارد؛ مثلاً `resources/js/pages/lesson.js` فقط ماژول‌ها را import می‌کند (player, lyrics, words, listening, shadowing, karaoke, rating, details, podcast-idea, shadowing-topic …).

---

## ۵. مسیرها (Routes)

فایل‌ها: `routes/web.php` (اصلی) + `auth.php` (`/login`, `/register` با `guest`) + `site.php` (`/`, `/faq`, `/terms`).

| بخش | آدرس‌ها | نام route |
|---|---|---|
| زبان | `GET /lang/{fa\|en}` | `lang` |
| موزیک | `/music`، `/music/{id}`، `/music/{id}/step/{step}` | `music.index/show/step` |
| پادکست | `/podcast`، `/podcast/series/{id}`، `/podcast/{id}`، `/podcast/{id}/step/{step}`، `/podcast/{id}/words.pdf` | `podcast.index/series/show/step/words-pdf` |
| شدوئینگ | `/shadowing`، `/shadowing/{id}`، `/shadowing/{id}/step/{step}` | `shadowing.index/show/step` |
| کتاب | `/books`، `/books/{id}`، `/books/{id}/step/{step}`، `/books/{id}/download` | `books.index/show/step/download` |
| بقیه | `/dashboard /profile /dictionary /level-test /support /subscription /subscription/result /words` | `dashboard, profile.index, dictionary.index, level-test.index, tickets.index, subscription.index, subscription.result, words.index` |
| ادمین | `/admin` و `/admin/{users,songs,podcast,shadowing,books,placement,dictionary,billing,tickets,reports,settings,staff}` | `admin.*` |
| ادمین پادکست | `/admin/podcast/{id}/edit`، `/admin/podcast/{id}/episodes/create`، `/admin/podcast/{id}/episodes/{ep}/edit` | `admin.podcast.edit`، `admin.podcast.episodes.create/edit` |

نکات:
- `{id}` و `{step}` با `whereNumber` محدود شده‌اند.
- `Nav.php` برای بخش‌های جدید از `Route::has('x') ? route('x') : '#'` استفاده می‌کند تا نبودن route صفحه را نشکند.
- **middleware ادمین و `auth` عمداً فعلاً نیست**؛ بک‌اند‌کار باید `auth` را روی گروه کاربر و `auth + can:admin` (یا معادل) را روی گروه `/admin` بگذارد. `/logout` (POST) را هم بک‌اند می‌سازد (فرم خروج به آن POST می‌کند).
- ترتیب مهم است: روت‌های ثابت (`/podcast/series/{id}`) قبل از `/podcast/{id}` بیایند.

---

## ۶. ناوبری

- **کاربر:** `app/Support/Nav.php` — یک‌جا تعریف می‌شود (کلیدها: dashboard, music, podcast, shadowing, game, books, words, level_test, dictionary, support). `soon=true` یعنی در منو نمایش داده نمی‌شود. نوار پایین موبایل ۴ آیتم دارد (`bottomKeys`) + «بیشتر».
- **ادمین:** `app/Support/AdminNav.php` — گروه‌ها: overview, content (songs, podcast, shadowing, books, placement, dictionary), people (users, billing), support (tickets, reports), system (settings, staff).
- **مدیران و دسترسی‌ها (`/admin/staff`، فقط مدیر اصلی):** افزودن ادمین با شماره‌ی موبایل و رمز پیش‌فرض (ساخت خودکار، «تغییر در اولین ورود»، پیامک)، نقش آماده (مدیر، محتوا، پشتیبان، مالی، فقط مشاهده) یا جدول دسترسی سفارشی برای هر بخش (بدون دسترسی/دیدن/ویرایش/کامل)، غیرفعال‌کردن، بازنشانی رمز، حذف. قرارداد: `AdminStaff.php`؛ رفتار: `resources/js/admin/staff.js`. آیکون‌ها SVG داخل همان کلاس. `badges` (عدد کنار آیتم) و `hide` (پنهان‌کردن آیتم بر اساس نقش) را بک‌اند می‌دهد.
- برچسب «به‌زودی» از هر دو پنل حذف شد؛ بخش ساخته‌نشده (فعلاً فقط game) اصلاً در منو نمی‌آید.

---

## ۷. الگوی صفحه‌ی آموزش (قلب پروژه)

یک view مشترک برای هر چهار نوع محتوا: **`resources/views/music/lesson.blade.php`**.

- متغیر `$kind` ∈ `music | podcast | shadowing | book` تعیین می‌کند کدام پوشه‌ی مرحله‌ها لود شود (`$stepDir`: `music | podcast | shadowing | books`) و کدام متن‌ها.
- هر **مرحله فایل جدا** دارد: `views/<dir>/steps/<name>.blade.php`.
- متن‌های مخصوص هر نوع در `lang/{fa,en}/<kind>_lesson.php` است و در کنترلر با
  `Lang::addLines(Arr::dot(trans('<kind>_lesson'), 'lesson.'), app()->getLocale())` روی `lesson.*` سوار می‌شود. (موزیک: `lesson.php`)
- نوار مراحل، درصد پیشرفت، دکمه‌های قبل/بعد و قفل مراحل برای کاربر رایگان همه در همین view است.
- کاربر رایگان روی محتوای غیررایگان فقط `free_steps` مرحله‌ی اول را می‌بیند؛ بقیه `steps/_locked.blade.php`.
- دسترسی رایگان: **۳ آیتم کاملاً رایگان**، بقیه فقط مرحله(ها)ی اول (برای کتاب: فقط فصل اول).

### مراحل هر نوع
**موزیک (۸ مرحله):** play، lyrics، words، listening، shadowing، karaoke، repeat، details.

**پادکست (۷ مرحله، مثل موزیک بدون karaoke):**
1. play — گوش‌دادن + پرسش «چقدر متوجه شدی؟» + کادر اختیاری **Main idea** (۱–۲ جمله).
2. lyrics — متن همزمان (ترانسکریپت).
3. words — لغات + دانلود PDF لغات.
4. listening — دیکته‌ی جمله؛ **جمله‌ها را سیستم انتخاب می‌کند** (کاربر انتخاب نمی‌کند).
5. shadowing — نمایش **آهنگ‌گفتار (prosody)**: بالا/پایین/یکنواخت + ریتم.
6. repeat — دوباره «چقدر متوجه شدی؟».
7. details — اطلاعات پادکست + **Main idea رسمی کنار نوشته‌ی کاربر**.
ساختار: «پادکست» = یک سری (series) با چند **اپیزود**؛ تعداد اپیزودها و **مدت** (خیلی برجسته) نشان داده می‌شود؛ ادمین می‌تواند «پیشنهادی» (recommended) علامت بزند.

**شدوئینگ (۵ مرحله):** listen (۳ بار) ← record (ضبط + بررسی AI) ← practice (تمرین بقیه) ← text (متن کامل + لغات سخت با محل ظهور) ← questions (پاسخ صوتی به سؤال‌ها + بررسی AI). هر موضوع نسخه‌های A1–C2 دارد؛ صوت از **AI TTS یا آپلود ادمین**.

**کتاب (۴ مرحله):** intro (خلاصه + اطلاعات کامل + فهرست فصل‌ها + دانلود) ← read (خواننده) ← review (جمله‌های محبوب، لغت‌های ذخیره‌شده، لغات کلیدی، خلاصه‌ی فصل با AI) ← author (بیوگرافی + آثار + کتاب‌های هم‌سطح).
- view ها: `resources/views/books/{index,print}.blade.php` و `books/steps/{intro,read,review,author}`؛ کارت: `components/book/card`؛ رفتار: `resources/js/modules/book-reader.js`.
- خواننده: یک فصل در هر لحظه، نشانک و «ادامه از جایی که ماندی»، تنظیمات **برای هر کتاب جدا** (تم روشن/سپیا/تیره، فونت ساده/کتابی، اندازه ۱۵ تا ۲۶)، لمس لغت ← کارت معنی (bottom sheet)، انتخاب متن ← «ذخیره‌ی جمله» (هایلایت داخل کتاب + تب «جمله‌های محبوب» در `/words?tab=sentences`)، پیشرفت = درصد خوانده‌شده‌ی فصل‌های باز.
- دسترسی: مرحله‌ها قفل نمی‌شوند؛ کاربر رایگان روی کتاب غیررایگان فقط **فصل اول** را دارد و متن بقیه‌ی فصل‌ها اصلاً به HTML نمی‌رسد (`Books::chapterLocked`).
- دانلود: `BookController::download` — فایل واقعی (اگر در public باشد) یا نسخه‌ی قابل‌چاپِ فصل‌های باز (`books/print`).

### نشانه‌گذاری prosody (`App\Support\Shadowing::prosody`)
`^` بالا رفتن، `_` پایین آمدن، `-` یکنواخت، `*کلمه*` تأکید، `|` جداکننده‌ی گروه معنایی. رندر در `components/lesson/prosody.blade.php`.
- **نمایش (بازطراحی‌شده):** هر گروه معنایی یک کپسول کم‌رنگ هم‌رنگ لحن است و آخرش یک نشان کوچک با فلش (↗ سبز / ↘ قرمز / → کهربایی؛ کامپوننت `x-lesson.tone-icon`)؛ کلمه‌ی تأکیددار پررنگ است با یک نقطه‌ی تأکید بالای آن. منحنی‌های «دست‌کشیده» حذف شد.
- ادمین همین نمایش را به‌صورت پیش‌نمایش زنده دارد (`x-ad.prosody` + `renderProsody` در `resources/js/admin/content.js`، هم‌قاعده با PHP).

---

## ۸. پیشرفت (Progress)

`app/Support/Progress.php`:
```
درصد = (مرحله‌های تمام‌شده + بخشی از مرحله‌ی جاری) ÷ تعداد کل مرحله‌ها × ۱۰۰
```
- بک‌اند فقط `completed_steps` و `current_step_fraction` (۰ تا ۱) می‌دهد. وضعیت: `new | in_progress | done`.
- `TOTAL_STEPS` برای موزیک ۸ است؛ پادکست ۷، شدوئینگ ۵، کتاب ۴ → تابع پارامتر `$total` دارد.
- داخل هر مرحله فقط این‌ها صدا زده می‌شوند:
```js
Teodel.lesson.setStepProgress(0.4);
Teodel.lesson.completeStep();
```
رویدادها برای ذخیره: `lesson:progress`، `lesson:step-complete`، `lesson:step-change` با `{ song, step, fraction, completed, percent }`.

---

## ۹. قرارداد داده (برای بک‌اند)

**همه‌ی داده‌ی نمونه در `app/Support/*.php` است** و بالای هر کلاس مدل داده با علامت 🔌 مستند شده. کنترلر فعلاً از همین کلاس‌ها می‌خواند؛ بک‌اند‌کار باید همان شکل خروجی را از دیتابیس بدهد (آرایه/Collection با همان کلیدها)، تا ویوها بدون تغییر کار کنند.

| کلاس | محتوا |
|---|---|
| `Podcast` | سری‌ها (id از ۱۰۰۱) و اپیزودها؛ `series() / all() / fmt()`، `STEPS` |
| `ShadowingTopics` | موضوع‌ها (id از ۲۰۰۱) با نسخه‌ی A1–C2 |
| `Shadowing` | برش‌های مرحله‌ی shadowing موزیک + `prosody()` |
| `Books` | کتاب‌ها (id از ۳۰۰۱): فصل‌ها (paragraphs)، خلاصه، نویسنده، لغات، `fmtMinutes`، `chapterLocked`، `FREE_CHAPTERS` |
| `Words`, `SavedWords`, `WordExtras`, `Dictionary` | لغات مرحله‌ی ۳، لغات ذخیره‌شده، داده‌ی تکمیلی |
| `Listening`, `Lrc`, `Details` | مرحله‌ی ۴، پارس LRC، مرحله‌ی ۸ |
| `Dashboard`, `Profile`, `Subscription`, `Tickets`, `PlacementTest` | قراردادهای صفحه‌های کاربر |
| `AuthFlow` | ورود: شماره ← کد پیامکی (یا رمز) ← (کاربر جدید) نام+رمز؛ چهار POST (`send/verify/password/register`) را بک‌اند می‌سازد |
| `Admin*` | قرارداد فرم‌های ادمین (Songs, Users, Billing, Tickets, Reports, Settings, Placement, Dictionary, Dashboard) |
| `AdminPodcast`, `AdminShadowing`, `AdminBooks` | قرارداد ویرایشگرهای جدید (سری/اپیزود، موضوع با نسخه‌های A1–C2، کتاب با فصل‌ها) + قانون کامل‌بودن هر تب (`*_NEED`) |
| `AdminContent` | ابزار مشترک: `doneMap` (کامل‌بودن تب‌ها، هم‌قاعده با JS) و `query` (فیلتر/صفحه‌بندی لیست‌های ادمین) |
| `SavedWords::sentences()` | جمله‌های محبوب کاربر (از کتاب‌ها) |

### مثال: لیست موزیک (`music.index`)
متغیرها: `$songs` (Paginator، ۹ در صفحه)، `$level` (a|b|c)، `$filters {q, filter: all|new|in_progress|done|free, sort: default|newest|points|duration|title}`، `$subscribed`، `$stats`، `$user`.
هر آیتم: `{ id, title, artist, cover, level, duration:"02:32", points, progress, status, is_free, free_steps, total_steps }`.
فیلتر/جستجو/مرتب‌سازی/صفحه‌بندی **سمت سرور** و با query string (`?level=&q=&filter=&sort=&page=`). «نمایش بیشتر» موبایل، HTML صفحه‌ی بعد را با fetch می‌گیرد (API جدا نمی‌خواهد).

پادکست و شدوئینگ و کتاب همین الگو را دارند (فیلترها: پادکست `recommended` و `episodes`؛ کتاب `type`, `recommended`, `pages`).

### مدل ادمین موزیک (نمونه کامل — `AdminSongs`)
فیلدها: `id, status(draft|published), title, artist, cover, level, is_free, points, accent, year, genre, wpm, topic, album, duration, desc_fa, desc_en, audio, audio_vocal, audio_backing, q_play{fa,en}, q_repeat{fa,en}, lyrics[{t,text,tr}], words[…], listening{easy[],hard[]}, shadowing[{start,end,text}], shadowing_repeats, interpretation, notes[…]`.
ذخیره: `data-save-url` (POST ساخت / PUT ویرایش، JSON کامل + CSRF) ← `200 {id,status,url?}` یا `422 {message,errors}`.
آپلود: `data-upload-url` (multipart: `file`, `kind=cover|audio|audio_vocal|audio_backing`) ← `{url, duration?}`.
حذف: `data-delete-url` (DELETE). «انتشار» فقط وقتی کامل‌بودن مراحل (completeness در همین کلاس) تأیید شود.

---

## ۱۰. نقاط اتصال بک‌اند (Hooks)

هر نقطه‌ی اتصال **سه راه** دارد (هر کدام را که راحت‌تری انتخاب کن):
1. **آدرس** در attribute های `data-*-url` روی عنصر ریشه (POST/PUT/DELETE + JSON + هدر CSRF).
2. **رویداد jQuery** لغوشدنی (`e.preventDefault()` و بعد `done()`/`fail()`).
3. **جایگزینی تابع** در `window.Teodel.*` (مثلاً `Teodel.admin.ai.generate = async (payload) => …`).

### ۱۰.۱ حالت دمو: `LOCAL`
ماژول‌هایی که ثابت `const LOCAL = true` دارند، ذخیره را در **localStorage** انجام می‌دهند (فقط دمو):
`tickets, dictionary, profile, dashboard, saved-words, podcast-idea, level-test, book-reader` (کاربر؛ تب «جمله‌های محبوب» در /words هم از `book-reader` پیروی می‌کند) و `admin/{bank, dictionary, placement, song, content}` (ادمین).
**با وصل‌شدن بک‌اند هر کدام را `false` کنید.**

### ۱۰.۲ رویدادهای سمت کاربر (برای ذخیره‌ی پیشرفت و رفتار)
- آموزش: `lesson:progress`, `lesson:step-complete`, `lesson:step-change`, `lesson:rating` (امتیاز «چقدر متوجه شدی»), `player:time`, `player:listened`
- listening: `listening:result` | clip: `clip:round`
- shadowing: `shadowing:listen`, `shadowing:attempt`, `shadowing:round`, `shadowing:answer`
- پادکست: `podcast:main-idea` (متن Main idea کاربر)
- کتاب: `book:progress`, `book:bookmark`, `book:settings`, `book:chapter-summary-request` (لغوشدنی؛ AI)، `sentence:save`, `sentence:remove`؛ و `word:bookmark` با `kind: 'book'`
- لغات: `word:bookmark`, `words:view`, `words:review-done`
- پروفایل: `profile:submit`, `profile:avatar`
- داشبورد: `dashboard:goal`, `dashboard:tip-dismiss`
- آزمون سطح: `placement:start|answer|finish|level|set-level`
- اشتراک: `subscription:select`, `subscription:checkout`
- تیکت: `ticket:created|replied|closed|reopened|read`
- ورود: `auth:success` | گزارش خطا: `report:submit`
- لیست موزیک: `music:list-updated` | دیالوگ‌ها: `dialog:open|close`

### ۱۰.۳ رویدادهای ادمین
`admin:song-save|song-saved`, `admin:word-saved`, `admin:question-saved`, `admin:user-action`, `admin:settings-ready|settings-save`, `admin:ai-generate|ai-applied`, `ad:export`.
ویرایشگر مشترک (پادکست، اپیزود، Shadowing، کتاب): `admin:content-save|content-saved`, `admin:content-upload`, `admin:content-delete` (همه با `kind`، لغوشدنی).

### ۱۰.۳.۱ ویرایشگر مشترک محتوای ادمین (`resources/js/admin/content.js`)
- قالب‌ها: `admin/content/editor.blade.php` (هدر، تب‌ها، پخش‌کننده‌ی تایم‌گیری، نوار ذخیره، دیالوگ انتشار/حذف/درون‌ریزی) و `admin/content/index.blade.php` (لیست). هر بخش فقط چند متغیر می‌دهد و تب‌هایش را می‌نویسد.
- کامپوننت‌های فرم: `x-ad.f` (فیلد)، `x-ad.up` (آپلود تصویر/صوت/فایل؛ مدت صوت خودکار)، `x-ad.prosody` (ویرایشگر لحن + پیش‌نمایش).
- اتصال با data-* : `data-f` (فیلد، مسیر نقطه‌دار)، `data-ce-list` + `template[data-ce-tpl]` + `data-k` (لیست‌ها)، `data-ce-ai` (AI)، `data-ce-tts` (ساخت صدا)، `data-ce-import` (SRT/LRC/پیست)، `data-ce-if` (نمایش شرطی). راهنمای کامل بالای فایل JS است.
- AI kinds جدید: `podcast_info, transcript_ai, transcript_tr, words_suggest, word_fill, dictation_suggest, shadowing_suggest, prosody, main_idea, shadowing_text, questions_suggest, book_info, book_summary, author_bio, chapter_summary, tts` — هرکدام تا وصل‌شدن بک‌اند دموی خودش را دارد.

### ۱۰.۴ مهم‌ترین endpoint‌ها (attribute ها)
`data-save-url`, `data-upload-url`, `data-delete-url`, `data-ai-url`, `data-endpoint` (گزارش خطا), `data-lookup-url` (جستجوی لغت), `data-check-url`/`data-answer-url`, `data-start-url`/`data-finish-url`, `data-checkout-url`, `data-reply-url`/`data-close-url`/`data-reopen-url`, `data-goal-url`, `data-bulk-url`/`data-export-url`.
جزئیات payload هر کدام در docblock بالای فایل JS مربوطه و کلاس `Support` همان بخش است.

### ۱۰.۵ هوش مصنوعی (AI)
- **دکمه‌ی AI در ادمین**: دیالوگ مشترک (`resources/js/admin/ai.js`). درخواست:
  `POST data-ai-url { kind, seeds, fields[], note, current{…}, locale }` ← `200 { fields:{name:value}, missing:[…] }`. فیلدی که AI پر کند علامت «ساخته‌ی AI — بررسی کن» می‌گیرد.
- **تنظیمات AI** در `AdminSettings`: `enabled, model, batch_max (۵–۳۰), key_set`. کلید واقعی **هرگز** به مرورگر نمی‌رود.
- **بررسی‌های AI سمت کاربر** (باید بک‌اند بسازد): تشخیص گفتار + نمره‌ی تلفظ/درک در shadowing (مرحله‌ی ضبط و پاسخ صوتی)، خلاصه‌ی فصل کتاب، ساخت صوت TTS برای shadowing/کتاب، ساخت اطلاعات/ترجمه/لغت در ادمین، تشخیص جمله‌های مناسب دیکته در پادکست.
- ضبط صدا در مرورگر با `MediaRecorder` و تشخیص گفتار مرورگر با `SpeechRecognition` (فقط نمونه‌ی دمو)؛ ارسال به سرور با رویدادهای shadowing.

### ۱۰.۶ فایل‌ها
- مدیا (mp3، کاور) از آدرس‌های `public/songs` و `public/covers` در دمو؛ در تولید از storage/CDN. **کاورها باید از همان دامنه یا با CORS درست سرو شوند** وگرنه اسکرین‌شات گزارش خطا خالی می‌شود.
- PDF لغات پادکست: `GET /podcast/{id}/words.pdf` (فعلاً view چاپی `podcast/words-print`؛ بک‌اند می‌تواند PDF واقعی بسازد).
- فایل دانلودی کتاب: لینک در داده‌ی کتاب (`file_url`) — بک‌اند با کنترل دسترسی می‌دهد.

---

## ۱۱. دسترسی و اشتراک

- فقط ۳ آیتم از هر بخش «کاملاً رایگان» است (`is_free`)؛ بقیه برای کاربر رایگان فقط `free_steps` اول (کتاب: فصل اول).
- `$subscribed` را بک‌اند می‌دهد. صفحه‌ی `/subscription` پلن‌ها + مودال تأیید + نتیجه‌ی پرداخت را دارد (رویداد `subscription:checkout`).
- قفل‌شدن مرحله‌ها فقط در UI است؛ **بک‌اند باید دسترسی را سمت سرور هم کنترل کند.**

---

## ۱۲. تست و کیفیت (روشی که تا امروز استفاده شد)

محیط ساخت Laravel و Tailwind 3 واقعی نداشت، پس:
- PHP: `php -l` برای همه‌ی فایل‌ها؛ JS: `node --check`.
- رندر Blade با یک رندرگر کوچک PHP، باندل JS با esbuild، کامپایل Tailwind، و تست با **Playwright/Chromium** (موبایل ۳۹۰×۸۴۴ و دسکتاپ، fa و en، `MediaRecorder` و `SpeechRecognition` ماک‌شده): بدون overflow افقی، بدون خطای کنسول.
- **بیلد واقعی `npm run build` و بازبینی چشمی در پروژه‌ی اصلی** را صاحب پروژه انجام می‌دهد؛ هر خطای بیلد را گزارش می‌دهد.

چک‌لیست قبل از تحویل هر update:
- [ ] کلیدهای lang در هر دو زبان هست
- [ ] هیچ «به‌زودی» نمایش داده نمی‌شود
- [ ] route های استفاده‌شده تعریف شده‌اند (`grep -rhoE "route\('[a-zA-Z0-9._-]+'" resources app`)
- [ ] ورودی جدید به `vite.config.js` اضافه شد (اگر صفحه‌ی جدید)
- [ ] `custom.css` دست نخورده
- [ ] RTL و LTR، موبایل و دسکتاپ، reduced-motion
- [ ] زیپ فقط شامل فایل‌های جدید/تغییرکرده

---

## ۱۳. راهنمای کوتاه برای بک‌اند‌کار (ترتیب پیشنهادی)

1. مدل‌ها و migration ها را از روی `app/Support/*` بساز (هر کلاس = یک جدول/رابطه).
2. در کنترلرها به‌جای `App\Support\X::all()` از Eloquent بخوان و **همان شکل آرایه** را بده.
3. middleware: `auth` برای کاربر، `auth` + نقش ادمین برای `/admin`؛ `/logout` و POST های ورود (`AuthFlow`).
4. پیشرفت: جدول `user_progress` (user_id، kind، item_id، completed_steps، fraction، updated_at) + endpoint برای رویدادهای `lesson:*`.
5. لغات ذخیره‌شده، جمله‌های محبوب کتاب، Main idea پادکست، امتیاز «چقدر متوجه شدی» (مرحله‌ی ۱ و ۶).
6. ادمین: ذخیره/آپلود/حذف طبق قرارداد ۹ و ۱۰؛ سپس `LOCAL=false`.
7. AI: endpoint مشترک ادمین + TTS + تشخیص گفتار/نمره‌دهی + خلاصه‌ی فصل.
8. پرداخت (`/subscription`)، تیکت، گزارش خطا (`report:submit`)، حالت نگهداری (`maintenance`).
9. کنترل دسترسی سرور (رایگان/اشتراکی) و فایل‌های دانلودی.

---

## ۱۴. راهنمای ویژه برای هوش مصنوعی که کار را ادامه می‌دهد

- ابتدا این فایل و `TEODEL-REMAINING-TASKS.md` را کامل بخوان؛ سپس کد مربوط به بخش مشابه را بخوان (برای Books: پادکست/شدوئینگ؛ برای ادمین پادکست/شدوئینگ/کتاب: `admin/songs` + `AdminSongs.php` + `admin/song.js`).
- **الگوی را کپی کن، نه اختراع**: view مشترک `music.lesson`، کلاس `Support` با قرارداد 🔌 در docblock، ماژول JS کوچک با `LOCAL`، کلیدهای lang دو زبانه.
- قبل از ساختن هر بخش: route ها، نام کلاس‌ها، نام کلیدهای lang را با `grep` چک کن (چند باگ قبلی از route/کلید ناموجود بود).
- در سندباکس معمولاً Laravel/npm نیست؛ از روش تست بخش ۱۲ استفاده کن و صادقانه بگو چه چیزی تست نشده است.
- با هر update یک زیپ جدید + یک‌خط توضیح ساده بده؛ اگر route جدید لازم است، **کل بلوک route را آماده بده** (کاربر قبلاً فقط به‌صورت دستی ادغام کرده و خطای `Route [x] not defined` گرفته).
