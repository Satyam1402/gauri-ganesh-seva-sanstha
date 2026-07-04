@extends('layouts.admin')

@section('title', 'Roles')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Roles']]" />
@endsection

@section('content')
    <div class="mb-6 flex justify-end">
        @can('create', Spatie\Permission\Models\Role::class)
            <x-ui.button href="{{ route('admin.roles.create') }}">Add Role</x-ui.button>
        @endcan
    </div>

    <x-ui.table :headings="['Role', 'Permissions', 'Users', '']">
        @forelse ($roles as $role)
            <tr>
                <td class="px-4 py-3 font-medium text-text-900 dark:text-night-text">{{ $role->name }}</td>
                <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $role->permissions_count }}</td>
                <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $role->users_count }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        @can('update', $role)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Edit</a>
                        @endcan

                        @can('delete', $role)
                            @if ($role->name !== \App\Enums\Role::SuperAdmin->value)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ $role->name }} role? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-error-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                    No roles found.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
@endsection
