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
                // مقدار رنگ‌ها در resources/css/custom.css (متغیرهای --brand-*) است
                brand: {
                    50: 'rgb(var(--brand-50) / <alpha-value>)',
                    100: 'rgb(var(--brand-100) / <alpha-value>)',
                    200: 'rgb(var(--brand-200) / <alpha-value>)',
                    300: 'rgb(var(--brand-300) / <alpha-value>)',
                    500: 'rgb(var(--brand-500) / <alpha-value>)',
                    600: 'rgb(var(--brand-600) / <alpha-value>)',
                    700: 'rgb(var(--brand-700) / <alpha-value>)',
                    800: 'rgb(var(--brand-800) / <alpha-value>)',
                    900: 'rgb(var(--brand-900) / <alpha-value>)',
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
