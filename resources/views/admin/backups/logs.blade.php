@extends('layouts.admin')

@section('title', 'Backup Audit Log')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Backups', 'url' => route('admin.backups.index')],
        ['label' => 'Audit Log'],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.backups.logs') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <x-ui.select name="event" :options="['' => 'All Events'] + $events" :selected="$filters['event'] ?? ''" />
            </div>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>

        <x-ui.button href="{{ route('admin.backups.index') }}" variant="secondary">Back to Backups</x-ui.button>
    </div>

    @if ($logs->isEmpty())
        <x-ui.empty-state heading="No log entries" message="Backup, download, delete and restore operations are recorded here." />
    @else
        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">When</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Event</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Backup</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Details</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-text-600 dark:text-night-text-muted">
                                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->diffForHumans() }}">{{ $log->created_at->format('d M Y, g:i:s A') }}</time>
                            </td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$log->event->badgeVariant()">{{ $log->event->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                @if ($log->backup)
                                    #{{ $log->backup->id }} <span class="font-mono text-xs">{{ $log->backup->filename }}</span>
                                @elseif ($log->backup_id)
                                    #{{ $log->backup_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-900 dark:text-night-text">
                                {{ $log->message }}
                                @if (! empty($log->context['reason']))
                                    <p class="mt-1 max-w-lg text-xs text-error-600">{{ $log->context['reason'] }}</p>
                                @endif
                                @if (! empty($log->context['scope']))
                                    <p class="text-xs text-text-400 dark:text-night-text-muted">Scope: {{ $log->context['scope'] }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-text-600 dark:text-night-text-muted">
                                {{ $log->user?->name ?? 'System' }}
                                @if ($log->ip_address)
                                    <span class="block text-xs">{{ $log->ip_address }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
@endsection
