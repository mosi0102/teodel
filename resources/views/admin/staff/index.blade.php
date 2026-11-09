@extends('layouts.admin')

@section('title', __('adminstaff.title'))

{{--
    مدیران پنل و دسترسی‌هایشان (/admin/staff) — فقط برای مدیر اصلی.
    فیلترها GET هستند؛ داده: app/Support/AdminStaff.php
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminStaff;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $q = $p['q'] ?? '';
    $role = $p['role'] ?? 'all';
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'role' => $role === 'all' ? null : $role], $o), fn ($v) => $v !== null && $v !== ''));
    $phone = fn ($ph) => $digits(substr($ph, 0, 4).' '.substr($ph, 4, 3).' '.substr($ph, 7));
    $roleTone = ['owner' => 'ai', 'manager' => 'info', 'custom' => 'warn'];
@endphp

@section('content')
<div>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminstaff.title') }}</h1>
            <p class="ad-sub">{{ __('adminstaff.sub') }}</p>
        </div>
        <div class="ad-acts">
            <a href="{{ url('/admin/staff/create') }}" class="btn-primary !min-h-10 !text-xs">{!! AdminNav::icon('plus', 'size-4') !!}{{ __('adminstaff.new') }}</a>
        </div>
    </div>

    <p class="st-note mb-4">{!! AdminNav::icon('staff', 'size-5 shrink-0') !!}<span>{{ __('adminstaff.owner_note') }}</span></p>

    <form method="GET" class="ad-tools" role="search">
        <div class="ad-tools__q">
            <input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminstaff.search_ph') }}" aria-label="{{ __('admin.search') }}" autocomplete="off">
        </div>
        @if ($role !== 'all') <input type="hidden" name="role" value="{{ $role }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs">{{ __('admin.common.filter') }}</button>
    </form>

    <nav class="ad-chips mb-4" aria-label="{{ __('adminstaff.col.role') }}">
        @foreach (['all', 'owner', 'manager', 'content', 'support', 'finance', 'viewer', 'custom'] as $r)
            @continue($r !== 'all' && empty($list['counts'][$r]))
            <a href="{{ $qs(['role' => $r === 'all' ? null : $r]) ?: '?' }}" class="chip-btn shrink-0" @if ($role === $r) aria-current="true" @endif>
                {{ $r === 'all' ? __('admin.common.all') : __('adminstaff.roles.'.$r.'.name') }} <b class="ms-1 tabular-nums">{{ $digits($list['counts'][$r]) }}</b>
            </a>
        @endforeach
    </nav>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('adminstaff.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('adminstaff.col.name') }}</th><th>{{ __('adminstaff.col.role') }}</th><th>{{ __('adminstaff.col.access') }}</th>
                        <th>{{ __('adminstaff.col.status') }}</th><th>{{ __('adminstaff.col.login') }}</th><th><span class="sr-only">{{ __('admin.common.edit') }}</span></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($list['rows'] as $m)
                            @php
                                $sections = collect(AdminStaff::SECTIONS)->filter(fn ($s) => AdminStaff::can($m['permissions'], $s));
                                $isOwner = $m['role'] === 'owner';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ url('/admin/staff/'.$m['id'].'/edit') }}" class="ad-user">
                                        <i class="st-av" data-role="{{ $m['role'] }}" aria-hidden="true">{{ mb_substr($m['name'], 0, 1) }}</i>
                                        <span class="min-w-0">
                                            <b class="block truncate">{{ $m['name'] }}</b>
                                            <small class="block truncate" dir="ltr">{{ $phone($m['phone']) }}</small>
                                        </span>
                                    </a>
                                </td>
                                <td data-label="{{ __('adminstaff.col.role') }}"><span class="ad-badge" data-tone="{{ $roleTone[$m['role']] ?? '' }}">{{ __('adminstaff.roles.'.$m['role'].'.name') }}</span></td>
                                <td data-label="{{ __('adminstaff.col.access') }}">
                                    @if ($isOwner || $sections->count() === count(AdminStaff::SECTIONS))
                                        <span class="text-xs font-bold text-slate-500">{{ __('adminstaff.access_all') }}</span>
                                    @else
                                        <span class="st-secs">
                                            @foreach ($sections->take(4) as $s)<span>{{ __('admin.nav.'.$s) }}</span>@endforeach
                                            @if ($sections->count() > 4)<span>+{{ $digits($sections->count() - 4) }}</span>@endif
                                        </span>
                                    @endif
                                </td>
                                <td data-label="{{ __('adminstaff.col.status') }}">
                                    <span class="ad-badge" data-tone="{{ $m['status'] === 'active' ? 'ok' : 'bad' }}">{{ __('adminstaff.status.'.$m['status']) }}</span>
                                    @if ($m['must_change_password'])<span class="ad-badge" data-tone="warn">{{ __('adminstaff.pending_pw') }}</span>@endif
                                </td>
                                <td data-label="{{ __('adminstaff.col.login') }}">@if ($m['last_login']) <time data-ad-ago="{{ $m['last_login'] }}"></time> @else <span class="text-slate-400">{{ __('adminstaff.never') }}</span> @endif</td>
                                <td><a href="{{ url('/admin/staff/'.$m['id'].'/edit') }}" class="btn-ghost !min-h-9 !px-3 !text-xs">{{ __('admin.common.edit') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="ad-pager"><span>{{ __('adminstaff.result', ['n' => $digits($list['total'])]) }}</span></p>
        @endif
    </section>
</div>
@endsection
