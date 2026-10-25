@php
    use App\Support\AdminNav;
    $groups = AdminNav::groups($adminBadges ?? [], $adminHide ?? []);
    $admin = $adminUser ?? ['name' => 'مدیر'];
@endphp
{{-- موبایل: کشوی کناری (با «بیشتر» باز می‌شود) | تبلت: ریل آیکونی | دسکتاپ: سایدبار کامل --}}
<aside id="ad-side" class="ad-side" aria-label="{{ __('admin.menu') }}">
    <div class="ad-side__brand">
        <a href="{{ url('/admin') }}" class="ad-side__logo" aria-label="{{ __('admin.brand') }}">
            <img src="{{ asset('images/adminUser.png') }}" alt="{{ __('admin.brand') }}" width="50" height="50"/>
        </a>
        <div class="ad-side__name ad-lbl">
            <b>Teodel</b>
            <small>{{ __('admin.title') }}</small>
        </div>
    </div>

    <nav class="ad-side__nav" aria-label="{{ __('admin.menu') }}">
        @foreach ($groups as $g)
            <div class="ad-side__group">
                <p class="ad-side__gl">{{ __('admin.groups.'.$g['key']) }}</p>
                @foreach ($g['items'] as $it)
                    @php $label = __('admin.nav.'.$it['key']); @endphp
                    <a href="{{ $it['href'] }}" class="ad-link" title="{{ $label }}"
                       @if ($adminActive === $it['key']) aria-current="page" @endif>
                        {!! AdminNav::icon($it['icon']) !!}
                        <span class="ad-link__t">{{ $label }}</span>
                        @if ($it['badge'] > 0)
                            <span class="ad-link__n" aria-label="{{ $it['badge'] }}">{{ $it['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="ad-side__foot">
        <a href="{{ url('/dashboard') }}" class="ad-link" title="{{ __('admin.view_site') }}">
            {!! AdminNav::icon('external') !!}
            <span class="ad-link__t">{{ __('admin.view_site') }}</span>
        </a>
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit" class="ad-link" title="{{ __('admin.logout') }}">
                {!! AdminNav::icon('logout') !!}
                <span class="ad-link__t">{{ __('admin.logout') }}</span>
            </button>
        </form>
    </div>
</aside>
