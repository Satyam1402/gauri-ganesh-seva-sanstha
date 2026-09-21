@extends('layouts.admin')

@section('title', 'Restore Backup #'.$backup->id)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Backups', 'url' => route('admin.backups.index')],
        ['label' => 'Restore #'.$backup->id],
    ]" />
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-ui.alert variant="error" class="mb-6">
            <p class="font-semibold">This operation overwrites live data.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                <li><strong>Database:</strong> every table is replaced with the contents of this archive. Donations, applications, enquiries, users and settings created after {{ $backup->completed_at->format('d M Y, g:i A') }} will be lost.</li>
                <li><strong>Uploaded files:</strong> files in the archive overwrite files of the same name; newer uploads that are not in the archive are left in place (nothing is deleted).</li>
                <li>A safety backup of the same scope is taken first and the restore is aborted if it fails.</li>
                <li>Enable <a href="{{ route('admin.settings.edit', 'maintenance') }}" class="underline">maintenance mode</a> before restoring so visitors and donors are not mid-transaction while data changes.</li>
                <li>The restore runs on the queue; all backup managers are notified when it finishes or fails.</li>
            </ul>
        </x-ui.alert>

        <div class="mb-6 rounded-lg border border-border-subtle bg-surface-white p-5 dark:border-night-border dark:bg-night-surface">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Archive validated</h2>
            <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <dt class="text-text-400 dark:text-night-text-muted">File</dt>
                <dd class="font-mono text-text-900 dark:text-night-text">{{ $backup->filename }}</dd>
                <dt class="text-text-400 dark:text-night-text-muted">Type</dt>
                <dd class="text-text-900 dark:text-night-text">{{ $backup->type->label() }}</dd>
                <dt class="text-text-400 dark:text-night-text-muted">Taken</dt>
                <dd class="text-text-900 dark:text-night-text">{{ $backup->completed_at->format('d M Y, g:i A') }} ({{ $backup->completed_at->diffForHumans() }})</dd>
                <dt class="text-text-400 dark:text-night-text-muted">Size</dt>
                <dd class="text-text-900 dark:text-night-text">{{ $backup->sizeForHumans() }}</dd>
                <dt class="text-text-400 dark:text-night-text-muted">Encrypted</dt>
                <dd class="text-text-900 dark:text-night-text">{{ $info->encrypted ? 'Yes — decrypted successfully' : 'No' }}</dd>
                <dt class="text-text-400 dark:text-night-text-muted">Contents</dt>
                <dd class="text-text-900 dark:text-night-text">
                    {{ $info->hasDatabase() ? count($info->dumpEntries).' database dump' : 'no database dump' }},
                    {{ $info->hasFiles() ? number_format($info->fileEntries).' uploaded files' : 'no uploaded files' }}
                    ({{ number_format($info->totalEntries) }} entries)
                </dd>
            </dl>
        </div>

        @if ($scopes === [])
            <x-ui.alert variant="warning" class="mb-6">
                Nothing in this archive can be restored in this environment.
                @unless ($databaseRestoreSupported)
                    Database restores require a MySQL/MariaDB connection and the <code>mysql</code> client (DB_DUMP_BINARY_PATH).
                @endunless
            </x-ui.alert>
            <x-ui.button href="{{ route('admin.backups.index') }}" variant="secondary">Back to Backups</x-ui.button>
        @else
            <form method="POST" action="{{ route('admin.backups.restore', $backup) }}" class="space-y-5 rounded-lg border border-border-subtle bg-surface-white p-5 dark:border-night-border dark:bg-night-surface" onsubmit="return confirm('Start the restore now? This cannot be undone except by restoring the safety backup.');">
                @csrf

                <x-ui.select label="What to restore" name="scope" :options="$scopes" :selected="old('scope', array_key_first($scopes))" :error="$errors->first('scope')" />

                <x-ui.input label="Type {{ $confirmation }} to confirm" name="confirmation" autocomplete="off" :error="$errors->first('confirmation')" />

                <x-ui.input label="Your current password" name="current_password" type="password" autocomplete="current-password" :error="$errors->first('current_password')" helper="Re-entered because this action is destructive." />

                <label class="flex items-start gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="acknowledge" value="1" class="mt-0.5 rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35" @checked(old('acknowledge'))>
                    <span>I understand that existing data will be overwritten and that this action is recorded in the audit log with my name and IP address.</span>
                </label>
                @error('acknowledge')
                    <p class="text-sm text-error-600">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" variant="danger">Restore Backup #{{ $backup->id }}</x-ui.button>
                    <x-ui.button href="{{ route('admin.backups.index') }}" variant="secondary">Cancel</x-ui.button>
                </div>
            </form>
        @endif
    </div>
@endsection
