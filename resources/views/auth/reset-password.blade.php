@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <x-ui.section-heading
        heading="Reset Your Password"
        subheading="Choose a new password for your account."
        align="center"
        class="mb-8"
    />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.input
            label="Email"
            name="email"
            type="email"
            value="{{ old('email', $request->email) }}"
            required
            autofocus
            autocomplete="username"
            :error="$errors->first('email')"
        />

        <x-ui.input
            label="New Password"
            name="password"
            type="password"
            required
            autocomplete="new-password"
            :error="$errors->first('password')"
        />

        <x-ui.input
            label="Confirm New Password"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            :error="$errors->first('password_confirmation')"
        />

        <x-ui.button type="submit" class="w-full" size="lg">Reset Password</x-ui.button>
    </form>
@endsection
