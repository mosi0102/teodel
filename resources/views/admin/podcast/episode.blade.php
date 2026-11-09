{{-- ویرایشگر اپیزود پادکست: هر تب = یک مرحله‌ی آموزش کاربر. قرارداد: app/Support/AdminPodcast.php --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.episode.'.$k, $r);
    $kind = 'podcast_episode';
    $need = \App\Support\AdminPodcast::EPISODE_NEED;
    $tabs = collect(\App\Support\AdminPodcast::EPISODE_TABS)->mapWithKeys(fn ($k) => [$k => $L('tabs.'.$k)])->all();
    $tabView = 'admin.podcast.tabs.';
    $listUrl = url('/admin/podcast/'.$series['id'].'/edit#episodes');
    $editUrl = url('/admin/podcast/'.$series['id'].'/episodes/:id/edit');
    $back = __('admincontent.podcast.title');
    $crumb = ['url' => $listUrl, 'label' => $series['title']];
    $backUrl = url('/admin/podcast');
    $newTitle = $L('new', ['n' => num($model['number'])]);
    $player = true;
@endphp
@extends('admin.content.editor')
