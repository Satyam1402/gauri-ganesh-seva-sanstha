@extends('layouts.admin')

@section('title', 'Reports')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Reports']]" />
@endsection

@php
    $cards = [
        'donations' => ['title' => 'Donations', 'text' => 'Totals, trends, campaign split, payment status and channel.'],
        'volunteers' => ['title' => 'Volunteers', 'text' => 'Applications by status, period, location and interest.'],
        'events' => ['title' => 'Events', 'text' => 'Events held, registrations and attendance per event.'],
        'contacts' => ['title' => 'Contact Enquiries', 'text' => 'Enquiry volume, categories, status and response time.'],
        'activities' => ['title' => 'Activities', 'text' => 'Programme activity by category, status and period.'],
        'blog' => ['title' => 'Blog', 'text' => 'Publishing cadence, categories and most-viewed posts.'],
        'gallery' => ['title' => 'Gallery', 'text' => 'Albums and photos by category and status.'],
    ];
@endphp

@section('content')
    <p class="mb-6 max-w-2xl text-sm text-text-600 dark:text-night-text-muted">
        Every report is built from live records and shows aggregate figures only. Personal details stay on the module screens they belong to.
    </p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $key => $card)
            @continue(! in_array($key, $allowed, true))
            <a href="{{ route('admin.reports.show', $key) }}" class="block rounded-lg border border-border-subtle bg-surface-white p-5 transition hover:border-primary-700/40 hover:shadow-md focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35 dark:border-night-border dark:bg-night-surface">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $card['title'] }}</h2>
                <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $card['text'] }}</p>
                <span class="mt-3 inline-block text-sm font-medium text-primary-700 dark:text-night-text">Open report →</span>
            </a>
        @endforeach
    </div>
@endsection
