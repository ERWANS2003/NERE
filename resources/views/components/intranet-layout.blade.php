@props(['title' => null, 'breadcrumbs' => null])

@php
    $title = $title ?? 'Intranet';
    $breadcrumbs = $breadcrumbs ?? [];
@endphp

@include('intranet.layouts.app', [
    'title' => $title,
    'breadcrumbs' => $breadcrumbs,
    'slot' => $slot
])
