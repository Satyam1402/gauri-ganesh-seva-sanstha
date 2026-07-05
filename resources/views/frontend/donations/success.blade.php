@extends('layouts.app')

@section('title', 'Thank You for Your Donation — '.config('app.name'))
@section('meta_description', 'Your donation to '.config('app.name').' was received. Thank you for your generosity.')

@section('content')
    <x-ui.section background="base">
        <div class="mx-auto max-w-xl">
            <x-ui.card>
                <div class="text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <x-ui.icon name="check" class="h-8 w-8 text-success-600" />
                    </div>

                    <h1 class="mt-5 font-display text-2xl font-semibold text-text-900 dark:text-night-text">
                        Thank You, {{ $donation->donor_name }}!
                    </h1>

                    <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">
                        @if ($donation->isCompleted())
                            Your donation of <strong class="text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</strong>
                            @if ($donation->campaign)
                                toward <strong>{{ $donation->campaign->name }}</strong>
                            @endif
                            has been received successfully.
                        @else
                            Your donation of <strong class="text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</strong> has been recorded
                            and will be confirmed once our team verifies the payment.
                        @endif
                    </p>

                    <dl class="mt-6 space-y-2 rounded-md bg-surface-muted p-4 text-left text-sm dark:bg-night-surface-alt">
                        @if ($donation->receipt_number)
                            <div class="flex justify-between gap-4">
                                <dt class="text-text-400 dark:text-night-text-muted">Receipt Number</dt>
                                <dd class="font-medium text-text-900 dark:text-night-text">{{ $donation->receipt_number }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-400 dark:text-night-text-muted">Reference</dt>
                            <dd class="font-medium text-text-900 dark:text-night-text">{{ $donation->reference }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-text-400 dark:text-night-text-muted">Date</dt>
                            <dd class="text-text-900 dark:text-night-text">{{ $donation->donated_at->format('d M Y, h:i A') }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-xs text-text-400 dark:text-night-text-muted">
                        @if ($donation->isCompleted())
                            Your receipt and a thank-you note are on their way to {{ $donation->donor_email }}.
                        @else
                            Once verified, your receipt will be emailed to {{ $donation->donor_email }}.
                        @endif
                    </p>

                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <x-ui.button href="{{ route('donations.campaigns.index') }}" variant="secondary">Explore More Campaigns</x-ui.button>
                        <x-ui.button href="{{ route('home') }}" variant="ghost">Back to Home</x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </x-ui.section>
@endsection
