@php
    use App\Support\AdminNav;
    $admin = $adminUser ?? ['name' => 'مدیر'];
@endphp
<header class="ad-top">
    <span class="ad-top__brand" aria-hidden="true">T</span>
    <p class="ad-top__title">{{ __('admin.nav.'.$adminActive) }}</p>

    <form class="ad-search" role="search" data-ad-search>
        {!! AdminNav::icon('search') !!}
        <input type="search" name="q" class="field" placeholder="{{ __('admin.search_ph') }}" aria-label="{{ __('admin.search') }}" autocomplete="off">
    </form>

    <div class="ad-top__acts">
        <a href="{{ url('/dashboard') }}" class="ad-ico" title="{{ __('admin.view_site') }}" aria-label="{{ __('admin.view_site') }}">{!! AdminNav::icon('external') !!}</a>
        <span class="ad-me"><i aria-hidden="true">{{ mb_substr($admin['name'], 0, 1) }}</i>{{ $admin['name'] }}</span>
        <form method="POST" action="{{ url('/logout') }}" class="md:hidden">
            @csrf
            <button type="submit" class="ad-ico" title="{{ __('admin.logout') }}" aria-label="{{ __('admin.logout') }}">{!! AdminNav::icon('logout') !!}</button>
        </form>
    </div>
</header>
