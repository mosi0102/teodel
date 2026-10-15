{{--
    مرحله 6: karaoke — موزیک کامل پخش می‌شود، لیریک هم‌زمان دنبال می‌شود (کلمه‌به‌کلمه) و کاربر به‌جای خواننده می‌خواند.
    کاربر می‌تواند صدای خواننده و صدای موزیک را جدا کم/زیاد یا بی‌صدا کند (میکسر داخل پخش‌کننده).
    رفتار: resources/js/modules/karaoke.js (لیریک) و player.js (پخش و میکسر)
    🔌 بک‌اند: $song['audio_vocal'] + $song['audio_backing'] (دو فایل هم‌طول)؛ توضیح کامل در x-lesson.player
--}}
@php
    $lines = \App\Support\Lrc::forSong($song);
    $playerSong = $lines ? array_merge($song, ['vocals' => \App\Support\Lrc::vocals($lines)]) : $song;
    $kLines = array_map(fn ($l) => ['t' => $l['t'], 'end' => $l['end'], 'text' => $l['text']], $lines);
@endphp

<div class="kx" data-karaoke
    data-lines='@json($kLines, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-msg-idle="{{ __('lesson.karaoke.idle') }}" data-msg-instr="{{ __('lesson.karaoke.instrumental') }}"
    data-msg-ready="{{ __('lesson.karaoke.ready') }}" data-msg-end="{{ __('lesson.karaoke.end') }}">

    @if (count($lines))
        {{-- صحنه‌ی لیریک: خط جاری بزرگ، خط بعدی کم‌رنگ زیرش (انگلیسی همیشه چپ‌به‌راست) --}}
        <div class="kstage" data-k-stage data-phase="idle" aria-label="{{ __('lesson.lyrics.label') }}">
            <div class="kstage__status" data-k-status>
                <span class="kcount" data-k-count hidden aria-hidden="true"><i></i><i></i><i></i></span>
                <span data-k-msg>{{ __('lesson.karaoke.idle') }}</span>
            </div>
            <p class="kline kline--cur" data-k-cur dir="ltr" lang="en"></p>
            <p class="kline kline--next" data-k-next dir="ltr" lang="en"></p>
        </div>
    @else
        <div class="kstage"><p class="m-auto text-sm font-bold text-white/80">{{ __('lesson.lyrics.empty') }}</p></div>
    @endif

    <x-lesson.player :song="$playerSong" kind="karaoke" />
</div>
