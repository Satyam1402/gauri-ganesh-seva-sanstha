@extends('layouts.admin')

@section('title', 'Edit Donation')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Donations', 'url' => route('admin.donations.index')],
        ['label' => 'Edit Donation #'.$donation->id],
    ]" />
@endsection

@section('content')
    @if ($donation->receipt_number)
        <p class="mb-6 text-sm text-text-600 dark:text-night-text-muted">
            Receipt <strong class="text-text-900 dark:text-night-text">{{ $donation->receipt_number }}</strong> has already been issued for this donation — edit with care.
        </p>
    @endif

    <form method="POST" action="{{ route('admin.donations.update', $donation) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.donations._form', ['donation' => $donation])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.donations.show', $donation) }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
