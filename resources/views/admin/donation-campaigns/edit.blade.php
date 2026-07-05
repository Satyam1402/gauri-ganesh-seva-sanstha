@extends('layouts.admin')

@section('title', 'Edit Campaign')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Campaigns', 'url' => route('admin.donation-campaigns.index')],
        ['label' => $campaign->name],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">{{ $campaign->name }}</h2>
            <p class="mt-1 text-sm text-text-400 dark:text-night-text-muted">
                {{ $campaign->donations_count }} donations &middot; Raised {{ format_inr((float) $campaign->raised_amount) }}
            </p>
        </div>
        <div class="flex gap-3">
            <x-ui.button href="{{ route('admin.donations.index', ['campaign' => $campaign->id]) }}" variant="secondary">View Donations</x-ui.button>
            @if ($campaign->status->value === 'active')
                <x-ui.button href="{{ route('donations.campaigns.show', $campaign) }}" variant="ghost" target="_blank">View on Site</x-ui.button>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.donation-campaigns.update', $campaign) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.donation-campaigns._form', ['campaign' => $campaign])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.donation-campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
