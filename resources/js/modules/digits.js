/** عدد را بر اساس زبان صفحه نشان می‌دهد (فارسی: ۰۱۲۳۴۵۶۷۸۹) — معادل num() در PHP */
export function digits(value) {
    const s = String(value);
    return document.documentElement.lang === 'fa' ? s.replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]) : s;
}
