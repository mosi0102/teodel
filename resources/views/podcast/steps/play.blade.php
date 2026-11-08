{{--
    پادکست — مرحله‌ی ۱ (play): پخش‌کننده + «چقدر متوجه شدی؟» + کادر اختیاریِ Main idea (یک یا دو جمله).
    Main idea در مرحله‌ی آخر (اطلاعات) کنار Main idea رسمی نشان داده می‌شود. رفتار: resources/js/modules/podcast-idea.js
--}}
@php
    $lyricLines = \App\Support\Lrc::forSong($song);
    $playerSong = $lyricLines ? array_merge($song, ['vocals' => \App\Support\Lrc::vocals($lyricLines)]) : $song;
    $kind = 'play';
@endphp
<div class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-6">
    <x-lesson.player :song="$playerSong" :kind="$kind" />

    <div class="space-y-4">
        <div class="rounded-2xl bg-slate-50/70 p-4 md:p-6">
            <x-lesson.rating :song="$song" :kind="$kind" />
        </div>

        {{-- Main idea (اختیاری) --}}
        <section class="idea" data-idea data-song="{{ $song['id'] }}" data-text="{{ $song['user_main_idea'] ?? '' }}" @if (! empty($urls['main_idea'])) data-save-url="{{ $urls['main_idea'] }}" @endif>
            <label for="idea-{{ $song['id'] }}" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-extrabold text-brand-900 md:text-base">
                <span class="grid size-9 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="bookmark" class="size-5" /></span>
                {{ __('podcast.idea.title') }}
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">{{ __('podcast.idea.optional') }}</span>
            </label>
            <p class="mt-2 text-xs leading-6 text-slate-500 md:text-sm">{{ __('podcast.idea.hint') }}</p>
            <textarea id="idea-{{ $song['id'] }}" data-idea-input rows="3" maxlength="300" dir="auto" class="field mt-3 min-h-24 resize-y py-3"
                placeholder="{{ __('podcast.idea.placeholder') }}"></textarea>
            <p class="mt-2 flex items-center justify-between text-xs text-slate-400">
                <span data-idea-saved class="font-bold text-emerald-600 opacity-0 transition-opacity duration-300" aria-live="polite">{{ __('podcast.idea.saved') }}</span>
                <span class="tabular-nums"><b data-idea-count>{{ num(0) }}</b>/{{ num(300) }}</span>
            </p>
        </section>
    </div>
</div>
