@extends('layouts.admin')

@section('title', 'Add Gallery Category')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Gallery', 'url' => route('admin.gallery-albums.index')],
        ['label' => 'Categories', 'url' => route('admin.gallery-categories.index')],
        ['label' => 'Add Category'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.gallery-categories.store') }}" class="max-w-xl space-y-6">
        @csrf

        <x-ui.card>
            <div class="space-y-5">
                <x-ui.input label="Name" name="name" value="{{ old('name') }}" required :error="$errors->first('name')" />
                <x-ui.input label="Slug" name="slug" value="{{ old('slug') }}" helper="Leave blank to auto-generate from the name." :error="$errors->first('slug')" />
                <x-ui.input label="Description" name="description" value="{{ old('description') }}" :error="$errors->first('description')" />

                <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                    Active — show as a filter on the public gallery
                </label>
            </div>
        </x-ui.card>

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Category</x-ui.button>
            <x-ui.button href="{{ route('admin.gallery-categories.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
