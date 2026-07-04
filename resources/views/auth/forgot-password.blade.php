@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <x-ui.section-heading
        heading="Forgot Your Password?"
        subheading="Enter your email and we'll send you a link to reset it."
        align="center"
        class="mb-8"
    />

    @session('status')
        <x-ui.alert variant="success" class="mb-6">{{ $value }}</x-ui.alert>
    @endsession

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        <x-ui.button type="submit" class="w-full" size="lg">Email Password Reset Link</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-text-600">
        <a href="{{ route('login') }}" class="text-primary-700 hover:underline">Back to sign in</a>
    </p>
@endsection
