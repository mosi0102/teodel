/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    future: {
        // hover فقط روی دستگاه‌هایی که واقعاً hover دارند (موبایل درگیر hover چسبنده نمی‌شود)
        hoverOnlyWhenSupported: true,
    },
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#eef0ff',
                    100: '#e0e4ff',
                    200: '#c7ccfb',
                    300: '#a3abf5',
                    500: '#4a56c7',
                    600: '#3a45b0',
                    700: '#2f3a9e',
                    800: '#262f7f',
                    900: '#1d2563',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgb(16 24 64 / .04), 0 4px 16px rgb(16 24 64 / .05)',
                lift: '0 10px 30px rgb(16 24 64 / .13)',
            },
            keyframes: {
                rise: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                rise: 'rise .45s cubic-bezier(.2,.7,.2,1) both',
            },
        },
    },
    plugins: [],
};
