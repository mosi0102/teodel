{{-- ویرایشگر موضوع Shadowing: اطلاعات + یک تب برای هر نسخه‌ی A1…C2. قرارداد: app/Support/AdminShadowing.php --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.shadowing.'.$k, $r);
    $kind = 'shadowing';
    $need = \App\Support\AdminShadowing::need();
    $tabs = ['info' => $L('tabs.info')];
    $tabMap = [];
    foreach (\App\Support\AdminShadowing::LEVELS as $l) {
        $tabs[$l] = strtoupper($l);
        $tabMap[$l] = 'admin.shadowing.tabs.level';
    }
    $tabView = 'admin.shadowing.tabs.';
    $listUrl = url('/admin/shadowing');
    $editUrl = url('/admin/shadowing/:id/edit');
    $back = $L('back');
    $newTitle = $L('new');
    $numbered = false;
@endphp
@extends('admin.content.editor')
