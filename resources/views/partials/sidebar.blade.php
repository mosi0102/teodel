@php
    // TODO(backend): به‌جای مقدار پیش‌فرض، $user را از auth()->user() بدهید
    $u = $user ?? ['name' => 'کاربر', 'days_left' => 0, 'level' => 'A1', 'skills' => ['listening' => 'A1', 'reading' => 'A1', 'writing' => 'A1', 'speaking' => 'A1']];
    $items = \App\Support\Nav::items();
@endphp

{{-- دسکتاپ: سایدبار کامل | تبلت: ریل آیکونی | موبایل: مخفی (ناوبری پایین) --}}
<aside id="sidebar" class="sidebar fixed inset-y-0 start-0 z-40 hidden overflow-hidden border-e border-slate-200 bg-white md:block" aria-label="{{ __('ui.menu') }}">
    <div class="flex h-full w-20 flex-col lg:w-72">

        <div class="flex h-16 shrink-0 items-center justify-center border-b border-slate-100 px-4">
            <a href="{{ url('/dashboard') }}" class="block" aria-label="Teodel">
                <img src="{{ asset('images/logo.svg') }}" alt="Teodel" width="120" height="36" class="hidden h-9 w-auto lg:block">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-700 text-lg font-extrabold text-white lg:hidden" aria-hidden="true">T</span>
            </a>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto overscroll-contain px-3 py-4 lg:px-4">

            {{-- کارت کاربر --}}
            <div class="rounded-2xl bg-slate-50 p-2.5 lg:p-3.5">
                <div class="flex items-center justify-center gap-3 lg:justify-start">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-100 text-lg font-extrabold text-brand-700" aria-hidden="true">{{ mb_substr($u['name'], 0, 1) }}</span>
                    <div class="hidden min-w-0 flex-1 lg:block">
                        <p class="truncate text-sm font-bold text-brand-900">{{ $u['name'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('ui.days_left') }}: <span class="font-bold text-brand-800">{{ num($u['days_left']) }}</span></p>
                    </div>
                </div>

                @if (($u['days_left'] ?? 0) <= 0)
                    <a href="{{ url('/subscription') }}" class="btn-primary mt-3 hidden min-h-10 w-full text-xs lg:inline-flex">
                        <x-icon name="sparkles" class="size-4" />
                        {{ __('ui.buy') }}
                    </a>
                    <a href="{{ url('/subscription') }}" class="mt-2 grid h-10 place-items-center rounded-xl bg-brand-700 text-white transition hover:bg-brand-800 active:scale-95 lg:hidden" aria-label="{{ __('ui.buy') }}" title="{{ __('ui.buy') }}">
                        <x-icon name="sparkles" class="size-5" />
                    </a>
                @endif
            </div>

            {{-- لینک‌ها --}}
            <nav aria-label="{{ __('ui.menu') }}" class="space-y-1">
                @foreach ($items as $item)
                    @php $label = __('ui.nav.' . $item['key']); @endphp

                    @if ($item['soon'])
                        <div class="nav-link justify-center lg:justify-start" aria-disabled="true" title="{{ $label }}">
                            <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span class="hidden min-w-0 flex-1 truncate lg:inline">{{ $label }}</span>
                            <span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 lg:inline">{{ __('ui.soon') }}</span>
                        </div>
                    @else
                        <a href="{{ $item['href'] }}" class="nav-link justify-center lg:justify-start" title="{{ $label }}" @if ($item['active']) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span class="hidden min-w-0 flex-1 truncate lg:inline">{{ $label }}</span>
                            @if ($item['key'] === 'level_test')
                                <span class="hidden text-xs font-bold text-brand-700 lg:inline" dir="ltr">{{ $u['level'] }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- میانگین نمره‌ی مهارت‌ها (فقط دسکتاپ؛ جای کافی دارد) --}}
            <div class="hidden rounded-2xl bg-gradient-to-br from-brand-50 to-indigo-100 p-3.5 lg:block">
                <p class="mb-3 text-xs font-bold text-brand-800">
                    {{ __('ui.avg_grade') }}: <span dir="ltr" class="inline-block rounded-md bg-brand-700 px-2 py-0.5 text-white">{{ $u['level'] }}</span>
                </p>
                <dl class="grid grid-cols-2 gap-2 text-center" dir="ltr">
                    @foreach (['listening', 'reading', 'writing', 'speaking'] as $skill)
                        <div class="rounded-xl bg-white/80 py-2">
                            <dd class="text-lg font-extrabold leading-none text-brand-900">{{ $u['skills'][$skill] ?? '—' }}</dd>
                            <dt class="mt-1 text-[11px] font-medium capitalize text-slate-500">{{ $skill }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</aside>
