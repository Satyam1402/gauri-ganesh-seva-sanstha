@extends('layouts.admin')

@section('title', 'Settings — '.$definition['label'])

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.edit', 'general')],
        ['label' => $definition['label']],
    ]" />
@endsection

@section('content')
    @if ($maintenanceOn)
        <x-ui.alert variant="warning" class="mb-6">
            <strong>Maintenance mode is on.</strong> Public visitors currently see the maintenance page.
            @if ($group !== 'maintenance')
                <a href="{{ route('admin.settings.edit', 'maintenance') }}" class="font-medium underline">Manage</a>
            @endif
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Tabs --}}
        <nav aria-label="Settings sections" class="lg:col-span-1">
            <ul class="flex gap-1 overflow-x-auto rounded-lg border border-border-subtle bg-surface-white p-1 lg:flex-col lg:overflow-visible dark:border-night-border dark:bg-night-surface">
                @foreach ($tabs as $key => $label)
                    <li class="shrink-0">
                        <a
                            href="{{ route('admin.settings.edit', $key) }}"
                            class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ $key === $group ? 'bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text' : 'text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt' }}"
                            @if ($key === $group) aria-current="page" @endif
                        >{{ $label }}</a>
                    </li>
                @endforeach
                <li class="shrink-0 lg:mt-2 lg:border-t lg:border-border-subtle lg:pt-2 dark:lg:border-night-border">
                    <a href="{{ route('admin.menu-items.index') }}" class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt">Navigation Menu ↗</a>
                </li>
            </ul>
        </nav>

        {{-- Form --}}
        <div class="lg:col-span-3">
            <form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <x-ui.card>
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $definition['label'] }}</h2>
                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $definition['description'] }}</p>

                    <div class="mt-6 space-y-5">
                        @foreach ($definition['fields'] as $key => $field)
                            @include('admin.settings._field', ['key' => $key, 'field' => $field, 'value' => $values[$key] ?? null])
                        @endforeach
                    </div>
                </x-ui.card>

                @if ($group === 'integrations')
                    <x-ui.card>
                        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Server-side Secrets</h2>
                        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">
                            These are read from the server <code>.env</code> file. Their values are never stored in the database or shown here — only whether they are set.
                        </p>
                        <dl class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                            @foreach ($envSecrets as $label => $configured)
                                <div class="flex items-center justify-between gap-4 py-2">
                                    <dt class="text-text-600 dark:text-night-text-muted">{{ $label }}</dt>
                                    <dd>
                                        <x-ui.badge :variant="$configured ? 'success' : 'neutral'">{{ $configured ? 'Configured' : 'Not set' }}</x-ui.badge>
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-ui.card>
                @endif

                @if ($group === 'general')
                    <x-ui.card>
                        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Website Status</h2>
                        <p class="mt-3 flex items-center gap-3 text-sm text-text-600 dark:text-night-text-muted">
                            <x-ui.badge :variant="$maintenanceOn ? 'warning' : 'success'">{{ $maintenanceOn ? 'Maintenance' : 'Live' }}</x-ui.badge>
                            <a href="{{ route('admin.settings.edit', 'maintenance') }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Change on the Maintenance tab →</a>
                        </p>
                    </x-ui.card>
                @endif

                <div class="flex gap-3">
                    <x-ui.button type="submit">Save {{ $definition['label'] }} Settings</x-ui.button>
                    <x-ui.button href="{{ route('home') }}" variant="ghost" target="_blank" rel="noopener">View Site ↗</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
