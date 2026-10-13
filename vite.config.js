import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // CSS ها entry جدا هستند (نه import داخل js) تا با <link> قبل از رندر لود شوند و صفحه فلش نزند
            input: [
                'resources/css/fonts.css',
                'resources/css/app.css',
                'resources/css/custom.css', // آخر، تا استایل‌های شخصی شما برنده باشد
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
