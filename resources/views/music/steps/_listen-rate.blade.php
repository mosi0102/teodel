{{--
    ساختار مشترک مرحله‌ی ۱ (play) و مرحله‌ی ۷ (repeat): پخش‌کننده + امتیازدهی.
    $kind = 'play' | 'repeat' . فقط متن‌های پرسش و مقایسه‌ی نتیجه فرق دارد.
    موبایل: پخش‌کننده بالا، امتیازدهی پایین | دسکتاپ: دو ستون کنار هم.
--}}
<div class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-6">
    <x-lesson.player :song="$song" :kind="$kind" />

    <div class="rounded-2xl bg-slate-50/70 p-4 md:p-6">
        <x-lesson.rating :song="$song" :kind="$kind" />
    </div>
</div>
