@extends('layouts.admin')

@section('title', 'Add Album')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Gallery', 'url' => route('admin.gallery-albums.index')],
        ['label' => 'Add Album'],
    ]" />
@endsection

@section('content')
    <p class="mb-6 text-sm text-text-600 dark:text-night-text-muted">
        Create the album first — photo and video upload opens on the next screen.
    </p>

    <form method="POST" action="{{ route('admin.gallery-albums.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.gallery-albums._form', ['album' => null])

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Album</x-ui.button>
            <x-ui.button href="{{ route('admin.gallery-albums.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
