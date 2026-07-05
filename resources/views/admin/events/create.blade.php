@extends('layouts.admin')

@section('title', 'Add Event')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Events', 'url' => route('admin.events.index')],
        ['label' => 'Add Event'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.events._form', ['event' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Event</x-ui.button>
            <x-ui.button href="{{ route('admin.events.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
