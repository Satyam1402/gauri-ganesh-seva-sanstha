@extends('layouts.admin')

@section('title', 'Edit '.$category->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Activities', 'url' => route('admin.activities.index')],
        ['label' => 'Categories', 'url' => route('admin.activity-categories.index')],
        ['label' => $category->name],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.activity-categories.update', $category) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-ui.input label="Name" name="name" value="{{ old('name', $category->name) }}" required :error="$errors->first('name')" />
            <x-ui.input label="Slug" name="slug" value="{{ old('slug', $category->slug) }}" :error="$errors->first('slug')" />

            <div>
                <label for="description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                >{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                Active
            </label>

            <div class="flex gap-3">
                <x-ui.button type="submit">Save Changes</x-ui.button>
                <x-ui.button href="{{ route('admin.activity-categories.index') }}" variant="ghost">Back to Categories</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
