// نقطه‌ی ورود پنل مدیریت (جدا از app.js تا سایت کاربران سبک بماند)
import $ from 'jquery';

window.$ = window.jQuery = $;

import './modules/dialogs';
import './modules/logout-confirm';
import './admin/shell';
import './admin/dashboard';
import './admin/ai';
import './admin/users';
import './admin/song';
import './admin/placement';
import './admin/dictionary';
