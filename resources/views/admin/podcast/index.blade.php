{{-- لیست سری‌های پادکست (/admin/podcast). داده: app/Support/AdminPodcast.php — قالب: admin/content/index --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.podcast.'.$k, $r);
    $title = $L('title');
    $sub = $L('sub');
    $base = '/admin/podcast';
    $newLabel = $L('new');
    $searchPh = $L('search_ph');
    $colItem = $L('col_item');
    $levels = ['a' => __('admincontent.level.a'), 'b' => __('admincontent.level.b'), 'c' => __('admincontent.level.c')];
    $tabs = ['info' => $L('tabs.info'), 'episodes' => $L('tabs.episodes')];
    $row = fn ($r) => [
        'title' => $r['title'],
        'sub' => $r['host'].' · '.$r['category'],
        'cover' => $r['cover'],
        'icon' => 'podcast',
        'level' => '<span class="ad-badge" data-tone="info">'.e(__('admincontent.level.'.$r['level'])).'</span>',
        'extra' => $L('episodes_n', ['n' => num(count($r['episodes']))]),
    ];
@endphp
@extends('admin.content.index')
