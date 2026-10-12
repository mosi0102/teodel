// ترتیب مهم است: فونت‌ها ← استایل‌های اصلی ← استایل شخصی شما (آخر، تا برنده باشد)
import '../css/fonts.css';
import '../css/app.css';
import '../css/custom.css';

import $ from 'jquery';

// جی‌کوئری سراسری: صفحات بعدی (مراحل ۱ تا ۸) هم از همین نمونه استفاده می‌کنند
window.$ = window.jQuery = $;

import './modules/dialogs';
import './modules/focus-mode';
import './modules/sidebar';
import './modules/music-list';
import './modules/report';
import './modules/lesson';
import './modules/progress-sync';
