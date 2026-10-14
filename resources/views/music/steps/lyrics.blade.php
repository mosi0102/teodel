{{--
    مرحله 2: lyrics — موزیک دوباره پخش می‌شود و لیریک هم‌زمان دیده می‌شود؛
    خط (و کلمه‌ی) در حال خواندن رنگ می‌گیرد. رفتار: resources/js/modules/lyrics.js
    موبایل: پخش‌کننده بالا، لیریک پایین | دسکتاپ: دو ستون کنار هم (هم‌ارتفاع).
--}}
@php
    $lyricLines = \App\Support\Lrc::forSong($song);
    $playerSong = $lyricLines ? array_merge($song, ['vocals' => \App\Support\Lrc::vocals($lyricLines)]) : $song;
@endphp
<div class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-6 lg:items-stretch">
    <x-lesson.player :song="$playerSong" kind="lyrics" />
    <x-lesson.lyrics :song="$song" />
</div>
