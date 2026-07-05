@extends('layouts.admin')

@section('title', 'Event Registrations')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Events', 'url' => route('admin.events.index')],
        ['label' => 'Registrations'],
    ]" />
@endsection

@section('content')
    {{-- Status counters --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($statuses as $value => $label)
            <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($statusCounts[$value] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.event-registrations.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-48">
                <x-ui.input name="q" placeholder="Search name, email, phone..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-56">
                <x-ui.select
                    name="event"
                    :options="['' => 'All Events'] + $events->all()"
                    :selected="$filters['event'] ?? ''"
                />
            </div>

            <div class="w-40">
                <x-ui.select
                    name="status"
                    :options="['' => 'All Statuses'] + $statuses"
                    :selected="$filters['status'] ?? ''"
                />
            </div>

            <div class="w-40">
                <x-ui.input label="From" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
            </div>
            <div class="w-40">
                <x-ui.input label="To" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>

        <div class="flex gap-3">
            <x-ui.button href="{{ route('admin.event-registrations.export', request()->query()) }}" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button href="{{ route('admin.event-registrations.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" variant="secondary">Export Excel</x-ui.button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Participant</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Event</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">City</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Registered</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                @forelse ($registrations as $registration)
                    <tr x-data="{ open: false }">
                        <td class="px-4 py-3">
                            <p class="font-medium text-text-900 dark:text-night-text">{{ $registration->name }}</p>
                            <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $registration->email }} · {{ $registration->phone }}</p>
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                            {{ $registration->event?->title ?? '—' }}
                            @if ($registration->event)
                                <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $registration->event->start_date->format('d M Y') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $registration->city ?? '—' }}</td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $registration->created_at->format('d M Y, g:i A') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$registration->status->badgeVariant()">{{ $registration->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="open = ! open" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text" x-text="open ? 'Close' : 'Manage'"></button>

                                <form method="POST" action="{{ route('admin.event-registrations.destroy', $registration) }}" onsubmit="return confirm('Delete this registration?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-error-600 hover:underline">Delete</button>
                                </form>
                            </div>

                            {{-- Inline manage panel --}}
                            <div x-show="open" x-cloak class="mt-3 rounded-md border border-border-subtle bg-surface-muted p-4 text-left dark:border-night-border dark:bg-night-surface-alt">
                                @if ($registration->message)
                                    <p class="mb-3 text-xs text-text-600 dark:text-night-text-muted"><span class="font-semibold">Message:</span> {{ $registration->message }}</p>
                                @endif

                                <form method="POST" action="{{ route('admin.event-registrations.update', $registration) }}" class="space-y-3">
                                    @csrf
                                    @method('PUT')

                                    <div class="max-w-xs">
                                        <x-ui.select label="Status" name="status" :options="$statuses" :selected="$registration->status->value" />
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Admin Notes</label>
                                        <textarea name="admin_notes" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ $registration->admin_notes }}</textarea>
                                    </div>

                                    <x-ui.button type="submit" size="sm">Save</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                            No registrations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $registrations->links() }}
    </div>
@endsection
