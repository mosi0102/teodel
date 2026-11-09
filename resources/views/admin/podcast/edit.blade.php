{{-- ویرایشگر سری پادکست: اطلاعات + لیست اپیزودها (ترتیب/حذف/افزودن). قرارداد: app/Support/AdminPodcast.php --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.podcast.'.$k, $r);
    $kind = 'podcast';
    $need = \App\Support\AdminPodcast::SERIES_NEED;
    $tabs = ['info' => $L('tabs.info'), 'episodes' => $L('tabs.episodes')];
    $tabView = 'admin.podcast.tabs.series-';
    $listUrl = url('/admin/podcast');
    $editUrl = url('/admin/podcast/:id/edit');
    $back = $L('back');
    $newTitle = $L('new');
    $numbered = false;
@endphp
@extends('admin.content.editor')
