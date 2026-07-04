@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
    <x-ui.section-heading
        heading="Welcome Back"
        subheading="Sign in to manage Gauri Ganesh Seva Sanstha"
        align="center"
        class="mb-8"
    />

    @session('status')
        <x-ui.alert variant="success" class="mb-6">{{ $value }}</x-ui.alert>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-ui.input
            label="Email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            required
            autofocus
            autocomplete="username"
            :error="$errors->first('email')"
        />

        <x-ui.input
            label="Password"
            name="password"
            type="password"
            required
            autocomplete="current-password"
            :error="$errors->first('password')"
        />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-text-600">
                <input type="checkbox" name="remember" class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-primary-700 hover:underline">
                Forgot your password?
            </a>
        </div>

        <x-ui.button type="submit" class="w-full" size="lg">Sign In</x-ui.button>
    </form>
@endsection
