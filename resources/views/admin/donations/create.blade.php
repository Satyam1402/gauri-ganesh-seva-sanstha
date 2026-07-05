@extends('layouts.admin')

@section('title', 'Record Donation')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Donations', 'url' => route('admin.donations.index')],
        ['label' => 'Record Donation'],
    ]" />
@endsection

@section('content')
    <p class="mb-6 text-sm text-text-600 dark:text-night-text-muted">
        Use this form for offline donations — cheques, cash deposits, or direct bank transfers received outside the website.
    </p>

    <form method="POST" action="{{ route('admin.donations.store') }}" class="space-y-6">
        @csrf

        @include('admin.donations._form', ['donation' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Record Donation</x-ui.button>
            <x-ui.button href="{{ route('admin.donations.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
