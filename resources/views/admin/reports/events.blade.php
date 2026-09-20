@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-reports.kpi label="Events" :value="$s['total']" :hint="$s['published'].' published'" />
        <x-reports.kpi label="Upcoming" :value="$s['upcoming']" tone="success" />
        <x-reports.kpi label="Past" :value="$s['past']" />
        <x-reports.kpi label="Cancelled" :value="$s['cancelled']" :tone="$s['cancelled'] ? 'error' : 'neutral'" />
        <x-reports.kpi label="Registrations" :value="$s['registrations']" :hint="$s['registrations_confirmed'].' confirmed'" />
        <x-reports.kpi label="Attended" :value="$s['registrations_attended']" :hint="$s['registrations_cancelled'].' cancelled'" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Events Held</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">By start date.</p>
            <x-charts.bars :data="$data['trend']" title="Events by period" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Registrations Received</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">For events in this period.</p>
            <x-charts.bars :data="$data['registrationTrend']" title="Registrations by period" color="text-accent-500" class="mt-4" />
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Category</h2>
            <x-charts.donut :data="$data['byCategory']" title="Events by category" class="mt-4" />
        </x-ui.card>

        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Registrations by Event</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Registrations per event</caption>
                    <thead class="border-b border-border-subtle text-xs uppercase tracking-wide text-text-400 dark:border-night-border dark:text-night-text-muted">
                        <tr>
                            <th scope="col" class="py-2 pr-4 font-medium">Event</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Status</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Registered</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Confirmed</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Attended</th>
                            <th scope="col" class="py-2 text-right font-medium">Capacity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-night-border">
                        @forelse ($data['byEvent'] as $event)
                            <tr>
                                <td class="py-2 pr-4">
                                    <span class="font-medium text-text-900 dark:text-night-text">{{ $event['title'] }}</span>
                                    <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $event['date'] }}</span>
                                </td>
                                <td class="py-2 pr-4"><x-ui.badge :variant="$event['status']->badgeVariant()">{{ $event['status']->label() }}</x-ui.badge></td>
                                <td class="py-2 pr-4 text-right text-text-900 dark:text-night-text">{{ $event['requires_registration'] ? number_format($event['registrations']) : '—' }}</td>
                                <td class="py-2 pr-4 text-right text-text-600 dark:text-night-text-muted">{{ $event['requires_registration'] ? number_format($event['confirmed']) : '—' }}</td>
                                <td class="py-2 pr-4 text-right text-text-600 dark:text-night-text-muted">{{ $event['requires_registration'] ? number_format($event['attended']) : '—' }}</td>
                                <td class="py-2 text-right text-text-600 dark:text-night-text-muted">{{ $event['capacity'] ? number_format($event['capacity']) : 'Unlimited' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-sm text-text-400 dark:text-night-text-muted">No events in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('viewAny', App\Models\EventRegistration::class)
                <p class="mt-3 text-xs text-text-400 dark:text-night-text-muted">Attendee details and exports live on the <a href="{{ route('admin.event-registrations.index') }}" class="text-primary-700 hover:underline dark:text-night-text">registrations screen</a>.</p>
            @endcan
        </x-ui.card>
    </div>
@endsection
