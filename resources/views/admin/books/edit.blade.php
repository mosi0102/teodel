{{-- ویرایشگر کتاب: اطلاعات، خلاصه و نویسنده، فصل‌ها، فایل‌ها، لغات. قرارداد: app/Support/AdminBooks.php --}}
@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $kind = 'book';
    $need = \App\Support\AdminBooks::NEED;
    $tabs = collect(\App\Support\AdminBooks::TABS)->mapWithKeys(fn ($k) => [$k => $L('tabs.'.$k)])->all();
    $tabView = 'admin.books.tabs.';
    $listUrl = url('/admin/books');
    $editUrl = url('/admin/books/:id/edit');
    $back = $L('back');
    $newTitle = $L('new');
    $numbered = false;
@endphp
@extends('admin.content.editor')
