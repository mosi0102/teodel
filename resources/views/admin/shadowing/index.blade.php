{{-- لیست موضوع‌های Shadowing (/admin/shadowing). داده: app/Support/AdminShadowing.php — قالب: admin/content/index --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.shadowing.'.$k, $r);
    $title = $L('title');
    $sub = $L('sub');
    $base = '/admin/shadowing';
    $newLabel = $L('new');
    $searchPh = $L('search_ph');
    $colItem = $L('col_item');
    $levels = collect(\App\Support\AdminShadowing::LEVELS)->mapWithKeys(fn ($l) => [$l => strtoupper($l)])->all();
    $tabs = ['info' => $L('tabs.info')] + $levels;
    $row = fn ($r) => [
        'title' => $r['title'],
        'sub' => $r['category'],
        'cover' => $r['cover'],
        'icon' => 'shadowing',
        'level' => '<span class="flex flex-wrap gap-1" dir="ltr">'.collect(\App\Support\AdminShadowing::LEVELS)->map(fn ($l) => '<span class="ce-lv'.(in_array($l, $r['levels'], true) ? ' is-on' : '').'">'.strtoupper($l).'</span>')->implode('').'</span>',
        'extra' => null,
    ];
@endphp
@extends('admin.content.index')
