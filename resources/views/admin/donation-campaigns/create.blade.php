@extends('layouts.admin')

@section('title', 'Add Campaign')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Campaigns', 'url' => route('admin.donation-campaigns.index')],
        ['label' => 'Add Campaign'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.donation-campaigns.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.donation-campaigns._form', ['campaign' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Campaign</x-ui.button>
            <x-ui.button href="{{ route('admin.donation-campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
