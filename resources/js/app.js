// استایل‌ها (fonts / app / custom) اینجا import نمی‌شوند؛ با @vite در layouts/app.blade.php لود می‌شوند.
import $ from 'jquery';

// جی‌کوئری سراسری: صفحات بعدی (مراحل ۱ تا ۸) هم از همین نمونه استفاده می‌کنند
window.$ = window.jQuery = $;

// فقط بخش‌های مشترک همه‌ی صفحه‌های کاربر اینجاست (دیالوگ‌ها، سایدبار، حالت تمرکز، گزارش خرابی، تأیید خروج).
// کد هر صفحه در resources/js/pages/*.js است و همان صفحه با @push('scripts') آن را لود می‌کند.
import './modules/dialogs';
import './modules/focus-mode';
import './modules/sidebar';
import './modules/report';
import './modules/logout-confirm';
