{{-- صفحه‌بندی عددی (فقط md به بالا). در موبایل دکمه‌ی «نمایش بیشتر» در index جایگزین آن است. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('music.pagination.label') }}" class="hidden justify-center md:flex">
        <ul class="flex items-center gap-2">

            {{-- قبلی --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="grid size-11 place-items-center rounded-full bg-white text-slate-300 shadow-card" aria-disabled="true">
                        <x-icon name="chevron-start" class="size-5 rtl:-scale-x-100" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('music.pagination.prev') }}"
                        class="grid size-11 place-items-center rounded-full bg-white text-slate-500 shadow-card transition duration-200 hover:-translate-y-0.5 hover:text-brand-700 hover:shadow-lift active:scale-95">
                        <x-icon name="chevron-start" class="size-5 rtl:-scale-x-100" />
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="grid size-11 place-items-center text-slate-400" aria-hidden="true">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid size-11 place-items-center rounded-full bg-brand-700 text-sm font-bold text-white shadow-lift">{{ num($page) }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('music.pagination.page', ['n' => num($page)]) }}"
                                    class="grid size-11 place-items-center rounded-full bg-white text-sm font-bold text-slate-600 shadow-card transition duration-200 hover:-translate-y-0.5 hover:text-brand-700 hover:shadow-lift active:scale-95">{{ num($page) }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- بعدی --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('music.pagination.next') }}"
                        class="grid size-11 place-items-center rounded-full bg-white text-slate-500 shadow-card transition duration-200 hover:-translate-y-0.5 hover:text-brand-700 hover:shadow-lift active:scale-95">
                        <x-icon name="chevron-end" class="size-5 rtl:-scale-x-100" />
                    </a>
                @else
                    <span class="grid size-11 place-items-center rounded-full bg-white text-slate-300 shadow-card" aria-disabled="true">
                        <x-icon name="chevron-end" class="size-5 rtl:-scale-x-100" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
