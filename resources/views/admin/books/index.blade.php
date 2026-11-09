{{-- لیست کتاب‌ها (/admin/books). داده: app/Support/AdminBooks.php — قالب: admin/content/index --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $title = $L('title');
    $sub = $L('sub');
    $base = '/admin/books';
    $newLabel = $L('new');
    $searchPh = $L('search_ph');
    $colItem = $L('col_item');
    $levels = ['a' => __('admincontent.level.a'), 'b' => __('admincontent.level.b'), 'c' => __('admincontent.level.c')];
    $tabs = collect(\App\Support\AdminBooks::TABS)->mapWithKeys(fn ($k) => [$k => $L('tabs.'.$k)])->all();
    $row = fn ($r) => [
        'title' => $r['title'],
        'sub' => $r['author'].' · '.__('books.type.'.$r['type']),
        'cover' => $r['cover'],
        'icon' => 'books',
        'level' => '<span class="ad-badge" data-tone="info" dir="ltr">'.e($r['cefr']).'</span>',
        'extra' => $L('chapters_n', ['n' => num(count($r['chapters']))]),
    ];
@endphp
@extends('admin.content.index')
