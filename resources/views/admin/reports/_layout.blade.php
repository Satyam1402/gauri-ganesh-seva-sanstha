{{--
    Common frame for every report page: tabs across the reports the user
    may open, the filter bar, then the report body in $slot-style sections
    via @yield('report').
--}}
@extends('layouts.admin')

@php
    $titles = [
        'donations' => 'Donation Reports',
        'volunteers' => 'Volunteer Reports',
        'events' => 'Event Reports',
        'contacts' => 'Contact Enquiry Reports',
        'activities' => 'Activity Reports',
        'blog' => 'Blog Reports',
        'gallery' => 'Gallery Reports',
    ];
@endphp

@section('title', $titles[$report])

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => $titles[$report]],
    ]" />
@endsection

@section('content')
    <nav aria-label="Report sections" class="mb-6 flex flex-wrap gap-2">
        @foreach ($titles as $key => $label)
            @continue(! in_array($key, $allowed, true))
            <a
                href="{{ route('admin.reports.show', $key) }}"
                class="rounded-full px-4 py-1.5 text-sm font-medium {{ $key === $report ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                @if ($key === $report) aria-current="page" @endif
            >{{ str_replace(' Reports', '', $label) }}</a>
        @endforeach
    </nav>

    @include('admin.reports._filters', ['labels' => $filterLabels ?? []])

    @yield('report')
@endsection
