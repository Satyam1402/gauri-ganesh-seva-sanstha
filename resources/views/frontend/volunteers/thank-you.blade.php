@extends('layouts.app')

@section('title', 'Application Received — '.config('app.name'))
@section('meta_description', 'Thank you for applying to volunteer with '.config('app.name').'. Our team will review your application and get back to you soon.')
@section('canonical_url', route('volunteer.create'))

@push('structured_data')
    {{-- Post-submission page: keep it out of search indexes. --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-ui.section background="base" spacing="lg">
        <div class="mx-auto max-w-2xl text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-success-600" aria-hidden="true">
                <x-ui.icon name="check" class="h-8 w-8" />
            </div>

            <h1 class="mt-6 font-display text-3xl font-semibold text-text-900 dark:text-night-text">
                Thank You{{ $firstName ? ', '.$firstName : '' }}!
            </h1>

            <p class="mt-4 text-base leading-relaxed text-text-600 dark:text-night-text-muted">
                Your volunteer application has been received. A confirmation email is on its
                way to your inbox — our volunteer coordinator will review your application
                and contact you within <strong>5–7 working days</strong>.
            </p>

            <div class="mx-auto mt-8 max-w-md rounded-lg border border-border-subtle bg-surface-white px-6 py-4 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Your Application Reference</p>
                <p class="mt-1 break-all font-mono text-sm text-text-900 dark:text-night-text">{{ $reference }}</p>
                <p class="mt-2 text-xs text-text-400 dark:text-night-text-muted">Keep this reference handy if you need to ask us about your application.</p>
            </div>

            <div class="mt-10">
                <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">What happens next?</h2>
                <ol class="mx-auto mt-4 max-w-md space-y-3 text-left text-sm text-text-600 dark:text-night-text-muted">
                    <li class="flex gap-3"><span class="font-semibold text-primary-700">1.</span> Our volunteer coordinator reviews your application.</li>
                    <li class="flex gap-3"><span class="font-semibold text-primary-700">2.</span> We reach out on your preferred channel for a short introduction.</li>
                    <li class="flex gap-3"><span class="font-semibold text-primary-700">3.</span> You receive your orientation and join your first seva activity.</li>
                </ol>
            </div>

            <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <x-ui.button href="{{ route('home') }}" variant="secondary">Back to Home</x-ui.button>
                <x-ui.button href="{{ route('events.index') }}" variant="primary">Explore Upcoming Events</x-ui.button>
            </div>
        </div>
    </x-ui.section>
@endsection
