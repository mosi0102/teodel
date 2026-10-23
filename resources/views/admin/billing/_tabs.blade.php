{{-- زبانه‌های اشتراک و پرداخت‌ها: پرداخت‌ها | قیمت پلن‌ها --}}
<nav class="ad-tabs mb-4" aria-label="{{ __('adminbilling.title') }}">
    <a href="{{ url('/admin/billing') }}" @if ($tab === 'payments') aria-current="page" @endif>{{ __('adminbilling.tab.payments') }}</a>
    <a href="{{ url('/admin/billing/plans') }}" @if ($tab === 'plans') aria-current="page" @endif>{{ __('adminbilling.tab.plans') }}</a>
</nav>
