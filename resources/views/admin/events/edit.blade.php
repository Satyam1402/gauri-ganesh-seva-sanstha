@extends('layouts.admin')

@section('title', 'Edit '.$event->title)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Events', 'url' => route('admin.events.index')],
        ['label' => $event->title],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-ui.badge :variant="$event->status->badgeVariant()">{{ $event->status->label() }}</x-ui.badge>
            <span class="text-sm text-text-600 dark:text-night-text-muted">{{ $event->dateRange() }}</span>
            @if ($event->status->value === 'published')
                <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
        </div>

        @if ($event->requires_registration)
            <x-ui.button href="{{ route('admin.event-registrations.index', ['event' => $event->id]) }}" variant="secondary" size="sm">
                Registrations ({{ $event->active_registrations_count }}@if ($event->max_participants) / {{ $event->max_participants }}@endif)
            </x-ui.button>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.events._form', ['event' => $event])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.events.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
