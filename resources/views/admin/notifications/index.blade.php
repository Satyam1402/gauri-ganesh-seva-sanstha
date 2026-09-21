@extends('layouts.admin')

@section('title', 'Notifications')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Notifications'],
    ]" />
@endsection

@section('content')
    {{-- Status tabs --}}
    <div class="mb-6 flex gap-1 border-b border-border-subtle dark:border-night-border">
        @foreach (['' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)
            <a
                href="{{ route('admin.notifications.index', array_filter(['status' => $value, 'category' => $filters['category'] ?? null])) }}"
                class="border-b-2 px-4 py-2.5 text-sm font-medium {{ ($filters['status'] ?? '') === $value ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted' }}"
            >
                {{ $label }}@if ($value === 'unread' && $unreadCount > 0) ({{ $unreadCount }})@endif
            </a>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap items-end gap-3">
            @if (! empty($filters['status']))
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="w-56">
                <x-ui.select name="category" :options="['' => 'All Categories'] + $categories" :selected="$filters['category'] ?? ''" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" :disabled="$unreadCount === 0">Mark All as Read</x-ui.button>
            </form>
            <form method="POST" action="{{ route('admin.notifications.clear-read') }}" onsubmit="return confirm('Delete all notifications you have already read?');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="ghost">Clear Read</x-ui.button>
            </form>
        </div>
    </div>

    @if ($notifications->isEmpty())
        <x-ui.empty-state
            heading="No notifications"
            message="{{ ! empty($filters['status']) || ! empty($filters['category']) ? 'Nothing matches the current filter.' : 'New donations, applications, registrations, enquiries and comments you are authorised for will appear here.' }}"
        />
    @else
        <div class="rounded-lg border border-border-subtle bg-surface-white dark:border-night-border dark:bg-night-surface">
            <ul class="divide-y divide-border-subtle dark:divide-night-border">
                @foreach ($notifications as $notification)
                    @include('admin.notifications._item', ['notification' => $notification, 'compact' => false])
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
