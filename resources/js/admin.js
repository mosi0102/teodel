// نقطه‌ی ورود پنل مدیریت (جدا از app.js تا سایت کاربران سبک بماند)
import $ from 'jquery';

window.$ = window.jQuery = $;

import './modules/dialogs';
import './modules/logout-confirm';
import './admin/shell';
import './admin/export';
import './admin/dashboard';
import './admin/ai';
import './admin/users';
import './admin/song';
import './admin/content'; // ویرایشگر مشترک پادکست، Shadowing و کتاب
import './admin/staff'; // مدیران و دسترسی‌ها
import './admin/placement';
import './admin/dictionary';
import './admin/billing';
import './admin/desk';
import './admin/report';
import './admin/settings';
