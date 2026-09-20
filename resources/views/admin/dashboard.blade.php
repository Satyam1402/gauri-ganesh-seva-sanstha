@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard']]" />
@endsection

@php
    $k = $overview['kpis'] ?? [];
    $c = $overview['charts'] ?? [];
@endphp

@section('content')
    <div class="space-y-6">
        @if ($overview === null)
            <x-ui.empty-state heading="No reports available for your role" message="Your account has no report permissions yet. Ask an administrator to grant access to the areas you work with.">
                <x-slot:action>
                    <x-ui.button href="{{ route('admin.profile.edit') }}" variant="secondary" size="sm">Your Profile</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-text-600 dark:text-night-text-muted">
                    Figures refresh every {{ App\Services\Reports\DashboardReportService::CACHE_MINUTES }} minutes · last built {{ \Illuminate\Support\Carbon::parse($overview['generated_at'])->diffForHumans() }}.
                </p>
                <div class="flex gap-2">
                    <x-ui.button href="{{ route('admin.dashboard', ['fresh' => 1]) }}" variant="ghost" size="sm">Refresh now</x-ui.button>
                    <x-ui.button href="{{ route('admin.reports.index') }}" variant="secondary" size="sm">All Reports</x-ui.button>
                </div>
            </div>

            {{-- KPI cards — only the areas this user may report on --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @isset($can['donations'])
                    <x-reports.kpi label="Total Donations" :value="$k['donations_total_amount']" money tone="success" :hint="number_format($k['donations_total_count']).' completed'" :href="route('admin.reports.show', 'donations')" />
                    <x-reports.kpi label="Donations This Month" :value="$k['donations_month_amount']" money :hint="number_format($k['donations_month_count']).' donations'" />
                    <x-reports.kpi label="Donations This Year" :value="$k['donations_year_amount']" money />
                    <x-reports.kpi label="Active Campaigns" :value="$k['active_campaigns']" :href="auth()->user()->can('viewAny', App\Models\DonationCampaign::class) ? route('admin.donation-campaigns.index') : null" />
                @endisset
                @isset($can['volunteers'])
                    <x-reports.kpi label="Total Volunteers" :value="$k['volunteers_total']" :hint="number_format($k['volunteers_approved']).' approved'" :href="route('admin.reports.show', 'volunteers')" />
                    <x-reports.kpi label="Pending Applications" :value="$k['volunteers_pending']" :tone="$k['volunteers_pending'] ? 'warning' : 'neutral'" :href="auth()->user()->can('viewAny', App\Models\VolunteerApplication::class) ? route('admin.volunteer-applications.index', ['status' => 'pending']) : null" />
                @endisset
                @isset($can['activities'])
                    <x-reports.kpi label="Published Activities" :value="$k['activities_total']" :href="route('admin.reports.show', 'activities')" />
                @endisset
                @isset($can['events'])
                    <x-reports.kpi label="Upcoming Events" :value="$k['events_upcoming']" :hint="number_format($k['events_total']).' total'" :href="route('admin.reports.show', 'events')" />
                    <x-reports.kpi label="Event Registrations" :value="$k['event_registrations']" :href="auth()->user()->can('viewAny', App\Models\EventRegistration::class) ? route('admin.event-registrations.index') : null" />
                @endisset
                @isset($can['contacts'])
                    <x-reports.kpi label="New Enquiries" :value="$k['enquiries_new']" :tone="$k['enquiries_new'] ? 'warning' : 'neutral'" :hint="number_format($k['enquiries_total']).' total'" :href="route('admin.reports.show', 'contacts')" />
                @endisset
                @isset($can['blog'])
                    <x-reports.kpi label="Published Blog Posts" :value="$k['blog_published']" :href="route('admin.reports.show', 'blog')" />
                @endisset
                @isset($can['gallery'])
                    <x-reports.kpi label="Gallery Albums" :value="$k['gallery_albums']" :href="route('admin.reports.show', 'gallery')" />
                @endisset
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                @isset($can['donations'])
                    <x-ui.card class="lg:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donation Trend</h3>
                            <span class="text-xs text-text-400 dark:text-night-text-muted">{{ $c['range_label'] }}</span>
                        </div>
                        <x-charts.bars :data="$c['donation_trend']" title="Completed donations per month" money class="mt-4" />
                    </x-ui.card>
                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Campaign</h3>
                        <x-charts.donut :data="collect($c['donation_by_campaign'])->pluck('amount', 'campaign')->all()" title="Completed amount by campaign, last 12 months" money :size="140" class="mt-4" />
                    </x-ui.card>
                @endisset
                @isset($can['volunteers'])
                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Volunteer Applications</h3>
                        <x-charts.bars :data="$c['volunteer_trend']" title="Volunteer applications per month" color="text-secondary-600" :height="180" class="mt-4" />
                    </x-ui.card>
                @endisset
                @isset($can['contacts'])
                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Contact Enquiries</h3>
                        <x-charts.bars :data="$c['enquiry_trend']" title="Contact enquiries per month" color="text-accent-500" :height="180" class="mt-4" />
                    </x-ui.card>
                @endisset
                @isset($can['events'])
                    <x-ui.card>
                        <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Event Activity</h3>
                        <x-charts.bars :data="$c['event_trend']" title="Events per month" :height="180" class="mt-4" />
                    </x-ui.card>
                @endisset
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Quick Actions --}}
            <x-ui.card>
                <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Quick Actions</h3>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @can('create', App\Models\BlogPost::class)
                        <x-ui.button href="{{ route('admin.blog-posts.create') }}" variant="secondary" size="sm">Add Blog Post</x-ui.button>
                    @endcan
                    @can('create', App\Models\Activity::class)
                        <x-ui.button href="{{ route('admin.activities.create') }}" variant="secondary" size="sm">Add Activity</x-ui.button>
                    @endcan
                    @can('create', App\Models\Event::class)
                        <x-ui.button href="{{ route('admin.events.create') }}" variant="secondary" size="sm">Add Event</x-ui.button>
                    @endcan
                    @can('viewAny', App\Models\User::class)
                        <x-ui.button href="{{ route('admin.users.create') }}" variant="secondary" size="sm">Add User</x-ui.button>
                    @endcan
                    <x-ui.button href="{{ route('admin.profile.edit') }}" variant="secondary" size="sm">Edit Profile</x-ui.button>
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
                        <dt class="text-text-600 dark:text-night-text-muted">Debug mode</dt>
                        <dd><x-ui.badge :variant="$systemStatus['debug_mode'] ? 'warning' : 'success'">{{ $systemStatus['debug_mode'] ? 'On' : 'Off' }}</x-ui.badge></dd>
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
    </div>
@endsection
