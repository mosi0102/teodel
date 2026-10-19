{{-- تأیید خروج: همه‌ی فرم‌های /logout (هدر دسکتاپ، منوی «بیشتر» موبایل) با resources/js/modules/logout-confirm.js اینجا می‌آیند --}}
@once
@push('modals')
<div id="logout-dialog" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="logout-title">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start gap-3.5">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-500">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v9"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/></svg>
            </span>
            <div class="min-w-0">
                <h2 id="logout-title" class="text-lg font-extrabold text-brand-900">{{ __('logout.title') }}</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('logout.text') }}</p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close data-logout-stay>{{ __('logout.back') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-logout-ok>{{ __('logout.ok') }}</button>
        </div>
    </div>
</div>
@endpush
@endonce
