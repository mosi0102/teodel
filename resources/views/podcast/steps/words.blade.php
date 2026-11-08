{{--
    پادکست — مرحله‌ی ۳ (words): همان مرحله‌ی لغات موزیک + دکمه‌ی دانلود PDF همه‌ی لغات و عبارت‌های دشوار.
    🔌 PDF: بک‌اند route('podcast.words-pdf') را به PDF واقعی (Content-Disposition: attachment) وصل کند؛ در دمو صفحه‌ی قابل‌چاپ باز می‌شود.
--}}
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-brand-50/70 px-4 py-3 md:px-5">
        <p class="flex items-center gap-2 text-sm font-bold text-brand-900">
            <x-icon name="file-text" class="size-5 shrink-0 text-brand-700" />
            {{ __('podcast.pdf.text') }}
        </p>
        <a href="{{ $urls['words_pdf'] ?? route('podcast.words-pdf', ['id' => $song['id']]) }}" target="_blank" rel="noopener" data-words-pdf data-song="{{ $song['id'] }}"
            class="btn-primary !min-h-10 !text-xs">
            <x-icon name="file-text" class="size-4" />
            {{ __('podcast.pdf.download') }}
        </a>
    </div>

    <x-lesson.words :song="$song" />
</div>
