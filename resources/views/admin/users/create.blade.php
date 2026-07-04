@extends('layouts.admin')

@section('title', 'Add User')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Users', 'url' => route('admin.users.index')],
        ['label' => 'Add User'],
    ]" />
@endsection

@section('content')
    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <x-ui.input label="Name" name="name" value="{{ old('name') }}" required :error="$errors->first('name')" />
            <x-ui.input label="Email" name="email" type="email" value="{{ old('email') }}" required :error="$errors->first('email')" />
            <x-ui.input label="Phone" name="phone" value="{{ old('phone') }}" :error="$errors->first('phone')" />

            <x-ui.select
                label="Status"
                name="status"
                :options="['active' => 'Active', 'suspended' => 'Suspended']"
                :selected="old('status', 'active')"
                :error="$errors->first('status')"
            />

            <x-ui.input label="Password" name="password" type="password" required autocomplete="new-password" :error="$errors->first('password')" />
            <x-ui.input label="Confirm Password" name="password_confirmation" type="password" required autocomplete="new-password" />

            <div>
                <p class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Roles</p>
                <div class="grid grid-cols-2 gap-2 rounded-md border border-border-subtle p-4 dark:border-night-border">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', []))) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <x-ui.button type="submit">Create User</x-ui.button>
                <x-ui.button href="{{ route('admin.users.index') }}" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
