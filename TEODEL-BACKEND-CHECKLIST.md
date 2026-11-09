# چک‌لیست اتصال بک‌اند — Teodel

> برای برنامه‌نویس بک‌اند. فرانت کامل است و با داده‌ی نمونه کار می‌کند. هر بخش زیر یک «نقطه‌ی اتصال» است.
> قرارداد دقیق داده‌ی هر بخش در docblock بالای کلاس `app/Support/*.php` (علامت 🔌) و فایل JS همان بخش است.
> هر نقطه‌ی اتصال سه راه دارد: **آدرس** (`data-*-url`)، **رویداد jQuery لغوشدنی**، یا **جایگزینی تابع** در `window.Teodel.*` — راهنما بخش ۱۰.

---

## ۱. Route و دسترسی (`routes/web.php`)

- [ ] `->middleware('auth')` روی گروه کاربر.
- [ ] `->middleware(['auth', 'can:admin'])` (یا نقش معادل) روی گروه `/admin`.
- [ ] `Route::post('/logout', …)->name('logout')` (فرم خروج به `/logout` POST می‌کند).
- [ ] چهار POST ورود (`send / verify / password / register`) — قرارداد: `app/Support/AuthFlow.php`.
- [ ] route های اختیاری که فعلاً در کنترلرها کامنت شده‌اند: `dashboard.goal`، `dictionary.search`، `level-test.start|finish|level`، `words.view`.
- [ ] کنترل دسترسی **سمت سرور** (قفل‌های UI کافی نیستند):
  - موزیک/پادکست/شدوئینگ: کاربر رایگان روی آیتم غیررایگان فقط `free_steps` مرحله‌ی اول.
  - کتاب: کاربر رایگان روی کتاب غیررایگان فقط فصل اول — هم برای متن، هم فایل دانلودی (`BookController::download`)، هم خلاصه‌ی AI.
  - PDF لغات پادکست (`podcast.words-pdf`).

## ۲. داده (جایگزینی mock ها)

هر کنترلر فعلاً از `App\Support\X` می‌خواند؛ همان **شکل آرایه** را از دیتابیس بدهید تا view ها بدون تغییر کار کنند.

| بخش | کلاس قرارداد | کنترلر |
|---|---|---|
| موزیک | `Words`, `Lrc`, `Listening`, `Shadowing`, `Details`, `Progress` | `MusicController` |
| پادکست | `Podcast` | `PodcastController` |
| شدوئینگ | `ShadowingTopics` | `ShadowingController` |
| کتاب | `Books` | `BookController` |
| لغات و جمله‌ها | `SavedWords` (`demo()`, `sentences()`) | `WordsController` |
| داشبورد | `Dashboard` (+ `sections`) | `DashboardController` |
| ادمین | `AdminSongs`, `AdminPodcast`, `AdminShadowing`, `AdminBooks`, `AdminUsers`, `AdminBilling`, `AdminTickets`, `AdminReports`, `AdminSettings`, `AdminPlacement`, `AdminDictionary`, `AdminDashboard` | `Admin/*Controller` |

- [ ] `subscribed()` و `user()` در کنترلرهای کاربر (فعلاً ثابت).
- [ ] پیشرفت: جدول `user_progress` (user_id، kind: music|podcast|shadowing|book، item_id، completed_steps، current_step_fraction، updated_at).

## ۳. حالت دمو (`LOCAL = true`) — بعد از اتصال هرکدام `false` شود

| فایل | چه چیزی را در مرورگر نگه می‌دارد |
|---|---|
| `resources/js/modules/progress-sync.js` (`LOCAL_PROGRESS`) | پیشرفت کارت‌های لیست |
| `modules/words.js` (`LOCAL_SAVE`) | بوکمارک لغت‌های مرحله‌ی ۳ |
| `modules/saved-words.js` | حذف/مرور لغت‌ها در `/words` |
| `modules/book-reader.js` | جای خواندن، نشانک، تنظیمات خواننده، **جمله‌های محبوب** (تب `/words` هم از همین پیروی می‌کند) |
| `modules/listening.js`, `shadowing.js`, `shadowing-topic.js` | نتیجه‌ی تمرین‌ها |
| `modules/podcast-idea.js` | Main idea کاربر |
| `modules/dashboard.js` | هدف روزانه |
| `modules/profile.js` | پروفایل و آواتار |
| `modules/dictionary.js` | لغت‌های دیکشنری |
| `modules/level-test.js` | آزمون تعیین سطح |
| `modules/tickets.js` | تیکت‌ها |
| `admin/song.js` | ویرایشگر موزیک |
| `admin/content.js` | ویرایشگرهای پادکست، اپیزود، Shadowing، کتاب |
| `admin/dictionary.js`, `admin/placement.js` | دیکشنری و بانک سؤال ادمین |

## ۴. Endpoint های ویرایشگرهای ادمین (جدید)

در هر کنترلر ادمین، آرایه‌ی `'urls'` کامنت شده است؛ با ساختن route ها از کامنت درآورید:

| کلید | متد | بدنه / پاسخ |
|---|---|---|
| `save` | POST (جدید) / PUT (ویرایش) | `{ kind, status: draft|published, model }` ← `{ id, status, url? }` یا `422 { message, errors:{field:[…]} }` |
| `upload` | POST multipart | `file`, `type: image|audio|file`, `kind` ← `{ url, duration? }` |
| `delete` | DELETE | ← `{ ok: true }` |
| `ai` | POST | `{ kind, fields[], list?, item?, seeds, current, locale }` ← `{ fields:{…} | fields:{ items:[…] }, missing:[…] }` |
| `preview` | — | لینک صفحه‌ی کاربر |

`kind` مدل: `podcast` (سری)، `podcast_episode`، `shadowing`، `book`. شکل `model` = قرارداد `AdminPodcast` / `AdminShadowing` / `AdminBooks`.

AI kinds: `podcast_info, transcript_ai, transcript_tr, words_suggest, word_fill, dictation_suggest, shadowing_suggest, prosody, main_idea, shadowing_text, questions_suggest, book_info, book_summary, author_bio, chapter_summary, tts` (برای `tts` پاسخ `fields.audio` = آدرس فایل ساخته‌شده).

- [ ] سمت کاربر کتاب: متن هر فصل در ادمین یک رشته است (`text`)؛ برای سایت به `paragraphs[]` تبدیل شود: `preg_split('/\n\s*\n/', $text)`.
- [ ] Shadowing: فقط نسخه‌های `enabled` به سایت برسند.
- [ ] پادکست: فقط جمله‌های دیکته‌ی `approved` به کاربر برسند.

## ۵. Endpoint های سمت کاربر کتاب (جدید)

در `BookController::step` آرایه‌ی `'urls'` (فعلاً `null`):

| کلید | متد | بدنه / پاسخ |
|---|---|---|
| `progress` | POST | `{ book, chapter, offset, percent }` (هر ۱.۵ ثانیه حداکثر) |
| `settings` | POST | `{ book, theme, font, size }` |
| `sentences` | POST / DELETE | `{ id, book, book_title, chapter, text, ref, at }` / `{ id }` |
| `summary` | POST | `{ book, chapter }` ← `{ text }` (خلاصه‌ی AI فصل) |
| `lookup` | GET | `?word=` ← `{ phrase, type, level, ipa, meaning, synonyms, antonyms, translate }` |
| `download` | GET | فایل کتاب با کنترل دسترسی |

## ۶. رویدادها (اگر به‌جای آدرس با JS وصل می‌کنید)

- آموزش: `lesson:progress`, `lesson:step-complete`, `lesson:step-change`, `lesson:rating`
- کتاب: `book:progress`, `book:bookmark`, `book:settings`, `book:chapter-summary-request` (لغوشدنی)، `sentence:save`, `sentence:remove`
- لغات: `word:bookmark` (`kind: 'book'` برای کتاب), `words:view`, `words:review-done`
- ادمین: `admin:content-save|content-saved|content-upload|content-delete`, `admin:song-*`, `admin:ai-generate|ai-applied`
- بقیه: بخش ۱۰.۲ و ۱۰.۳ راهنما.

## ۷. فایل‌ها و AI

- [ ] مدیا از storage/CDN با CORS درست (اسکرین‌شات گزارش خطا).
- [ ] TTS (شدوئینگ و کتاب)، تشخیص گفتار + نمره‌دهی (شدوئینگ)، خلاصه‌ی فصل (کتاب)، تایم‌دهی/ترجمه‌ی ترانسکریپت (پادکست).
- [ ] کلید AI فقط سمت سرور (`AdminSettings`: `key_set`).

## ۸. مدیران و دسترسی‌ها (`/admin/staff`)

قرارداد: `app/Support/AdminStaff.php` — رفتار: `resources/js/admin/staff.js`.

- [ ] جدول ادمین‌ها: `name, phone (یکتا، 09xxxxxxxxx), password (هش), role (owner|manager|content|support|finance|viewer|custom), permissions (JSON: بخش ← none|view|edit|full), status, must_change_password, last_login, created_by`.
- [ ] route های `/admin/staff*` فقط برای `role = owner`؛ مدیر اصلی قابل حذف/غیرفعال/محدود شدن نیست.
- [ ] Gate/Policy برای هر بخش با `AdminStaff::can($perms, $section, 'view'|'edit'|'full')` — روی route های GET (view) و POST/PUT (edit) و حذف/انتشار/بازپرداخت (full).
- [ ] منوی هر ادمین: `$adminHide = AdminStaff::hidden($perms)` (بخش «بدون دسترسی» در منو نمی‌آید).
- [ ] ذخیره `POST /admin/staff` (جدید، با `password`) و `PUT /admin/staff/{id}` (بدون رمز) ← `{ id }` یا `422 { errors: { phone: [...] } }`.
- [ ] بازنشانی رمز `POST /admin/staff/{id}/password { password, must_change_password: true, send_sms }`.
- [ ] حذف `DELETE /admin/staff/{id}`.
- [ ] ورود ادمین با شماره + رمز؛ اگر `must_change_password` بود، قبل از ورود به پنل صفحه‌ی تغییر رمز.
- [ ] ارسال پیامک اطلاعات ورود وقتی `send_sms = true`.
- [ ] در `admin/content.js` و `admin/staff.js` و … ثابت `LOCAL` را `false` کنید.
