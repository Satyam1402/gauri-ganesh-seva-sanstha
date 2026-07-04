@php
    $navLink = fn (string $routeName, string $pattern) => request()->routeIs($pattern)
        ? 'mt-1 flex items-center gap-2 rounded-md px-2 py-2 font-medium text-primary-700 bg-primary-100 dark:bg-night-surface-alt dark:text-night-text'
        : 'mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt';

    $comingSoon = 'mt-1 flex items-center gap-2 rounded-md px-2 py-2 text-text-400 cursor-not-allowed dark:text-night-text-muted/60';
@endphp

<aside
    class="fixed inset-y-0 left-0 z-30 w-60 -translate-x-full border-r border-border-subtle bg-surface-white transition-transform lg:translate-x-0 dark:border-night-border dark:bg-night-surface"
    :class="{ '-translate-x-0': sidebarOpen }"
>
    <div class="flex h-16 items-center border-b border-border-subtle px-6 dark:border-night-border">
        <a href="{{ route('admin.dashboard') }}" class="font-display text-lg font-semibold text-primary-700 dark:text-night-text">
            {{ config('app.name') }}
        </a>
    </div>

    <nav class="flex flex-col gap-6 overflow-y-auto px-4 py-6 text-sm">
        <div>
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Overview</p>
            <a href="{{ route('admin.dashboard') }}" class="{{ $navLink('admin.dashboard', 'admin.dashboard') }}">Dashboard</a>
        </div>

        <div>
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Fundraising</p>
            <span class="{{ $comingSoon }}" title="Coming soon">Campaigns</span>
            <span class="{{ $comingSoon }}" title="Coming soon">Donations</span>
        </div>

        <div>
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Engagement</p>
            <span class="{{ $comingSoon }}" title="Coming soon">Volunteers</span>
            <span class="{{ $comingSoon }}" title="Coming soon">Help Requests</span>
            <span class="{{ $comingSoon }}" title="Coming soon">Testimonials</span>
        </div>

        <div>
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Content</p>
            @can('viewAny', App\Models\HomeSection::class)
                <a href="{{ route('admin.home-sections.index') }}" class="{{ $navLink('admin.home-sections.index', 'admin.home-sections.*') }}">Homepage</a>
            @endcan
            @can('viewAny', App\Models\AboutSection::class)
                <a href="{{ route('admin.about-sections.index') }}" class="{{ $navLink('admin.about-sections.index', 'admin.about-sections.*') }}">About Us</a>
            @endcan
            <span class="{{ $comingSoon }}" title="Coming soon">Blog</span>
            <span class="{{ $comingSoon }}" title="Coming soon">Gallery</span>
            <span class="{{ $comingSoon }}" title="Coming soon">Team</span>
        </div>

        <div>
            <p class="px-2 text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">System</p>
            <span class="{{ $comingSoon }}" title="Coming soon">Settings</span>
            @can('viewAny', App\Models\User::class)
                <a href="{{ route('admin.users.index') }}" class="{{ $navLink('admin.users.index', 'admin.users.*') }}">Users</a>
            @endcan
            @can('viewAny', Spatie\Permission\Models\Role::class)
                <a href="{{ route('admin.roles.index') }}" class="{{ $navLink('admin.roles.index', 'admin.roles.*') }}">Roles</a>
            @endcan
            <span class="{{ $comingSoon }}" title="Coming soon">Audit Log</span>
        </div>
    </nav>
</aside>
