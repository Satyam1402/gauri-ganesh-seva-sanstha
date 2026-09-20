@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-reports.kpi label="Applications" :value="$s['total']" />
        <x-reports.kpi label="Pending" :value="$s['pending']" tone="warning" />
        <x-reports.kpi label="Under Review" :value="$s['under_review']" />
        <x-reports.kpi label="Approved" :value="$s['approved']" tone="success" />
        <x-reports.kpi label="Rejected" :value="$s['rejected']" :tone="$s['rejected'] ? 'error' : 'neutral'" />
        <x-reports.kpi label="On Hold / Archived" :value="$s['on_hold'] + $s['archived']" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Applications Over Time</h2>
            <x-charts.bars :data="$data['trend']" title="Volunteer applications by period" color="text-secondary-600" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Status</h2>
            <x-charts.donut :data="collect($s)->except('total')->mapWithKeys(fn ($v, $k) => [App\Enums\VolunteerApplicationStatus::from($k)->label() => $v])->all()" title="Applications by status" class="mt-4" />
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Areas of Interest</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Applicants may pick more than one.</p>
            <div class="mt-4">@include('admin.reports._breakdown', ['rows' => $data['byInterest'], 'title' => 'Applications by area of interest', 'columns' => ['Area', 'Applicants']])</div>
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By State</h2>
            <div class="mt-4">@include('admin.reports._breakdown', ['rows' => $data['byState'], 'title' => 'Applications by state', 'columns' => ['State', 'Applicants']])</div>
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Top Cities</h2>
            <div class="mt-4">@include('admin.reports._breakdown', ['rows' => $data['byCity'], 'title' => 'Applications by city', 'columns' => ['City', 'Applicants']])</div>
        </x-ui.card>
    </div>

    <p class="mt-6 text-xs text-text-400 dark:text-night-text-muted">
        Aggregate counts only — applicant names, contact details and uploaded documents are available on the
        @can('viewAny', App\Models\VolunteerApplication::class)<a href="{{ route('admin.volunteer-applications.index') }}" class="text-primary-700 hover:underline dark:text-night-text">applications screen</a>@else applications screen @endcan
        to users with volunteer permissions.
    </p>
@endsection
