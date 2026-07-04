@extends('layouts.admin')

@section('title', 'Add Role')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Roles', 'url' => route('admin.roles.index')],
        ['label' => 'Add Role'],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5">
            @csrf

            <x-ui.input label="Role Name" name="name" value="{{ old('name') }}" required :error="$errors->first('name')" />

            <div>
                <p class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Permissions</p>
                <div class="space-y-4 rounded-md border border-border-subtle p-4 dark:border-night-border">
                    @foreach ($permissions as $group => $groupPermissions)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $group }}</p>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', []))) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                                        {{ $permission->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('permissions')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <x-ui.button type="submit">Create Role</x-ui.button>
                <x-ui.button href="{{ route('admin.roles.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
