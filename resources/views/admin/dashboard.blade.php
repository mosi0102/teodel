@extends('layouts.admin')

@section('title', __('admin.dash.title'))

{{--
    داشبورد مدیریت: KPI، روند ۳۰ روز، ترکیب پلن‌ها، نیازمند اقدام، آخرین پرداخت‌ها، پربازدیدها و سلامت محتوا.
    داده: app/Support/AdminDashboard.php (قرارداد) — رفتار: resources/js/admin/dashboard.js
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    // اگر کنترلر داده‌ی مدیر نفرستاده بود (یا متغیر $d با چیز دیگری قاطی شده بود) نمونه‌ی دمو نشان بده، نه خطا
    $d = isset($d['kpi'], $d['plan_mix']) ? $d : \App\Support\AdminDashboard::demo();
    $k = $d['kpi'];
    $total = max(1, array_sum(array_column($d['plan_mix'], 'count')));
    $paidPct = round((1 - ($d['plan_mix'][0]['count'] ?? 0) / $total) * 100);
    $delta = function ($v) use ($digits) {
        if ($v === null) return '';
        $up = $v >= 0;
        return '<span class="ad-delta" data-dir="'.($up ? 'up' : 'down').'">'.AdminNav::icon($up ? 'up' : 'down').$digits(abs(round($v, 1))).'٪</span>';
    };
    $cover = fn ($s) => ! empty($s['cover'])
        ? 'background-image:url('.e($s['cover']).');background-size:cover;background-position:center'
        : 'background:linear-gradient(135deg,hsl('.(($s['id'] * 47) % 360).' 65% 46%),hsl('.((($s['id'] * 47) + 50) % 360).' 60% 28%))';
    $planTone = ['free' => 'free', '1m' => 'm1', '6m' => 'm6', '12m' => 'm12'];
    $stTone = ['success' => 'ok', 'failed' => 'bad', 'cancelled' => 'warn', 'pending' => 'info'];
    $bar = function ($have, $need) { return $need > 0 ? min(100, (int) round($have / $need * 100)) : 100; };
@endphp

@section('content')
<div data-ad-dash data-hour-greet='@json(__('admin.dash.hello'))'
    data-series='@json($d['series'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
    data-t='@json(__('admin.dash.chart'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-greets='@json(__('admin.dash.greet'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-name="{{ $d['admin']['name'] }}">

    <div class="ad-head">
        <div>
            <h1 class="ad-h1" data-ad-hello>{{ __('admin.dash.hello', ['greet' => __('admin.dash.greet.morning'), 'name' => $d['admin']['name']]) }}</h1>
            <p class="ad-sub">{{ __('admin.dash.sub') }}</p>
        </div>
    </div>

    <nav class="ad-quick mb-4 xl:mb-5" aria-label="{{ __('admin.dash.quick.title') }}">
        <a href="{{ url('/admin/songs/create') }}">{!! AdminNav::icon('songs') !!}{{ __('admin.dash.quick.song') }}</a>
        <a href="{{ url('/admin/placement/create') }}">{!! AdminNav::icon('placement') !!}{{ __('admin.dash.quick.question') }}</a>
        <a href="{{ url('/admin/dictionary/create') }}">{!! AdminNav::icon('dictionary') !!}{{ __('admin.dash.quick.word') }}</a>
        <a href="{{ url('/admin/tickets?status=open') }}">{!! AdminNav::icon('tickets') !!}{{ __('admin.dash.quick.ticket') }}</a>
    </nav>

    <section class="ad-kpis" aria-label="KPI">
        <article class="ad-kpi">
            <span class="ad-kpi__ic">{!! AdminNav::icon('users') !!}</span>
            <b class="ad-kpi__v">{{ $n($k['users']['total']) }}</b>
            <span class="ad-kpi__l">{{ __('admin.dash.kpi.users') }}</span>
            <p class="ad-kpi__s">{!! $delta($k['users']['delta']) !!}<span>{{ __('admin.dash.kpi.users_sub', ['n' => $n($k['users']['new_week'])]) }}</span></p>
        </article>
        <article class="ad-kpi" data-tone="ok">
            <span class="ad-kpi__ic">{!! AdminNav::icon('billing') !!}</span>
            <b class="ad-kpi__v">{{ $n($k['subs']['active']) }}</b>
            <span class="ad-kpi__l">{{ __('admin.dash.kpi.subs') }}</span>
            <p class="ad-kpi__s">{!! $delta($k['subs']['delta']) !!}<span>{{ __('admin.dash.kpi.subs_sub', ['p' => $digits($paidPct)]) }}</span></p>
        </article>
        <article class="ad-kpi" data-tone="ai">
            <span class="ad-kpi__ic">{!! AdminNav::icon('sparkles') !!}</span>
            <b class="ad-kpi__v">{{ $n($k['revenue']['month']) }}<small>{{ __('admin.common.toman') }}</small></b>
            <span class="ad-kpi__l">{{ __('admin.dash.kpi.revenue') }}</span>
            <p class="ad-kpi__s">{!! $delta($k['revenue']['delta']) !!}<span>{{ __('admin.dash.kpi.revenue_sub', ['n' => $n($k['revenue']['prev_month'])]) }}</span></p>
        </article>
        <article class="ad-kpi" data-tone="warn">
            <span class="ad-kpi__ic">{!! AdminNav::icon('tickets') !!}</span>
            <b class="ad-kpi__v">{{ $n($k['tickets']['open']) }}</b>
            <span class="ad-kpi__l">{{ __('admin.dash.kpi.tickets') }}</span>
            <p class="ad-kpi__s"><span>{{ __('admin.dash.kpi.tickets_sub', ['n' => $n($k['tickets']['unanswered'])]) }}</span></p>
        </article>
    </section>

    <div class="ad-grid">
        <section class="ad-card is-2" aria-labelledby="ad-chart-t">
            <div class="ad-card__h">
                <h2 class="ad-card__t" id="ad-chart-t">{{ __('admin.dash.chart.title') }}</h2>
                <div class="ad-seg" role="group" aria-label="{{ __('admin.dash.chart.title') }}">
                    <button type="button" aria-pressed="true" data-ad-series="revenue">{{ __('admin.dash.chart.revenue') }}</button>
                    <button type="button" aria-pressed="false" data-ad-series="signups">{{ __('admin.dash.chart.signups') }}</button>
                </div>
            </div>
            <div class="ad-chart" data-ad-chart role="img" aria-label="{{ __('admin.dash.chart.title') }}">
                <span class="ad-chart__dot" data-ad-dot></span>
                <span class="ad-chart__tip" data-ad-tip></span>
            </div>
            <div class="ad-chart__x" data-ad-x></div>
            <div class="ad-sums">
                <div><b data-ad-sum="total">—</b><small>{{ __('admin.dash.chart.total') }}</small></div>
                <div><b data-ad-sum="avg">—</b><small>{{ __('admin.dash.chart.avg') }}</small></div>
                <div><b data-ad-sum="best">—</b><small>{{ __('admin.dash.chart.best') }}</small></div>
            </div>
        </section>

        <section class="ad-card" aria-labelledby="ad-mix-t">
            <h2 class="ad-card__t mb-3" id="ad-mix-t">{{ __('admin.dash.mix.title') }}</h2>
            <div class="ad-mix" role="img" aria-label="{{ __('admin.dash.mix.title') }}">
                @foreach ($d['plan_mix'] as $m)
                    <i data-tone="{{ $planTone[$m['plan']] }}" data-w="{{ round($m['count'] / $total * 100, 1) }}"></i>
                @endforeach
            </div>
            <ul class="ad-legend">
                @foreach ($d['plan_mix'] as $m)
                    <li data-tone="{{ $planTone[$m['plan']] }}">{{ __('admin.dash.mix.'.$m['plan']) }}<b>{{ $n($m['count']) }}</b><small>{{ $digits(round($m['count'] / $total * 100)) }}٪</small></li>
                @endforeach
            </ul>
            <div class="ad-conv">
                <b>{{ $digits($paidPct) }}٪</b>
                <span>{{ __('admin.dash.mix.conv') }}</span>
            </div>
        </section>

        <section class="ad-card is-2" aria-labelledby="ad-tx-t">
            <div class="ad-card__h">
                <h2 class="ad-card__t" id="ad-tx-t">{{ __('admin.dash.tx.title') }}</h2>
                <a href="{{ url('/admin/billing') }}" class="ad-more">{{ __('admin.dash.attention.all') }}{!! AdminNav::icon('arrow') !!}</a>
            </div>
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('admin.dash.tx.user') }}</th><th>{{ __('admin.dash.tx.plan') }}</th><th>{{ __('admin.dash.tx.amount') }}</th>
                        <th>{{ __('admin.dash.tx.status') }}</th><th>{{ __('admin.dash.tx.time') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($d['transactions'] as $t)
                            <tr>
                                <td data-label="{{ __('admin.dash.tx.user') }}">{{ $t['user'] }}</td>
                                <td data-label="{{ __('admin.dash.tx.plan') }}">{{ __('admin.dash.mix.'.$t['plan']) }}</td>
                                <td data-label="{{ __('admin.dash.tx.amount') }}" class="ad-num">{{ $n($t['amount']) }} <small class="font-medium text-slate-400">{{ __('admin.common.toman') }}</small></td>
                                <td data-label="{{ __('admin.dash.tx.status') }}"><span class="ad-badge" data-tone="{{ $stTone[$t['status']] }}">{{ __('admin.status.'.$t['status']) }}</span></td>
                                <td data-label="{{ __('admin.dash.tx.time') }}"><time data-ad-ago="{{ $t['at'] }}"></time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ad-card" aria-labelledby="ad-att-t">
            <h2 class="ad-card__t mb-3" id="ad-att-t">{{ __('admin.dash.attention.title') }}</h2>
            <div class="ad-att">
                @if ($d['attention']['ai_review']['count'] > 0)
                    <a href="{{ url($d['attention']['ai_review']['url']) }}" class="ad-aibar">{!! AdminNav::icon('sparkles') !!}<span>{{ __('admin.dash.attention.ai_review') }}: {{ $n($d['attention']['ai_review']['count']) }}</span></a>
                @endif
                @foreach (['tickets' => 'subject', 'reports' => 'title'] as $key => $field)
                    <div>
                        <h3>{{ __('admin.dash.attention.'.$key) }}<a href="{{ url('/admin/'.$key) }}" class="ad-more">{{ __('admin.dash.attention.all') }}</a></h3>
                        @if (empty($d['attention'][$key]))
                            <p class="ad-empty">{{ __('admin.dash.attention.none') }}</p>
                        @else
                            <ul>
                                @foreach ($d['attention'][$key] as $it)
                                    <li><a href="{{ url($it['url']) }}"><span class="ad-dot"></span><span class="grow"><span class="t">{{ $it[$field] }}</span><small>{{ __('admin.dash.attention.by', ['name' => $it['user']]) }} · <time data-ad-ago="{{ $it['at'] }}"></time></small></span></a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="ad-card is-2" aria-labelledby="ad-top-t">
            <h2 class="ad-card__t mb-3" id="ad-top-t">{{ __('admin.dash.songs.title') }}</h2>
            <ol class="ad-rank">
                @foreach ($d['top_songs'] as $i => $s)
                    <li><a href="{{ url($s['url']) }}">
                        <span class="ad-rank__n">{{ $digits($i + 1) }}</span>
                        <span class="ad-rank__c" style="{!! $cover($s) !!}"></span>
                        <span class="ad-rank__t"><b>{{ $s['title'] }}</b><small>{{ $s['artist'] }}</small></span>
                        <span class="ad-rank__s"><b>{{ $n($s['plays']) }}</b>{{ __('admin.dash.songs.done', ['p' => $digits($s['completion'])]) }}</span>
                    </a></li>
                @endforeach
            </ol>
        </section>

        <section class="ad-card" aria-labelledby="ad-health-t">
            <h2 class="ad-card__t mb-1" id="ad-health-t">{{ __('admin.dash.health.title') }}</h2>
            @php
                $hs = $d['health']['songs'];
                $hq = $d['health']['questions'];
                $hw = $d['health']['words'];
                $min = $hq['min_per_level'];
            @endphp
            <div class="divide-y divide-slate-50">
                <div class="ad-hrow">
                    <div><span>{{ __('admin.dash.health.songs') }}</span><b>{{ $n($hs['published']) }}</b></div>
                    <small>{{ __('admin.dash.health.drafts') }}: {{ $n($hs['drafts']) }} · {{ __('admin.dash.health.incomplete') }}: {{ $n($hs['incomplete']) }}</small>
                </div>
                <div class="ad-hrow">
                    <div><span>{{ __('admin.dash.health.words') }}</span><b>{{ $n($hw['total']) }}</b></div>
                    <div class="ad-meter" data-state="{{ $hw['no_image'] / max(1, $hw['total']) > .5 ? 'low' : 'ok' }}"><i data-w="{{ 100 - $bar($hw['no_image'], $hw['total']) }}"></i></div>
                    <small>{{ __('admin.dash.health.no_image') }}: {{ $n($hw['no_image']) }}</small>
                </div>
                <div class="ad-hrow">
                    <div><span>{{ __('admin.dash.health.questions') }}</span><small class="!mt-0">{{ __('admin.dash.health.questions_hint', ['n' => $n($min)]) }}</small></div>
                    @foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $lv)
                        @php $c = $hq[$lv]; $p = $bar($c, $min); @endphp
                        <div class="mt-2 !text-xs"><span>{{ __('admin.dash.health.level', ['l' => $lv]) }}</span><span class="tabular-nums">{{ $n($c) }} / {{ $n($min) }}</span></div>
                        <div class="ad-meter" data-state="{{ $p >= 100 ? 'ok' : ($p >= 50 ? 'low' : 'bad') }}"><i data-w="{{ $p }}"></i></div>
                    @endforeach
                    <a href="{{ url('/admin/placement') }}" class="ad-more mt-2">{{ __('admin.dash.health.fix') }}{!! AdminNav::icon('arrow') !!}</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
