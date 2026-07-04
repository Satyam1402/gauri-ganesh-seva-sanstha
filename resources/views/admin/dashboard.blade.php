@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard']]" />
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Donations This Month', 'icon' => 'currency-rupee'],
                ['label' => 'Active Campaigns', 'icon' => 'megaphone'],
                ['label' => 'Pending Volunteer Applications', 'icon' => 'user-group'],
                ['label' => 'Pending Help Requests', 'icon' => 'hand-raised'],
            ] as $stat)
                <x-ui.card>
                    <div class="flex items-start justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 dark:bg-night-surface-alt">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-700 dark:text-night-text" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-9-6h18" />
                            </svg>
                        </span>
                        <x-ui.badge variant="neutral">Coming soon</x-ui.badge>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-text-900 dark:text-night-text">&mdash;</p>
                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $stat['label'] }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Chart placeholder --}}
            <x-ui.card class="lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donation Trend</h3>
                    <x-ui.badge variant="neutral">Coming soon</x-ui.badge>
                </div>
                <div class="mt-4 flex h-64 items-center justify-center rounded-md border border-dashed border-border-subtle text-sm text-text-400 dark:border-night-border dark:text-night-text-muted">
                    Chart will appear once donation data is available.
                </div>
            </x-ui.card>

            {{-- System status --}}
            <x-ui.card>
                <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">System Status</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-text-600 dark:text-night-text-muted">Environment</dt>
                        <dd><x-ui.badge :variant="$systemStatus['environment'] === 'production' ? 'success' : 'warning'">{{ ucfirst($systemStatus['environment']) }}</x-ui.badge></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-600 dark:text-night-text-muted">Laravel</dt>
                        <dd class="font-medium text-text-900 dark:text-night-text">{{ $systemStatus['laravel_version'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-600 dark:text-night-text-muted">PHP</dt>
                        <dd class="font-medium text-text-900 dark:text-night-text">{{ $systemStatus['php_version'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-600 dark:text-night-text-muted">Queue</dt>
                        <dd class="font-medium text-text-900 dark:text-night-text">{{ ucfirst($systemStatus['queue_connection']) }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ([
                'Recent Donations' => 'No donations recorded yet — this module ships in a later phase.',
                'Recent Volunteers' => 'No volunteer applications yet — this module ships in a later phase.',
            ] as $title => $empty)
                <x-ui.card>
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $title }}</h3>
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">{{ $empty }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ([
                'Recent Activities' => 'No activities published yet — this module ships in a later phase.',
                'Latest Blog Posts' => 'No blog posts published yet — this module ships in a later phase.',
            ] as $title => $empty)
                <x-ui.card>
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $title }}</h3>
                    <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">{{ $empty }}</p>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Quick Actions --}}
            <x-ui.card>
                <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Quick Actions</h3>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @can('viewAny', App\Models\User::class)
                        <x-ui.button href="{{ route('admin.users.create') }}" variant="secondary" size="sm">Add User</x-ui.button>
                    @endcan
                    @can('viewAny', Spatie\Permission\Models\Role::class)
                        <x-ui.button href="{{ route('admin.roles.index') }}" variant="secondary" size="sm">Manage Roles</x-ui.button>
                    @endcan
                    <x-ui.button href="{{ route('admin.profile.edit') }}" variant="secondary" size="sm">Edit Profile</x-ui.button>
                    <x-ui.button variant="secondary" size="sm" disabled class="opacity-40">Add Blog Post</x-ui.button>
                </div>
            </x-ui.card>

            {{-- Recent Notifications --}}
            <x-ui.card>
                <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Recent Notifications</h3>
                <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">You're all caught up — no new notifications.</p>
            </x-ui.card>
        </div>
    </div>
@endsection
