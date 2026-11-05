{{--
    دکمه‌ی خروجی Excel / CSV برای لیست‌های پنل.
    ورودی: $name (نام فایل)، $table (سلکتور جدول؛ اختیاری)، $url (اندپوینت بک‌اند؛ اختیاری)
    @include('admin.partials.export', ['name' => 'users', 'url' => $urls['export'] ?? null])
    جزئیات قرارداد: resources/js/admin/export.js
--}}
<div class="ad-exp" data-ad-export data-export-name="{{ $name }}"
    @isset($table) data-export-table="{{ $table }}" @endisset
    @if (!empty($url)) data-export-url="{{ $url }}" @endif
    data-t-done="{{ __('admin.export.done') }}" data-t-empty="{{ __('admin.export.empty') }}">
    <button type="button" class="btn-outline !min-h-10 !text-xs" data-exp-toggle aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>{{ __('admin.export.btn') }}
    </button>
    <div class="ad-exp__menu" data-exp-menu hidden>
        <button type="button" class="ad-exp__item" data-exp-fmt="xlsx"><b>Excel</b><small>.xlsx</small></button>
        <button type="button" class="ad-exp__item" data-exp-fmt="csv"><b>CSV</b><small>.csv</small></button>
        <p class="ad-exp__hint">{{ __('admin.export.hint') }}</p>
    </div>
</div>
