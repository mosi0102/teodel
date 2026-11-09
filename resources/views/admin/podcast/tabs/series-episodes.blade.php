@php
    $L = fn ($k, $r = []) => __('admincontent.podcast.'.$k, $r);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
    $isNew = empty($model['id']);
@endphp
<section class="ad-card" aria-labelledby="ce-pe-t">
    <div class="ad-card__h">
        <div class="min-w-0">
            <h2 class="ad-card__t" id="ce-pe-t">{{ $L('episodes_title') }} <span class="ad-badge" data-tone="info" data-ce-count="episodes"></span></h2>
            <p class="ad-hint !mt-0.5">{{ $L('episodes_lead') }}</p>
        </div>
        @unless ($isNew)
            <a href="{{ url('/admin/podcast/'.$model['id'].'/episodes/create') }}" class="btn-primary !min-h-10 !text-xs">{!! $ic('plus') !!}{{ $L('ep_new') }}</a>
        @endunless
    </div>

    @if ($isNew)
        <p class="ed-warn">{{ $L('save_first') }}</p>
    @endif

    <ol class="ce-eps" data-ce-list="episodes"></ol>
    <p class="ad-empty" data-ce-empty="episodes" hidden>{{ $L('episodes_empty') }}</p>

    <template data-ce-tpl="episodes">
        <li class="ce-ep" data-ce-item>
            <span class="ce-ep__n" data-ce-n></span>
            <a class="ce-ep__t" data-ce-href="{{ url('/admin/podcast/:model/episodes/:id/edit') }}">
                <b data-ce-text="title" dir="ltr"></b>
                <small><span data-ce-text="duration" data-fmt="mmss" dir="ltr"></span> · <span data-ce-text="status" data-fmt="status"></span> · {{ $L('ep_steps') }} <span data-ce-text="steps_done" data-fmt="num"></span>/{{ num(6) }}</small>
            </a>
            <div class="flex shrink-0 items-center gap-0.5">
                <button type="button" class="ed-del" data-ce-up aria-label="{{ __('admincontent.list.up') }}">{!! $ic('up') !!}</button>
                <button type="button" class="ed-del" data-ce-down aria-label="{{ __('admincontent.list.down') }}">{!! $ic('down') !!}</button>
                <a class="ed-del" data-ce-href="{{ url('/admin/podcast/:model/episodes/:id/edit') }}" aria-label="{{ __('admin.common.edit') }}">{!! $ic('arrow', 'size-4 ltr:rotate-0 rtl:rotate-180') !!}</a>
                <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
            </div>
        </li>
    </template>
</section>
