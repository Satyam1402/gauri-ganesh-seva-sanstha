@extends('layouts.admin')

@section('title', 'My Profile')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'My Profile']]" />
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Profile Information</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Update your name, email, and phone number.</p>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input label="Name" name="name" value="{{ old('name', $user->name) }}" required :error="$errors->first('name')" />
                <x-ui.input label="Email" name="email" type="email" value="{{ old('email', $user->email) }}" required :error="$errors->first('email')" />
                <x-ui.input label="Phone" name="phone" value="{{ old('phone', $user->phone) }}" :error="$errors->first('phone')" />

                <div class="flex flex-wrap gap-2">
                    @foreach ($user->roles as $role)
                        <x-ui.badge variant="neutral">{{ $role->name }}</x-ui.badge>
                    @endforeach
                </div>

                <x-ui.button type="submit">Save Changes</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Change Password</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Use a strong password you don't reuse elsewhere.</p>

            <form method="POST" action="{{ route('admin.password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input label="Current Password" name="current_password" type="password" required autocomplete="current-password" :error="$errors->first('current_password')" />
                <x-ui.input label="New Password" name="password" type="password" required autocomplete="new-password" :error="$errors->first('password')" />
                <x-ui.input label="Confirm New Password" name="password_confirmation" type="password" required autocomplete="new-password" />

                <x-ui.button type="submit">Update Password</x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
