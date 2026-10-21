@php
    use App\Support\AdminNav;
    $bn = AdminNav::bottom();
@endphp
<nav class="ad-bnav" aria-label="{{ __('admin.menu') }}">
    @foreach ($bn as $k)
        <a href="{{ url($k === 'dashboard' ? '/admin' : '/admin/'.$k) }}" @if ($adminActive === $k) aria-current="page" @endif>
            {!! AdminNav::icon($k) !!}
            <span>{{ __('admin.nav.'.$k) }}</span>
            @if (($adminBadges[$k] ?? 0) > 0)
                <span class="ad-link__n">{{ $adminBadges[$k] }}</span>
            @endif
        </a>
    @endforeach
    <button type="button" data-ad-open aria-controls="ad-side" aria-label="{{ __('admin.open_menu') }}">
        {!! AdminNav::icon('menu') !!}
        <span>{{ __('admin.more') }}</span>
    </button>
</nav>
