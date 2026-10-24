import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // CSS ها entry جدا هستند (نه import داخل js) تا با <link> قبل از رندر لود شوند و صفحه فلش نزند
            input: [
                'resources/css/fonts.css',
                'resources/css/app.css',
                'resources/css/admin.css', // فقط پنل مدیریت
                'resources/css/auth.css', // فقط ورود و ثبت‌نام
                'resources/css/custom.css', // آخر، تا استایل‌های شخصی شما برنده باشد
                'resources/js/app.js',
                'resources/js/admin.js', // فقط پنل مدیریت
                'resources/js/auth.js', // فقط ورود و ثبت‌نام
            ],
            refresh: true,
        }),
    ],
    // فایرفاکس با آدرس پیش‌فرض [::1] و CORS سخت‌گیرانه مشکل دارد (فونت و CSS لود نمی‌شود)؛
    // با آدرس 127.0.0.1 و CORS باز، سرور توسعه در همه‌ی مرورگرها کار می‌کند. (فقط برای npm run dev)
    server: {
        host: '127.0.0.1',
        cors: true,
        hmr: { host: '127.0.0.1' },
    },
});
