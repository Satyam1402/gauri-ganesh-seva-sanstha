@extends('layouts.admin')

@section('title', 'Users')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Users']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex w-full max-w-sm gap-2">
            <div class="flex-1">
                <x-ui.input name="q" placeholder="Search by name or email" value="{{ $search }}" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>

        @can('create', App\Models\User::class)
            <x-ui.button href="{{ route('admin.users.create') }}">Add User</x-ui.button>
        @endcan
    </div>

    <x-ui.table :headings="['Name', 'Email', 'Roles', 'Status', '']">
        @forelse ($users as $user)
            <tr>
                <td class="px-4 py-3 font-medium text-text-900 dark:text-night-text">{{ $user->name }}</td>
                <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($user->roles as $role)
                            <x-ui.badge variant="neutral">{{ $role->name }}</x-ui.badge>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-3">
                    <x-ui.badge :variant="$user->status === 'active' ? 'success' : 'error'">{{ ucfirst($user->status) }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        @can('update', $user)
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Edit</a>
                        @endcan

                        @can('delete', $user)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-600 hover:underline">Delete</button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                    No users found.
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
@endsection
