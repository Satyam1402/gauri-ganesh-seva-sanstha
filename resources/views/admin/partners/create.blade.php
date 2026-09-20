@extends('layouts.admin')

@section('title', 'Add Partner')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Partners & Sponsors', 'url' => route('admin.partners.index')],
        ['label' => 'Add Partner'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.partners.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.partners._form', ['partner' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Partner</x-ui.button>
            <x-ui.button href="{{ route('admin.partners.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
