// استایل‌ها (fonts / app / custom) اینجا import نمی‌شوند؛ با @vite در layouts/app.blade.php لود می‌شوند.
import $ from 'jquery';

// جی‌کوئری سراسری: صفحات بعدی (مراحل ۱ تا ۸) هم از همین نمونه استفاده می‌کنند
window.$ = window.jQuery = $;

import './modules/dialogs';
import './modules/focus-mode';
import './modules/sidebar';
import './modules/music-list';
import './modules/report';
import './modules/lesson';
import './modules/player';
import './modules/rating';
import './modules/lyrics';
import './modules/words';
import './modules/clip';
import './modules/listening';
import './modules/shadowing';
import './modules/karaoke';
import './modules/details';
import './modules/saved-words';
import './modules/profile';
import './modules/dictionary';
import './modules/level-test';
import './modules/progress-sync';
