@extends('layouts.admin')

@section('title', 'Backups')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Backups'],
    ]" />
@endsection

@section('content')
    @php
        $latest = $overview['latest'];
        $latestAge = $latest?->completed_at?->diffForHumans();
        $latestStale = $latest === null || $latest->completed_at->lt(now()->subDays(2));
        $retention = $overview['retention'];
    @endphp

    {{-- Health summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border {{ $latestStale ? 'border-warning-600/50' : 'border-border-subtle' }} bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
            <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Last successful backup</p>
            <p class="mt-1 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $latestAge ?? 'Never' }}</p>
            @if ($latest)
                <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $latest->type->label() }} · {{ $latest->sizeForHumans() }} · {{ $latest->completed_at->format('d M Y, g:i A') }}</p>
            @endif
        </div>

        <div class="rounded-lg border {{ $overview['encrypted'] ? 'border-border-subtle' : 'border-error-600/50' }} bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
            <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Archive encryption</p>
            <p class="mt-1 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $overview['encrypted'] ? 'AES-256 on' : 'Off' }}</p>
            <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $overview['encrypted'] ? 'BACKUP_ARCHIVE_PASSWORD is set.' : 'Set BACKUP_ARCHIVE_PASSWORD in .env — archives contain donor and volunteer data.' }}</p>
        </div>

        <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
            <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Storage</p>
            <p class="mt-1 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ implode(' + ', $overview['disks']) }}</p>
            <p class="text-xs text-text-400 dark:text-night-text-muted">Private disk under storage/app — never web-accessible.{{ count($overview['disks']) > 1 ? ' Mirrored off-site.' : ' Enable BACKUP_S3_ENABLED for an off-site copy.' }}</p>
        </div>

        <div class="rounded-lg border {{ $overview['schedulerStale'] ? 'border-warning-600/50' : 'border-border-subtle' }} bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
            <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Scheduler</p>
            <p class="mt-1 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $overview['latestScheduled'] ? $overview['latestScheduled']->created_at->diffForHumans() : 'No scheduled run yet' }}</p>
            <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $overview['schedulerStale'] ? 'No scheduled backup in 2 days — check the server cron (docs/BACKUPS.md).' : 'Daily DB 01:30 · files 02:00 · weekly full Sun 03:00 · cleanup 04:00.' }}</p>
        </div>
    </div>

    @unless ($overview['dumpBinaryConfigured'])
        <x-ui.alert variant="warning" class="mb-6">
            DB_DUMP_BINARY_PATH is not set — database dumps rely on <code>mysqldump</code> being on the server PATH. On XAMPP point it at the MySQL <code>bin</code> folder.
        </x-ui.alert>
    @endunless

    {{-- Actions --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <form method="POST" action="{{ route('admin.backups.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="w-64">
                <x-ui.select label="Create a backup" name="type" :options="$types" :selected="old('type', 'database')" />
            </div>
            <x-ui.button type="submit">Run Backup Now</x-ui.button>
        </form>

        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('admin.backups.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <x-ui.select name="type" :options="['' => 'All Types'] + $types" :selected="$filters['type'] ?? ''" />
                </div>
                <div class="w-40">
                    <x-ui.select name="status" :options="['' => 'All Statuses'] + $statuses" :selected="$filters['status'] ?? ''" />
                </div>
                <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.backups.cleanup') }}" onsubmit="return confirm('Apply the retention policy now? Archives outside the policy will be deleted (the newest is always kept).');">
                @csrf
                <x-ui.button type="submit" variant="ghost">Apply Retention</x-ui.button>
            </form>

            <x-ui.button href="{{ route('admin.backups.logs') }}" variant="ghost">Audit Log</x-ui.button>
        </div>
    </div>

    <p class="mb-4 text-xs text-text-400 dark:text-night-text-muted">
        Retention: keep everything for {{ $retention['keep_all_backups_for_days'] }} days, then daily for {{ $retention['keep_daily_backups_for_days'] }} days,
        weekly for {{ $retention['keep_weekly_backups_for_weeks'] }} weeks, monthly for {{ $retention['keep_monthly_backups_for_months'] }} months,
        yearly for {{ $retention['keep_yearly_backups_for_years'] }} years; cap {{ number_format($retention['delete_oldest_backups_when_using_more_megabytes_than']) }} MB. The newest archive and the last valid backup are never removed.
    </p>

    @if ($backups->isEmpty())
        <x-ui.empty-state heading="No backups yet" message="Run your first backup above. Scheduled backups will appear here automatically once the server cron is configured." />
    @else
        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Backup</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Type</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Size</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Storage</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-text-600 dark:text-night-text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @foreach ($backups as $backup)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-text-900 dark:text-night-text">#{{ $backup->id }} <span class="font-mono text-xs text-text-400 dark:text-night-text-muted">{{ $backup->filename }}</span></p>
                                <p class="text-xs text-text-400 dark:text-night-text-muted">
                                    {{ $backup->trigger->label() }}{{ $backup->creator ? ' · '.$backup->creator->name : '' }}{{ $backup->encrypted ? ' · encrypted' : ' · not encrypted' }}
                                </p>
                                @if ($backup->isFailed() && $backup->failure_reason)
                                    <p class="mt-1 max-w-md text-xs text-error-600">{{ $backup->failure_reason }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$backup->type->badgeVariant()">{{ $backup->type->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$backup->status->badgeVariant()">{{ $backup->status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $backup->sizeForHumans() ?? '—' }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ implode(', ', $backup->disks ?: [$backup->disk]) }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                <time datetime="{{ $backup->created_at->toIso8601String() }}" title="{{ $backup->created_at->format('d M Y, g:i:s A') }}">{{ $backup->created_at->format('d M Y, g:i A') }}</time>
                                @if ($backup->durationSeconds() !== null)
                                    <span class="block text-xs">took {{ $backup->durationSeconds() }}s</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($backup->isCompleted())
                                        <a href="{{ route('admin.backups.download', $backup) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Download</a>
                                        <a href="{{ route('admin.backups.restore.confirm', $backup) }}" class="text-sm font-medium text-warning-600 hover:underline">Restore…</a>
                                    @endif
                                    @if ($backup->isFailed())
                                        <form method="POST" action="{{ route('admin.backups.retry', $backup) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Retry</button>
                                        </form>
                                    @endif
                                    @unless ($backup->isInProgress() || $backup->status === \App\Enums\BackupStatus::Deleted)
                                        <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}" onsubmit="return confirm('Delete this backup archive permanently?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-error-600 hover:underline">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $backups->links() }}
        </div>
    @endif
@endsection
